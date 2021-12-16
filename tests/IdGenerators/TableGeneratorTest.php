<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class TableGeneratorTest extends TestCase
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
    protected function tearDown(): void
    {
        $this->primeReset();
    }

    /**
     *
     */
    public function test_generation_id()
    {
        $this->pack()->declareEntity(__NAMESPACE__.'\\TableUser');

        $service = Prime::service();
        $connection = $service->connection('test');
        $connection->from('table_user_seq')->update(['id' => 10]);

        $entity = new TableUser();
        $data = ['name' => 'test'];
        
        $generator = new TableGenerator($service->repository($entity)->mapper());
        $generator->setCurrentConnection($connection);
        $generator->generate($data, $service);

        $service->repository($entity)->builder()->insert($data);

        $generator->postProcess($entity);
        
        $this->assertEquals(11, $data['id']);
        $this->assertEquals($data['id'], $entity->id);
        $this->assertEquals(11, $connection->from('table_user_seq')->inRow('id'));
    }


    /**
     *
     */
    public function test_no_generation_if_id_is_set()
    {
        $this->pack()->declareEntity(__NAMESPACE__.'\\TableUser');

        $service = Prime::service();
        $entity = new TableUser();
        $data = ['id' => 100, 'name' => 'test'];

        $generator = new TableGenerator($service->repository($entity)->mapper());
        $generator->setCurrentConnection($service->connection('test'));
        $generator->generate($data, $service);

        $service->repository($entity)->builder()->insert($data);

        $generator->postProcess($entity);

        $this->assertEquals(100, $data['id']);
        $this->assertNull($entity->id);
    }
}


class TableUser
{
    public $id;
    public $name;
}

class TableUserMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'table_user',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function sequence(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'table_user_seq',
            'column'       => null,
            'tableOptions' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')->sequence()
            ->string('name')
        ;
    }
}