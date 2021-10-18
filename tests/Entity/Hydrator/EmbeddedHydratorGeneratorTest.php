<?php

namespace Bdf\Prime\Entity\Hydrator;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Bench\HydratorGeneration;
use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Entity\ImportableInterface;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class EmbeddedHydratorGeneratorTest extends TestCase
{
    use PrimeTestCase;
    use HydratorGeneration;

    /**
     * @var HydratorGenerator
     */
    protected $generator;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->primeStart();
    }

    /**
     *
     */
    public function test_generate_hydrate_on_by_inheritance_relation()
    {
        $generator = new HydratorGenerator($this->prime(), HydratEntity::repository()->mapper(), HydratEntity::class);
        $code = $generator->generate();

        $this->assertStringContainsString('$__rel_embbed->import($data[\'embbed\']);', $code);
    }

    /**
     *
     */
    public function test_hydrate()
    {
        $hydrator = $this->createGeneratedHydrator(HydratEntity::class);

        $entity = new HydratEntity();
        $entity->embbed = new HydratEmbeddedEntity();

        $hydrator->hydrate($entity, ['embbed' => ['value' => 'bar']]);

        $this->assertEquals('bar', $entity->embbed->value);
    }

    /**
     *
     */
    public function test_extract()
    {
        $hydrator = $this->createGeneratedHydrator(HydratEntity::class);

        $entity = new HydratEntity();
        $entity->embbed = new HydratEmbeddedEntity();
        $entity->embbed->value = 'bar';

        $this->assertEquals(['embbed' => ['value' => 'bar']], $hydrator->extract($entity));
    }
}

class HydratEntity extends Model
{
    public $embbed;
}

class HydratEmbeddedEntity implements ImportableInterface
{
    use ArrayInjector;

    public $value;
}

class HydratEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'hydra_entity'
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder->embedded('embbed', HydratEmbeddedEntity::class, function ($builder) {
            $builder->string('value');
        });
    }
}