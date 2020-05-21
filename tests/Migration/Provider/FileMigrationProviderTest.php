<?php

namespace Bdf\Prime\Migration\Provider;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 *
 */
class FileMigrationProviderTest extends TestCase
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $wrongFilesPath;

    /**
     * @var string
     */
    private $workingDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * 
     */
    protected function setUp(): void
    {
        parent::setUp();

        $path = realpath(__DIR__ . '/..');

        $this->path           = $path.'/_assets/migrations';
        $this->wrongFilesPath = $path.'/_assets/wrong';
        $this->workingDir     = $path.'/_working';
        $this->filesystem = new Filesystem();

        $this->filesystem->remove($this->workingDir);
        $this->filesystem->mkdir($this->workingDir);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove($this->workingDir);
    }

    /**
     *
     */
    public function test_import()
    {
        $di = $this->createMock(ContainerInterface::class);
        $locator = new FileMigrationProvider(new MigrationFactory($di), $this->path);
        $locator->import();

        $this->assertEquals([
            '100' => new \Migration100('100', $di),
            '101' => new \Migration101('101', $di),
            '200' => new \Migration200('200', $di),
            '300' => new \Migration300('300', $di),
            '400' => new \Migration400('400', $di),
            '500' => new \Migration500('500', $di),
            '600' => new \Migration600('600', $di),
        ], $locator->all());
    }

    /**
     *
     */
    public function test_parseFile_throws_exception_because_version_is_missing()
    {
        $di = $this->createMock(ContainerInterface::class);
        $filename = '_MissingVersion.php';

        $this->filesystem->copy($this->wrongFilesPath . DIRECTORY_SEPARATOR . $filename, $this->workingDir . DIRECTORY_SEPARATOR . $filename);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The file "' . $this->workingDir . DIRECTORY_SEPARATOR . $filename . '" does not have a valid migration filename');

        (new FileMigrationProvider(new MigrationFactory($di), $this->workingDir))->import();
    }

    /**
     *
     */
    public function test_parseFile_throws_exception_because_classname_is_missing()
    {
        $di = $this->createMock(ContainerInterface::class);
        $filename = '150_.php';

        $this->filesystem->copy($this->wrongFilesPath . DIRECTORY_SEPARATOR . $filename, $this->workingDir . DIRECTORY_SEPARATOR . $filename);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The file "' . $this->workingDir . DIRECTORY_SEPARATOR . $filename . '" does not have a valid migration filename');

        (new FileMigrationProvider(new MigrationFactory($di), $this->workingDir))->import();
    }

    /**
     *
     */
    public function test_import_throws_exception_because_name_is_duplicated()
    {
        $di = $this->createMock(ContainerInterface::class);
        $filename = '150_Migration100.php';

        $this->filesystem->copy($this->path . DIRECTORY_SEPARATOR . '100_Migration100.php', $this->workingDir . DIRECTORY_SEPARATOR . '100_Migration100.php');
        $this->filesystem->copy($this->wrongFilesPath . DIRECTORY_SEPARATOR . $filename, $this->workingDir . DIRECTORY_SEPARATOR . $filename);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Duplicate migration name "Migration100", version "150" has the same name as version "100"');

        (new FileMigrationProvider(new MigrationFactory($di), $this->workingDir))->import();
    }

    /**
     *
     */
    public function test_migration_throws_exception_because_class_is_not_found()
    {
        $di = $this->createMock(ContainerInterface::class);
        $filename = '150_ClassNotFound.php';

        $this->filesystem->copy($this->wrongFilesPath . DIRECTORY_SEPARATOR . $filename, $this->workingDir . DIRECTORY_SEPARATOR . $filename);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Could not find class "ClassNotFound"');

        $provider = new FileMigrationProvider(new MigrationFactory($di), $this->workingDir);
        $provider->import();
        $provider->migration('150');
    }

    /**
     *
     */
    public function test_create_with_custom_stage()
    {
        $di = $this->createMock(ContainerInterface::class);
        $provider = new FileMigrationProvider(new MigrationFactory($di), $this->workingDir);

        $path = $provider->create('123', 'MyMigration', 'my_stage');

        $this->assertFileExists($path);
        $this->assertStringEndsWith('123_MyMigration.php', $path);

        $content = file_get_contents($path);
        $this->assertStringContainsString(<<<PHP
    public function stage()
    {
        return 'my_stage';
    }
PHP
    , $content
);
    }

    /**
     *
     */
    public function test_create_with_default_stage()
    {
        $di = $this->createMock(ContainerInterface::class);
        $provider = new FileMigrationProvider(new MigrationFactory($di), $this->workingDir);

        $path = $provider->create('123', 'MyMigration');

        $this->assertFileExists($path);
        $this->assertStringEndsWith('123_MyMigration.php', $path);

        $content = file_get_contents($path);
        $this->assertStringNotContainsString('public function stage()', $content);
    }

    /**
     *
     */
    public function test_import_after_create()
    {
        $di = $this->createMock(ContainerInterface::class);
        $provider = new FileMigrationProvider(new MigrationFactory($di), $this->workingDir);

        $provider->create('123', 'MyMigration');
        $provider->import();

        $this->assertEquals(
            ['123' => new \MyMigration('123', $di)],
            $provider->all()
        );
    }
}
