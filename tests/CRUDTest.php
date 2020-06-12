<?php

namespace Bdf\Prime;

use Bdf\Prime\Exception\DBALException;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class CRUDTest extends TestCase
{
    use PrimeTestCase;

    /**
     * Basic user for tests
     * 
     * @var User 
     */
    protected $basicUser;
    
    /**
     * Basic customer for tests
     * 
     * @var Customer 
     */
    protected $basicCustomer;
    
    /**
     * 
     */
    protected function setUp(): void
    {
        $this->primeStart();
        
        $this->basicUser = new User([
            'id'            => 1,
            'name'          => 'TEST1',
            'customer'      => new Customer(['id' => '1']),
            'dateInsert'    => new \DateTime(),
            'roles'         => ['2']
        ]);
        
        $this->basicCustomer = new Customer([
            'name'          => 'TEST',
        ]);
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([
            User::class,
            Customer::class,
        ]);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeReset();
    }

    /**
     * 
     */
    public function test_insert()
    {
        $repository = Prime::repository(User::class);

        $this->assertEquals(1, $repository->insert($this->basicUser), 'method insert');
        $this->assertEquals(1, $repository->count(), 'method count');
        $this->assertEquals(1, $this->basicUser->id, 'primary is set');
        $this->assertTrue(Prime::exists($this->basicUser, false), 'entity exists');
    }

    /**
     *
     */
    public function test_insert_sequence()
    {
        $repository = Prime::repository('Bdf\Prime\Customer');

        $entity = new Customer([
            'name' => __FUNCTION__,
        ]);

        $this->assertEquals(1, $repository->insert($entity), 'method insert');
        $this->assertEquals(1, $repository->count(), 'method count');
        $this->assertEquals(1, $entity->id, 'primary is set');
        $this->assertTrue(Prime::exists($entity), 'entity exists');
    }

    /**
     * 
     */
    public function test_insert_throws_duplicate_entry()
    {
        $this->pack()->nonPersist($this->basicUser);

        $this->expectException('Bdf\Prime\Exception\DBALException');
//        $this->expectException('Doctrine\DBAL\Exception\UniqueConstraintViolationException');

        Prime::repository('Bdf\Prime\User')->insert($this->basicUser);
    }

    /**
     * 
     */
    public function test_insert_ignore_an_existing_entity()
    {
        $this->pack()->nonPersist($this->basicUser);

        $repository = Prime::repository('Bdf\Prime\User');

        $this->assertEquals(0, $repository->insertIgnore($this->basicUser), 'method insert ignore');
        $this->assertEquals(1, $repository->count(), 'method count');
    }

    /**
     * 
     */
    public function test_save_non_existing_entity()
    {
        $repository = Prime::repository('Bdf\Prime\Customer');
        
        $this->assertEquals(1, $repository->save($this->basicCustomer), 'method save');
        $this->assertEquals(1, $repository->count(), 'method count');
    }

    /**
     *
     */
    public function test_save_existing_entity()
    {
        $this->pack()->nonPersist($this->basicCustomer);
        
        $repository = Prime::repository('Bdf\Prime\Customer');

        $this->assertEquals(1, $repository->save($this->basicCustomer), 'method save');
        $this->assertEquals(1, $repository->count(), 'method count');
    }

    /**
     *
     */
    public function test_update()
    {
        $this->pack()->nonPersist($this->basicCustomer);

        $repository = Prime::repository('Bdf\Prime\Customer');

        $entity = $repository->get(1);

        $entity->name = __FUNCTION__ . ' updated';
        $this->assertEquals(1, $repository->update($entity), 'method update');

        $this->assertTrue(Prime::exists($entity), 'entity updated');
    }

    /**
     *
     */
    public function test_update_without_change()
    {
        $this->pack()->nonPersist($this->basicCustomer);

        $repository = Prime::repository('Bdf\Prime\Customer');

        $entity = $repository->get(1);

        $count = $repository->update($entity);

        // sqlite still returns 1, mysql 0
//        $this->assertEquals(0, $count, 'method update');

        $this->assertTrue(Prime::exists($entity), 'entity exists');
    }

    /**
     *
     */
    public function test_replace_create_entity()
    {
        $repository = Prime::repository('Bdf\Prime\Customer');

        $this->assertEquals(1, $repository->replace($this->basicCustomer), 'method replace');
        $this->assertEquals(1, $repository->count(), 'method count');
        $this->assertTrue(Prime::exists($this->basicCustomer), 'entity exists');
    }

    /**
     *
     */
    public function test_replace_update_entity()
    {
        $repository = Prime::repository('Bdf\Prime\Customer');

        $this->pack()->nonPersist($this->basicCustomer);

        $entity = $repository->get(1);
        $entity->name = __FUNCTION__ . ' updated';

        $this->assertEquals(2, $repository->replace($entity), 'method replace'); // 2 means DELETE + INSERT
        $this->assertEquals(1, $repository->count(), 'method count');
        $this->assertTrue(Prime::exists($entity), 'entity exists');
    }

    /**
     *
     */
    public function test_find()
    {
        $this->pack()->nonPersist([
            new User(['id' => 1, 'name' => 'TEST1', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 2, 'name' => 'TEST2', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
        ]);

        $result = Prime::repository('Bdf\Prime\User')->find([
            'customer.id' => 1,
            ':limit'      => 3,
            ':order'      => 'id',
        ]);

        $this->assertEquals(2, count($result), 'method count');
    }

    /**
     *
     */
    public function test_find_distinct()
    {
        $this->pack()->nonPersist([
            new User(['id' => 1, 'name' => 'TEST1', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 2, 'name' => 'TEST2', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 3, 'name' => 'TEST3', 'customer' => new Customer(['id' => '2']), 'roles' => ['2']]),
        ]);
        
        $repository = Prime::repository('Bdf\Prime\User');

        $entities = $repository->find([
            ':distinct'  => true,
        ], 'customer.id');

        $count = $repository->count([
            ':distinct'  => true,
        ], 'customer.id');

        $this->assertEquals(2, $count);
        $this->assertEquals(count($entities), $count);
    }

    /**
     *
     */
    public function test_findOne()
    {
        $this->pack()->nonPersist([
            new User(['id' => 1, 'name' => 'TEST1', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 2, 'name' => 'TEST2', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 3, 'name' => 'TEST3', 'customer' => new Customer(['id' => '2']), 'roles' => ['2']]),
        ]);

        $repository = Prime::repository('Bdf\Prime\User');

        $entity = $repository->findOne([
            'name :like' => 'TEST%',
        ]/*, ['id', 'name']*/);

//        print_r($entity);
        $this->assertEquals(1, $entity->id, 'id');
        $this->assertEquals(1, $entity->customer->id, 'customer.id');
    }

    /**
     *
     */
    public function test_iterator()
    {
        $this->pack()->nonPersist([
            new User(['id' => 1, 'name' => 'TEST1', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 2, 'name' => 'TEST2', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 3, 'name' => 'TEST3', 'customer' => new Customer(['id' => '2']), 'roles' => ['2']]),
        ]);

        $repository = Prime::repository('Bdf\Prime\User');

        $iterator = $repository->walk(1);

        $nb = 0;
        foreach ($iterator as $entity) {
            $nb++;
            $this->assertEquals($nb, $entity->id);
        }

        $this->assertEquals(3, $nb);
    }

    /**
     *
     */
    public function test_group_by()
    {
        $this->pack()->nonPersist([
            new User(['id' => 1, 'name' => 'TEST1', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 2, 'name' => 'TEST2', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 3, 'name' => 'TEST3', 'customer' => new Customer(['id' => '2']), 'roles' => ['2']]),
        ]);

        $repository = Prime::repository('Bdf\Prime\User');

        $collection = $repository
            ->by('name')
            ->all();

        $this->assertEquals(3, count($collection));
        foreach ($collection as $key => $entity) {
            $this->assertEquals($entity->name, $key);
        }
    }

    /**
     *
     */
    public function test_duplicate()
    {
        $this->pack()->nonPersist($this->basicCustomer);
        
        $repository = Prime::repository('Bdf\Prime\Customer');

        $this->assertEquals(1, $repository->duplicate($this->basicCustomer), 'method duplicate');
        $this->assertEquals(2, $repository->count(), 'method count');
        $this->assertTrue(Prime::exists($this->basicCustomer), 'entity exists');

    }

    /**
     * 
     */
    public function test_filters()
    {
        $this->pack()->nonPersist($this->basicUser);

        $repository = Prime::repository('Bdf\Prime\User');
        $entity = $repository->findOne([
            'nameLike' => 'EST1'
        ]);

        $this->assertEquals(1, $entity->id);
    }

    /**
     *
     */
    public function test_scope()
    {
        $this->pack()->nonPersist([
            new User(['id' => 1, 'name' => 'TEST1', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 2, 'name' => 'TEST2', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 3, 'name' => 'TEST3', 'customer' => new Customer(['id' => '2']), 'roles' => ['2']]),
        ]);

        $repository = Prime::repository('Bdf\Prime\User');

        $result = $repository->testScope(1);

        $this->assertEquals(1, count($result));
        $this->assertEquals(['test' => 1], $result[0]);
    }

    /**
     *
     */
    public function test_event()
    {
        $this->pack()->nonPersist(
            new User(['id' => 1, 'name' => 'TEST1 to check event', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']])
        );
            
        $entity = Prime::repository('Bdf\Prime\User')->get(1);

        $this->assertEquals('TEST1 afterLoad', $entity->name);
    }

    /**
     * 
     */
    public function test_transaction()
    {
        $repository = Prime::repository('Bdf\Prime\User');

        try {
            $repository
                ->transaction(function($repository) {
                    $repository->insert($this->basicUser);
                    $repository->insert($this->basicUser);
                });
        } catch (\Bdf\Prime\Exception\DBALException $e) {
            $this->assertEquals(0, $repository->count());
            return;
        }

        $this->fail('Exception was not thrown');
    }

    /**
     *
     */
    public function test_multiple_embedded()
    {
        $this->pack()->nonPersist(
            Document::entity([
                'id' => 1,
                'customerId'   => '10',
                'uploaderType' => 'user',
                'uploaderId'   => '1',
                'contact' => (object)[
                    'name'     => 'Holmes',
                    'location' => new Location([
                        'address' => '221b Baker Street',
                        'city'    => 'London',
                    ])
                ],
            ])
        );

        $document = Document::get(1);

        $this->assertInstanceOf('Bdf\Prime\Contact', $document->contact);
        $this->assertInstanceOf('Bdf\Prime\Location', $document->contact->location);

        $this->assertEquals('Holmes', $document->contact->name);
        $this->assertEquals('221b Baker Street', $document->contact->location->address);
        $this->assertEquals('London', $document->contact->location->city);
    }

    /**
     * @group dev
     */
    public function test_partial_index()
    {
        // Force supports partial indexes : Doctrine not set to true whereas is supported
        $platform = new class extends SqlitePlatform {
            public function supportsPartialIndexes() { return true; }
        };

        $this->prime()->connections()->declareConnection('test2', [
            'adapter' => 'sqlite',
            'memory' => true,
            'platform' => $platform
        ]);

        PartialIndexEntity::repository()->on('test2');

        $this->pack()->nonPersist([
            new PartialIndexEntity([
                'id' => 1,
                'value' => 12
            ]),
            new PartialIndexEntity([
                'id' => 2,
                'value' => 55
            ])
        ]);

        try {
            (new PartialIndexEntity(['value' => 12]))->insert();
            $this->fail('expects UniqueConstraintViolationException');
        } catch (DBALException $e) {
            $this->assertStringContainsString('UNIQUE constraint failed: partial_index_entity_.value', $e->getPrevious()->getMessage());
        }

        // Index not applied for 55
        (new PartialIndexEntity(['value' => 55]))->insert();

        $this->assertEquals(3, PartialIndexEntity::count());
    }
}
