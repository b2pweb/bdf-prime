<?php

namespace Bdf\Prime\Serializer;

use Bdf\Prime\Collection\ArrayCollection;
use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestEntity;
use Bdf\Serializer\Metadata\Driver\StaticMethodDriver;
use Bdf\Serializer\Metadata\MetadataFactory;
use Bdf\Serializer\Normalizer\PropertyNormalizer;
use Bdf\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../_files/serializable_entities.php';

/**
 *
 */
class PrimeCollectionNormalizerTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->configurePrime();
    }

    /**
     *
     */
    public function test_normalizer_instance()
    {
        $serializer = $this->getSerializer();

        $object = new ArrayCollection();

        $this->assertInstanceOf(PrimeCollectionNormalizer::class, $serializer->getLoader()->getNormalizer($object));
    }

    /**
     *
     */
    public function test_normalization()
    {
        $data = [
            'id' => 1
        ];

        $this->assertEquals(
            [$data],
            $this->getSerializer()->toArray(new ArrayCollection([$data]))
        );
    }

    /**
     *
     */
    public function test_normalization_with_entity()
    {
        $this->assertEquals(
            [
                [
                    'id' => 1,
                    'testname' => 'user1',
                    'customer' => [
                        'id' => 666,
                        'name' => 'customer'
                    ]
                ],
                [
                    'id' => 2,
                    'testname' => 'user2',
                    'customer' => [
                        'id' => 666,
                        'name' => 'customer'
                    ],
                ]
            ],
            $this->getSerializer()->toArray(
                new ArrayCollection([
                    new UserWithCustomer(1, 'user1', new Customer(666, 'customer')),
                    new UserWithCustomer(2, 'user2', new Customer(666, 'customer')),
                ])
            )
        );
    }

    /**
     *
     */
    public function test_normalization_with_entity_and_include_type()
    {
        $this->assertEquals(
            [
                '@type' => ArrayCollection::class,
                'data' => [
                    [
                        '@type' => UserWithCustomer::class,
                        'data' => [
                            'id' => 1,
                            'testname' => 'user1',
                            'customer' => [
                                '@type' => Customer::class,
                                'data' => [
                                    'id' => 666,
                                    'name' => 'customer'
                                ]
                            ]
                        ],
                    ],
                    [
                        '@type' => UserWithCustomer::class,
                        'data' => [
                            'id' => 2,
                            'testname' => 'user2',
                            'customer' => [
                                '@type' => Customer::class,
                                'data' => [
                                    'id' => 666,
                                    'name' => 'customer'
                                ]
                            ],
                        ]
                    ]
                ]
            ],
            $this->getSerializer()->toArray(
                new ArrayCollection([
                    new UserWithCustomer(1, 'user1', new Customer(666, 'customer')),
                    new UserWithCustomer(2, 'user2', new Customer(666, 'customer')),
                ]),
                ['include_type' => true]
            )
        );
    }

    /**
     *
     */
    public function test_normalization_with_groupBy()
    {
        $this->assertEquals(
            [
                'user1' => [
                    'id' => 1,
                    'testname' => 'user1',
                    'customer' => [
                        'id' => 666,
                        'name' => 'customer1'
                    ]
                ],
                'user2' => [
                    'id' => 2,
                    'testname' => 'user2',
                    'customer' => [
                        'id' => 123,
                        'name' => 'customer2'
                    ]
                ]
            ],
            $this->getSerializer()->toArray(
                new ArrayCollection([
                    'user1' => new UserWithCustomer(1, 'user1', new Customer(666, 'customer1')),
                    'user2' => new UserWithCustomer(2, 'user2', new Customer(123, 'customer2')),
                ])
            )
        );
    }

    /**
     *
     */
    public function test_normalization_with_groupBy_combine()
    {
        $this->assertEquals(
            [
                'user1' => [
                    [
                        'id' => 1,
                        'testname' => 'user1',
                        'customer' => [
                            'id' => 666,
                            'name' => 'customer1'
                        ]
                    ],
                    [
                        'id' => 2,
                        'testname' => 'user1',
                        'customer' => [
                            'id' => 123,
                            'name' => 'customer2'
                        ]
                    ]
                ],
                'user2' => [
                    [
                        'id' => 3,
                        'testname' => 'user2',
                        'customer' => [
                            'id' => 666,
                            'name' => 'customer1'
                        ]
                    ],
                ]
            ],
            $this->getSerializer()->toArray(
                new ArrayCollection([
                    'user1' => [
                        new UserWithCustomer(1, 'user1', new Customer(666, 'customer1')),
                        new UserWithCustomer(2, 'user1', new Customer(123, 'customer2')),
                    ],
                    'user2' => [
                        new UserWithCustomer(3, 'user2', new Customer(666, 'customer1')),
                    ]
                ])
            )
        );
    }

    /**
     *
     */
    public function test_normalization_with_groupBy_preserve()
    {
        $this->assertEquals(
            [
                'user1' => [
                    'key1' => [
                        'id' => 1,
                        'testname' => 'user1',
                        'customer' => [
                            'id' => 666,
                            'name' => 'customer1'
                        ]
                    ],
                    'key2' => [
                        'id' => 2,
                        'testname' => 'user1',
                        'customer' => [
                            'id' => 123,
                            'name' => 'customer2'
                        ]
                    ]
                ],
                'user2' => [
                    'key1' => [
                        'id' => 3,
                        'testname' => 'user2',
                        'customer' => [
                            'id' => 666,
                            'name' => 'customer1'
                        ]
                    ],
                ]
            ],
            $this->getSerializer()->toArray(new ArrayCollection([
                    'user1' => [
                        'key1' => new UserWithCustomer(1, 'user1', new Customer(666, 'customer1')),
                        'key2' => new UserWithCustomer(2, 'user1', new Customer(123, 'customer2')),
                    ],
                    'user2' => [
                        'key1' => new UserWithCustomer(3, 'user2', new Customer(666, 'customer1')),
                    ]
            ]))
        );
    }

    /**
     *
     */
    public function test_clone()
    {
        $serializer = $this->getSerializer();

        $expected = new ArrayCollection([
            new UserWithCustomer(1, 'user1', new Customer(666, 'customer')),
            new UserWithCustomer(2, 'user2', new Customer(666, 'customer')),
        ]);

        $this->assertEquals(
            $expected,
            $serializer->fromArray(
                $serializer->toArray($expected, ['include_type' => true]),
                ArrayCollection::class
            )
        );
    }

    /**
     *
     */
    public function test_denormalization()
    {
        $data = [
            [
                'id' => 1,
                'name' => 'name1'
            ],
            [
                'id' => 2,
                'name' => 'name2'
            ]
        ];

        $this->assertEquals(
            new ArrayCollection($data),
            $this->getSerializer()->fromArray($data, ArrayCollection::class)
        );
    }

    /**
     *
     */
    public function test_denormalization_with_entity()
    {
        $data = [
            [
                '@type' => UserWithCustomer::class,
                'data' => [
                    'id' => 1,
                    'testname' => 'user1',
                    'customer' => [
                        'id' => 666,
                        'name' => 'customer',
                        'email' => 'customer@email.com'
                    ],
                ]
            ],
            [
                '@type' => UserWithCustomer::class,
                'data' => [
                    'id' => 2,
                    'testname' => 'user2',
                    'customer' => [
                        'id' => 666,
                        'name' => 'customer',
                        'email' => 'customer@email.com'
                    ],
                ]
            ]
        ];

        $this->assertEquals(
            new ArrayCollection([
                new UserWithCustomer(1, 'user1', new Customer(666, 'customer')),
                new UserWithCustomer(2, 'user2', new Customer(666, 'customer')),
            ]),
            $this->getSerializer()->fromArray($data, ArrayCollection::class)
        );
    }

    /**
     *
     */
    public function test_denormalization_with_entity_and_no_type_tag()
    {
        $data = [
            [
                'id' => 1,
                'name' => 'user1',
                'customer' => [
                    'id' => 666,
                    'name' => 'customer',
                    'email' => 'customer@email.com'
                ]
            ],
            [
                'id' => 2,
                'name' => 'user2',
                'customer' => [
                    'id' => 666,
                    'name' => 'customer',
                    'email' => 'customer@email.com'
                ]
            ]
        ];

        $this->assertEquals(
            new ArrayCollection($data),
            $this->getSerializer()->fromArray($data, ArrayCollection::class)
        );
    }

    /**
     *
     */
    public function test_denormalization_with_entity_with_parametrized_type()
    {
        $data = [
            [
                'id' => 1,
                'name' => 'user1',
                'customer' => [
                    'id' => 666,
                    'name' => 'customer',
                    'email' => 'customer@email.com'
                ]
            ],
            [
                'id' => 2,
                'name' => 'user2',
                'customer' => [
                    'id' => 666,
                    'name' => 'customer',
                    'email' => 'customer@email.com'
                ]
            ]
        ];

        $this->assertEquals(
            new ArrayCollection([
                new UserWithCustomer(1, 'user1', new Customer(666, 'customer')),
                new UserWithCustomer(2, 'user2', new Customer(666, 'customer')),
            ]),
            $this->getSerializer()->fromArray($data, ArrayCollection::class.'<'.UserWithCustomer::class.'>')
        );
    }

    /**
     *
     */
    public function test_denormalization_with_groupBy()
    {
        $data = [
            'user1' => [
                '@type' => UserWithCustomer::class,
                'data' => [
                    'id' => 1,
                    'testname' => 'user1',
                    'customer' => [
                        'id' => 666,
                        'name' => 'customer1',
                        'email' => 'customer1@email.com'
                    ],
                ]
            ],
            'user2' => [
                '@type' => UserWithCustomer::class,
                'data' => [
                    'id' => 2,
                    'testname' => 'user2',
                    'customer' => [
                        'id' => 123,
                        'name' => 'customer2',
                        'email' => 'customer2@email.com'
                    ],
                ]
            ]
        ];

        $this->assertEquals(
            new ArrayCollection([
                'user1' => new UserWithCustomer(1, 'user1', new Customer(666, 'customer1')),
                'user2' => new UserWithCustomer(2, 'user2', new Customer(123, 'customer2')),
            ]),
            $this->getSerializer()->fromArray($data, ArrayCollection::class)
        );
    }

    /**
     *
     */
    public function test_denormalization_with_groupBy_combine()
    {
        $data = [
            'user1' => [
                [
                    '@type' => UserWithCustomer::class,
                    'data' => [
                        'id' => 1,
                        'testname' => 'user1',
                        'customer' => [
                            'id' => 666,
                            'name' => 'customer1',
                            'email' => 'customer1@email.com'
                        ],
                    ]
                ],
                [
                    '@type' => UserWithCustomer::class,
                    'data' => [
                        'id' => 2,
                        'testname' => 'user1',
                        'customer' => [
                            'id' => 123,
                            'name' => 'customer2',
                            'email' => 'customer2@email.com'
                        ],
                    ]
                ]
            ],
            'user2' => [
                [
                    '@type' => UserWithCustomer::class,
                    'data' => [
                        'id' => 3,
                        'testname' => 'user2',
                        'customer' => [
                            'id' => 666,
                            'name' => 'customer1',
                            'email' => 'customer1@email.com'
                        ],
                    ]
                ],
            ]
        ];

        $this->assertEquals(
            new ArrayCollection([
                'user1' => [
                    new UserWithCustomer(1, 'user1', new Customer(666, 'customer1')),
                    new UserWithCustomer(2, 'user1', new Customer(123, 'customer2')),
                ],
                'user2' => [
                    new UserWithCustomer(3, 'user2', new Customer(666, 'customer1')),
                ]
            ]),
            $this->getSerializer()->fromArray($data, ArrayCollection::class)
        );
    }

    /**
     *
     */
    public function test_denormalization_with_groupBy_combine_and_parametrized_type()
    {
        $data = [
            'user1' => [
                [
                    'id' => 1,
                    'testname' => 'user1',
                    'customer' => [
                        'id' => 666,
                        'name' => 'customer1',
                        'email' => 'customer1@email.com'
                    ],
                ],
                [
                    'id' => 2,
                    'testname' => 'user1',
                    'customer' => [
                        'id' => 123,
                        'name' => 'customer2',
                        'email' => 'customer2@email.com'
                    ],
                ]
            ],
            'user2' => [
                [
                    'id' => 3,
                    'testname' => 'user2',
                    'customer' => [
                        'id' => 666,
                        'name' => 'customer1',
                        'email' => 'customer1@email.com'
                    ],
                ],
            ]
        ];

        $this->assertEquals(
            new ArrayCollection([
                'user1' => [
                    new UserWithCustomer(1, 'user1', new Customer(666, 'customer1')),
                    new UserWithCustomer(2, 'user1', new Customer(123, 'customer2')),
                ],
                'user2' => [
                    new UserWithCustomer(3, 'user2', new Customer(666, 'customer1')),
                ]
            ]),
            $this->getSerializer()->fromArray($data, ArrayCollection::class.'<'.UserWithCustomer::class.'[]>')
        );
    }

    /**
     *
     */
    public function test_denormalization_with_groupBy_preserve()
    {
        $data = [
            'user1' => [
                'key1' => [
                    '@type' => UserWithCustomer::class,
                    'data' => [
                        'id' => 1,
                        'testname' => 'user1',
                        'customer' => [
                            'id' => 666,
                            'name' => 'customer1',
                            'email' => 'customer1@email.com'
                        ],
                    ]
                ],
                'key2' => [
                    '@type' => UserWithCustomer::class,
                    'data' => [
                        'id' => 2,
                        'testname' => 'user1',
                        'customer' => [
                            'id' => 123,
                            'name' => 'customer2',
                            'email' => 'customer2@email.com'
                        ],
                    ]
                ]
            ],
            'user2' => [
                'key1' => [
                    '@type' => UserWithCustomer::class,
                    'data' => [
                        'id' => 3,
                        'testname' => 'user2',
                        'customer' => [
                            'id' => 666,
                            'name' => 'customer1',
                            'email' => 'customer1@email.com'
                        ],
                    ]
                ],
            ]
        ];

        $this->assertEquals(
            new ArrayCollection([
                'user1' => [
                    'key1' => new UserWithCustomer(1, 'user1', new Customer(666, 'customer1')),
                    'key2' => new UserWithCustomer(2, 'user1', new Customer(123, 'customer2')),
                ],
                'user2' => [
                    'key1' => new UserWithCustomer(3, 'user2', new Customer(666, 'customer1')),
                ]
            ]),
            $this->getSerializer()->fromArray($data, ArrayCollection::class)
        );
    }

    /**
     *
     */
    public function test_denormalization_with_custom_collection()
    {
        $serializer = $this->getSerializer();
        $serializer->getLoader()->associate(MyCustomCollection::class, $this->getSerializer()->getLoader()->getNormalizer(ArrayCollection::class));

        $data = [
            [
                'id' => 1,
                'name' => 'user1',
                'customer' => [
                    'id' => 666,
                    'name' => 'customer',
                    'email' => 'customer@email.com'
                ]
            ],
            [
                'id' => 2,
                'name' => 'user2',
                'customer' => [
                    'id' => 666,
                    'name' => 'customer',
                    'email' => 'customer@email.com'
                ]
            ]
        ];

        $this->assertEquals(
            new MyCustomCollection([
                new UserWithCustomer(1, 'user1', new Customer(666, 'customer')),
                new UserWithCustomer(2, 'user2', new Customer(666, 'customer')),
            ]),
            $serializer->fromArray($data, MyCustomCollection::class.'<'.UserWithCustomer::class.'>')
        );
    }

    /**
     *
     */
    public function test_denormalization_with_EntityCollection()
    {
        $data = [
            [
                'id' => 1,
                'name' => 'user1'
            ],
            [
                'id' => 2,
                'name' => 'user2'
            ]
        ];

        $this->assertEquals(
            new EntityCollection(
                $this->prime()->repository(TestEntity::class),
                [
                    new TestEntity(['id' => 1, 'name' => 'user1', 'foreign' => null]),
                    new TestEntity(['id' => 2, 'name' => 'user2', 'foreign' => null]),
                ]
            ),
            $this->getSerializer()->fromArray($data, EntityCollection::class.'<'.TestEntity::class.'>')
        );
    }

    /**
     *
     */
    public function test_supports()
    {
        $normalizer = new PrimeCollectionNormalizer($this->prime());

        $this->assertTrue($normalizer->supports(ArrayCollection::class));
        $this->assertTrue($normalizer->supports(EntityCollection::class));
        $this->assertFalse($normalizer->supports('unknown'));
    }

    /**
     * @return \Bdf\Serializer\Serializer
     */
    private function getSerializer()
    {
        return SerializerBuilder::create()
            ->setNormalizers([
                new PrimeCollectionNormalizer($this->prime()),
                new PropertyNormalizer(new MetadataFactory([new StaticMethodDriver()]))
            ])
            ->build();
    }
}

class MyCustomCollection extends ArrayCollection {}