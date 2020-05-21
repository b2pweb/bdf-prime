<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class GuidGeneratorTest extends TestCase
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
        $this->pack()->declareEntity(__NAMESPACE__.'\\GuidUser');

        $service = Prime::service();
        $entity = new GuidUser();
        $data = ['name' => 'test'];
        
        $generator = new GuidGenerator($service->repository($entity)->mapper());
        $generator->setCurrentConnection($service->connection('test'));
        $generator->generate($data, $service);

        $service->repository($entity)->builder()->insert($data);

        $generator->postProcess($entity);
        
        $this->assertRegExp('/[\w\d\-]+/', $data['id']);
        $this->assertEquals($data['id'], $entity->id);
    }


    /**
     *
     */
    public function test_no_generation_if_id_is_set()
    {
        $this->pack()->declareEntity(__NAMESPACE__.'\\GuidUser');

        $service = Prime::service();
        $entity = new GuidUser();
        $data = ['id' => '100-01', 'name' => 'test'];

        $generator = new GuidGenerator($service->repository($entity)->mapper());
        $generator->setCurrentConnection($service->connection('test'));
        $generator->generate($data, $service);

        $service->repository($entity)->builder()->insert($data);

        $generator->postProcess($entity);

        $this->assertRegExp('/[\w\d\-]+/', $data['id']);
        $this->assertNull($entity->id);
    }
}

class GuidUser
{
    public $id;
    public $name;
}

class GuidUserMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table'      => 'guid_user',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
    {
        $builder
            ->bigint('id')->primary()
            ->string('name')
        ;
    }
}