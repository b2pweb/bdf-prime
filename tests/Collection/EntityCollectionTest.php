<?php

namespace Bdf\Prime\Collection;

use Bdf\Prime\Admin;
use Bdf\Prime\CompositePkEntity;
use Bdf\Prime\Customer;
use Bdf\Prime\CustomerPack;
use Bdf\Prime\Document;
use Bdf\Prime\Faction;
use Bdf\Prime\Pack;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\TestFile;
use Bdf\Prime\Folder;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Test\RepositoryAssertion;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 *
 */
class EntityCollectionTest extends TestCase
{
    use PrimeTestCase;
    use RepositoryAssertion;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->configurePrime();

        if ($this->pack()->isInitialized()) {
            $this->pack()->destroy();
        }

        $this->pack()
            ->declareEntity([
                Folder::class, TestFile::class, Faction::class
            ])
            ->persist([
            'customer' => new Customer([
                'id'            => '123',
                'name'          => 'Customer',
            ]),
            'customer2' => new Customer([
                'id'            => '456',
                'name'          => 'Customer 2',
            ]),
            'customer3' => new Customer([
                'id'            => '789',
                'name'          => 'Customer 3',
            ]),

            'pack1' => new Pack(['id' => 1, 'label' => 'pack 1']),
            'pack2' => new Pack(['id' => 2, 'label' => 'pack 2']),
            'pack3' => new Pack(['id' => 3, 'label' => 'pack 3']),

            new CustomerPack(['customerId' => '123', 'packId' => 1]),
            new CustomerPack(['customerId' => '123', 'packId' => 2]),
            new CustomerPack(['customerId' => '456', 'packId' => 3]),
            new CustomerPack(['customerId' => '789', 'packId' => 2]),

            'admin' => new Admin([
                'id'            => '10',
                'name'          => 'Admin User',
                'roles'         => ['1'],
            ]),

            'user' => new User([
                'id'            => '321',
                'name'          => 'Web User',
                'roles'         => ['1'],
                'customer'      => new Customer([
                    'id'            => '123',
                ]),
            ]),
            'user2' => new User([
                'id'            => '741',
                'name'          => 'User 2',
                'roles'         => ['1'],
                'customer'      => new Customer([
                    'id'            => '456',
                ]),
            ]),
            'user3' => new User([
                'id'            => '852',
                'name'          => 'User 3',
                'roles'         => ['1'],
                'customer'      => new Customer([
                    'id'            => '789',
                ]),
            ]),

            'document-admin' => new Document([
                'id'             => '1',
                'customerId'     => '123',
                'uploaderType'   => 'admin',
                'uploaderId'     => '10',
            ]),
            'document-user' => new Document([
                'id'             => '2',
                'customerId'     => '123',
                'uploaderType'   => 'user',
                'uploaderId'     => '321',
            ]),
        ])->initialize();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->primeStop();
        Customer::repository()->detachAll();
    }

    /**
     *
     */
    public function test_load()
    {
        $collection = new EntityCollection(
            Document::repository(),
            Document::all()
        );

        $collection->load('uploader');

        $this->assertSameEntity($this->pack()->get('admin'), $collection->get(0)->uploader);
        $this->assertSameEntity($this->pack()->get('user'),  $collection->get(1)->uploader);
    }

    /**
     *
     */
    public function test_refresh()
    {
        $collection = new EntityCollection(
            Customer::repository(),
            Customer::all()
        );

        $this->pack()->get('customer')->name = 'new name';
        $this->pack()->get('customer')->save();

        $collection->refresh();

        $this->assertSameEntity(Customer::get('123'), $collection->get(0));
        $this->assertSameEntity(Customer::get('456'), $collection->get(1));
        $this->assertSameEntity(Customer::get('789'), $collection->get(2));
    }

    /**
     *
     */
    public function test_link()
    {
        $collection = new EntityCollection(
            Customer::repository(),
            Customer::all()
        );

        $users = $collection->link('users')->all();

        $this->assertEntities([
            $this->pack()->get('user'),
            $this->pack()->get('user2'),
            $this->pack()->get('user3'),
        ], $users);

        $this->assertEntities([
            $this->pack()->get('user2'),
            $this->pack()->get('user3'),
        ], $collection->link('users')->where('name', ':like', 'User%')->all());
    }

    /**
     *
     */
    public function test_link_belongsToMany()
    {
        $collection = new EntityCollection(
            Customer::repository(),
            Customer::all()
        );

        $this->assertEntities([
            $this->pack()->get('pack1'),
            $this->pack()->get('pack2'),
            $this->pack()->get('pack3'),
            $this->pack()->get('pack2'),
        ], $collection->link('packs')->all());

        $this->assertEntities([
            $this->pack()->get('pack1'),
            $this->pack()->get('pack2'),
            $this->pack()->get('pack3'),
        ], $collection->link('packs')->distinct()->all());

        $this->assertEntities([
            $this->pack()->get('pack2'),
            $this->pack()->get('pack3'),
        ], $collection->link('packs')->where('id', '>=', 2)->distinct()->all());
    }

    /**
     *
     */
    public function test_delete_simple()
    {
        $collection = new EntityCollection(
            Customer::repository(),
            Customer::all()
        );

        $collection->delete();

        $this->assertEmpty(Customer::all());
    }

    /**
     *
     */
    public function test_delete_blocking_listener()
    {
        $collection = new EntityCollection(
            Customer::repository(),
            Customer::all()
        );

        $entities = [];

        Customer::repository()->deleting(function ($entity, $repository) use (&$count, &$entities) {
            $entities[] = $entity;

            $this->assertSame(Customer::repository(), $repository);

            return false;
        });

        $collection->delete();

        $this->assertCount(3, Customer::all());
        $this->assertSameEntities($entities, Customer::all());
    }

    /**
     *
     */
    public function test_delete_filter_listener()
    {
        $collection = new EntityCollection(
            Customer::repository(),
            Customer::all()
        );

        Customer::repository()->deleting(function ($entity) {
            return $entity->id != 123;
        });

        $collection->delete();

        $this->assertCount(1, Customer::all());
        $this->assertEntity($this->pack()->get('customer'), Customer::first());
    }

    /**
     *
     */
    public function test_import_array()
    {
        $collection = new EntityCollection(Customer::repository());

        $collection->import([
            [
                'id'   => '111',
                'name' => 'Customer 111'
            ],
            [
                'id'   => '222',
                'name' => 'Customer 222'
            ],
            [
                'id'   => '333',
                'name' => 'Customer 333'
            ]
        ]);

        $this->assertEquals('Customer 111', $collection->get(0)->name);
        $this->assertEquals('Customer 222', $collection->get(1)->name);
        $this->assertEquals('Customer 333', $collection->get(2)->name);
    }

    /**
     *
     */
    public function test_import_entity()
    {
        $collection = new EntityCollection(Customer::repository());

        $collection->import([
            new Customer([
                'id'   => '111',
                'name' => 'Customer 111'
            ]),
            new Customer([
                'id'   => '222',
                'name' => 'Customer 222'
            ]),
            new Customer([
                'id'   => '333',
                'name' => 'Customer 333'
            ])
        ]);

        $this->assertEquals('Customer 111', $collection->get(0)->name);
        $this->assertEquals('Customer 222', $collection->get(1)->name);
        $this->assertEquals('Customer 333', $collection->get(2)->name);
    }

    /**
     *
     */
    public function test_export()
    {
        $collection = new EntityCollection(
            Customer::repository(),
            Customer::all()
        );

        $this->assertEquals([
            [
                'id' => '123',
                'name' => 'Customer',
                'packs' => null,
                'documents' => null,
                'location' => null,
                'parentId' => null,
                'parent' => null,
            ],
            [
                'id' => '456',
                'name' => 'Customer 2',
                'packs' => null,
                'documents' => null,
                'location' => null,
                'parentId' => null,
                'parent' => null,
            ],
            [
                'id' => '789',
                'name' => 'Customer 3',
                'packs' => null,
                'documents' => null,
                'location' => null,
                'parentId' => null,
                'parent' => null,
            ]
        ], $collection->export());
    }

    /**
     *
     */
    public function test_export_selected_attributes()
    {
        $collection = new EntityCollection(
            Customer::repository(),
            Customer::all()
        );

        $this->assertEquals(
            [['name' => 'Customer'], ['name' => 'Customer 2'], ['name' => 'Customer 3']],
            $collection->export(['name'])
        );
    }

    /**
     *
     */
    public function test_save()
    {
        $collection = new EntityCollection(
            Customer::repository(),
            [
                new Customer([
                    'name' => 'Customer 111'
                ]),
                new Customer([
                    'name' => 'Customer 222'
                ]),
                new Customer([
                    'name' => 'Customer 333'
                ])
            ]
        );

        $collection->save();

        $this->assertEntity($collection->get(0), Customer::get(1));
        $this->assertEntity($collection->get(1), Customer::get(2));
        $this->assertEntity($collection->get(2), Customer::get(3));
    }

    /**
     *
     */
    public function test_query()
    {
        $collection = new EntityCollection(
            Customer::repository(),
            Customer::all()
        );

        // Not in collection
        (new Customer(['name' => 'Customer 111', 'id' => '111']))->insert();

        $query = $collection->query();

        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertSame($collection->repository(), $query->repository());

        $this->assertEntities(
            [$this->pack()->get('customer')],
            $query->where('id', ':like', '1%')->all()
        );
    }

    /**
     *
     */
    public function test_composite_pk()
    {
        $this->pack()->nonPersist([
            $c1 = new CompositePkEntity(['key1' => 'a', 'key2' => 'b']),
            $c2 = new CompositePkEntity(['key1' => 'b', 'key2' => 'c']),
            $c3 = new CompositePkEntity(['key1' => 'a', 'key2' => 'c']),
        ]);

        $collection = new EntityCollection(
            CompositePkEntity::repository(),
            [$c1, $c2]
        );

        $query = $collection->query();

        $this->assertInstanceOf(QueryInterface::class, $query);
        $this->assertSame($collection->repository(), $query->repository());
        $this->assertEntities([$c1, $c2], $query->all());
    }

    /**
     *
     */
    public function test_update()
    {
        $collection = new EntityCollection(
            Customer::repository(),
            Customer::all()
        );

        (new Customer(['name' => 'Boss', 'id' => '42']))->insert();

        $collection->update([
            'parentId' => '42'
        ]);

        foreach ($collection as $customer) {
            $this->assertEquals('42', $customer->parentId);
            $this->assertEquals('42', Customer::get($customer->id)->parentId);
        }
    }

    /**
     *
     */
    public function test_from_query()
    {
        $collection = Customer::wrapAs('collection')->all();

        $this->assertInstanceOf(EntityCollection::class, $collection);
        $this->assertEntities([
            $this->pack()->get('customer'),
            $this->pack()->get('customer2'),
            $this->pack()->get('customer3'),
        ], $collection->all());
    }

    /**
     * @dataProvider delegationMethods
     */
    public function test_delegation($method, array $arguments = [], $retType)
    {
        $retVal = new stdClass();
        $storage = $this->createMock(CollectionInterface::class);
        $storage->expects($this->once())
            ->method($method)
            ->with(...$arguments)
            ->willReturn($retVal)
        ;

        $collection = new EntityCollection(
            $this->createMock(RepositoryInterface::class),
            $storage
        );

        $return = $collection->{$method}(...$arguments);

        switch ($retType) {
            case 'this':
                $this->assertSame($collection, $return);
                break;
            case 'rtrn':
                $this->assertSame($retVal, $return);
                break;
            case 'self':
                $this->assertInstanceOf(EntityCollection::class, $return);
                $this->assertSame($collection->repository(), $return->repository());
                break;
        }
    }

    /**
     * @return array
     */
    public function delegationMethods()
    {
        return [
            ['pushAll',      [[]],                                   'this'],
            ['push',         [new stdClass()],                       'this'],
            ['put',          ['a', new stdClass()],                  'this'],
            ['all',          [],                                     'rtrn'],
            ['get',          ['a'],                                  'rtrn'],
            ['has',          ['a'],                                  'rtrn'],
            ['remove',       ['a'],                                  'this'],
            ['clear',        [],                                     'this'],
            ['keys',         [],                                     'rtrn'],
            ['isEmpty',      [],                                     'rtrn'],
            ['map',          [function() {}],                        'self'],
            ['filter',       [],                                     'self'],
            ['groupBy',      ['a', CollectionInterface::GROUPBY],    'self'],
            ['contains',     [new stdClass()],                       'rtrn'],
            ['indexOf',      [new stdClass()],                       'rtrn'],
            ['merge',        [[]],                                   'self'],
            ['sort',         [],                                     'self'],
            ['toArray',      [],                                     'rtrn'],
            ['offsetExists', ['a'],                                  'rtrn'],
            ['offsetGet',    ['a'],                                  'rtrn'],
            ['offsetSet',    ['a', new stdClass()],                  'null'],
            ['offsetUnset',  ['a'],                                  'null'],
            ['count',        [],                                     'rtrn'],
        ];
    }

    /**
     *
     */
    public function test_saveAll()
    {
        $collection = new EntityCollection(User::repository(), [
            new User([
                'id'            => '321',
                'name'          => 'Web User - up',
                'roles'         => ['1'],
                'customer'      => new Customer([
                    'id'            => '123',
                ]),
                'faction' => new Faction([
                    'domain' => 'user',
                    'name' => 'f1'
                ])
            ]),
            new User([
                'id'            => '741',
                'name'          => 'User 2 - up',
                'roles'         => ['1'],
                'customer'      => new Customer([
                    'id'            => '456',
                ]),
                'faction' => new Faction([
                    'domain' => 'user',
                    'name' => 'f2'
                ])
            ]),
        ]);

        $this->assertEquals(6, $collection->saveAll('faction'));

        $this->assertEquals($collection[0]->faction, Faction::get(1));
        $this->assertEquals($collection[1]->faction, Faction::get(2));

        $this->assertEquals($collection[0]->name, User::get(321)->name);
        $this->assertEquals($collection[1]->name, User::get(741)->name);
    }

    /**
     *
     */
    public function test_deleteAll()
    {
        $collection = new EntityCollection(User::repository(), [
            $this->pack()->get('user')->load('documents'),
            $this->pack()->get('user2')->load('documents'),
        ]);

        $this->assertEquals(3, $collection->deleteAll('documents'));

        $collection->refresh();
        $this->assertTrue($collection->isEmpty());

        $this->assertNull(Document::get(2));
    }

    /**
     *
     */
    public function test_toArray()
    {
        $collection = new EntityCollection(User::repository(), User::all());

        $this->assertEquals([
            [
                'id'       => '321',
                'name'     => 'Web User',
                'roles'    => ['1'],
                'customer' => ['id' => '123'],
                'faction'  => ['enabled' => true]
            ],
            [
                'id'       => '741',
                'name'     => 'User 2',
                'roles'    => ['1'],
                'customer' => ['id' => '456'],
                'faction'  => ['enabled' => true]
            ],
            [
                'id'       => '852',
                'name'     => 'User 3',
                'roles'    => ['1'],
                'customer' => ['id' => '789'],
                'faction'  => ['enabled' => true]
            ]
        ], $collection->toArray());
    }
}
