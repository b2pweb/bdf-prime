<?php

namespace Php81\Mapper;

require_once __DIR__ . '/Fixtures/singleinheritance_backed_enum.php';
require_once __DIR__ . '/Fixtures/singleinheritance_unit_enum.php';

use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Relations\Relation;
use Php81\Mapper\Fixtures\ChildEntity1;
use Php81\Mapper\Fixtures\ChildEntity1Mapper;
use Php81\Mapper\Fixtures\ChildEntity2;
use Php81\Mapper\Fixtures\ChildEntity2Mapper;
use Php81\Mapper\Fixtures\ParentEntity;
use Php81\Mapper\Fixtures\ParentEntityMapper;
use Php81\Mapper\Fixtures\TypeDiscriminatorEnum;
use Php81\Mapper\Fixtures\TypeDiscriminatorUnitEnum;
use Php81\Mapper\Fixtures\UnitChildEntity1;
use Php81\Mapper\Fixtures\UnitChildEntity2;
use Php81\Mapper\Fixtures\UnitParentEntity;
use Php81\Mapper\Fixtures\UnitParentEntityMapper;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SingleTableInheritanceMapperTest extends TestCase
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

    /**
     * @dataProvider providePrepareFromRepositoryData
     */
    public function test_prepareFromRepository_from_parent(array $data, array $expectations)
    {
        $mapper = new ParentEntityMapper(Prime::service(), ParentEntity::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $entity = $mapper->prepareFromRepository($data, Prime::connection('test')->platform());
        
        $this->assertInstanceOf($expectations['__instanceof__'], $entity);

        $this->assertEquals($expectations['id'], $entity->id, 'id');
        $this->assertEquals($expectations['typeId'], $entity->typeId, 'typeId');
        $this->assertEquals($expectations['name'], $entity->name, 'name');
        $this->assertEquals($expectations['dateInsert'], $entity->dateInsert, 'dateInsert');
        $this->assertEquals($expectations['targetId'], $entity->targetId, 'targetId');
    }

    /**
     * @return array
     */
    public function providePrepareFromRepositoryData()
    {
        return [
            [
                // Data
                [
                    'id'          => 2,
                    'type_id'     => 'child1',
                    'name'        => 'test',
                    'date_insert' => '2015-04-20 10:00:00',
                    'target_id'   => 100
                ],
                // Expectations
                [
                    '__instanceof__' => ChildEntity1::class,
                    'id'             => 2,
                    'typeId'         => TypeDiscriminatorEnum::Child1,
                    'name'           => 'test',
                    'dateInsert'     => new \DateTime('2015-04-20 10:00:00'),
                    'targetId'       => 100
                ]
            ],
            [
                // Data
                [
                    'id'          => 3,
                    'type_id'     => 'child2',
                    'name'        => 'test2',
                    'date_insert' => '2015-04-20 10:30:00',
                    'target_id'   => 105
                ],
                // Expectations
                [
                    '__instanceof__' => ChildEntity2::class,
                    'id'             => 3,
                    'typeId'         => TypeDiscriminatorEnum::Child2,
                    'name'           => 'test2',
                    'dateInsert'     => new \DateTime('2015-04-20 10:30:00'),
                    'targetId'       => 105
                ]
            ]
        ];
    }

    /**
     * @dataProvider providePrepareFromRepositoryDataUnitEnum
     */
    public function test_prepareFromRepository_from_parent_unit_enum(array $data, array $expectations)
    {
        $mapper = new UnitParentEntityMapper(Prime::service(), UnitParentEntity::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $entity = $mapper->prepareFromRepository($data, Prime::connection('test')->platform());

        $this->assertInstanceOf($expectations['__instanceof__'], $entity);

        $this->assertEquals($expectations['id'], $entity->id, 'id');
        $this->assertEquals($expectations['typeId'], $entity->typeId, 'typeId');
        $this->assertEquals($expectations['name'], $entity->name, 'name');
        $this->assertEquals($expectations['dateInsert'], $entity->dateInsert, 'dateInsert');
        $this->assertEquals($expectations['targetId'], $entity->targetId, 'targetId');
    }

    /**
     * @return array
     */
    public function providePrepareFromRepositoryDataUnitEnum()
    {
        return [
            [
                // Data
                [
                    'id'          => 2,
                    'type_id'     => 'Child1',
                    'name'        => 'test',
                    'date_insert' => '2015-04-20 10:00:00',
                    'target_id'   => 100
                ],
                // Expectations
                [
                    '__instanceof__' => UnitChildEntity1::class,
                    'id'             => 2,
                    'typeId'         => TypeDiscriminatorUnitEnum::Child1,
                    'name'           => 'test',
                    'dateInsert'     => new \DateTime('2015-04-20 10:00:00'),
                    'targetId'       => 100
                ]
            ],
            [
                // Data
                [
                    'id'          => 3,
                    'type_id'     => 'Child2',
                    'name'        => 'test2',
                    'date_insert' => '2015-04-20 10:30:00',
                    'target_id'   => 105
                ],
                // Expectations
                [
                    '__instanceof__' => UnitChildEntity2::class,
                    'id'             => 3,
                    'typeId'         => TypeDiscriminatorUnitEnum::Child2,
                    'name'           => 'test2',
                    'dateInsert'     => new \DateTime('2015-04-20 10:30:00'),
                    'targetId'       => 105
                ]
            ]
        ];
    }

    /**
     * 
     */
    public function test_prepareFromRepository_should_throws_exception_if_discriminator_field_not_found()
    {
        $data = [
            'id'          => 2,
            'name'        => 'test',
            'date_insert' => '2015-04-20 10:00:00',
            'target_id'   => 100
        ];

        $mapper = new ParentEntityMapper(Prime::service(), ParentEntityMapper::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $this->expectException('Exception');
        $this->expectExceptionMessage('Discriminator field "type_id" not found');

        $mapper->prepareFromRepository($data, Prime::connection('test')->platform());
    }

    /**
     * 
     */
    public function test_prepareFromRepository_should_throws_exception_if_unknown_discriminator_type()
    {
        $data = [
            'id'          => 2,
            'type_id'     => 'fakechild',
            'name'        => 'test',
            'date_insert' => '2015-04-20 10:00:00',
            'target_id'   => 100
        ];

        $mapper = new ParentEntityMapper(Prime::service(), ParentEntityMapper::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $this->expectException('Exception');
        $this->expectExceptionMessage('Unknown discriminator type "fakechild"');

        $mapper->prepareFromRepository($data, Prime::connection('test')->platform());
    }

    /**
     *
     */
    public function test_getMapperByDiscriminatorValue()
    {
        $mapper = new ParentEntityMapper(Prime::service(), ParentEntityMapper::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $this->assertInstanceOf(ChildEntity1Mapper::class, $mapper->getMapperByDiscriminatorValue('child1'));
        $this->assertInstanceOf(ChildEntity2Mapper::class, $mapper->getMapperByDiscriminatorValue('child2'));
    }

    /**
     * 
     */
    public function test_constraints_from_parent()
    {
        $mapper = new ParentEntityMapper(Prime::service(), ParentEntity::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $this->assertEquals([], $mapper->constraints());
    }

    /**
     * 
     */
    public function test_constraints_from_children()
    {
        $mapper1 = new ChildEntity1Mapper(Prime::service(), ChildEntity1::class);
        $mapper1->build();
        $mapper2 = new ChildEntity2Mapper(Prime::service(), ChildEntity2::class);
        $mapper2->build();

        $this->assertEquals(['typeId' => 'child1'], $mapper1->constraints());
        $this->assertEquals(['typeId' => 'child2'], $mapper2->constraints());
    }

    /**
     *
     */
    public function test_getDiscriminatorValueByRawData()
    {
        $mapper = new ParentEntityMapper(Prime::service(), ParentEntityMapper::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $this->assertEquals(
            'child1',
            $mapper->getDiscriminatorValueByRawData([
                'type_id' => 'child1'
            ])
        );
    }

    /**
     *
     */
    public function test_getDiscriminatorValueByRawData_should_throw_exception()
    {
        $mapper = new ParentEntityMapper(Prime::service(), ParentEntityMapper::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $this->expectException('Exception');
        $this->expectExceptionMessage('Discriminator field "type_id" not found');

        $mapper->getDiscriminatorValueByRawData([
            'id' => 10
        ]);
    }

    /**
     * 
     */
    public function test_getDiscriminatorType()
    {
        $mapper = new ParentEntityMapper(Prime::service(), ParentEntityMapper::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $this->assertEquals(ChildEntity1Mapper::class, $mapper->getDiscriminatorType('child1'));
        $this->assertEquals(ChildEntity2Mapper::class, $mapper->getDiscriminatorType('child2'));
    }

    /**
     *
     */
    public function test_getDiscriminatorType_should_throw_exception()
    {
        $mapper = new ParentEntityMapper(Prime::service(), ParentEntityMapper::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $this->expectException('Exception');
        $this->expectExceptionMessage('Unknown discriminator type "fakechild"');

        $mapper->getDiscriminatorType('fakechild');
    }

    /**
     *
     */
    public function test_get_entity_map()
    {
        $mapper = new ParentEntityMapper(Prime::service(), ParentEntity::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $expected = [
            'child1' => ChildEntity1::class,
            'child2' => ChildEntity2::class,
        ];

        $this->assertEquals($expected, $mapper->getEntityMap());
    }

    /**
     *
     */
    public function test_get_entity_map_unit_enum()
    {
        $mapper = new UnitParentEntityMapper(Prime::service(), UnitParentEntity::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $expected = [
            'Child1' => UnitChildEntity1::class,
            'Child2' => UnitChildEntity2::class,
        ];

        $this->assertEquals($expected, $mapper->getEntityMap());
    }

    /**
     *
     */
    public function test_get_entity_map_from_child()
    {
        $mapper = new ChildEntity2Mapper(Prime::service(), ChildEntity2::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $expected = [
            'child1' => ChildEntity1::class,
            'child2' => ChildEntity2::class,
        ];

        $this->assertEquals($expected, $mapper->getEntityMap());
    }

    /**
     *
     */
    public function test_relation()
    {
        $mapper = new ParentEntityMapper(Prime::service(), ParentEntity::class);
        $mapper->build();
        $mapper->setMapperFactory(Prime::service()->mappers());

        $expected = [
            'name' => 'target',
            'type'      => Relation::BY_INHERITANCE,
            'localKey'  => 'targetId',
        ];

        $this->assertEquals($expected, $mapper->relation('target'));
    }
}
