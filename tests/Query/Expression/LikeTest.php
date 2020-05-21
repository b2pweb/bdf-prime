<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Query\Compiler\CompilerInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class LikeTest extends TestCase
{
    /**
     *
     */
    public function test_setContext()
    {
        $like = new Like('');

        $like->setContext($this->createMock(CompilerInterface::class), 'col', '=');

        $this->assertEquals('col', $like->getColumn());
        $this->assertEquals(':like', $like->getOperator());
    }

    /**
     *
     */
    public function test_generate_defaults()
    {
        $like = new Like('');

        $this->assertEquals('foo', $like->generate('foo'));
    }

    /**
     *
     */
    public function test_generate_escape()
    {
        $like = new Like('');
        $like->escape();

        $this->assertEquals('my\_unsafe\%', $like->generate('my_unsafe%'));
    }

    /**
     *
     */
    public function test_generate_no_escape()
    {
        $like = new Like('');
        $like->escape(false);

        $this->assertEquals('my_unsafe%', $like->generate('my_unsafe%'));
    }

    /**
     *
     */
    public function test_default_escape()
    {
        $like = new Like('');

        $this->assertEquals('my_unsafe%', $like->generate('my_unsafe%'));
    }

    /**
     *
     */
    public function test_generate_end()
    {
        $like = new Like('');

        $like->end();

        $this->assertEquals('foo%', $like->generate('foo'));
    }

    /**
     *
     */
    public function test_generate_start()
    {
        $like = new Like('');

        $like->start();

        $this->assertEquals('%foo', $like->generate('foo'));
    }

    /**
     *
     */
    public function test_generate_enclose()
    {
        $like = new Like('');

        $like->enclose();

        $this->assertEquals('%foo%', $like->generate('foo'));
    }

    /**
     *
     */
    public function test_getValue_startsWith()
    {
        $like = new Like('foo');

        $like->startsWith();

        $this->assertEquals('foo%', $like->getValue());
    }

    /**
     *
     */
    public function test_getValue_endsWith()
    {
        $like = new Like('foo');

        $like->endsWith();

        $this->assertEquals('%foo', $like->getValue());
    }

    /**
     *
     */
    public function test_getValue_contains()
    {
        $like = new Like('foo');

        $like->contains();

        $this->assertEquals('%foo%', $like->getValue());
    }

    /**
     *
     */
    public function test_getValue_searchableArray()
    {
        $like = new Like('foo');

        $like->searchableArray();

        $this->assertEquals('%,foo,%', $like->getValue());
    }

    /**
     *
     */
    public function test_getValue_array()
    {
        $like = new Like(['hello', 'world', '!']);

        $like->contains();

        $this->assertEquals(['%hello%', '%world%', '%!%'], $like->getValue());
    }
}
