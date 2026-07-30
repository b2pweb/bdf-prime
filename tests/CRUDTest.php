<?php

namespace Bdf\Prime;

use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Query\Expression\Attribute;
use Bdf\Prime\Record\Field;
use Bdf\Prime\Record\LoadRelation;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

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

        $entity = $repository->findById(1);

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

        $entity = $repository->findById(1);

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

        $entity = $repository->findById(1);
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
    public function test_walker_with_delete_should_not_skip_entities()
    {
        $entities = [];

        for ($i = 1; $i <= 10; ++$i) {
            $entities[] = new User(['id' => $i, 'name' => 'TEST'.$i, 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]);
        }

        $this->pack()->nonPersist($entities);

        $iterator = User::repository()->builder()->walk(3);

        $actual = [];
        foreach ($iterator as $entity) {
            $actual[] = $entity;
            $entity->delete();
        }

        $this->assertEquals($entities, $actual);
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
            
        $entity = Prime::repository('Bdf\Prime\User')->findById(1);

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

        $document = Document::findById(1);

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
            public function supportsPartialIndexes(): bool { return true; }
        };

        $this->prime()->connections()->declareConnection('test2', [
            'adapter' => 'sqlite',
            'memory' => true,
            'platform' => $platform
        ]);
        $connection = $this->prime()->connection('test2');
        $r = new ReflectionProperty(Connection::class, 'platform');
        $r->setValue($connection, $platform);

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

    /**
     * @see https://github.com/b2pweb/bdf-prime/issues/8
     */
    public function test_custom_nullable_type()
    {
        $this->pack()->declareEntity(MyCustomNullableEntity::class);

        // Simple insert
        $entity = new MyCustomNullableEntity(['foo' => new Foo('oof')]);
        $entity->insert();

        // Check PHP and database value
        $this->assertEquals(new Foo('oof'), MyCustomNullableEntity::refresh($entity)->foo);
        $this->assertSame('oof', MyCustomNullableEntity::where('id', $entity->id)->inRow('foo'));

        // Update to null should save as "0"
        $entity->foo = null;
        $entity->update();
        $this->assertSame('0', MyCustomNullableEntity::where('id', $entity->id)->inRow('foo'));

        // Insert with null should save as "0"
        $withNull = new MyCustomNullableEntity(['foo' => null]);
        $withNull->insert();

        $this->assertSame('0', MyCustomNullableEntity::where('id', $withNull->id)->inRow('foo'));

        // Test select on custom type : null should be converted
        $query = MyCustomNullableEntity::where('foo', null);

        $this->assertEquals([$entity, $withNull], $query->all());
        $this->assertEquals('SELECT t0.* FROM my_custom_nullable t0 WHERE t0.foo = ?', $query->toSql());
        $this->assertSame(['0'], $query->getBindings());

        $query = MyCustomNullableEntity::where('foo', [null, new Foo('bar')]);
        $this->assertEquals('SELECT t0.* FROM my_custom_nullable t0 WHERE t0.foo IN (?,?)', $query->toSql());
        $this->assertSame(['0', 'bar'], $query->getBindings());
    }

    public function test_record()
    {
        $this->pack()->nonPersist([
            new User([
                'id' => 12,
                'name' => 'John',
                'roles' => ['2'],
                'customer' => new Customer(['id' => '1']),
            ]),
            new User([
                'id' => 13,
                'name' => 'Mark',
                'roles' => ['5'],
                'customer' => new Customer(['id' => '1']),
            ]),
        ]);

        $records = User::repository()->builder()->as(IdNameRecord::class)->all();

        $this->assertContainsOnly(IdNameRecord::class, $records);
        $this->assertEquals([
            new IdNameRecord('12', 'John'),
            new IdNameRecord('13', 'Mark'),
        ], $records);
    }

    public function test_record_with_transformer()
    {
        $this->pack()->nonPersist([
            new User([
                'id' => 12,
                'name' => 'John',
                'roles' => ['2'],
                'customer' => new Customer(['id' => '1']),
            ]),
            new User([
                'id' => 13,
                'name' => 'Mark',
                'roles' => ['5'],
                'customer' => new Customer(['id' => '1']),
            ]),
        ]);

        $records = User::repository()->builder()->as(RecordWithTransformer::class)->all();

        $this->assertContainsOnly(RecordWithTransformer::class, $records);
        $this->assertEquals([
            new RecordWithTransformer('12', '61409aa1fd47d4a5332de23cbf59a36f'),
            new RecordWithTransformer('13', 'b82a9a13f4651e9abcbde90cd24ce2cb'),
        ], $records);
    }

    public function test_record_with_relation()
    {
        $this->pack()->nonPersist([
            $customer1 = new Customer(['id' => 1, 'name' => 'Customer 1']),
            $customer2 = new Customer(['id' => 2, 'name' => 'Customer 2']),
            new User([
                'id' => 12,
                'name' => 'John',
                'roles' => ['2'],
                'customer' => $customer1,
            ]),
            new User([
                'id' => 13,
                'name' => 'Mark',
                'roles' => ['5'],
                'customer' => $customer2,
            ]),
            $doc1 = new Document([
                'id' => 1,
                'customerId' => 1,
                'uploaderType' => 'user',
                'uploaderId' => 12,
            ]),
            $doc2 = new Document([
                'id' => 2,
                'customerId' => 1,
                'uploaderType' => 'user',
                'uploaderId' => 12,
            ]),
            $doc3 = new Document([
                'id' => 3,
                'customerId' => 2,
                'uploaderType' => 'user',
                'uploaderId' => 13,
            ]),
        ]);

        $records = User::repository()->builder()->as(NameAndCustomer::class)->all();

        $this->assertEquals([
            new NameAndCustomer('John', $customer1),
            new NameAndCustomer('Mark', $customer2),
        ], $records);

        $records = User::repository()->builder()->as(NameAndDocuments::class)->all();

        $this->assertEquals([
            new NameAndDocuments('John', [$doc1, $doc2]),
            new NameAndDocuments('Mark', [$doc3]),
        ], $records);
    }

    public function test_record_with_relation_record()
    {
        $this->pack()->nonPersist([
            $customer1 = new Customer(['id' => 1, 'name' => 'Customer 1']),
            $customer2 = new Customer(['id' => 2, 'name' => 'Customer 2']),
            new User([
                'id' => 12,
                'name' => 'John',
                'roles' => ['2'],
                'customer' => $customer1,
            ]),
            new User([
                'id' => 13,
                'name' => 'Mark',
                'roles' => ['5'],
                'customer' => $customer2,
            ]),
            $doc1 = new Document([
                'id' => 1,
                'customerId' => 1,
                'uploaderType' => 'user',
                'uploaderId' => 12,
                'contact' => new Contact([
                    'name' => 'John',
                ]),
            ]),
            $doc2 = new Document([
                'id' => 2,
                'customerId' => 1,
                'uploaderType' => 'user',
                'uploaderId' => 12,
                'contact' => new Contact([
                    'name' => 'Jean',
                ]),
            ]),
            $doc3 = new Document([
                'id' => 3,
                'customerId' => 2,
                'uploaderType' => 'user',
                'uploaderId' => 13,
                'contact' => new Contact([
                    'name' => 'Michel',
                ]),
            ]),
        ]);

        $records = User::repository()->builder()->as(NameAndCustomerRecord::class)->all();

        $this->assertEquals([
            new NameAndCustomerRecord('John', new CustomerRecord(1, 'Customer 1', true)),
            new NameAndCustomerRecord('Mark', new CustomerRecord(2, 'Customer 2', true)),
        ], $records);

        $records = User::repository()->builder()->as(NameAndDocumentsRecord::class)->all();

        $this->assertEquals([
            new NameAndDocumentsRecord('John', [new DocumentRecord(1, 'John'), new DocumentRecord(2, 'Jean')]),
            new NameAndDocumentsRecord('Mark', [new DocumentRecord(3, 'Michel')]),
        ], $records);
    }

    public function test_record_with_by()
    {
        $this->declareUsersForRecord();

        $records = User::repository()->builder()->by('name')->as(IdNameRecord::class)->all();

        $this->assertEquals([
            'John' => new IdNameRecord('12', 'John'),
            'Mark' => new IdNameRecord('13', 'Mark'),
            'Paul' => new IdNameRecord('14', 'Paul'),
        ], $records);
    }

    public function test_record_with_by_combine()
    {
        $this->declareUsersForRecord();

        $records = User::repository()->builder()->by('name', true)->as(IdNameRecord::class)->all();

        $this->assertEquals([
            'John' => [new IdNameRecord('12', 'John')],
            'Mark' => [new IdNameRecord('13', 'Mark')],
            'Paul' => [new IdNameRecord('14', 'Paul')],
        ], $records);
    }

    public function test_record_with_by_on_attribute_not_declared_on_record()
    {
        $this->declareUsersForRecord();

        $query = User::repository()->builder()->by('id')->as(NameOnlyRecord::class);

        $this->assertEquals('SELECT t0.name_, t0.id_ FROM user_ t0', $query->toSql());
        $this->assertEquals([
            12 => new NameOnlyRecord('John'),
            13 => new NameOnlyRecord('Mark'),
            14 => new NameOnlyRecord('Paul'),
        ], $query->all());
    }

    public function test_record_with_by_on_embedded_attribute_not_declared_on_record()
    {
        $this->declareUsersForRecord();

        $query = User::repository()->builder()->by('customer.id', true)->as(NameOnlyRecord::class);

        $this->assertEquals('SELECT t0.name_, t0.customer_id FROM user_ t0', $query->toSql());
        $this->assertEquals([
            1 => [new NameOnlyRecord('John'), new NameOnlyRecord('Paul')],
            2 => [new NameOnlyRecord('Mark')],
        ], $query->all());
    }

    public function test_record_with_by_on_renamed_property()
    {
        $this->declareUsersForRecord();

        $records = User::repository()->builder()->by('name')->as(RecordWithRenamedProperty::class)->all();

        $this->assertEquals([
            'John' => new RecordWithRenamedProperty('John'),
            'Mark' => new RecordWithRenamedProperty('Mark'),
            'Paul' => new RecordWithRenamedProperty('Paul'),
        ], $records);
    }

    public function test_record_with_by_and_relation()
    {
        $this->declareUsersForRecord();

        $records = User::repository()->builder()->by('id')->as(NameAndCustomerRecord::class)->all();

        $this->assertEquals([
            12 => new NameAndCustomerRecord('John', new CustomerRecord(1, 'Customer 1', true)),
            13 => new NameAndCustomerRecord('Mark', new CustomerRecord(2, 'Customer 2', true)),
            14 => new NameAndCustomerRecord('Paul', new CustomerRecord(1, 'Customer 1', true)),
        ], $records);
    }

    public function test_record_with_with_should_raise_error()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('with() method is not available with record. Use #[LoadRelation] attribute instead.');

        $this->declareUsersForRecord();

        User::repository()->builder()->with('customer')->as(IdNameRecord::class)->all();
    }

    private function declareUsersForRecord(): void
    {
        $this->pack()->nonPersist([
            new Customer(['id' => 1, 'name' => 'Customer 1']),
            new Customer(['id' => 2, 'name' => 'Customer 2']),
            new User([
                'id' => 12,
                'name' => 'John',
                'roles' => ['2'],
                'customer' => new Customer(['id' => '1']),
            ]),
            new User([
                'id' => 13,
                'name' => 'Mark',
                'roles' => ['5'],
                'customer' => new Customer(['id' => '2']),
            ]),
            new User([
                'id' => 14,
                'name' => 'Paul',
                'roles' => ['5'],
                'customer' => new Customer(['id' => '1']),
            ]),
        ]);
    }

    public function test_with_custom_storage_type()
    {
        $this->pack()->declareEntity(EntityWithCustomStorageType::class);
        $entity = new EntityWithCustomStorageType([
            'id' => 42,
            'name' => 'foo',
            'value' => ['foo', 'bar', 'baz'],
            'enabled' => true,
        ]);
        $entity->insert();

        $this->assertEquals($entity, EntityWithCustomStorageType::refresh($entity));

        $this->assertEquals([[
            'id' => '42.0',
            'name' => 'foo',
            'value' => ',foo,bar,baz,',
            'enabled' => '1',
        ]], EntityWithCustomStorageType::repository()->builder()->execute()->all());
    }
}

class IdNameRecord
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
    ) {}
}

class NameOnlyRecord
{
    public function __construct(
        public readonly string $name,
    ) {}
}

class RecordWithRenamedProperty
{
    public function __construct(
        #[Field('name')]
        public readonly string $label,
    ) {}
}

class RecordWithTransformer
{
    public function __construct(
        public readonly string $id,
        #[Field(transformer: 'md5')]
        public readonly string $name,
    ) {}
}

class NameAndCustomer
{
    public function __construct(
        public readonly string $name,

        #[LoadRelation]
        public readonly Customer $customer,
    ) {}
}

class NameAndCustomerRecord
{
    public function __construct(
        public readonly string $name,

        #[LoadRelation(Customer::class)]
        public readonly CustomerRecord $customer,
    ) {}
}

final readonly class CustomerRecord
{
    public function __construct(
        public int $id,
        public string $name,
        #[Field(expression: new Attribute('parentId', '%s IS NULL'))]
        public bool $isParent,
    ) {}
}

class NameAndDocuments
{
    public function __construct(
        public readonly string $name,

        #[LoadRelation(Document::class)]
        public readonly array $documents,
    ) {}
}

class NameAndDocumentsRecord
{
    public function __construct(
        public readonly string $name,

        #[LoadRelation(Document::class, as: DocumentRecord::class)]
        public readonly array $documents,
    ) {}
}

final readonly class DocumentRecord
{
    public function __construct(
        public int $id,
        #[Field('contact.name')]
        public string $contact,
    ) {}
}
