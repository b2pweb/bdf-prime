<?php

namespace Bdf\Prime\Test;

use Bdf\Prime\TestEntity;
use DateTime;
use PHPUnit\Framework\Constraint\RegularExpression;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class RepositoryAssertionTest extends TestCase
{
    use RepositoryAssertion;
    
    /**
     * 
     */
    public function test_testpack()
    {
        $this->assertInstanceOf('Bdf\Prime\Test\TestPack', $this->getTestPack());
    }
    
    /**
     * 
     */
    public function test_assertion_same_entities()
    {
        $time = time();
        
        $entityExpected = new TestEntity([
            'id'            => 1,
            'name'          => 'Test1',
            'foreign'       => ['id' => 12, 'name' => 'test1'],
            'dateInsert'    => (new DateTime())->setTimestamp($time)
        ]);
        $entity = new TestEntity([
            'id'            => 1,
            'name'          => 'Test1',
            'foreign'       => ['id' => 12, 'name' => 'test2'],
            'dateInsert'    => (new DateTime())->setTimestamp($time)
        ]);
        
        $this->assertSameEntity($entityExpected, $entity);
    }
    
    /**
     * 
     */
    public function test_entities_are_not_the_same()
    {
        $entityExpected = new TestEntity([
            'id'      => 1,
            'name'    => ['Test1'],
            'foreign' => ['id' => 12, 'name' => 'test1']
        ]);
        $entity = new TestEntity([
            'id'      => 1,
            'name'    => 'Test1',
            'foreign' => ['id' => 13, 'name' => 'test2']
        ]);
        
        try {
            $this->assertSameEntity($entityExpected, $entity);
        } catch (ExpectationFailedException $e) {
//            echo $e->getMessage();
            //ok
            return;
        }

        $this->fail('entities should not be the same');
    }

    /**
     * 
     */
    public function test_assertion_same_array_of_entities()
    {
        $time = time();
        
        $entitiesExpected = [
            new TestEntity([
                'id'            => 1,
                'name'          => 'Test1',
                'foreign'       => ['id' => 12, 'name' => 'test1'],
                'dateInsert'    => (new DateTime())->setTimestamp($time)
            ])
        ];
        $entities = [
            new TestEntity([
                'id'            => 1,
                'name'          => 'Test1',
                'foreign'       => ['id' => 12, 'name' => 'test2'],
                'dateInsert'    => (new DateTime())->setTimestamp($time)
            ])
        ];
        
        $this->assertSameEntities($entitiesExpected, $entities);
    }
    
    /**
     * 
     */
    public function test_assertion_not_same_array_of_entities()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/Expected attribute "'.preg_quote(TestEntity::class).'::name" is not the same/');

        $entitiesExpected = [
            new TestEntity([
                'id'      => 1,
                'name'    => ['Test1'],
                'foreign' => ['id' => 12, 'name' => 'test1']
            ])
        ];
        $entities = [
            new TestEntity([
                'id'      => 1,
                'name'    => 'Test1',
                'foreign' => ['id' => 13, 'name' => 'test2']
            ])
        ];
        
        $this->assertSameEntities($entitiesExpected, $entities);
    }

    /**
     *
     */
    public function test_assertion_not_same_class_of_entities()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/Failed asserting that two entities are the same/');

        $entitiesExpected = (object)[
            'id'      => 1,
            'name'    => ['Test1'],
            'foreign' => ['id' => 12, 'name' => 'test1']
        ];
        $entities = new TestEntity([
            'id'      => 1,
            'name'    => 'Test1',
            'foreign' => ['id' => 13, 'name' => 'test2']
        ]);

        $this->assertSameEntity($entitiesExpected, $entities);
    }

    /**
     *
     */
    public function test_same_entities_have_not_the_same_number()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/Failed asserting that two collection entities are the same/');

        $entitiesExpected = [
            new TestEntity([
                'id'      => 1,
                'name'    => ['Test1'],
                'foreign' => ['id' => 12, 'name' => 'test1']
            ])
        ];
        $entities = [
            new TestEntity([
                'id'      => 1,
                'name'    => 'Test1',
                'foreign' => ['id' => 13, 'name' => 'test2']
            ]),
            new TestEntity([
                'id'      => 2,
                'name'    => 'Test2',
                'foreign' => ['id' => 13, 'name' => 'test2']
            ])
        ];

        $this->assertSameEntities($entitiesExpected, $entities);
    }

    /**
     *
     */
    public function test_entities_have_not_the_same_number()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/Failed asserting that two collection entities are the same/');

        $entitiesExpected = [
            new TestEntity([
                'id'      => 1,
                'name'    => ['Test1'],
                'foreign' => ['id' => 12, 'name' => 'test1']
            ])
        ];
        $entities = [
            new TestEntity([
                'id'      => 1,
                'name'    => 'Test1',
                'foreign' => ['id' => 13, 'name' => 'test2']
            ]),
            new TestEntity([
                'id'      => 2,
                'name'    => 'Test2',
                'foreign' => ['id' => 13, 'name' => 'test2']
            ])
        ];

        $this->assertEntities($entitiesExpected, $entities);
    }

    /**
     *
     */
    public function test_assertion_entities()
    {
        $entitiesExpected = [
            new TestEntity([
                'id'            => 1,
                'name'          => 'Test1',
                'foreign'       => ['id' => 12, 'name' => 'test1'],
                'dateInsert'    => new DateTime()
            ])
        ];
        $entities = [
            new TestEntity([
                'id'            => 1,
                'name'          => 'Test1',
                'foreign'       => ['id' => 12, 'name' => 'test2'],
                'dateInsert'    => new DateTime()
            ])
        ];

        $this->assertEntities($entitiesExpected, $entities);
    }

    /**
     *
     */
    public function test_custom_constraint()
    {
        $time = time();

        $entityExpected = [
            'id'            => new RegularExpression('/\d+/'),
            'name'          => 'Test1',
            'foreign.id'    => 12,
            'dateInsert'    => (new DateTime())->setTimestamp($time)
        ];
        $entity = new TestEntity([
            'id'            => '1',
            'name'          => 'Test1',
            'foreign'       => ['id' => 12, 'name' => 'test2'],
            'dateInsert'    => (new DateTime())->setTimestamp($time)
        ]);

        $this->assertEntityValues(TestEntity::class, $entityExpected, $entity);
    }

    /**
     *
     */
    public function test_custom_assertion_entities()
    {
        $time = time();

        $entitiesExpected = [
            [
                'id'            => new RegularExpression('/\d+/'),
                'name'          => 'Test1',
                'foreign.id'    => 12,
                'dateInsert'    => (new DateTime())->setTimestamp($time)
            ]
        ];
        $entities = [
            new TestEntity([
                'id'            => '1',
                'name'          => 'Test1',
                'foreign'       => ['id' => 12, 'name' => 'test2'],
                'dateInsert'    => (new DateTime())->setTimestamp($time)
            ])
        ];

        $this->assertEntityValues(TestEntity::class, $entitiesExpected, $entities);
    }
}