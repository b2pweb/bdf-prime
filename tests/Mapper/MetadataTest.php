<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Entity\Instantiator\InstantiatorInterface;
use Bdf\Prime\Location;
use Bdf\Prime\Mapper\Builder\IndexBuilder;
use Bdf\Prime\PolymorphContainer;
use Bdf\Prime\PolymorphContainerMapper;
use Bdf\Prime\PolymorphSubA;
use Bdf\Prime\PolymorphSubB;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Relations\Builder\RelationBuilder;
use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEmbeddedEntityMapper;
use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MetadataTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->configurePrime();
    }

    /**
     * 
     */
    public function test_embedded()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        $meta = $mapper->metadata();
        $this->assertEquals(TestEmbeddedEntity::class, $meta->embedded('foreign')['class']);
        $this->assertEquals(null, $meta->embedded('unknow'));
    }

    /**
     *
     */
    public function test_polymorph_embedded()
    {
        $mapper = new PolymorphContainerMapper(Prime::service(), PolymorphContainer::class);
        $mapper->build();
        $meta = $mapper->metadata();

        $this->assertEquals(['A' => PolymorphSubA::class, 'B' => PolymorphSubB::class], $meta->embedded('embedded')['class_map']);
        $this->assertEquals('sub_type', $meta->embedded('embedded')['discriminator_field']);
        $this->assertTrue($meta->embedded('embedded')['polymorph']);

        $this->assertEquals(Location::class, $meta->embedded('embedded.location')['class']);
    }
    
    /**
     * 
     */
    public function test_meta()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        $meta = $mapper->metadata();
        $this->assertEquals('dateInsert', $meta->meta('date_insert', 'fields')['attribute']);
        $this->assertEquals('date_insert', $meta->meta('dateInsert', 'attributes')['field']);
        $this->assertEquals(null, $meta->meta('unknow'));
    }
    
    /**
     * 
     */
    public function test_basic_info()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        $meta = $mapper->metadata();
        
        $this->assertEquals(TestEntity::class, $meta->getEntityClass());
        $this->assertEquals(InstantiatorInterface::USE_CONSTRUCTOR_HINT, $meta->instantiatorHint);
        $this->assertEquals('test', $meta->connection());
        $this->assertEquals('test_db', $meta->database());
        $this->assertEquals('test_table', $meta->table());
        $this->assertEquals(['engine' => 'innodb'], $meta->tableOptions());
        $this->assertEquals(true, $meta->isBuilt());
    }

    /**
     * 
     */
    public function test_embeddeds()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        $meta = $mapper->metadata();
        
        $embeddeds = $meta->embeddeds();
        
        $this->assertEquals(TestEmbeddedEntity::class, $embeddeds['foreign']['class']);
        $this->assertEquals(InstantiatorInterface::USE_CONSTRUCTOR_HINT, $embeddeds['foreign']['hint']);
        $this->assertEquals('foreign', $embeddeds['foreign']['path']);
        $this->assertEquals('root', $embeddeds['foreign']['parentPath']);
        $this->assertEquals(['foreign'], $embeddeds['foreign']['paths']);
    }
    
    /**
     * 
     */
    public function test_attributes()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        $meta = $mapper->metadata();
        
        $attributes = $meta->attributes();
        
        $this->assertEquals('foreign_key', $attributes['foreign.id']['field']);
        $this->assertEquals('foreign.id', $attributes['foreign.id']['attribute']);
        $this->assertEquals('foreign', $attributes['foreign.id']['embedded']);
        $this->assertEquals('integer', $attributes['foreign.id']['type']);
        $this->assertEquals(null, $attributes['foreign.id']['primary']);
        $this->assertEquals(null, $attributes['foreign.id']['default']);

        $this->assertEquals('id', $attributes['id']['field']);
        $this->assertEquals('id', $attributes['id']['attribute']);
        $this->assertEquals('integer', $attributes['id']['type']);
        $this->assertEquals(Metadata::PK_AUTOINCREMENT, $attributes['id']['primary']);
        $this->assertEquals(null, $attributes['id']['embedded']);
        $this->assertEquals(null, $attributes['id']['default']);
    }
    
    /**
     * 
     */
    public function test_primary()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        $meta = $mapper->metadata();
        
        $this->assertEquals(['id'], $meta->primary());
        $this->assertEquals(['id'], $meta->primary('fields'));
        $this->assertEquals(Metadata::PK_AUTOINCREMENT, $meta->primary('type'));
        $this->assertEquals(true, $meta->isPrimary('id'));
        $this->assertEquals(false, $meta->isPrimary('foreign.id'));
        $this->assertEquals(true, $meta->isAutoIncrementPrimaryKey());
        $this->assertEquals(false, $meta->isSequencePrimaryKey());
        $this->assertEquals(false, $meta->isCompositePrimaryKey());
    }

    /**
     *
     */
    public function test_mode_eager()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        $meta = $mapper->metadata();

        $this->assertEquals(['parent' => ['constraints' => [], 'relations' => []]], $meta->eagerRelations());
    }

    /**
     *
     */
    public function test_indexes()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        $meta = $mapper->metadata();

        $this->assertEquals([
            'name_search' => [
                'fields' => [
                    'name' => ['length' => 12]
                ]
            ],
            [
                'fields' => ['not_declared' => []],
                'spacial' => true,
                'myopt' => 'val',
            ],
            [
                'fields' => ['name' => []],
                'unique' => true,
            ]
        ], $meta->indexes());
    }

    /**
     *
     */
    public function test_indexes_legacy_format()
    {
        $mapper = new TestEmbeddedEntityMapper(Prime::service(), TestEmbeddedEntity::class);
        $mapper->build();
        $meta = $mapper->metadata();

        $this->assertEquals([
            [
                'fields' => [
                    'name_' => [],
                ]
            ],
            'id_name' => [
                'fields' => [
                    'pk_id' => [],
                    'name_' => [],
                ],
            ],
        ], $meta->indexes());
    }
}

class TestEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection'   => 'test',
            'database'     => 'test_db',
            'table'        => 'test_table',
            'tableOptions' => [
                'engine' => 'innodb',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')
            ->autoincrement()

            ->string('name', 255)->unique()

            ->embedded('foreign', TestEmbeddedEntity::class, function($builder) {
                $builder->integer('id')->alias('foreign_key');
            })

            ->dateTime('dateInsert')->nillable()->alias('date_insert')

            ->integer('parentId')->nillable()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('parent')
            ->belongsTo(TestEntity::class, 'parentId')
            ->mode(RelationBuilder::MODE_EAGER);


        $builder->on('children')
                ->hasMany(TestEntity::class.'::parentId')
                ->mode(RelationBuilder::MODE_EAGER)
                ->detached();
    }

    public function buildIndexes(IndexBuilder $builder): void
    {
        $builder
            ->add('name_search')->on('name', ['length' => 12])
            ->add()->on('not_declared')->flag('spacial')->option('myopt', 'val')
        ;
    }
}
