<?php

namespace Bdf\Prime;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ValueObjectFunctionalTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->configurePrime();

        $this->pack()
            ->declareEntity([
                TestEntityWithValueObject::class,
                PersonWithValueObject::class,
            ])
            ->initialize()
        ;
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_simple_crud()
    {
        $entity = new TestEntityWithValueObject();
        $entity->name = TestEntityName::from('foo');
        $entity->dateInsert = new DateTimeImmutable();

        $entity->save();

        $this->assertEquals(TestEntityId::from(1), $entity->id);
        $this->assertSame(1, $entity->id->value());

        $refreshed = TestEntityWithValueObject::refresh($entity);

        $this->assertEquals($entity, $refreshed);
        $this->assertEquals(TestEntityId::from(1), $refreshed->id);
        $this->assertEquals(TestEntityName::from('foo'), $refreshed->name);

        $refreshed->name = TestEntityName::from('bar');
        $refreshed->update(['name']);

        $refreshed = TestEntityWithValueObject::refresh($entity);
        $this->assertEquals(TestEntityName::from('bar'), $refreshed->name);

        $refreshed->delete();
        $this->assertNull(TestEntityWithValueObject::refresh($entity));
    }

    public function test_simple_with_embedded_crud()
    {
        $entity = new PersonWithValueObject();
        $entity->firstName = Name::from('john');
        $entity->lastName = Name::from('doe');
        $entity->address->street = Street::from('foo');
        $entity->address->city = City::from('bar');
        $entity->address->zip = ZipCode::from('zip');
        $entity->address->country = Country::from('country');

        $entity->save();

        $this->assertEquals(PersonId::from(1), $entity->id);
        $this->assertSame(1, $entity->id->value());

        $refreshed = PersonWithValueObject::refresh($entity);

        $this->assertEquals($entity, $refreshed);
        $this->assertEquals(PersonId::from(1), $refreshed->id);
        $this->assertEquals(Name::from('john'), $refreshed->firstName);
        $this->assertEquals(City::from('bar'), $refreshed->address->city);

        $refreshed->firstName = Name::from('bar');
        $refreshed->update(['firstName']);

        $refreshed = PersonWithValueObject::refresh($entity);
        $this->assertEquals(Name::from('bar'), $refreshed->firstName);

        $refreshed->delete();
        $this->assertNull(PersonWithValueObject::refresh($entity));
    }
}
