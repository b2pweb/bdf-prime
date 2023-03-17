<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\Entity\CriteriaGenerator;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\ServiceLocator;
use Bdf\Util\Console\BdfStyle;
use Bdf\Util\File\ClassFileLocator;
use Bdf\Util\File\PhpClassFile;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

use function file_exists;
use function is_dir;
use function realpath;
use function str_replace;
use function str_starts_with;

/**
 * Command for generate a custom criteria class from a mapper
 */
#[AsCommand('prime:criteria', 'Generate criteria class by mapper')]
class CriteriaCommand extends Command
{
    public const CLASS_SUFFIX = 'Criteria';

    protected static $defaultName = 'prime:criteria';

    private ServiceLocator $locator;

    /**
     * EntityCommand constructor.
     *
     * @param ServiceLocator $locator
     */
    public function __construct(ServiceLocator $locator)
    {
        parent::__construct(static::$defaultName);

        $this->locator = $locator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('mapper', InputArgument::REQUIRED, 'Mapper file or directory to parse')
            ->addOption('backup', 'b', InputOption::VALUE_NONE, 'Backup file if exists')
            ->addOption('show', 's', InputOption::VALUE_NONE, 'Show generated code')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not write generated code')
            ->setDescription('Generate criteria class by mapper')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> generate criteria from mapper
use mapper argument to parse a directory or a mapper file
  <info>php %command.full_name% app/models</info>
  <info>php %command.full_name% app/models/MyMapper.php</info>

Backup files with backup option
  <info>php %command.full_name% --backup app/models</info>
  <info>php %command.full_name% -b app/models</info>

To only show generated code, use show option in combination with dry-run
  <info>php %command.full_name% --show --dry-run app/models</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new BdfStyle($input, $output);

        $path = realpath($io->argument('mapper'));

        $cache = $this->locator->mappers()->getMetadataCache();
        $this->locator->mappers()->setMetadataCache(null);

        foreach ($this->getClassIterator($path) as $classInfo) {
            $className = $classInfo->getClass();

            if (!$this->locator->mappers()->isMapper($className)) {
                $io->debug("'{$className}' is not mapper class");
                continue;
            }

            $mapper = $this->locator->mappers()->createMapper($this->locator, $className);
            $generator = new CriteriaGenerator($mapper->getEntityClass() . self::CLASS_SUFFIX);

            $this->runUserActions($io, $generator, $mapper, $classInfo);
        }

        $this->locator->mappers()->setMetadataCache($cache);
        return 0;
    }

    /**
     * @param string $path
     *
     * @return iterable<PhpClassFile>
     */
    protected function getClassIterator(string $path): iterable
    {
        if (is_dir($path)) {
            return new ClassFileLocator($path);
        } else {
            $classFile = new PhpClassFile($path);
            $classFile->extractClassInfo();

            return [$classFile];
        }
    }

    protected function runUserActions(BdfStyle $io, CriteriaGenerator $generator, Mapper $mapper, PhpClassFile $classInfo): void
    {
        $fileName = str_replace('Mapper', self::CLASS_SUFFIX, $classInfo->getRealPath());
        $filesystem = new Filesystem();

        if (file_exists($fileName)) {
            $userChoice = (string) $io->choice("'$fileName' exists. what do you want ?", [
                '1'     => 'Regenerate: will replace the existing entity',
                '2'     => 'Update: will complete entity with new properties and methods',
                '3'     => 'Cancel',
            ]);

            if ($userChoice === 'Cancel') {
                return;
            }

            if (str_starts_with($userChoice, 'Update')) {
                $generator->loadFromFile($fileName);
            }

            if ($io->option('backup') && !$io->option('dry-run')) {
                $filesystem->copy($fileName, $fileName.'.bak');
                $io->comment('backup file "' . $fileName.'.bak' . '"');
            }
        }

        $generator->parseMetadata($mapper->metadata());
        $generator->parseCustomFilters($mapper->filters());

        $code = $generator->dump();

        if ($io->option('show')) {
            $io->writeln($code);
        }

        if (!$io->option('dry-run')) {
            $filesystem->dumpFile($fileName, $code);
            $io->comment('generated file "' . $fileName . '"');
        }
    }
}
