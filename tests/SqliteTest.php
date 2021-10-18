<?php

namespace Bdf\Prime;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Mapper\Mapper;
use PHPUnit\Framework\TestCase;

/**
 * @group sqlite
 */
class SqliteTest extends TestCase
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
    public function test_sqlite()
    {
        Prime::create(Sqlite::class);

        /** @var SimpleConnection $connection */
        $connection = Prime::connection('test');
        
        for ($i = 1; $i <= 4; $i++) {
            Prime::save($entity = new Sqlite());

            $this->assertEquals($i, $entity->id);
            $this->assertEquals($i, $connection->from('sequence')->max('id'));
            $this->assertEquals(1, $connection->from('sequence')->count('id'));
        }
    }
}

class Sqlite
{
    public $id;
    
    public function __construct($id = null)
    {
        $this->id = $id;
    }
}

class SqliteMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'sqlite',
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder->bigint('id')->sequence();
    }
    
    /**
     * {@inheritdoc}
     */
    public function sequence(): array
    {
        return [
            'connection'   => 'test',
            'table'        => 'sequence',
            'column'       => 'id',
            'tableOptions' => [],
        ];
    }
}