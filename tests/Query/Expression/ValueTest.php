<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ValueTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    public function setUp(): void
    {
        $this->primeStart();
    }

    /**
     *
     */
    public function test_setContext()
    {
        $value = new Value('');

        $value->setContext($this->createMock(CompilerInterface::class), 'col', '=');

        $this->assertEquals('col', $value->getColumn());
        $this->assertEquals('=', $value->getOperator());
    }

    /**
     *
     */
    public function test_getValue_no_type()
    {
        $data = new \stdClass();
        $value = new Value($data);

        $this->assertSame($data, $value->getValue());
    }

    /**
     *
     */
    public function test_getValue_with_type()
    {
        $value = new Value([1, 2, 5]);

        $compiler = $this->createMock(CompilerInterface::class);

        $value->setContext($compiler, 'col', '=');
        $value->setType($this->prime()->connection('test')->getConfiguration()->getTypes()->get('searchable_array'));

        $this->assertSame(',1,2,5,', $value->getValue());
    }
}
