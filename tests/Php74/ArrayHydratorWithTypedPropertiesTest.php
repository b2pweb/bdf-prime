<?php

namespace Php74;

require_once __DIR__.'/_files/simple_entity.php';

use Bdf\Prime\Entity\Hydrator\ArrayHydrator;
use Bdf\Prime\Entity\Hydrator\Exception\InvalidTypeException;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ArrayHydratorWithTypedPropertiesTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var ArrayHydrator
     */
    protected $hydrator;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->hydrator = new ArrayHydrator();
    }

    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }

    /**
     *
     */
    public function test_simple_hydrate_and_extract()
    {
        $entity = new SimpleEntity();

        $this->hydrator->hydrate($entity, ['id' => 5, 'firstName' => 'John', 'lastName' => 'Doe']);

        $this->assertEquals((new SimpleEntity())->setId(5)->setFirstName('John')->setLastName('Doe'), $entity);
        $this->assertSame(['id' => 5, 'firstName' => 'John', 'lastName' => 'Doe'], $this->hydrator->extract($entity));
    }

    /**
     *
     */
    public function test_simple_hydrate_and_extract_without_setters()
    {
        $entity = new WithoutSetter();

        $this->hydrator->hydrate($entity, ['id' => 5, 'value' => 'foo']);

        $this->assertSame(['id' => 5, 'value' => 'foo'], $this->hydrator->extract($entity));
    }

    /**
     *
     */
    public function test_hydrate_extract_not_all_properties()
    {
        $entity = new SimpleEntity();

        $this->hydrator->hydrate($entity, ['id' => 5, 'firstName' => 'John']);

        $this->assertEquals((new SimpleEntity())->setId(5)->setFirstName('John'), $entity);
        $this->assertSame(['id' => 5, 'firstName' => 'John'], $this->hydrator->extract($entity));
    }

    /**
     *
     */
    public function test_hydrate_invalid_type_should_raise_InvalidTypeException()
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessageMatches('/Try to hydrate with an invalid type :.*Php74\\\\SimpleEntity.*id.*/i');
        $entity = new SimpleEntity();

        $this->hydrator->hydrate($entity, ['id' => 'invalid']);
    }

    /**
     *
     */
    public function test_hydrate_without_setter_invalid_type_should_raise_InvalidTypeException()
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessageMatches('/Try to hydrate with an invalid type :.*Php74\\\\WithoutSetter.*id.*/i');
        $entity = new WithoutSetter();

        $this->hydrator->hydrate($entity, ['id' => 'invalid']);
    }

    /**
     *
     */
    public function test_hydrate_null_on_not_null_property_should_raise_InvalidTypeException()
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessageMatches('/Try to hydrate with an invalid type :.*Php74\\\\SimpleEntity.*firstName.*/i');
        $entity = new SimpleEntity();

        $this->hydrator->hydrate($entity, ['firstName' => null]);
    }

    /**
     *
     */
    public function test_hydrate_null_on_nullable_property()
    {
        $entity = new SimpleEntity();

        $this->hydrator->hydrate($entity, ['id' => null]);
        $this->assertNull($entity->id());
    }

    /**
     *
     */
    public function test_hydrate_transtyping()
    {
        $entity = new SimpleEntity();

        $this->hydrator->hydrate($entity, ['id' => '5', 'firstName' => 123, 'lastName' => 456]);

        $this->assertEquals((new SimpleEntity())->setId(5)->setFirstName('123')->setLastName('456'), $entity);
        $this->assertSame(['id' => 5, 'firstName' => '123', 'lastName' => '456'], $this->hydrator->extract($entity));
    }
}

class WithoutSetter
{
    protected int $id;
    protected string $value;

    public function id(): int
    {
        return $this->id;
    }

    public function value(): string
    {
        return $this->value;
    }
}

class WithoutSetterMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return ['connection' => 'test', 'table' => 'without_setter'];
    }

    public function buildFields($builder)
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('value')
        ;
    }
}
