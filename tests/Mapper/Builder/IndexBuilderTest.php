<?php

namespace Bdf\Prime\Mapper\Builder;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class IndexBuilderTest extends TestCase
{
    /**
     *
     */
    public function test_add()
    {
        $builder = new IndexBuilder();

        $builder
            ->add()->on('a')
            ->add('z')->on('b')
            ->add()->on('c')
        ;

        $this->assertSame([
            0 => ['fields' => ['a' => []]],
            'z' => ['fields' => ['b' => []]],
            1 => ['fields' => ['c' => []]],
        ], $builder->build());
    }

    /**
     *
     */
    public function test_on_simple()
    {
        $builder = new IndexBuilder();

        $builder->add()->on('a');

        $this->assertSame([
            ['fields' => ['a' => []]]
        ], $builder->build());

        $builder->on('b', ['length' => 12]);

        $this->assertSame([
            ['fields' => [
                'a' => [],
                'b' => ['length' => 12],
            ]]
        ], $builder->build());
    }

    /**
     *
     */
    public function test_on_with_array()
    {
        $builder = new IndexBuilder();

        $builder->add()->on(['a', 'b' => ['length' => 12]]);

        $this->assertSame([
            ['fields' => [
                'a' => [],
                'b' => ['length' => 12],
            ]]
        ], $builder->build());
    }

    /**
     *
     */
    public function test_flag()
    {
        $builder = new IndexBuilder();

        $builder->add()->on('a');

        $this->assertSame($builder, $builder->flag('azerty'));

        $this->assertSame([[
            'fields' => ['a' => []],
            'azerty' => true,
        ]], $builder->build());
    }

    /**
     *
     */
    public function test_unique()
    {
        $builder = new IndexBuilder();

        $builder->add()->on('a');

        $this->assertSame($builder, $builder->unique());

        $this->assertSame([[
            'fields' => ['a' => []],
            'unique' => true,
        ]], $builder->build());
    }

    /**
     *
     */
    public function test_option()
    {
        $builder = new IndexBuilder();

        $builder->add()->on('a');

        $this->assertSame($builder, $builder->option('opt', 'val'));

        $this->assertSame([[
            'fields' => ['a' => []],
            'opt' => 'val',
        ]], $builder->build());
    }
}
