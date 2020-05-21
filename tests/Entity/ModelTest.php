<?php

namespace Bdf\Prime\Entity;

use Bdf\Prime\Admin;
use Bdf\Prime\Customer;
use Bdf\Prime\Folder;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Right;
use Bdf\Prime\TestFile;
use Bdf\Prime\User;
use Bdf\Prime\UserCustomMetadata;
use Bdf\Prime\UserInheritedMetadata;
use Bdf\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ModelTest extends TestCase
{
    use PrimeTestCase;
    
    
    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([
            'Bdf\Prime\Admin',
            'Bdf\Prime\Customer',
            'Bdf\Prime\User',
        ]);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }
    
    /**
     *
     */
    public function test_basic()
    {
        $user = Admin::entity(['name' => 'test', 'roles' => []]);
        $user->insert();
        $this->assertEquals($user, Admin::get(1));
        
        $user->name = 'replaced';
        $user->update();
        $this->assertEquals($user, Admin::get(1));
        
        $user->delete();
        $this->assertEquals(null, Admin::get(1));
    }

    /**
     *
     */
    public function test_fromArray()
    {
        Prime::service()->setSerializer(SerializerBuilder::create()->build());
        $data = [
            'id'       => '911',
            'name'     => 'A web user',
            'customer' => [
                'id'   => '18',
                'name' => 'Dady',
            ],
        ];

        $webUser = UserInheritedMetadata::fromArray($data);

        $expected = new UserInheritedMetadata();
        $expected->id = $data['id'];
        $expected->name = $data['name'];
        $expected->customer = new Customer();
        $expected->customer->id = $data['customer']['id'];
        $expected->customer->name = $data['customer']['name'];

        $this->assertEquals($expected, $webUser);
    }


    /**
     *
     */
    public function test_import()
    {
        $data = [
            'id'       => '911',
            'name'     => 'A web user',
            'customer' => [
                'id'   => '18',
                'name' => 'Dady',
            ],
        ];

        $webUser = new UserInheritedMetadata();
        $webUser->import($data);

        $expected = new UserInheritedMetadata();
        $expected->id = $data['id'];
        $expected->name = $data['name'];
        $expected->customer = new Customer();
        $expected->customer->id = $data['customer']['id'];
        $expected->customer->name = $data['customer']['name'];

        $this->assertEquals($expected, $webUser);
    }

    /**
     *
     */
    public function test_toArray_inherited_metadata()
    {
        Prime::service()->setSerializer(SerializerBuilder::create()->build());
        $data = [
            'id'       => '911',
            'name'     => 'A web user',
            'customer' => [
                'id'   => '18',
                'name' => 'Dady',
            ],
            'rights'   => [
                [
                    'id'   => '1',
                    'name' => ':read',
                ],
                [
                    'id'   => '2',
                    'name' => ':write',
                ]
            ],
        ];

        $readRight = new Right();
        $readRight->id = $data['rights'][0]['id'];
        $readRight->name = $data['rights'][0]['name'];

        $writeRight = new Right();
        $writeRight->id = $data['rights'][1]['id'];
        $writeRight->name = $data['rights'][1]['name'];

        $webUser = new UserInheritedMetadata();
        $webUser->id = $data['id'];
        $webUser->name = $data['name'];
        $webUser->customer = new Customer();
        $webUser->customer->id = $data['customer']['id'];
        $webUser->customer->name = $data['customer']['name'];
        $webUser->rights = [$readRight, $writeRight];

        $this->assertEquals($data, $webUser->toArray());
    }

    /**
     *
     */
    public function test_toArray_selected()
    {
        Prime::service()->setSerializer(SerializerBuilder::create()->build());
        $data = [
            'id'       => '911',
            'name'     => 'A web user',
            'customer' => [
                'id'   => '18',
                'name' => 'Dady',
            ],
            'rights'   => [
                [
                    'id'   => '1',
                    'name' => ':read',
                ],
                [
                    'id'   => '2',
                    'name' => ':write',
                ]
            ],
        ];

        $readRight = new Right();
        $readRight->id = $data['rights'][0]['id'];
        $readRight->name = $data['rights'][0]['name'];

        $writeRight = new Right();
        $writeRight->id = $data['rights'][1]['id'];
        $writeRight->name = $data['rights'][1]['name'];

        $webUser = new UserInheritedMetadata();
        $webUser->id = $data['id'];
        $webUser->name = $data['name'];
        $webUser->customer = new Customer();
        $webUser->customer->id = $data['customer']['id'];
        $webUser->customer->name = $data['customer']['name'];
        $webUser->rights = [$readRight, $writeRight];

        $this->assertEquals([
            'id' => '911',
            'name' => 'A web user'
        ], $webUser->toArray(['include' => ['id', 'name']]));
    }

    /**
     *
     */
    public function test_export_all()
    {
        $data = [
            'id'       => '911',
            'name'     => 'A web user',
            'customer' => [
                'id'   => '18',
                'name' => 'Dady',
                'packs' => null,
                'documents' => null,
                'location' => null,
                'parentId' => null,
                'parent' => null
            ],
            'rights'   => [
                [
                    'id'   => '1',
                    'name' => ':read',
                ],
                [
                    'id'   => '2',
                    'name' => ':write',
                ]
            ],
        ];

        $readRight = new Right();
        $readRight->id = $data['rights'][0]['id'];
        $readRight->name = $data['rights'][0]['name'];

        $writeRight = new Right();
        $writeRight->id = $data['rights'][1]['id'];
        $writeRight->name = $data['rights'][1]['name'];

        $webUser = new UserInheritedMetadata();
        $webUser->id = $data['id'];
        $webUser->name = $data['name'];
        $webUser->customer = new Customer();
        $webUser->customer->id = $data['customer']['id'];
        $webUser->customer->name = $data['customer']['name'];
        $webUser->rights = [$readRight, $writeRight];


        $data['rights'] = [$readRight, $writeRight];
        $this->assertEquals($data, $webUser->export());
    }

    /**
     *
     */
    public function test_export_selected()
    {
        $data = [
            'id'       => '911',
            'name'     => 'A web user',
            'customer' => [
                'id'   => '18',
                'name' => 'Dady',
                'packs' => null,
                'documents' => null,
                'location' => null,
                'parentId' => null,
                'parent' => null
            ],
            'rights'   => [
                [
                    'id'   => '1',
                    'name' => ':read',
                ],
                [
                    'id'   => '2',
                    'name' => ':write',
                ]
            ],
        ];

        $readRight = new Right();
        $readRight->id = $data['rights'][0]['id'];
        $readRight->name = $data['rights'][0]['name'];

        $writeRight = new Right();
        $writeRight->id = $data['rights'][1]['id'];
        $writeRight->name = $data['rights'][1]['name'];

        $webUser = new UserInheritedMetadata();
        $webUser->id = $data['id'];
        $webUser->name = $data['name'];
        $webUser->customer = new Customer();
        $webUser->customer->id = $data['customer']['id'];
        $webUser->customer->name = $data['customer']['name'];
        $webUser->rights = [$readRight, $writeRight];


        $this->assertEquals([
            'id' => '911',
            'name' => 'A web user'
        ], $webUser->export(['id', 'name']));
    }

    /**
     *
     */
    public function test_toArray_custom_metadata()
    {
        Prime::service()->setSerializer(SerializerBuilder::create()->build());
        $data = [
            'id'       => '911',
            'name'     => 'A web user',
        ];

        $webUser = new UserCustomMetadata();
        $webUser->id = $data['id'];
        $webUser->name = $data['name'];
        $webUser->customer = new Customer();
        $webUser->customer->id = '18';
        $webUser->customer->name = 'Dady';

        $this->assertEquals($data, $webUser->toArray());
    }

    /**
     *
     */
    public function test_save()
    {
        $user = Admin::entity(['name' => 'test', 'roles' => []]);
        $user->save();
        $this->assertEquals($user, Admin::get(1));
        
        $user->name = 'replaced';
        $user->save();
        $this->assertEquals($user, Admin::get(1));
    }

    /**
     *
     */
    public function test_replace()
    {
        $user = Admin::entity(['name' => 'test', 'roles' => []]);
        $user->replace();
        $this->assertEquals($user, Admin::get(1));
        
        $user->name = 'replaced';
        $user->replace();
        $this->assertEquals($user, Admin::get(1));
    }

    /**
     *
     */
    public function test_duplicate()
    {
        $user = Admin::entity(['name' => 'test', 'roles' => []]);
        $user->save();
        
        $user->duplicate();
        $this->assertEquals(2, $user->id);
    }

    /**
     *
     */
    public function test_load_event()
    {
        $event = 0;
        $user = Admin::entity(['name' => 'test', 'roles' => []]);
        $user->save();

        Admin::loaded(function() use(&$event) {
            $event++;
        });
        Admin::get(1);
        
        $this->assertEquals(1, $event);
    }

    /**
     *
     */
    public function test_save_event()
    {
        $event = 0;
        $user = Admin::entity(['name' => 'test', 'roles' => []]);
        $user->saving(function() use(&$event) {
            $event++;
        });
        $user->saved(function() use(&$event) {
            $event++;
        });
        $user->save();
        $this->assertEquals(2, $event);
    }
    
    /**
     *
     */
    public function test_insert_event()
    {
        $event = 0;
        $user = Admin::entity(['name' => 'test', 'roles' => []]);
        $user->inserting(function() use(&$event) {
            $event++;
        });
        $user->inserted(function() use(&$event) {
            $event++;
        });
        $user->insert();
        $this->assertEquals(2, $event);
    }

    /**
     *
     */
    public function test_update_event()
    {
        $event = 0;
        $user = Admin::entity(['name' => 'test', 'roles' => []]);
        $user->updating(function() use(&$event) {
            $event++;
        });
        $user->updated(function() use(&$event) {
            $event++;
        });
        $user->update();
        $this->assertEquals(2, $event);
    }

    /**
     *
     */
    public function test_delete_event()
    {
        $event = 0;
        $user = Admin::entity(['name' => 'test', 'roles' => []]);
        $user->deleting(function() use(&$event) {
            $event++;
        });
        $user->deleted(function() use(&$event) {
            $event++;
        });
        $user->delete();
        $this->assertEquals(2, $event);
    }

    /**
     *
     */
    public function test_load_relation()
    {
        $customer = Customer::entity([
            'id' => 1,
            'name' => 'customer',
            'roles' => []
        ]);
        $customer->insert();
        
        User::entity([
            'id' => 1,
            'name' => 'user',
            'roles' => [],
            'customer' => $customer
        ])->insert();
        
        
        $user = User::get(1);
        $this->assertEquals(null, $user->customer->name);
        
        $user->load('customer');
        $this->assertEquals('customer', $user->customer->name);
    }

    /**
     *
     */
    public function test_load_already_load_should_not_reload()
    {
        $customer = Customer::entity([
            'id' => 1,
            'name' => 'customer',
            'roles' => []
        ]);
        $customer->insert();

        User::entity([
            'id' => 1,
            'name' => 'user',
            'roles' => [],
            'customer' => $customer
        ])->insert();


        $user = User::with('customer')->get(1);

        $loadedCustomer = $user->customer;
        $user->load('customer');

        $this->assertSame($loadedCustomer, $user->customer);
    }

    /**
     *
     */
    public function test_reload()
    {
        $customer = Customer::entity([
            'id' => 1,
            'name' => 'customer',
            'roles' => []
        ]);
        $customer->insert();

        User::entity([
            'id' => 1,
            'name' => 'user',
            'roles' => [],
            'customer' => $customer
        ])->insert();


        $user = User::get(1);

        $user->reload('customer');
        $this->assertEquals('customer', $user->customer->name);
        $loadedCustomer = $user->customer;

        $customer->name = 'new name';
        $customer->save();

        $user->reload('customer');
        $this->assertNotSame($loadedCustomer, $user->customer);
        $this->assertEquals('new name', $user->customer->name);
    }
    
    /**
     *
     */
    public function test_on_relation()
    {
        $customer = Customer::entity([
            'id' => 1,
            'name' => 'customer',
            'roles' => []
        ]);
        $user = User::entity([
            'id' => 1,
            'name' => 'user',
            'roles' => [],
            'customer' => $customer
        ]);
        $customer->insert();
        $user->insert();
        
        $customer = $user->relation('customer')->first();
        $this->assertEquals('customer', $customer->name);
    }

    /**
     * 
     */
    /*public function test_saveAll()
    {
        $user = User::entity([
            'id' => 1,
            'name' => 'user',
            'roles' => [],
            'customer' => Customer::entity([
                'id' => 1,
                'name' => 'customer',
                'roles' => []
            ])
        ]);
        
        $user->saveAll('customer');

        $this->assertTrue(Prime::exists($user));
        $this->assertTrue(Prime::exists($user->customer));
    }*/

    /**
     *
     */
    public function test_serialization()
    {
        $user = User::entity([
            'id' => 1,
            'name' => 'user',
            'roles' => [1],
            'customer' => Customer::entity([
                'id' => 1,
                'name' => 'customer',
            ])
        ]);

        $json = Prime::service()->serializer()->toJson($user);

        $this->assertEquals('{"id":1,"name":"user","roles":[1],"customer":{"id":1,"name":"customer"}}', $json);
    }

    /**
     *
     */
    public function test_deserialization()
    {
        $user = User::entity([
            'id' => 1,
            'name' => 'user',
            'roles' => [1],
            'customer' => Customer::entity([
                'id' => 1,
                'name' => 'customer',
            ])
        ]);

        $json = '{"id":1,"name":"user","roles":[1],"customer":{"id":1,"name":"customer"}}';
        $result = Prime::service()->serializer()->fromJson($json, User::class);

        $this->assertEquals($user, $result);
    }

    /**
     *
     */
    public function test_toArray_with_collection()
    {
        $folder = new Folder([
            'id'    => 123,
            'name'  => 'folder',
            'files' => [
                [
                    'id'       => 1,
                    'name'     => 'file1',
                    'folderId' => 123,
                ],
                [
                    'id'       => 2,
                    'name'     => 'file2',
                    'folderId' => 123,
                ],
            ],
        ]);

        $this->assertEquals([
            'id'    => 123,
            'name'  => 'folder',
            'files' => [
                [
                    'id'       => 1,
                    'name'     => 'file1',
                    'folderId' => 123,
                    'owner'    => [],
                    'group'    => [],
                ],
                [
                    'id'       => 2,
                    'name'     => 'file2',
                    'folderId' => 123,
                    'owner'    => [],
                    'group'    => [],
                ],
            ],
        ], $folder->toArray());
    }

    /**
     *
     */
    public function test_fromArray_with_collection()
    {
        $folder = Folder::fromArray([
            'id'    => 123,
            'name'  => 'folder',
            'files' => [
                [
                    'id'       => 1,
                    'name'     => 'file1',
                    'folderId' => 123,
                    'owner'    => [],
                    'group'    => [],
                ],
                [
                    'id'       => 2,
                    'name'     => 'file2',
                    'folderId' => 123,
                    'owner'    => [],
                    'group'    => [],
                ],
            ],
        ]);

        $this->assertSame(TestFile::repository(), $folder->files->repository());
        $this->assertCount(2, $folder->files);
        $this->assertEquals('file1', $folder->files[0]->name);
        $this->assertEquals('file2', $folder->files[1]->name);

        $this->assertEquals(new Folder([
            'id'    => 123,
            'name'  => 'folder',
            'files' => [
                [
                    'id'       => 1,
                    'name'     => 'file1',
                    'folderId' => 123,
                ],
                [
                    'id'       => 2,
                    'name'     => 'file2',
                    'folderId' => 123,
                ],
            ],
        ]), $folder);
    }

    /**
     *
     */
    public function test_toArray_with_embedded()
    {
        $place = new Place();
        $place->id = '1';
        $place->bag = new Bag();
        $place->bag->foo = new Foo('foo');
        $place->bag->bar = new Bar('bar');

        $this->assertEquals(
            [
                'id' => '1',
                'bag' => [
                    'foo' => [
                        'name' => 'foo'
                    ],
                    'bar' => [
                        'name' => 'bar'
                    ],
                ]
            ],
            $place->toArray()
        );
    }

    /**
     *
     */
    public function test_destructor_will_free_relation_info()
    {
        $customer = new Customer([
            'id' => 1,
            'name' => 'customer',
            'roles' => []
        ]);
        $customer->insert();

        (new User([
            'id' => 1,
            'name' => 'user',
            'roles' => [],
            'customer' => $customer
        ]))->insert();


        $user = User::get(1);

        $user->load('customer');
        $this->assertTrue($user->relation('customer')->isLoaded());

        // destroy the user
        unset($user);

        // Recreate a new user : the same object id will be generated
        $user = new User();
        $this->assertFalse($user->relation('customer')->isLoaded());
    }
}
