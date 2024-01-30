<?php

namespace Bdf\Prime\Mapper\Builder;

use Bdf\Prime\Name;
use Bdf\Prime\PolymorphSubA;
use Bdf\Prime\PolymorphSubB;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class FieldBuilderTest extends TestCase
{
    /**
     * 
     */
    public function test_add_primary()
    {
        $builder = new FieldBuilder();
        
        $builder->bigint('id')->primary();

        $this->assertSame(true, $builder['id']['primary']);
    }
    
    /**
     * 
     */
    public function test_add_autoincrement()
    {
        $builder = new FieldBuilder();
        
        $builder->bigint('id')->autoincrement();

        $this->assertSame('autoincrement', $builder['id']['primary']);
    }
    
    /**
     * 
     */
    public function test_add_sequence()
    {
        $builder = new FieldBuilder();
        
        $builder->bigint('id')->sequence();

        $this->assertSame('sequence', $builder['id']['primary']);
    }
    
    /**
     * @dataProvider typeProvider
     */
    public function test_add_type($method, $expected)
    {
        $builder = new FieldBuilder();
        
        $builder->$method('property');

        $expected += ['default' => null];

        $this->assertEquals(['property' => $expected], $builder->fields());
    }

    /**
     *
     */
    public function test_arrayOf()
    {
        $builder = new FieldBuilder();

        $builder->arrayOf('property', 'MyType');

        $this->assertSame(['property' => ['type' => 'MyType[]', 'default' => null]], $builder->fields());
    }
    
    public function typeProvider()
    {
        return [
            ['string',          ['type' => 'string', 'length' => 255]],
            ['text',            ['type' => 'text']],
            ['integer',         ['type' => 'integer']],
            ['bigint',          ['type' => 'bigint']],
            ['smallint',        ['type' => 'smallint']],
            ['tinyint',         ['type' => 'tinyint']],
            ['float',           ['type' => 'double']],
            ['double',          ['type' => 'double']],
            ['decimal',         ['type' => 'decimal']],
            ['boolean',         ['type' => 'boolean']],
            ['date',            ['type' => 'date']],
            ['dateTime',        ['type' => 'datetime']],
            ['dateTimeTz',      ['type' => 'datetimetz']],
            ['time',            ['type' => 'time']],
            ['timestamp',       ['type' => 'timestamp']],
            ['binary',          ['type' => 'binary']],
            ['blob',            ['type' => 'blob']],
            ['guid',            ['type' => 'guid']],
            ['json',            ['type' => 'json']],
            ['simpleArray',     ['type' => 'array']],
            ['object',          ['type' => 'object']],
            ['arrayObject',     ['type' => 'array_object']],
            ['searchableArray', ['type' => 'array']],
            ['arrayOfInt',      ['type' => 'integer[]']],
            ['arrayOfDouble',   ['type' => 'double[]']],
            ['arrayOfDateTime', ['type' => 'datetime[]']],
        ];
    }

    /**
     * 
     */
    public function test_embedded()
    {
        $builder = new FieldBuilder();
        
        $builder->embedded('customer', 'Customer', function($builder) {
            $builder->string('name');
        });

        $expected = [
            'customer' => [
                'class'    => 'Customer',
                'embedded' => [
                    'name' => [
                        'type'       => 'string',
                        'default'    => null,
                        'length'     => 255,
                    ],
                ]
            ]
        ];

        $this->assertSame($expected, $builder->fields());
    }

    public function test_valueObject()
    {
        $builder = new FieldBuilder();
        $builder->string('name')->valueObject(Name::class);

        $expected = [
            'name' => [
                'type'       => 'string',
                'default'    => null,
                'length'     => 255,
                'valueObject' => Name::class,
            ],
        ];

        $this->assertSame($expected, $builder->fields());
    }

    /**
     *
     */
    public function test_polymorph()
    {
        $builder = new FieldBuilder();

        $builder->polymorph('sub', ['A' => PolymorphSubA::class, 'B' => PolymorphSubB::class], function(PolymorphBuilder $builder) {
            $builder
                ->string('type')->alias('sub_type')->discriminator()
                ->string('name')->alias('sub_name')
            ;
        });

        $expected = [
            'sub' => [
                'class_map' => ['A' => PolymorphSubA::class, 'B' => PolymorphSubB::class],
                'polymorph' => true,
                'embedded' => [
                    'type' => [
                        'type'       => 'string',
                        'default'    => null,
                        'length'     => 255,
                        'alias' => 'sub_type',
                        'nillable' => false
                    ],
                    'name' => [
                        'type'       => 'string',
                        'default'    => null,
                        'length'     => 255,
                        'alias' => 'sub_name'
                    ],
                ],
                'discriminator_field' => 'sub_type',
                'discriminator_attribute' => 'type',
            ]
        ];

        $this->assertSame($expected, $builder->fields());
    }
    
    /**
     * 
     */
    public function test_add_length_and_default()
    {
        $builder = new FieldBuilder();
        
        $builder->string('name', 60, '');

        $this->assertSame(60, $builder['name']['length']);
        $this->assertSame('', $builder['name']['default']);
    }
    
    /**
     * 
     */
    public function test_add_alias()
    {
        $builder = new FieldBuilder();
        
        $builder->bigint('id')->alias('pk_id');

        $this->assertSame('pk_id', $builder['id']['alias']);
    }
    
    /**
     * 
     */
    public function test_add_default()
    {
        $builder = new FieldBuilder();
        
        $builder->string('name')->setDefault('John');

        $this->assertSame('John', $builder['name']['default']);
    }

    /**
     *
     */
    public function test_add_length()
    {
        $builder = new FieldBuilder();

        $builder->string('name')->length(60);

        $this->assertSame(60, $builder['name']['length']);
    }

    /**
     *
     */
    public function test_add_comment()
    {
        $builder = new FieldBuilder();

        $builder->string('name')->comment('test comment');

        $this->assertSame('test comment', $builder['name']['comment']);
    }

    /**
     *
     */
    public function test_add_precision()
    {
        $builder = new FieldBuilder();

        $builder->string('name')->precision(5, 2);

        $this->assertSame(5, $builder['name']['precision']);
        $this->assertSame(2, $builder['name']['scale']);
    }

    /**
     *
     */
    public function test_add_php_option()
    {
        $builder = new FieldBuilder();

        $builder->string('name')->phpOptions('foo', 'bar');

        $this->assertSame('bar', $builder['name']['phpOptions']['foo']);
    }

    /**
     *
     */
    public function test_add_timezone()
    {
        $builder = new FieldBuilder();

        $builder->dateTime('name')->timezone('UTC');

        $this->assertSame('UTC', $builder['name']['phpOptions']['timezone']);
    }

    /**
     *
     */
    public function test_add_class_name()
    {
        $builder = new FieldBuilder();

        $builder->dateTime('name')->phpClass('stdClass');

        $this->assertSame('stdClass', $builder['name']['phpOptions']['className']);
    }

    /**
     *
     */
    public function test_add_schema_options()
    {
        $builder = new FieldBuilder();

        $builder->string('name')
            ->schemaOptions(['custom' => 'test']);

        $this->assertSame('test', $builder['name']['customSchemaOptions']['custom']);

        $builder->schemaOption('custom2', 'test2');
        $this->assertSame('test2', $builder['name']['customSchemaOptions']['custom2']);
    }

    public function test_useNativeJsonType()
    {
        $builder = new FieldBuilder();

        $builder->json('foo')->useNativeJsonType();
        $this->assertSame(['use_native_json' => true], $builder['foo']['customSchemaOptions']);
    }

    public function test_jsonObjectAsArray()
    {
        $builder = new FieldBuilder();

        $builder->json('foo')->jsonObjectAsArray(false);
        $this->assertSame(['object_as_array' => false], $builder['foo']['phpOptions']);
    }

    /**
     *
     */
    public function test_add_platform_options()
    {
        $builder = new FieldBuilder();

        $builder->string('name')
            ->platformOptions(['custom' => 'test']);

        $this->assertSame('test', $builder['name']['platformOptions']['custom']);
    }

    /**
     *
     */
    public function test_add_definition()
    {
        $builder = new FieldBuilder();

        $builder->string('name')
            ->definition('custom schema');

        $this->assertSame('custom schema', $builder['name']['columnDefinition']);
    }

    /**
     * @dataProvider flagProvider
     */
    public function test_add_flag($method, $type = null)
    {
        $builder = new FieldBuilder();
        
        $builder->integer('age')->$method();

        $expected = [
            'age' => [
                'type'           => 'integer',
                'default'        => null,
                $type ?: $method => true,
            ],
        ];

        $this->assertSame($expected, $builder->fields());
    }
    
    public function flagProvider()
    {
        return [
            ['nillable'],
            ['unsigned'],
            ['unique'],
            ['fixed'],
        ];
    }
    
    /**
     * 
     */
    public function test_iterator()
    {
        $builder = new FieldBuilder();
        
        $builder->bigint('id');
        $builder->string('name');

        $items = ['id', 'name'];
        $i = 0;
        
        foreach ($builder as $key => $meta) {
            $this->assertSame($items[$i++], $key);
        }
        
        $this->assertSame(2, $i);
    }
    
    /**
     * 
     */
    public function test_array_access()
    {
        $builder = new FieldBuilder();
        
        $builder->bigint('id');

        $this->assertTrue(isset($builder['id']));
        $this->assertSame('bigint', $builder['id']['type']);
        
        unset($builder['id']);
        $this->assertTrue(isset($builder['id']));
        
        $builder['id'] = null;
        $this->assertTrue(isset($builder['id']));
    }
}
