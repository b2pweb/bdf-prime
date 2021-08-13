<?php

namespace Php74;

use Bdf\Prime\Entity\Hydrator\Exception\UninitializedPropertyException;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Test\TestPack;
use PHPUnit\Framework\TestCase;

/**
 * Class CRUDTest
 */
class CRUDTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->primeStart();

        TestPack::pack()->declareEntity([SimpleEntity::class, EntityWithEmbedded::class])->initialize();
    }

    protected function tearDown(): void
    {
        TestPack::pack()->destroy();
        $this->unsetPrime();
    }

    /**
     *
     */
    public function test_insert_refresh()
    {
        $entity = (new SimpleEntity())
            ->setId(5)
            ->setFirstName('John')
            ->setLastName('Doe')
        ;

        $entity->insert();

        $this->assertEquals($entity, SimpleEntity::refresh($entity));
    }

    /**
     *
     */
    public function test_insert_autoincrement()
    {
        $entity = (new SimpleEntity())
            ->setId(null)
            ->setFirstName('John')
            ->setLastName('Doe')
        ;

        $entity->insert();

        $this->assertSame(1, $entity->id());
        $this->assertEquals($entity, SimpleEntity::refresh($entity));
    }

    /**
     *
     */
    public function test_insert_invalid_entity()
    {
        $this->expectException(UninitializedPropertyException::class);

        (new SimpleEntity())->insert();
    }
}
