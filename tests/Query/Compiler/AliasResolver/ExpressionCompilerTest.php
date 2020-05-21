<?php

namespace Bdf\Prime\Query\Compiler\AliasResolver;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ExpressionCompilerTest extends TestCase
{
    /**
     * @var ExpressionCompiler
     */
    protected $compiler;


    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->compiler = new ExpressionCompiler();
    }

    /**
     *
     */
    public function test_compile_dynamic()
    {
        $expression = 'user.location.address.name';

        $this->assertEquals(
            [new ExpressionToken(ExpressionToken::TYPE_DYN, ['user', 'location', 'address', 'name'])],
            $this->compiler->compile($expression)
        );
    }

    /**
     *
     */
    public function test_compile_dynamic_attr()
    {
        $expression = 'user.location.address>name';

        $this->assertEquals(
            [
                new ExpressionToken(ExpressionToken::TYPE_DYN, ['user', 'location', 'address']),
                new ExpressionToken(ExpressionToken::TYPE_ATTR, 'name'),
            ],
            $this->compiler->compile($expression)
        );
    }

    /**
     *
     */
    public function test_compile_complex_attr()
    {
        $expression = '>address.name';

        $this->assertEquals(
            [
                new ExpressionToken(ExpressionToken::TYPE_ATTR, 'address.name'),
            ],
            $this->compiler->compile($expression)
        );
    }

    /**
     *
     */
    public function test_compile_alias_dyn()
    {
        $expression = '$t1.address.name';

        $this->assertEquals(
            [
                new ExpressionToken(ExpressionToken::TYPE_ALIAS, 't1'),
                new ExpressionToken(ExpressionToken::TYPE_DYN, ['address', 'name'])
            ],
            $this->compiler->compile($expression)
        );
    }

    /**
     *
     */
    public function test_compile_alias_attr()
    {
        $expression = '$t1>name';

        $this->assertEquals(
            [
                new ExpressionToken(ExpressionToken::TYPE_ALIAS, 't1'),
                new ExpressionToken(ExpressionToken::TYPE_ATTR, 'name'),
            ],
            $this->compiler->compile($expression)
        );
    }

    /**
     *
     */
    public function test_compile_static_attr()
    {
        $expression = '"user.location.address">name';

        $this->assertEquals(
            [
                new ExpressionToken(ExpressionToken::TYPE_STA, 'user.location.address'),
                new ExpressionToken(ExpressionToken::TYPE_ATTR, 'name'),
            ],
            $this->compiler->compile($expression)
        );
    }

    /**
     *
     */
    public function test_compile_static_dyn()
    {
        $expression = '"user.location.address"city.id';

        $this->assertEquals(
            [
                new ExpressionToken(ExpressionToken::TYPE_STA, 'user.location.address'),
                new ExpressionToken(ExpressionToken::TYPE_DYN, ['city', 'id']),
            ],
            $this->compiler->compile($expression)
        );
    }

    /**
     *
     */
    public function test_compile_static_dyn_attr()
    {
        $expression = '"user.location".address.city>name';

        $this->assertEquals(
            [
                new ExpressionToken(ExpressionToken::TYPE_STA, 'user.location'),
                new ExpressionToken(ExpressionToken::TYPE_DYN, ['address', 'city']),
                new ExpressionToken(ExpressionToken::TYPE_ATTR, 'name'),
            ],
            $this->compiler->compile($expression)
        );
    }

    /**
     *
     */
    public function test_compile_alias_dyn_attr()
    {
        $expression = '$t1.address.city>name';

        $this->assertEquals(
            [
                new ExpressionToken(ExpressionToken::TYPE_ALIAS, 't1'),
                new ExpressionToken(ExpressionToken::TYPE_DYN, ['address', 'city']),
                new ExpressionToken(ExpressionToken::TYPE_ATTR, 'name'),
            ],
            $this->compiler->compile($expression)
        );
    }
}
