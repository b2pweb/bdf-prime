<?php

namespace Bdf\Prime\Entity\Hydrator;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Customer;
use Bdf\Prime\Document;
use Bdf\Prime\Location;
use Bdf\Prime\PolymorphContainer;
use Bdf\Prime\PolymorphSubA;
use Bdf\Prime\PolymorphSubB;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\ServiceLocator;
use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEntity;
use Bdf\Prime\TestEntityMapper;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MapperHydratorTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MapperHydrator
     */
    protected $hydrator;

    public function setUp(): void
    {
        $this->primeStart();

        $prime = new ServiceLocator();
        $this->hydrator = new MapperHydrator();
        $this->hydrator->setPrimeInstantiator($prime->instantiator());
        $this->hydrator->setPrimeMetadata((new TestEntityMapper($prime, TestEntity::class))->metadata());
    }

    /**
     *
     */
    public function test_extractOne()
    {
        $date = new DateTime();

        $entity = new TestEntity([
            "id" => 147,
            "name" => "Claude",
            "foreign" => [
                "id" => 22,
                "name" => "Pierre",
                "city" => "CAVAILLON"
            ],
            "dateInsert" => $date
        ]);

        $this->assertEquals(147, $this->hydrator->extractOne($entity, "id"));
        $this->assertEquals("Claude", $this->hydrator->extractOne($entity, "name"));
        $this->assertEquals(new TestEmbeddedEntity([
            "id" => 22,
            "name" => "Pierre",
            "city" => "CAVAILLON"
        ]), $this->hydrator->extractOne($entity, "foreign"));
        $this->assertEquals($date, $this->hydrator->extractOne($entity, "dateInsert"));
        $this->assertEquals(22, $this->hydrator->extractOne($entity, "foreign.id"));
    }

    /**
     *
     */
    public function test_extractOne_on_not_declared_embedded_should_raise_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot read from attribute "foreign.name" : it\'s not declared');

        $entity = new TestEntity([
            "id" => 147,
            "name" => "Claude",
            "foreign" => [
                "id" => 22,
                "name" => "Pierre",
                "city" => "CAVAILLON"
            ],
            "dateInsert" => new DateTime()
        ]);

        $this->assertNull($this->hydrator->extractOne($entity, "foreign.name"));
    }

    /**
     *
     */
    public function test_extractOne_on_not_declared_attribute_should_raise_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot read from attribute "not_declared" : it\'s not declared');

        $entity = new TestEntity([
            "id" => 147,
            "name" => "Claude",
            "foreign" => [
                "id" => 22,
                "name" => "Pierre",
                "city" => "CAVAILLON"
            ],
            "dateInsert" => new DateTime()
        ]);

        $this->assertNull($this->hydrator->extractOne($entity, "not_declared"));
    }

    /**
     *
     */
    public function test_extractOne_on_declared_embedded_attribute_with_null_embedded_should_return_null()
    {
        $entity = new TestEntity([
            "id" => 147,
            "name" => "Claude",
            "dateInsert" => new DateTime()
        ]);

        $this->assertNull($this->hydrator->extractOne($entity, 'foreign.id'));
    }

    /**
     *
     */
    public function test_hydrateOne()
    {
        $entity = new TestEntity();

        $this->hydrator->hydrateOne($entity, "id", 159);
        $this->assertEquals(159, $entity->id);

        $this->hydrator->hydrateOne($entity, "name", "Claude");
        $this->assertEquals("Claude", $entity->name);

        $foreign = new TestEmbeddedEntity([
            "id" => 22,
            "name" => "Pierre",
            "city" => "CAVAILLON"
        ]);
        $this->hydrator->hydrateOne($entity, "foreign", $foreign);
        $this->assertSame($foreign, $entity->foreign);

        $this->hydrator->hydrateOne($entity, "foreign.id", 654);
        $this->assertEquals(654, $foreign->id);

        $entity->foreign = null;
        $this->hydrator->hydrateOne($entity, "foreign.id", 777);
        $this->assertInstanceOf(TestEmbeddedEntity::class, $entity->foreign);
        $this->assertEquals(777, $entity->foreign->id);
    }

    /**
     *
     */
    public function test_hydrateOne_on_not_declared_embedded_attribute_should_raise_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot write to attribute "foreign.name" : it\'s not declared');

        $entity = new TestEntity();

        $this->hydrator->hydrateOne($entity, 'foreign.name', 'other');
    }

    /**
     *
     */
    public function test_hydrateOne_on_not_declared_attribute_should_raise_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot write to attribute "not_declared" : it\'s not declared');

        $entity = new TestEntity();

        $this->hydrator->hydrateOne($entity, 'not_declared', 'other');
    }

    /**
     *
     */
    public function test_flatExtract()
    {
        $date = new DateTime();

        $entity = new TestEntity([
            "id" => 147,
            "name" => "Claude",
            "foreign" => [
                "id" => 22,
                "name" => "Pierre",
                "city" => "CAVAILLON"
            ],
            "dateInsert" => $date
        ]);

        $this->assertEquals([
            "id" => 147,
            "name" => "Claude",
            "foreign.id" => 22,
            "dateInsert" => $date
        ], $this->hydrator->flatExtract($entity));
    }

    /**
     *
     */
    public function test_flatExtract_selected()
    {
        $date = new DateTime();

        $entity = new TestEntity([
            "id" => 147,
            "name" => "Claude",
            "foreign" => [
                "id" => 22,
                "name" => "Pierre",
                "city" => "CAVAILLON"
            ],
            "dateInsert" => $date
        ]);

        $this->assertEquals([
            "id" => 147,
            "foreign.id" => 22,
        ], $this->hydrator->flatExtract($entity, ["id" => true, "foreign.id" => true]));
    }

    /**
     *
     */
    public function test_flatHydrate()
    {
        $date = new DateTime();

        $entity = new TestEntity();
        $types = (new DummyPlatform())->types();

        $this->hydrator->flatHydrate($entity, [
            'id' => '147',
            'name' => 'Claude',
            'foreign_key' => '22',
            'date_insert' => $date->format('Y-m-d H:i:s')
        ], $types);

        $this->assertSame(147, $entity->id);
        $this->assertSame('Claude', $entity->name);
        $this->assertSame(22, $entity->foreign->id);
        $this->assertEquals($date, $entity->dateInsert);

        $entity->foreign = null;
        $this->hydrator->flatHydrate($entity, ['foreign_key' => '666'], $types);
        $this->assertSame(666, $entity->foreign->id);
    }

    /**
     *
     */
    public function test_deep_embedded()
    {
        $types = (new DummyPlatform())->types();
        $mapper = Document::repository()->mapper();
        $hydrator = new MapperHydrator();
        $hydrator->setPrimeInstantiator(Document::locator()->instantiator());
        $hydrator->setPrimeMetadata($mapper->metadata());

        $document = new Document([
            'id' => 1,
            'customerId'   => '10',
            'uploaderType' => 'user',
            'uploaderId'   => '1',
            'contact' => (object)[
                'name'     => 'Holmes',
                'location' => new Location([
                    'address' => '221b Baker Street',
                    'city'    => 'London',
                ])
            ],
        ]);

        $this->assertSame([
            'id' => 1,
            'customerId'               => '10',
            'uploaderType'             => 'user',
            'uploaderId'               => '1',
            'contact.name'             => 'Holmes',
            'contact.location.address' => '221b Baker Street',
            'contact.location.city'    => 'London',
        ], $hydrator->flatExtract($document));

        $this->assertSame([
            'contact.name'             => 'Holmes',
            'contact.location.address' => '221b Baker Street',
        ], $hydrator->flatExtract($document, ['contact.name' => true, 'contact.location.address' => true]));

        $this->assertSame('London', $hydrator->extractOne($document, 'contact.location.city'));

        $hydrator->hydrateOne($document, 'contact.location.city', 'Paris');
        $this->assertSame('Paris', $document->contact->location->city);

        $hydrator->flatHydrate($document, [
            'id_' => 741,
            'customer_id'               => '888',
            'uploader_id'               => '74',
            'contact_name'             => 'Bob',
            'contact_address' => '111',
            'contact_city'    => 'CAVAILLON',
        ], $types);

        $this->assertSame('741', $document->id);
        $this->assertSame("888", $document->customerId);
        $this->assertSame("74", $document->uploaderId);
        $this->assertSame("Bob", $document->contact->name);
        $this->assertSame("111", $document->contact->location->address);
        $this->assertSame("CAVAILLON", $document->contact->location->city);
    }

    /**
     *
     */
    public function test_functional_extractOne_with_relation()
    {
        $mapper = Customer::repository()->mapper();
        $hydrator = new MapperHydrator();
        $hydrator->setPrimeInstantiator(Customer::locator()->instantiator());
        $hydrator->setPrimeMetadata($mapper->metadata());

        $customer = new Customer(['name' => 'base customer']);

        $this->assertNull($hydrator->extractOne($customer, 'documents'));
        $this->assertNull($hydrator->extractOne($customer, 'parent'));
    }

    /**
     *
     */
    public function test_flatHydrate_polymorph_embedded()
    {
        $mapper = PolymorphContainer::repository()->mapper();
        $hydrator = new MapperHydrator();
        $hydrator->setPrimeInstantiator(PolymorphContainer::locator()->instantiator());
        $hydrator->setPrimeMetadata($mapper->metadata());
        $types = (new DummyPlatform())->types();

        $entity = new PolymorphContainer();

        $hydrator->flatHydrate($entity, [
            'id'       => '123',
            'sub_name' => 'my entity',
            'sub_type' => 'A',
        ], $types);

        $this->assertSame(123, $entity->id());
        $this->assertInstanceOf(PolymorphSubA::class, $entity->embedded());
        $this->assertSame('A', $entity->embedded()->type());
        $this->assertSame('my entity', $entity->embedded()->name());

        $hydrator->flatHydrate($entity, [
            'id'       => '123',
            'sub_name' => 'other',
            'sub_type' => 'B',
            'sub_address' => 'my address',
            'sub_city' => 'my city'
        ], $types);

        $this->assertInstanceOf(PolymorphSubB::class, $entity->embedded());
        $this->assertSame('B', $entity->embedded()->type());
        $this->assertSame('other', $entity->embedded()->name());
        $this->assertInstanceOf(Location::class, $entity->embedded()->location());
        $this->assertEquals('my address', $entity->embedded()->location()->address);
        $this->assertEquals('my city', $entity->embedded()->location()->city);
    }

    /**
     *
     */
    public function test_flatExtract_polymorph_embedded()
    {
        $mapper = PolymorphContainer::repository()->mapper();
        $hydrator = new MapperHydrator();
        $hydrator->setPrimeInstantiator(PolymorphContainer::locator()->instantiator());
        $hydrator->setPrimeMetadata($mapper->metadata());

        $entity = new PolymorphContainer([
            'id' => 123,
            'embedded' => (new PolymorphSubA('my name'))->setLocation(new Location([
                'city'    => 'my city',
                'address' => 'my address',
            ]))
        ]);

        $this->assertEquals([
            'id' => 123,
            'embedded.type' => 'A',
            'embedded.name' => 'my name',
            'embedded.location.address' => 'my address',
            'embedded.location.city' => 'my city',
        ], $hydrator->flatExtract($entity));

        $this->assertEquals([
            'embedded.name' => 'my name',
        ], $hydrator->flatExtract($entity, ['embedded.name' => true]));

        $entity->setEmbedded(null);

        $this->assertEquals([
            'id' => 123,
            'embedded.type' => null,
            'embedded.name' => null,
            'embedded.location.address' => null,
            'embedded.location.city' => null,
        ], $hydrator->flatExtract($entity));
    }

    /**
     *
     */
    public function test_extractOne_polymorph_embedded()
    {
        $mapper = PolymorphContainer::repository()->mapper();
        $hydrator = new MapperHydrator();
        $hydrator->setPrimeInstantiator(PolymorphContainer::locator()->instantiator());
        $hydrator->setPrimeMetadata($mapper->metadata());

        $entity = new PolymorphContainer([
            'id' => 123,
            'embedded' => new PolymorphSubA('my name')
        ]);

        $this->assertEquals('my name', $hydrator->extractOne($entity, 'embedded.name'));
        $this->assertNull($hydrator->extractOne($entity, 'embedded.location.city'));

        $entity->embedded()->setLocation(new Location(['city' => 'my city']));
        $this->assertEquals('my city', $hydrator->extractOne($entity, 'embedded.location.city'));

        $entity->setEmbedded(new PolymorphSubB('other'));
        $this->assertEquals('other', $hydrator->extractOne($entity, 'embedded.name'));

        $entity->setEmbedded(null);
        $this->assertNull($hydrator->extractOne($entity, 'embedded.name'));
    }

    /**
     *
     */
    public function test_hydrateOne_polymorph_embedded()
    {
        $mapper = PolymorphContainer::repository()->mapper();
        $hydrator = new MapperHydrator();
        $hydrator->setPrimeInstantiator(PolymorphContainer::locator()->instantiator());
        $hydrator->setPrimeMetadata($mapper->metadata());

        $entity = new PolymorphContainer([
            'id' => 123,
            'embedded' => new PolymorphSubA('my name')
        ]);

        $hydrator->hydrateOne($entity, 'embedded.name', 'my new name');
        $this->assertEquals('my new name', $entity->embedded()->name());

        $entity->setEmbedded(new PolymorphSubB('other'));
        $hydrator->hydrateOne($entity, 'embedded.name', 'other name');
        $this->assertEquals('other name', $entity->embedded()->name());

        $hydrator->hydrateOne($entity, 'embedded.location.city', 'my city');
        $this->assertEquals('my city', $entity->embedded()->location()->city);
    }

    /**
     *
     */
    public function test_hydrateOne_polymorph_embedded_not_instantiated()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot write to attribute embedded.name : the embedded entity cannot be resolved');

        $mapper = PolymorphContainer::repository()->mapper();
        $hydrator = new MapperHydrator();
        $hydrator->setPrimeInstantiator(PolymorphContainer::locator()->instantiator());
        $hydrator->setPrimeMetadata($mapper->metadata());

        $entity = new PolymorphContainer(['id' => 123]);

        $hydrator->hydrateOne($entity, 'embedded.name', 'my new name');
    }
}
