<?php

namespace Php74;

use Bdf\Prime\Entity\Hydrator\Exception\InvalidTypeException;
use Bdf\Prime\Entity\Hydrator\Exception\UninitializedPropertyException;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 * Class MapperHydratorWithTypedPropertiesTest
 *
 * @todo test undeclared field exception
 */
class MapperHydratorWithTypedPropertiesTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->primeStart();
    }

    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }

    /**
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function test_fromRepository_with_all_properties()
    {
        $entity = SimpleEntity::repository()->mapper()->prepareFromRepository([
            'id' => '15',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ], SimpleEntity::repository()->connection()->platform());

        $this->assertEquals((new SimpleEntity())->setId(15)->setFirstName('John')->setLastName('Doe'), $entity);
    }

    /**
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function test_fromRepository_with_missing_properties_should_not_fail()
    {
        $entity = SimpleEntity::repository()->mapper()->prepareFromRepository(['firstName' => 'John'], SimpleEntity::repository()->connection()->platform());

        $this->assertEquals((new SimpleEntity())->setFirstName('John'), $entity);
    }

    /**
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function test_fromRepository_with_missing_properties_on_embedded_should_not_fail()
    {
        $entity = EntityWithEmbedded::repository()->mapper()->prepareFromRepository(['emb_fn' => 'John'], EntityWithEmbedded::repository()->connection()->platform());

        $this->assertEquals((new EntityWithEmbedded)->setEmbedded((new SimpleEntity())->setFirstName('John')), $entity);
    }

    /**
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function test_fromRepository_with_null_on_nonnull_properties_should_not_fail()
    {
        $entity = SimpleEntity::repository()->mapper()->prepareFromRepository(['firstName' => 'John', 'lastName' => null], SimpleEntity::repository()->connection()->platform());

        $this->assertEquals((new SimpleEntity())->setFirstName('John'), $entity);
    }

    /**
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function test_fromRepository_with_null_on_nonnull_properties_on_embedded_should_not_fail()
    {
        $entity = EntityWithEmbedded::repository()->mapper()->prepareFromRepository(['emb_fn' => 'John', 'emb_ln' => null], EntityWithEmbedded::repository()->connection()->platform());

        $this->assertEquals((new EntityWithEmbedded())->setEmbedded((new SimpleEntity())->setFirstName('John')), $entity);
    }

    /**
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function test_toRepository_with_all_properties()
    {
        $entity = (new SimpleEntity())->setId(15)->setFirstName('John')->setLastName('Doe');
        $data = SimpleEntity::repository()->mapper()->prepareToRepository($entity);

        $this->assertSame(['id' => 15, 'firstName' => 'John', 'lastName' => 'Doe'], $data);
    }

    /**
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    public function test_toRepository_with_not_initialized_properties_should_fail()
    {
        $this->expectException(UninitializedPropertyException::class);

        $entity = (new SimpleEntity())->setFirstName('John');
        $data = SimpleEntity::repository()->mapper()->prepareToRepository($entity);
    }

    /**
     *
     */
    public function test_extractOne_success()
    {
        $this->assertSame(15, SimpleEntity::repository()->extractOne((new SimpleEntity())->setId(15), 'id'));
    }

    /**
     *
     */
    public function test_extractOne_not_initialized_should_fail()
    {
        $this->expectException(UninitializedPropertyException::class);
        SimpleEntity::repository()->extractOne(new SimpleEntity(), 'id');
    }

    /**
     *
     */
    public function test_extractOne_not_initialized_on_embedded_attribute_should_fail()
    {
        $this->expectException(UninitializedPropertyException::class);
        EntityWithEmbedded::repository()->extractOne((new EntityWithEmbedded())->setEmbedded(new SimpleEntity()), 'embedded.firstName');
    }

    /**
     *
     */
    public function test_extractOne_not_initialized_on_embedded_should_fail()
    {
        $this->expectException(UninitializedPropertyException::class);
        EntityWithEmbedded::repository()->extractOne(new EntityWithEmbedded(), 'embedded');
    }

    /**
     *
     */
    public function test_hydrateOne_success()
    {
        $entity = new SimpleEntity();
        SimpleEntity::repository()->hydrateOne($entity, 'id', 15);

        $this->assertSame(15, $entity->id());
    }

    /**
     *
     */
    public function test_hydrateOne_invalid_type()
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessageMatches('/Try to hydrate with an invalid type :.*Php74\\\\SimpleEntity.*id.*must be (of the type )?int or null, string.*\(declared type on mapper : integer\)/i');

        $entity = new SimpleEntity();
        SimpleEntity::repository()->hydrateOne($entity, 'id', 'foo');
    }

    /**
     *
     */
    public function test_extractOne_on_embedded_success()
    {
        $this->assertSame('John', EntityWithEmbedded::repository()->extractOne(
            (new EntityWithEmbedded())->setEmbedded((new SimpleEntity())->setFirstName('John')),
            'embedded.firstName')
        );
    }

    /**
     *
     */
    public function test_extractOne_on_embedded_not_initialized_should_fail()
    {
        $this->expectException(UninitializedPropertyException::class);
        EntityWithEmbedded::repository()->extractOne(new EntityWithEmbedded(), 'embedded.firstName');
    }

    /**
     *
     */
    public function test_hydrateOne_on_embedded_success()
    {
        $entity = new EntityWithEmbedded();
        EntityWithEmbedded::repository()->hydrateOne($entity, 'embedded.firstName', 'John');

        $this->assertSame('John', $entity->embedded()->firstName());
    }

    /**
     *
     */
    public function test_hydrateOne_on_embedded_invalid_type()
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessageMatches('/Try to hydrate with an invalid type :.*Php74\\\\SimpleEntity.*firstName.*must be (of the type )?string, (stdClass|object).*\(declared type on mapper : string\)/i');

        $entity = new EntityWithEmbedded();
        EntityWithEmbedded::repository()->hydrateOne($entity, 'embedded.firstName', new \stdClass());
    }
}
