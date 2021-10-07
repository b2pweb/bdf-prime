<?php

namespace Bdf\Prime\Connection\Result;

use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DoctrineResultSetTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var SimpleConnection
     */
    protected $connection;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->connection = Prime::connection('test');
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([
            'Bdf\Prime\TestEntity',
        ]);

        $pack->persist([
            new TestEntity([
                'id' => 1,
                'name' => 'John'
            ]),
            new TestEntity([
                'id' => 2,
                'name' => 'Mickey'
            ])
        ]);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
    }

    /**
     *
     */
    public function test_all()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());

        $this->assertEquals([
            [
                'id' => 1,
                'name' => 'John'
            ],
            [
                'id' => 2,
                'name' => 'Mickey'
            ]
        ], $resultSet->all());

        $this->assertTrue($resultSet->isRead());
        $this->assertFalse($resultSet->isWrite());
        $this->assertFalse($resultSet->hasWrite());
    }

    /**
     *
     */
    public function test_all_column()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->fetchMode(ResultSetInterface::FETCH_COLUMN);
        $this->assertEquals([1, 2], $resultSet->all());

        unset($resultSet); // Close the cursor

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->fetchMode(ResultSetInterface::FETCH_COLUMN, 1);
        $this->assertEquals(['John', 'Mickey'], $resultSet->all());

        unset($resultSet); // Close the cursor

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->asColumn();
        $this->assertEquals([1, 2], $resultSet->all());

        unset($resultSet); // Close the cursor

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->asColumn(1);
        $this->assertEquals(['John', 'Mickey'], $resultSet->all());
    }

    /**
     *
     */
    public function test_all_num()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->fetchMode(ResultSetInterface::FETCH_NUM);

        $this->assertEquals([
            [1, 'John'],
            [2, 'Mickey']
        ], $resultSet->all());

        unset($resultSet); // Close the cursor

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->asList();

        $this->assertEquals([
            [1, 'John'],
            [2, 'Mickey']
        ], $resultSet->all());
    }

    /**
     *
     */
    public function test_all_object()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->fetchMode(ResultSetInterface::FETCH_OBJECT);

        $this->assertEquals([
            (object) [
                'id' => 1,
                'name' => 'John'
            ],
            (object) [
                'id' => 2,
                'name' => 'Mickey'
            ]
        ], $resultSet->all());

        unset($resultSet); // Close the cursor

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->asObject();

        $this->assertEquals([
            (object) [
                'id' => 1,
                'name' => 'John'
            ],
            (object) [
                'id' => 2,
                'name' => 'Mickey'
            ]
        ], $resultSet->all());
    }

    /**
     *
     */
    public function test_all_class()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->fetchMode(ResultSetInterface::FETCH_CLASS, TestEntity::class);

        $this->assertEquals([
            new TestEntity([
                'id' => 1,
                'name' => 'John'
            ]),
            new TestEntity([
                'id' => 2,
                'name' => 'Mickey'
            ])
        ], $resultSet->all());

        unset($resultSet); // Close the cursor

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->asClass(TestEntity::class);

        $this->assertEquals([
            new TestEntity([
                'id' => 1,
                'name' => 'John'
            ]),
            new TestEntity([
                'id' => 2,
                'name' => 'Mickey'
            ])
        ], $resultSet->all());
    }

    /**
     *
     */
    public function test_asClass_with_constructor_argument()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->asClass(TestEntity::class, [['parentId' => 15]]);

        $this->assertEquals([
            new TestEntity([
                'id' => 1,
                'name' => 'John',
                'parentId' => 15,
            ]),
            new TestEntity([
                'id' => 2,
                'name' => 'Mickey',
                'parentId' => 15,
            ])
        ], $resultSet->all());
    }

    /**
     *
     */
    public function test_current()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());

        $this->assertEquals([
            'id' => 1,
            'name' => 'John'
        ], $resultSet->current());
    }

    /**
     *
     */
    public function test_current_column()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->fetchMode(DoctrineResultSet::FETCH_COLUMN, 1);

        $this->assertEquals('John', $resultSet->current());

        unset($resultSet); // Close the cursor

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->asColumn(1);

        $this->assertEquals('John', $resultSet->current());
    }

    /**
     *
     */
    public function test_current_class()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());

        $this->assertEquals(new TestEntity(['id' => 1, 'name' => 'John']), $resultSet->asClass(TestEntity::class)->current());
        $resultSet->next();
        $resultSet->next();
        $this->assertFalse($resultSet->asClass(TestEntity::class)->current());
    }

    /**
     *
     */
    public function test_current_assoc()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());

        $this->assertEquals(['id' => 1, 'name' => 'John'], $resultSet->asAssociative()->current());
        $resultSet->next();
        $resultSet->next();
        $this->assertFalse($resultSet->current());
    }

    /**
     *
     */
    public function test_current_object()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());

        $this->assertEquals((object) ['id' => 1, 'name' => 'John'], $resultSet->asObject()->current());
        $resultSet->next();
        $resultSet->next();
        $this->assertFalse($resultSet->current());
    }

    /**
     *
     */
    public function test_current_list()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());

        $this->assertEquals([1, 'John'], $resultSet->asList()->current());
        $resultSet->next();
        $resultSet->next();
        $this->assertFalse($resultSet->current());
    }

    /**
     *
     */
    public function test_iterator()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());

        $this->assertEquals([
            [
                'id' => 1,
                'name' => 'John'
            ],
            [
                'id' => 2,
                'name' => 'Mickey'
            ]
        ], iterator_to_array($resultSet));
    }

    /**
     *
     */
    public function test_iterator_object()
    {
        $stmt = $this->connection->prepare('SELECT id, name FROM test_');

        $resultSet = new DoctrineResultSet($stmt->executeQuery());
        $resultSet->asObject();

        $this->assertEquals([
            (object) [
                'id' => 1,
                'name' => 'John'
            ],
            (object) [
                'id' => 2,
                'name' => 'Mickey'
            ]
        ], iterator_to_array($resultSet));
    }
}
