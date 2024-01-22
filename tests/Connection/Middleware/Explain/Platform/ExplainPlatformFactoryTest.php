<?php

namespace Connection\Middleware\Explain\Platform;

use Bdf\Prime\Connection\Middleware\Explain\Platform\ExplainPlatformFactory;
use Bdf\Prime\Connection\Middleware\Explain\Platform\NullExplainPlatform;
use Bdf\Prime\Connection\Middleware\Explain\Platform\SqliteExplainPlatform;
use Bdf\Prime\PrimeTestCase;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;

class ExplainPlatformFactoryTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->configurePrime();
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_supportedPlatform()
    {
        $this->assertInstanceOf(SqliteExplainPlatform::class, (new ExplainPlatformFactory())->createExplainPlatform($this->prime()->connection('test')->platform()->grammar()));
    }

    public function test_not_supported_platform_should_return_null_platform()
    {
        $this->assertInstanceOf(NullExplainPlatform::class, (new ExplainPlatformFactory())->createExplainPlatform($this->createMock(AbstractPlatform::class)));
    }
}
