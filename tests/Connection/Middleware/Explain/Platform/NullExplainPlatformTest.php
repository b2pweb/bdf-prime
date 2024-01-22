<?php

namespace Connection\Middleware\Explain\Platform;

use Bdf\Prime\Connection\Middleware\Explain\ExplainResult;
use Bdf\Prime\Connection\Middleware\Explain\Platform\NullExplainPlatform;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;

class NullExplainPlatformTest extends TestCase
{
    public function test_compile()
    {
        $this->assertNull((new NullExplainPlatform())->compile('SELECT * FROM foo'));
    }

    public function test_parse()
    {
        $this->assertEquals(new ExplainResult(), (new NullExplainPlatform())->parse($this->createMock(Result::class)));
    }

    public function test_supports()
    {
        $this->assertTrue(NullExplainPlatform::supports($this->createMock(AbstractPlatform::class)));
    }
}
