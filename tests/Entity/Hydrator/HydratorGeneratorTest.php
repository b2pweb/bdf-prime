<?php

namespace Bdf\Prime\Entity\Hydrator;

use DateTimeImmutable;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Admin;
use Bdf\Prime\ArrayHydratorTestEntity;
use Bdf\Prime\ArrayHydratorTestEntity2;
use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\Bench\HydratorGeneration;
use Bdf\Prime\Contact;
use Bdf\Prime\Customer;
use Bdf\Prime\Document;
use Bdf\Prime\DocumentControlTask;
use Bdf\Prime\EmbeddedEntity2;
use Bdf\Prime\Entity\Hydrator\Exception\FieldNotDeclaredException;
use Bdf\Prime\Entity\Hydrator\Exception\HydratorGenerationException;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Folder;
use Bdf\Prime\Location;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\PolymorphContainer;
use Bdf\Prime\PolymorphSubA;
use Bdf\Prime\PolymorphSubB;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Task;
use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEntity;
use Bdf\Prime\TestFile;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 *
 */
class HydratorGeneratorTest extends TestCase
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

        $this->generator = new HydratorGenerator(
            Prime::service(),
            Prime::repository(TestEntity::class)->mapper(),
            TestEntity::class
        );
    }

    /**
     *
     */
    public function test_error_on_inaccessible_property()
    {
        $this->expectException(HydratorGenerationException::class);

        $hydrator = new HydratorGenerator(
            Prime::service(),
            Prime::repository(TestInaccessiblePropertyEntity::class)->mapper(),
            TestInaccessiblePropertyEntity::class
        );

        $hydrator->generate();
    }

    /**
     *
     */
    public function test_error_on_not_found_property()
    {
        $this->expectException(HydratorGenerationException::class);

        $hydrator = new HydratorGenerator(
            Prime::service(),
            Prime::repository(TestPropertyNotFoundEntity::class)->mapper(),
            TestPropertyNotFoundEntity::class
        );

        $hydrator->generate();
    }

    /**
     *
     */
    public function test_generate_has_valid_interface()
    {
        $code = $this->generator->generate();

        $this->assertStringContainsString('implements \Bdf\Prime\Entity\Hydrator\HydratorGeneratedInterface', $code);
        $this->assertStringContainsString('public function setPrimeInstantiator(InstantiatorInterface $instantiator): void', $code);
        $this->assertStringContainsString('public function setPrimeMetadata(Metadata $metadata): void', $code);
        $this->assertStringContainsString('public function hydrate($object, array $data): void', $code);
        $this->assertStringContainsString('public function extract($object, array $attributes = []): array', $code);
        $this->assertStringContainsString('public function flatExtract($object, ?array $attributes = null): array', $code);
        $this->assertStringContainsString('public function flatHydrate($object, array $data, PlatformTypesInterface $types): void', $code);
        $this->assertStringContainsString('public function extractOne($object, string $attribute)', $code);
        $this->assertStringContainsString('public function hydrateOne($object, string $attribute, $value): void', $code);
        $this->assertStringContainsString('public static function supportedPrimeClassName(): string', $code);
        $this->assertStringContainsString('public static function embeddedPrimeClasses(): array', $code);
    }

    /**
     *
     */
    public function test_generate_has_attributes_in_data()
    {
        $code = $this->generator->generate();

        $this->assertStringContainsString('$data[\'id\']', $code);
        $this->assertStringContainsString('$data[\'name\']', $code);
        $this->assertStringContainsString('$data[\'foreign\']', $code);
        $this->assertStringContainsString('$data[\'dateInsert\']', $code);
    }

    /**
     *
     */
    public function test_generate_setter_with_public()
    {
        $code = $this->generator->generate();

        $this->assertStringContainsString('$object->id =', $code);
        $this->assertStringContainsString('$object->name =', $code);
        $this->assertStringContainsString('$object->foreign =', $code);
        $this->assertStringContainsString('$object->dateInsert =', $code);
    }

    /**
     *
     */
    public function test_generate_instanceof_on_embedded_accessible()
    {
        $code = $this->generator->generate();

        $this->assertStringContainsString('$__rel_foreign = $object->foreign', $code);
        $this->assertStringContainsString('$__rel_foreign instanceof \Bdf\Prime\TestEmbeddedEntity', $code);
    }

    /**
     *
     */
    public function test_generate_hydrate_on_by_inheritance_relation()
    {
        $generator = new HydratorGenerator($this->prime(), Task::repository()->mapper(), Task::class);
        $code = $generator->generate();

        $expected = <<<EOL
                    if (\$__rel_target instanceof \Bdf\Prime\Document) {
                        \$__rel_target->import(\$data['target']);
                    } elseif (\$__rel_target instanceof \Bdf\Prime\Customer) {
                        \$__rel_target->import(\$data['target']);
                    }
EOL;

        $this->assertStringContainsString($expected, $code);
    }

    /**
     *
     */
    public function test_generate_extract_on_by_inheritance_relation()
    {
        $generator = new HydratorGenerator($this->prime(), Task::repository()->mapper(), Task::class);
        $code = $generator->generate();

        $this->assertStringContainsString('\'target\' => (($__rel_target = $object->target) === null ? null : ($__rel_target instanceof \Bdf\Prime\Document ? $__rel_target->export() : ($__rel_target instanceof \Bdf\Prime\Customer ? $__rel_target->export() : $__rel_target))),', $code);
        $this->assertStringContainsString('$values[\'target\'] = ($__rel_target = $object->target) === null ? null : ($__rel_target instanceof \Bdf\Prime\Document ? $__rel_target->export() : ($__rel_target instanceof \Bdf\Prime\Customer ? $__rel_target->export() : $__rel_target));', $code);
    }

    /**
     *
     */
    public function test_generate_with_SingleTableInheritanceMapper_will_use_getters_and_setters_on_overriden_properties()
    {
        $generator = new HydratorGenerator($this->prime(), Task::repository()->mapper(), Task::class);
        $code = $generator->generate();

        $this->assertStringNotContainsString('$object->overridenProperty =', $code);
        $this->assertStringContainsString('$object->setOverridenProperty(', $code);
        $this->assertStringContainsString('$object->overridenProperty()', $code);
        $this->assertStringNotContainsString('$object->overridenProperty;', $code);
    }

    /**
     *
     */
    public function test_generate_with_inherited_mappers_can_use_properties()
    {
        $generator = new HydratorGenerator($this->prime(), DocumentControlTask::repository()->mapper(), Task::class);
        $code = $generator->generate();

        $this->assertStringContainsString('$object->overridenProperty =', $code);
        $this->assertStringContainsString('$object->overridenProperty;', $code);
    }

    /**
     *
     */
    public function test_implements_functional()
    {
        $hydrator = $this->createHydrator();

        $this->assertInstanceOf(HydratorGeneratedInterface::class, $hydrator);

        $this->assertEquals(TestEntity::class, $hydrator::supportedPrimeClassName());
        $this->assertEquals([], $hydrator::embeddedPrimeClasses());
    }

    /**
     *
     */
    public function test_hydration_functional()
    {
        $hydrator = $this->createHydrator();

        $object = new TestEntity();

        $hydrator->hydrate($object, [
            'id' => 1,
            'name' => 'Test',
            'foreign' => [
                'id' => 5,
                'name' => 'Embbed',
                'city' => 'Cavaillon'
            ]
        ]);

        $this->assertSame(1, $object->id);
        $this->assertSame('Test', $object->name);

        $this->assertSame(5, $object->foreign->id);
        $this->assertSame('Embbed', $object->foreign->name);
        $this->assertSame('Cavaillon', $object->foreign->city);

        $foreign = new TestEmbeddedEntity();

        $hydrator->hydrate($object, [
            'foreign' => $foreign
        ]);

        $this->assertSame($foreign, $object->foreign);
    }

    /**
     *
     */
    public function test_flat_hydration_functional()
    {
        $types = (new DummyPlatform())->types();
        $hydrator = $this->createHydrator();

        $object = new TestEntity();

        $hydrator->flatHydrate($object, [
            'id' => 1,
            'name' => 'Test',
            'foreign_key' => 5
        ], $types);

        $this->assertSame(1, $object->id);
        $this->assertSame('Test', $object->name);
        $this->assertSame(5, $object->foreign->id);
    }

    /**
     *
     */
    public function test_generate_setter_with_methods()
    {
        $generator = new HydratorGenerator(
            Prime::service(),
            Prime::repository(ArrayHydratorTestEntity::class)->mapper(),
            ArrayHydratorTestEntity::class
        );

        $code = $generator->generate();

        $this->assertStringContainsString('$object->setName(', $code);
        $this->assertStringContainsString('$object->setPhone(', $code);
        $this->assertStringContainsString('$object->setPassword(', $code);
        $this->assertStringContainsString('$object->setRef(', $code);
        $this->assertStringContainsString('$object->setRef2(', $code);
    }

    /**
     *
     */
    public function test_generate_hydrate_with_embedded()
    {
        $generator = new HydratorGenerator(
            Prime::service(),
            Prime::service()->repository(ArrayHydratorTestEntity::class)->mapper(),
            ArrayHydratorTestEntity::class
        );

        $code = $generator->generate();

        $this->assertStringContainsString('$__rel_ref = $object->ref', $code);
        $this->assertStringContainsString('$__rel_ref2 = $object->ref2', $code);

        $this->assertStringContainsString('$__rel_ref->import($data[\'ref\']);', $code);
        $this->assertStringContainsString('$__rel_ref2->import($data[\'ref2\']);', $code);
    }

    /**
     *
     */
    public function test_generate_with_non_importable_embed()
    {
        $this->generator = new HydratorGenerator(
            Prime::service(),
            Prime::service()->repository(ArrayHydratorTestEntity2::class)->mapper(),
            ArrayHydratorTestEntity::class
        );

        $code = $this->generator->generate();
        $hydrator = $this->createHydrator();

        $this->assertEquals([EmbeddedEntity2::class], $hydrator::embeddedPrimeClasses());
        $this->assertStringContainsString('public function __construct($__Bdf_Prime_EmbeddedEntity2_hydrator)', $code);
        $this->assertStringContainsString('$this->__Bdf_Prime_EmbeddedEntity2_hydrator->hydrate($__rel_ref, $data[\'ref\']);', $code);
        $this->assertStringContainsString('$this->__Bdf_Prime_EmbeddedEntity2_hydrator->hydrate($__rel_ref2, $data[\'ref2\']);', $code);
        $this->assertStringContainsString('$values[\'ref\'] = ($__rel_ref = $object->ref) === null ? null : ($__rel_ref instanceof \Bdf\Prime\EmbeddedEntity2 ? $this->__Bdf_Prime_EmbeddedEntity2_hydrator->extract($__rel_ref) : $__rel_ref);', $code);
    }

    /**
     *
     */
    public function test_generate_flatExtract()
    {
        $generator = new HydratorGenerator(
            Prime::service(),
            Prime::service()->repository(ArrayHydratorTestEntity::class)->mapper(),
            ArrayHydratorTestEntity::class
        );

        $code = $generator->generate();

        $this->assertStringContainsString('$data[\'ref.id\'] = $__embedded->getId();', $code);
        $this->assertStringContainsString('$data[\'ref2.id\'] = $__embedded->getId();', $code);
        $this->assertStringContainsString('$data = [\'name\' => ($object->name), \'phone\' => ($object->phone), \'password\' => ($object->getPassword())];', $code);
        $this->assertStringContainsString('$__embedded = $object->ref;', $code);
        $this->assertStringContainsString('$__embedded = $object->ref2;', $code);

        $line = [
            'if ($__embedded === null) {',
            '$__embedded = $this->__instantiator->instantiate(\'Bdf\\Prime\\EmbeddedEntity\', 1);',
            '$object->ref = $__embedded;',
            '}'
        ];

        $line = array_map(function($line) {
            return '\s*' . preg_quote($line);
        }, $line);

        $line = implode(PHP_EOL, $line);

        $this->assertMatchesRegularExpression('#' . $line . '#s', $code);
    }

    /**
     *
     */
    public function test_generate_flatExtract_with_deep_embedded()
    {
        $generator = new HydratorGenerator(
            Prime::service(),
            Prime::service()->repository(Document::class)->mapper(),
            Document::class
        );

        $code = $generator->generate();

        $this->assertStringContainsString('$__tmp_0 = $object->contact;', $code);
        $this->assertStringContainsString('$__embedded = $__tmp_0->location;', $code);

        $line = [
            'if ($__tmp_0 === null) {',
            '$__tmp_0 = $this->__instantiator->instantiate(\'Bdf\\Prime\\Contact\', 1);',
            '$object->contact = $__tmp_0;',
            '}'
        ];

        $line = array_map(function($line) {
            return '\s*' . preg_quote($line);
        }, $line);

        $line = implode(PHP_EOL, $line);

        $this->assertMatchesRegularExpression('#' . $line . '#s', $code);
    }

    /**
     *
     */
    public function test_generate_flatHydrate()
    {
        $generator = new HydratorGenerator(
            Prime::service(),
            Prime::service()->repository(ArrayHydratorTestEntity::class)->mapper(),
            ArrayHydratorTestEntity::class
        );

        $code = $generator->generate();

        $this->assertStringContainsString('$value = $typeinteger->fromDatabase($data[\'ref.id\']);', $code);
        $this->assertStringContainsString('$value = $typeinteger->fromDatabase($data[\'ref2.id\']);', $code);
        $this->assertStringContainsString('$__embedded->setId($value);', $code);
    }

    /**
     *
     */
    public function test_generate_flatHydrate_with_arrayOf()
    {
        $generator = new HydratorGenerator(
            Prime::service(),
            Prime::service()->repository(ArrayOfEntity::class)->mapper(),
            ArrayOfEntity::class
        );

        $code = $generator->generate();

        $this->assertStringContainsString('$typearrayOfinteger = $types->get(\'integer[]\');', $code);
        $this->assertStringContainsString('$value = $typearrayOfinteger->fromDatabase($data[\'values\']);', $code);
        $this->assertStringContainsString('$object->values = $value;', $code);
    }

    /**
     *
     */
    public function test_generate_extractOne()
    {
        $generator = new HydratorGenerator(
            Prime::service(),
            Prime::service()->repository(ArrayHydratorTestEntity::class)->mapper(),
            ArrayHydratorTestEntity::class
        );

        $code = $generator->generate();

        $this->assertStringContainsString('switch ($attribute)', $code);
        $this->assertStringContainsString("case 'name':", $code);
        $this->assertStringContainsString("case 'phone':", $code);
        $this->assertStringContainsString("case 'password':", $code);
        $this->assertStringContainsString("case 'ref.id':", $code);
        $this->assertStringContainsString("case 'ref2.id':", $code);
        $this->assertStringContainsString("case 'ref':", $code);
        $this->assertStringContainsString("case 'ref2':", $code);
    }

    /**
     *
     */
    public function test_extract_all_functional()
    {
        $hydrator = $this->createHydrator();

        $expected = [
            'id' => 1,
            'name' => 'Test',
            'foreign' => [
                'id' => 5,
                'name' => 'Embbed',
                'city' => 'Cavaillon'
            ],
            'dateInsert' => new DateTime()
        ];

        $object = new TestEntity($expected);

        $array = $hydrator->extract($object);

        $this->assertEquals($expected, $array);
    }

    /**
     *
     */
    public function test_extract_selected_functional()
    {
        $hydrator = $this->createHydrator();

        $expected = [
            'name' => 'Test',
            'foreign' => [
                'id' => 5,
                'name' => 'Embbed',
                'city' => 'Cavaillon'
            ]
        ];

        $object = new TestEntity([
            'id' => 1,
            'name' => 'Test',
            'foreign' => [
                'id' => 5,
                'name' => 'Embbed',
                'city' => 'Cavaillon'
            ],
            'dateInsert' => new DateTime()
        ]);

        $array = $hydrator->extract($object, ['name', 'foreign']);

        $this->assertEquals($expected, $array);
    }

    /**
     *
     */
    public function test_flat_extract_all_functional()
    {
        $hydrator = $this->createHydrator();

        $expected = [
            'id' => 1,
            'name' => 'Test',
            'foreign.id' =>  5,
            'dateInsert' => new DateTime()
        ];

        $object = new TestEntity([
            'id' => 1,
            'name' => 'Test',
            'foreign' => [
                'id' => 5,
                'name' => 'Embbed',
                'city' => 'Cavaillon'
            ],
            'dateInsert' => new DateTime()
        ]);

        $array = $hydrator->flatExtract($object);

        $this->assertEquals($expected, $array);
    }

    /**
     *
     */
    public function test_flat_extract_selected_functional()
    {
        $hydrator = $this->createHydrator();

        $expected = [
            'name' => 'Test',
            'foreign.id' => 5
        ];

        $object = new TestEntity([
            'id' => 1,
            'name' => 'Test',
            'foreign' => [
                'id' => 5,
                'name' => 'Embbed',
                'city' => 'Cavaillon'
            ],
            'dateInsert' => new DateTime()
        ]);

        $array = $hydrator->flatExtract($object, ['name' => true, 'foreign.id' => true]);

        $this->assertEquals($expected, $array);
    }

    /**
     *
     */
    public function test_flat_extract_will_create_embedded()
    {
        $hydrator = $this->createHydrator();

        $object = new TestEntity([
            'id' => 1,
            'name' => 'Test',
            'dateInsert' => new DateTime()
        ]);

        $object->foreign = null;

        $hydrator->flatExtract($object);

        $this->assertInstanceOf(TestEmbeddedEntity::class, $object->foreign);
    }

    /**
     *
     */
    public function test_extractOne_functional()
    {
        $hydrator = $this->createHydrator();

        $object = new TestEntity([
            'id' => 1,
            'name' => 'Test',
            'foreign' => [
                'id' => 5,
                'name' => 'Embbed',
                'city' => 'Cavaillon'
            ],
            'dateInsert' => new DateTime()
        ]);

        $this->assertEquals(1, $hydrator->extractOne($object, 'id'));
        $this->assertEquals('Test', $hydrator->extractOne($object, 'name'));
        $this->assertEquals(5, $hydrator->extractOne($object, 'foreign.id'));

        $this->assertEquals(new TestEmbeddedEntity([
            'id' => 5,
            'name' => 'Embbed',
            'city' => 'Cavaillon'
        ]), $hydrator->extractOne($object, 'foreign'));
    }

    /**
     *
     */
    public function test_extractOne_functional_on_not_declared_embedded_should_raise_exception()
    {
        $this->expectException(FieldNotDeclaredException::class);
        $this->expectExceptionMessage('The field "foreign.name" is not declared for the entity ' . TestEntity::class);

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

        $hydrator = $this->createHydrator();

        $this->assertNull($hydrator->extractOne($entity, "foreign.name"));
    }

    /**
     *
     */
    public function test_extractOne_on_not_declared_attribute_should_raise_exception()
    {
        $this->expectException(FieldNotDeclaredException::class);
        $this->expectExceptionMessage('The field "not_declared" is not declared for the entity ' . TestEntity::class);

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

        $hydrator = $this->createHydrator();

        $this->assertNull($hydrator->extractOne($entity, "not_declared"));
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

        $hydrator = $this->createHydrator();

        $this->assertNull($hydrator->extractOne($entity, 'foreign.id'));
    }

    /**
     *
     */
    public function test_hydrateOne_functional()
    {
        $hydrator = $this->createHydrator();

        $object = new TestEntity();

        $hydrator->hydrateOne($object, "id", 5);
        $this->assertEquals(5, $object->id);

        $hydrator->hydrateOne($object, "name", "Test");
        $this->assertEquals("Test", $object->name);

        $foreign = new TestEmbeddedEntity();
        $hydrator->hydrateOne($object, "foreign", $foreign);
        $this->assertSame($foreign, $object->foreign);

        $hydrator->hydrateOne($object, "foreign.id", 123);
        $this->assertEquals(123, $foreign->id);

        $object->foreign = null;
        $hydrator->hydrateOne($object, "foreign.id", 456);
        $this->assertEquals(456, $object->foreign->id);
    }

    /**
     *
     */
    public function test_hydrateOne_on_not_declared_embedded_attribute_should_raise_exception()
    {
        $this->expectException(FieldNotDeclaredException::class);
        $this->expectExceptionMessage('The field "foreign.name" is not declared for the entity ' . TestEntity::class);

        $entity = new TestEntity();

        $hydrator = $this->createHydrator();
        $hydrator->hydrateOne($entity, 'foreign.name', 'other');
    }

    /**
     *
     */
    public function test_hydrateOne_on_not_declared_attribute_should_raise_exception()
    {
        $this->expectException(FieldNotDeclaredException::class);
        $this->expectExceptionMessage('The field "not_declared" is not declared for the entity ' . TestEntity::class);

        $entity = new TestEntity();

        $hydrator = $this->createHydrator();
        $hydrator->hydrateOne($entity, 'not_declared', 'other');
    }

    /**
     *
     */
    public function test_functional_deep_embedded()
    {
        $types = (new DummyPlatform())->types();
        $hydrator = $this->createGeneratedHydrator(Document::class);

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

        $this->assertEquals([
            "id" => 1,
            "customerId"               => "10",
            "uploaderType"             => "user",
            "uploaderId"               => "1",
            "contact.name"             => "Holmes",
            "contact.location.address" => "221b Baker Street",
            "contact.location.city"    => "London",
        ], $hydrator->flatExtract($document));

        $this->assertEquals([
            "contact.name"             => "Holmes",
            "contact.location.address" => "221b Baker Street",
        ], $hydrator->flatExtract($document, ["contact.name" => true, "contact.location.address" => true]));

        $this->assertEquals("London", $hydrator->extractOne($document, "contact.location.city"));

        $hydrator->hydrateOne($document, "contact.location.city", "Paris");
        $this->assertEquals("Paris", $document->contact->location->city);

        $hydrator->flatHydrate($document, [
            "id_" => 741,
            "customer_id"               => "888",
            "uploader_id"               => "74",
            "contact_name"             => "Bob",
            "contact_address" => "111",
            "contact_city"    => "CAVAILLON",
        ], $types);

        $this->assertEquals(741, $document->id);
        $this->assertEquals("888", $document->customerId);
        $this->assertEquals("74", $document->uploaderId);
        $this->assertEquals("Bob", $document->contact->name);
        $this->assertEquals("111", $document->contact->location->address);
        $this->assertEquals("CAVAILLON", $document->contact->location->city);
    }

    /**
     *
     */
    public function test_functional_relation_morph()
    {
        $hydrator = $this->createGeneratedHydrator(Document::class);

        $document = new Document();

        $document->uploader = new Admin();

        $hydrator->hydrate($document, [
            "uploader"     => [
                "name" => "admin-uploader"
            ],
            "uploaderType" => "admin"
        ]);

        $this->assertEquals("admin-uploader", $document->uploader->name);

        $document->uploader = new User();
        $customer = new Customer();

        $hydrator->hydrate($document, [
            "uploader"     => [
                "name" => "user-uploader",
                "customer" => $customer
            ],
            "uploaderType" => "user"
        ]);

        $this->assertEquals("user-uploader", $document->uploader->name);
        $this->assertSame($customer, $document->uploader->customer);

        // Invalid type
        $document->uploader = new TestEntity();

        $hydrator->hydrate($document, [
            "uploader"     => [
                "name" => "test"
            ]
        ]);

        $this->assertNotEquals("test", $document->uploader->name);

        $document = new Document([
            "id" => 145,
            "customerId" => 14,
            "uploaderId" => 25,
            "uploader"     => new User([
                "id" => 25,
                "name" => "user-uploader",
                "customer" => new Customer([
                    "id" => 14,
                    "name" => "user-customer"
                ])
            ]),
            "uploaderType" => "user",
        ]);

        $this->assertEquals([
            "id" => 145,
            "customerId" => 14,
            "uploaderId" => 25,
            "uploader"     => [
                "id" => 25,
                "name" => "user-uploader",
                "customer" => [
                    "id" => 14,
                    "parentId" => null,
                    "parent" => null,
                    "name" => "user-customer",
                    "packs" => null,
                    "location" => null,
                    "locationWithConstraint" => null,
                    "documents" => null
                ],
                "roles" => null,
                "faction" => null,
                "documents" => null,
                "none" => null,
            ],
            "uploaderType" => "user",
            "contact" => null
        ], $hydrator->extract($document));
    }

    /**
     *
     */
    public function test_functional_self_reference()
    {
        $hydrator = $this->createGeneratedHydrator(Customer::class);

        $customer = new Customer([
            "name" => "base customer",
            "parent" => new Customer([
                "name" => "parent",
                "parent" => new Customer([
                    "name" => "grand parent"
                ])
            ])
        ]);

        $hydrator->hydrate($customer, [
            "parent" => [
                "parent" => [
                    "id" => 1234
                ]
            ]
        ]);

        $this->assertEquals(1234, $customer->parent->parent->id);

        $extracted = $hydrator->extract($customer, ["parent"]);

        $this->assertEquals("parent", $extracted["parent"]["name"]);
        $this->assertEquals("grand parent", $extracted["parent"]["parent"]["name"]);
    }

    /**
     *
     */
    public function test_functional_extractOne_with_relation()
    {
        $hydrator = $this->createGeneratedHydrator(Customer::class);
        $hydrator->setPrimeInstantiator($this->prime()->instantiator());

        $customer = new Customer(['name' => 'base customer']);

        $this->assertNull($hydrator->extractOne($customer, 'documents'));
        $this->assertNull($hydrator->extractOne($customer, 'parent'));
    }

    public function test_functional_hydrate_with_collection()
    {
        $folder = new Folder();
        $hydrator = $this->createGeneratedHydrator(Folder::class);

        $hydrator->hydrate($folder, [
            'files' => [
                ['name' => 'file1'],
                ['name' => 'file2'],
                ['name' => 'file3'],
            ]
        ]);

        $this->assertCount(3, $folder->files);
        $this->assertEquals([
            new TestFile(['name' => 'file1']),
            new TestFile(['name' => 'file2']),
            new TestFile(['name' => 'file3']),
        ], $folder->files->all());
    }

    /**
     *
     */
    public function test_functional_extract_with_collection()
    {
        $hydrator = $this->createGeneratedHydrator(Folder::class);
        $folder = new Folder([
            'files' => [
                ['name' => 'file1'],
                ['name' => 'file2'],
                ['name' => 'file3'],
            ]
        ]);

        $folder->files = TestFile::collection([
            new TestFile(['name' => 'file1']),
            new TestFile(['name' => 'file2']),
            new TestFile(['name' => 'file3']),
        ]);

        $this->assertEquals([
            'id'       => null,
            'name'     => null,
            'parentId' => null,
            'parent'   => null,
            'files'    => [
                [
                    'id'       => null,
                    'folderId' => null,
                    'name'     => 'file1',
                    'owner'    => ['name' => null, 'groups' => null],
                    'group'    => ['name' => null, 'users' => null]
                ],
                [
                    'id'       => null,
                    'folderId' => null,
                    'name'     => 'file2',
                    'owner'    => ['name' => null, 'groups' => null],
                    'group'    => ['name' => null, 'users' => null]
                ],
                [
                    'id'       => null,
                    'folderId' => null,
                    'name'     => 'file3',
                    'owner'    => ['name' => null, 'groups' => null],
                    'group'    => ['name' => null, 'users' => null]
                ],
            ],
        ], $hydrator->extract($folder));
    }

    /**
     *
     */
    public function test_functional_with_inheritance()
    {
        $hydrator = $this->createGeneratedHydrator(Task::class);

        $task = new DocumentControlTask();
        $task->target = new Document();

        $hydrator->hydrate($task, ['target' => ['id' => 123]]);
        $this->assertEquals(123, $task->target->id);
        $this->assertEquals([
            'target' => [
                'id' => 123,
                'customerId' => null,
                'uploaderType' => null,
                'uploaderId' => null,
                'contact' => null,
                'uploader' => null,
            ]
        ], $hydrator->extract($task, ['target']));

        $hydrator->hydrate($task, ['overridenProperty' => 'new-value']);
        $this->assertEquals(['overridenProperty' => 'new-value'], $hydrator->extract($task, ['overridenProperty']));

        $specificHydrator = $this->createGeneratedHydrator(DocumentControlTask::class);

        $specificHydrator->hydrate($task, ['target' => ['id' => 456]]);
        $this->assertEquals(456, $task->target->id);
        $this->assertEquals([
            'target' => [
                'id' => 456,
                'customerId' => null,
                'uploaderType' => null,
                'uploaderId' => null,
                'contact' => null,
                'uploader' => null,
            ]
        ], $specificHydrator->extract($task, ['target']));

        $specificHydrator->hydrate($task, ['overridenProperty' => 'other-value']);
        $this->assertEquals(['overridenProperty' => 'other-value'], $specificHydrator->extract($task, ['overridenProperty']));
    }

    /**
     *
     */
    public function test_generate_detetime_immutable_utc()
    {
        $hydrator = $this->createGeneratedHydrator(Task::class);
        $task = new DocumentControlTask();

        $hydrator->flatHydrate($task, [
            'createdAt' => '2019-06-10 10:09:20',
            'updatedAt' => '2019-06-10 10:09:20',
            'deletedAt' => '2019-06-10 10:09:20',
        ], Prime::service()->connection('test')->platform()->types());

        $this->assertInstanceOf(DateTimeImmutable::class, $task->createdAt);
        $this->assertInstanceOf(DateTimeImmutable::class, $task->updatedAt);
        $this->assertInstanceOf(\DateTime::class, $task->deletedAt);
        $this->assertEquals(new DateTimeImmutable('2019-06-10T10:09:20+00:00'), $task->createdAt);
        $this->assertEquals(new DateTimeImmutable('2019-06-10T10:09:20+00:00'), $task->updatedAt);
        $this->assertEquals(new \DateTime('2019-06-10T10:09:20+02:00'), $task->deletedAt);
    }

    /**
     *
     */
    public function test_constructor_dependency()
    {
        $hydrator = $this->createGeneratedHydrator(ComplexConstructorEntity::class);

        $entity = new ComplexConstructorEntity('1');
        $hydrator->hydrate($entity, ['id' => '2']);

        $this->assertEquals('2', $entity->id());
    }

    /**
     *
     */
    public function test_hydrate_embedded_not_importable()
    {
        $hydrator = $this->createGeneratedHydrator(Document::class);

        $document = new Document();

        $hydrator->hydrate($document, [
            'contact' => $contact = new Contact([
                'name' => 'Bob',
                'location' => new Location([
                    'address' => '127 av. Joseph Boitelet',
                    'city'    => 'Cavaillon'
                ])
            ])
        ]);

        $this->assertEquals($contact, $document->contact);
    }

    /**
     *
     */
    public function test_flatHydrate_polymorph_embedded()
    {
        $hydrator = $this->createGeneratedHydrator(PolymorphContainer::class);
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
            'sub_address' => 'My address',
            'sub_city' => 'My city'
        ], $types);

        $this->assertInstanceOf(PolymorphSubB::class, $entity->embedded());
        $this->assertSame('B', $entity->embedded()->type());
        $this->assertSame('other', $entity->embedded()->name());
        $this->assertSame('My address', $entity->embedded()->location()->address);
        $this->assertSame('My city', $entity->embedded()->location()->city);
    }

    /**
     *
     */
    public function test_flatExtract_polymorph_embedded()
    {
        $hydrator = $this->createGeneratedHydrator(PolymorphContainer::class);

        $entity = new PolymorphContainer([
            'id' => 123,
            'embedded' => new PolymorphSubA('my name')
        ]);

        $this->assertEquals([
            'id' => 123,
            'embedded.type' => 'A',
            'embedded.name' => 'my name',
            'embedded.location.address' => null,
            'embedded.location.city' => null,
        ], $hydrator->flatExtract($entity));

        $entity->setEmbedded(new PolymorphSubB('other'));
        $entity->embedded()->setLocation(new Location([
            'address' => '127 av. Joseph Boitelet'
        ]));

        $this->assertEquals([
            'id' => 123,
            'embedded.type' => 'B',
            'embedded.name' => 'other',
            'embedded.location.address' => '127 av. Joseph Boitelet',
            'embedded.location.city' => null,
        ], $hydrator->flatExtract($entity));

        $this->assertEquals([
            'embedded.name' => 'other',
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
        $hydrator = $this->createGeneratedHydrator(PolymorphContainer::class);

        $entity = new PolymorphContainer([
            'id' => 123,
            'embedded' => new PolymorphSubA('my name')
        ]);

        $this->assertEquals('my name', $hydrator->extractOne($entity, 'embedded.name'));

        $entity->setEmbedded(new PolymorphSubB('other'));
        $this->assertEquals('other', $hydrator->extractOne($entity, 'embedded.name'));

        $entity->setEmbedded(null);
        $this->assertNull($hydrator->extractOne($entity, 'embedded.name'));

        $entity->setEmbedded(new PolymorphSubA('my name'));
        $entity->embedded()->setLocation(new Location(['address' => 'my address']));
        $this->assertEquals('my address', $hydrator->extractOne($entity, 'embedded.location.address'));
    }

    /**
     *
     */
    public function test_hydrateOne_polymorph_embedded()
    {
        $hydrator = $this->createGeneratedHydrator(PolymorphContainer::class);

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
     * @return HydratorGeneratedInterface
     */
    protected function createHydrator()
    {
        $hydratorClassName = $this->generator->hydratorFullClassName();

        if (!class_exists($hydratorClassName)) {
            $code = $this->generator->generate();
            eval(str_replace('<?php', '', $code));
        }

        /** @var HydratorGeneratedInterface $hydrator */
        $hydrator = new $hydratorClassName(new ArrayHydrator());
        $hydrator->setPrimeInstantiator(Prime::service()->instantiator());

        return $hydrator;
    }
}

class TestInaccessiblePropertyEntity extends Model
{
    private $inaccessible;
}

class TestInaccessiblePropertyEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder->add('inaccessible');
    }
}

class TestPropertyNotFoundEntity extends Model
{
}

class TestPropertyNotFoundEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder->add('notFound');
    }
}


class ComplexConstructorEntity extends Model
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function id()
    {
        return $this->id;
    }
}

class ComplexConstructorEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder->string('id');
    }
}

class ArrayOfEntity extends Model
{
    /**
     * @var int[]
     */
    protected $values;
}

class ArrayOfEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder->arrayOfInt('values');
    }
}