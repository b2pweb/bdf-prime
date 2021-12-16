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
class AutoIncrementGeneratorTest extends TestCase
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
        $this->pack()->declareEntity(__NAMESPACE__.'\\AutoIncrementUser');

        $service = Prime::service();
        $entity = new AutoIncrementUser();
        $data = ['name' => 'test'];
        
        $generator = new AutoIncrementGenerator($service->repository($entity)->mapper());
        $generator->setCurrentConnection($service->connection('test'));
        $generator->generate($data, $service);

        $service->repository($entity)->builder()->insert($data);

        $generator->postProcess($entity);

        $this->assertTrue(empty($data['id']));
        $this->assertMatchesRegularExpression('/[\d]+/', $entity->id);
    }

    /**
     *
     */
    public function test_no_generation_if_id_is_set()
    {
        $this->pack()->declareEntity(__NAMESPACE__.'\\AutoIncrementUser');

        $service = Prime::service();
        $entity = new AutoIncrementUser();
        $data = ['id' => 100, 'name' => 'test'];

        $generator = new AutoIncrementGenerator($service->repository($entity)->mapper());
        $generator->setCurrentConnection($service->connection('test'));
        $generator->generate($data, $service);

        $service->repository($entity)->builder()->insert($data);

        $generator->postProcess($entity);

        $this->assertEquals(100, $data['id']);
        $this->assertNull($entity->id);
    }
}


class AutoIncrementUser
{
    public $id;
    public $name;
}

class AutoIncrementUserMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'auto_user',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')->autoincrement()
            ->string('name')
        ;
    }
}