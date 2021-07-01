<?php

namespace Bdf\Prime\Query\Compiler\Preprocessor;

use Bdf\Prime\Document;
use Bdf\Prime\Faction;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Compiler\AliasResolver\AliasResolver;
use Bdf\Prime\Query\Expression\Raw;
use Bdf\Prime\Query\Expression\TypedExpressionInterface;
use Bdf\Prime\Query\Query;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class OrmPreprocessorTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var OrmPreprocessor
     */
    protected $preprocessor;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->configurePrime();

        $this->preprocessor = new OrmPreprocessor(Prime::repository(Faction::class));
    }

    /**
     *
     */
    public function test_forInsert()
    {
        $query = new Query($this->prime()->connection('test'));

        $this->assertSame($query, $this->preprocessor->forInsert($query));
    }

    /**
     *
     */
    public function test_forUpdate()
    {
        $query = new Query($this->prime()->connection('test'));
        $query->where('foo', 'bar');

        $updateQuery = $this->preprocessor->forUpdate($query);

        $this->assertNotSame($query, $updateQuery);
        $this->assertEquals(
            array_merge_recursive($query->statements, [
                'where' => [
                    [
                        'nested' => [[
                            'column' => 'enabled',
                            'operator' => '=',
                            'value' => true,
                            'glue' => 'AND'
                        ]],
                        'glue' => 'AND'
                    ]
                ]
            ]),
            $updateQuery->statements
        );
    }

    /**
     *
     */
    public function test_forDelete()
    {
        $query = new Query($this->prime()->connection('test'));
        $query->where('foo', 'bar');

        $updateQuery = $this->preprocessor->forDelete($query);

        $this->assertNotSame($query, $updateQuery);
        $this->assertEquals(
            array_merge_recursive($query->statements, [
                'where' => [
                    [
                        'nested' => [[
                            'column' => 'enabled',
                            'operator' => '=',
                            'value' => true,
                            'glue' => 'AND'
                        ]],
                        'glue' => 'AND'
                    ]
                ]
            ]),
            $updateQuery->statements
        );
    }

    /**
     *
     */
    public function test_forSelect()
    {
        $baseQuery = Faction::where('userFaction.name', 'My name');
        $cloned = clone $baseQuery;

        $selectQuery = $this->preprocessor->forSelect($baseQuery);

        $this->assertNotEquals($selectQuery, $cloned);
        $this->assertEquals($baseQuery, $cloned);

        $this->assertEquals('t0', $selectQuery->statements['tables']['faction_']['alias']);
    }

    /**
     *
     */
    public function test_forSelect_already_compiled()
    {
        $selectQuery = $this->preprocessor->forSelect(Faction::where('userFaction.name', 'My name'));
        $selectQuery->compiler()->compileSelect($selectQuery);

        $this->assertNotSame($selectQuery, $this->preprocessor->forSelect($selectQuery));
        $this->assertEquals($selectQuery, $this->preprocessor->forSelect($selectQuery));
    }

    /**
     *
     */
    public function test_field_on_write()
    {
        $type = true;
        $this->assertEquals('name_', $this->preprocessor->field('name', $type));
        $this->assertInstanceOf(SqlStringType::class, $type);

        $this->assertEquals('not_found', $this->preprocessor->field('not_found'));
    }

    /**
     *
     */
    public function test_field_on_select_unit()
    {
        $resolver = $this->createMock(AliasResolver::class);
        $reflection = new \ReflectionProperty($this->preprocessor, 'aliasResolver');
        $reflection->setAccessible(true);
        $reflection->setValue($this->preprocessor, $resolver);

        $reflection = new \ReflectionProperty($this->preprocessor, 'type');
        $reflection->setAccessible(true);
        $reflection->setValue($this->preprocessor, 'select');

        $resolver->expects($this->once())
            ->method('resolve')
            ->with('userFaction.name', null)
            ->willReturn('t1.name_')
        ;

        $this->assertEquals('t1.name_', $this->preprocessor->field('userFaction.name'));
    }

    /**
     *
     */
    public function test_field_on_select_functional()
    {
        $query = Prime::repository(Faction::class)->builder();
        $this->preprocessor->forSelect($query);

        $this->assertEquals('t1.name_', $this->preprocessor->field('userFaction.name'));
    }

    /**
     *
     */
    public function test_expression_column_value()
    {
        $this->assertEquals([
            'column' => 'name_',
            'value' => 123,
            'converted' => true
        ], $this->preprocessor->expression([
            'column' => 'name',
            'value' => 123
        ]));
    }

    /**
     *
     */
    public function test_expression_column_boolean()
    {
        $this->assertEquals([
            'column' => 'enabled_',
            'value' => 1,
            'converted' => true
        ], $this->preprocessor->expression([
            'column' => 'enabled',
            'value' => true
        ]));
    }

    /**
     *
     */
    public function test_expression_raw()
    {
        $expression = [
            'raw' => new Raw('sss')
        ];

        $this->assertSame($expression, $this->preprocessor->expression($expression));
    }

    /**
     *
     */
    public function test_expression_searchable_array()
    {
        $expression = [
            'column' => 'roles',
            'operator' => 'in',
            'value' => [[1, 2], [3], ',5,6,'],
            'converted' => true
        ];

        $preprocessor = new OrmPreprocessor(User::repository());

        $this->assertEquals([
            'column' => 'roles_',
            'operator' => 'in',
            'value' => [',1,2,', ',3,', ',5,6,'],
            'converted' => true
        ], $preprocessor->expression($expression));
    }

    /**
     *
     */
    public function test_expression_with_typed_expression_will_setType()
    {
        $typed = $this->createMock(TypedExpressionInterface::class);

        $expression = [
            'column' => 'roles',
            'operator' => 'in',
            'value' => $typed,
            'converted' => true
        ];

        $preprocessor = new OrmPreprocessor(User::repository());

        $typed->expects($this->once())
            ->method('setType')
            ->with($this->prime()->connection('test')->getConfiguration()->getTypes()->get('array'))
        ;

        $this->assertEquals([
            'column' => 'roles_',
            'operator' => 'in',
            'value' => $typed,
            'converted' => true
        ], $preprocessor->expression($expression));
    }

    /**
     *
     */
    public function test_table_no_resolver()
    {
        $table = [
            'table' => 'my_table',
            'alias' => 'my_alias'
        ];

        $this->assertSame($table, $this->preprocessor->table($table));
    }

    /**
     *
     */
    public function test_table_register_new()
    {
        $query = new Query($this->prime()->connection('test'));
        $this->preprocessor->forSelect($query);

        $table = [
            'table' => Document::class,
            'alias' => null
        ];

        $this->assertEquals([
            'table' => 'document_',
            'alias' => 't0'
        ], $this->preprocessor->table($table));
    }

    /**
     *
     */
    public function test_table_register_registered()
    {
        $query = new Query($this->prime()->connection('test'));
        $this->preprocessor->forSelect($query);

        $this->preprocessor->table([
            'table' => Document::class,
            'alias' => 'my_alias'
        ]);

        $this->assertEquals([
            'table' => 'document_',
            'alias' => 'my_alias'
        ], $this->preprocessor->table([
            'table' => null,
            'alias' => 'my_alias'
        ]));
    }

    /**
     *
     */
    public function test_root_no_resolver()
    {
        $this->assertNull($this->preprocessor->root());
    }

    /**
     *
     */
    public function test_root_with_resolver()
    {
        $query = new Query($this->prime()->connection('test'));
        $this->preprocessor->forSelect($query);

        $this->assertEquals('t0', $this->preprocessor->root());
    }
}
