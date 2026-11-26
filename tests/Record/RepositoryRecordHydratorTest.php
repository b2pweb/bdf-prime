<?php

namespace Bdf\Prime\Record;

use Bdf\Prime\Customer;
use Bdf\Prime\Document;
use Bdf\Prime\Faction;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function sprintf;

class RepositoryRecordHydratorTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->primeStart();
    }

    public function declareTestData(TestPack $pack)
    {
        $pack->persist([
            $customer1 = new Customer([
                'id' => 1,
                'name' => 'John Inc.',
            ]),
            $customer2 = new Customer([
                'id' => 2,
                'name' => 'Doe Ltd.',
            ]),
            new User([
                'id' => 1,
                'name' => 'John Miller',
                'customer' => $customer1,
                'roles' => ['admin', 'user'],
            ]),
            new User([
                'id' => 2,
                'name' => 'Jane Doe',
                'customer' => $customer2,
                'roles' => ['admin', 'user'],
            ]),
            new User([
                'id' => 3,
                'name' => 'Mickey Mouse',
                'customer' => $customer1,
                'roles' => ['user'],
            ]),
        ]);
    }

    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }

    public function test_base_entity()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));

        $this->assertNull($hydrator->projection(User::class));

        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $this->assertSame($rows, $hydrator->prepare(User::class, $rows));
        $this->assertEquals(new User([
            'id' => 1,
            'name' => 'John Miller',
            'customer' => new Customer(['id' => 1]),
            'roles' => ['admin', 'user'],
        ]), $hydrator->instantiate(User::class, $rows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(User::class, $rows, $rows));
    }

    public function test_simple()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $this->assertSame(['id', 'name'], $hydrator->projection(SimpleOrmRecord::class));
        $this->assertSame($rows, $hydrator->prepare(SimpleOrmRecord::class, $rows));
        $this->assertEquals(new SimpleOrmRecord(1, 'John Miller'), $hydrator->instantiate(SimpleOrmRecord::class, $rows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(SimpleOrmRecord::class, $rows, $rows));
    }

    public function test_with_name_mapping()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $this->assertSame(['id', 'name'], $hydrator->projection(OrmRecordWithNameMapping::class));
        $this->assertSame($rows, $hydrator->prepare(OrmRecordWithNameMapping::class, $rows));
        $this->assertEquals(new OrmRecordWithNameMapping(1, 'John Miller'), $hydrator->instantiate(OrmRecordWithNameMapping::class, $rows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithNameMapping::class, $rows, $rows));
    }

    public function test_with_relation()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $expectedRows = $rows;
        $expectedRows[0]['customer'] = new Customer(['id' => 1, 'name' => 'John Inc.']);
        $expectedRows[1]['customer'] = new Customer(['id' => 2, 'name' => 'Doe Ltd.']);
        $expectedRows[2]['customer'] = new Customer(['id' => 1, 'name' => 'John Inc.']);

        $this->assertSame(['name', 'customer.id'], $hydrator->projection(OrmRecordWithRelation::class));
        $this->assertEquals($expectedRows, $hydrator->prepare(OrmRecordWithRelation::class, $rows));
        $this->assertEquals(new OrmRecordWithRelation('John Miller', new Customer(['id' => 1, 'name' => 'John Inc.'])), $hydrator->instantiate(OrmRecordWithRelation::class, $expectedRows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithRelation::class, $rows, $rows));
    }

    public function test_with_relation_implicit_type()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $expectedRows = $rows;
        $expectedRows[0]['customer'] = new Customer(['id' => 1, 'name' => 'John Inc.']);
        $expectedRows[1]['customer'] = new Customer(['id' => 2, 'name' => 'Doe Ltd.']);
        $expectedRows[2]['customer'] = new Customer(['id' => 1, 'name' => 'John Inc.']);

        $this->assertSame(['name', 'customer.id'], $hydrator->projection(OrmRecordWithRelationImplicit::class));
        $this->assertEquals($expectedRows, $hydrator->prepare(OrmRecordWithRelationImplicit::class, $rows));
        $this->assertEquals(new OrmRecordWithRelationImplicit('John Miller', new Customer(['id' => 1, 'name' => 'John Inc.'])), $hydrator->instantiate(OrmRecordWithRelationImplicit::class, $expectedRows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithRelationImplicit::class, $rows, $rows));
    }

    public function test_with_relation_transformer()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $expectedRows = $rows;
        $expectedRows[0]['customer'] = new Customer(['id' => 1, 'name' => 'John Inc.']);
        $expectedRows[1]['customer'] = new Customer(['id' => 2, 'name' => 'Doe Ltd.']);
        $expectedRows[2]['customer'] = new Customer(['id' => 1, 'name' => 'John Inc.']);

        $this->assertSame(['name', 'customer.id'], $hydrator->projection(OrmRecordWithRelationTransformer::class));
        $this->assertEquals($expectedRows, $hydrator->prepare(OrmRecordWithRelationTransformer::class, $rows));
        $this->assertEquals(new OrmRecordWithRelationTransformer('John Miller', 'John Inc. (1)'), $hydrator->instantiate(OrmRecordWithRelationTransformer::class, $expectedRows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithRelationTransformer::class, $rows, $rows));
    }

    public function test_with_relation_as_record()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $expectedRows = $rows;
        $expectedRows[0]['customer'] = new CustomerAsRecord(1, 'John Inc.');
        $expectedRows[1]['customer'] = new CustomerAsRecord(2, 'Doe Ltd.');
        $expectedRows[2]['customer'] = new CustomerAsRecord(1, 'John Inc.');

        $this->assertSame(['name', 'customer.id'], $hydrator->projection(OrmRecordWithRelationAsRecord::class));
        $this->assertEquals($expectedRows, $hydrator->prepare(OrmRecordWithRelationAsRecord::class, $rows));
        $this->assertEquals(new OrmRecordWithRelationAsRecord('John Miller', new CustomerAsRecord(1, 'John Inc.')), $hydrator->instantiate(OrmRecordWithRelationAsRecord::class, $expectedRows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithRelationAsRecord::class, $rows, $rows));
    }

    public function test_with_relation_as_record_explicitly_defined()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $expectedRows = $rows;
        $expectedRows[0]['customer'] = new CustomerAsRecord(1, 'John Inc.');
        $expectedRows[1]['customer'] = new CustomerAsRecord(2, 'Doe Ltd.');
        $expectedRows[2]['customer'] = new CustomerAsRecord(1, 'John Inc.');

        $this->assertSame(['name', 'customer.id'], $hydrator->projection(OrmRecordWithRelationAsExplicitRecord::class));
        $this->assertEquals($expectedRows, $hydrator->prepare(OrmRecordWithRelationAsExplicitRecord::class, $rows));
        $this->assertEquals(new OrmRecordWithRelationAsExplicitRecord('John Miller', new CustomerAsRecord(1, 'John Inc.')), $hydrator->instantiate(OrmRecordWithRelationAsExplicitRecord::class, $expectedRows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithRelationAsExplicitRecord::class, $rows, $rows));
    }

    public function test_with_relation_as_record_and_transformer()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $expectedRows = $rows;
        $expectedRows[0]['customer'] = new CustomerAsRecord(1, 'John Inc.');
        $expectedRows[1]['customer'] = new CustomerAsRecord(2, 'Doe Ltd.');
        $expectedRows[2]['customer'] = new CustomerAsRecord(1, 'John Inc.');

        $this->assertSame(['name', 'customer.id'], $hydrator->projection(OrmRecordWithRelationAsRecordAndTransformer::class));
        $this->assertEquals($expectedRows, $hydrator->prepare(OrmRecordWithRelationAsRecordAndTransformer::class, $rows));
        $this->assertEquals(new OrmRecordWithRelationAsRecordAndTransformer('John Miller', 'John Inc. (1)'), $hydrator->instantiate(OrmRecordWithRelationAsRecordAndTransformer::class, $expectedRows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithRelationAsRecordAndTransformer::class, $rows, $rows));
    }

    public function test_with_nullable_relation_as_record()
    {
        $this->pack()->nonPersist([
            new Faction([
                'id' => 1,
                'name' => 'Sith',
                'domain' => 'user',
                'enabled' => true,
            ]),
        ]);

        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'faction_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'faction_id' => null, 'roles_' => ',admin,user,'],
        ];

        $expectedRows = $rows;
        $expectedRows[0]['faction'] = new FactionAsRecord(1, 'Sith');
        $expectedRows[1]['faction'] = null;

        $this->assertSame(['name', 'faction.id'], $hydrator->projection(OrmRecordWithNullableRelationAsRecord::class));
        $this->assertEquals($expectedRows, $hydrator->prepare(OrmRecordWithNullableRelationAsRecord::class, $rows));
        $this->assertEquals(new OrmRecordWithNullableRelationAsRecord('John Miller', new FactionAsRecord(1, 'Sith')), $hydrator->instantiate(OrmRecordWithNullableRelationAsRecord::class, $expectedRows[0], $this->prime()->connection('test')->platform()));
        $this->assertEquals(new OrmRecordWithNullableRelationAsRecord('Jane Doe', null), $hydrator->instantiate(OrmRecordWithNullableRelationAsRecord::class, $expectedRows[1], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithNullableRelationAsRecord::class, $rows, $rows));
    }

    public function test_with_collection_relation_as_record()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(Customer::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Inc.'],
            ['id_' => 2, 'name_' => 'Doe Ltd.'],
            ['id_' => 404, 'name_' => 'Unknown'],
        ];

        $expectedRows = $rows;
        $expectedRows[0]['users'] = [new UserAsRecord(1, 'John Miller'), new UserAsRecord(3, 'Mickey Mouse')];
        $expectedRows[1]['users'] = [new UserAsRecord(2, 'Jane Doe')];
        $expectedRows[2]['users'] = [];

        $this->assertSame(['name', 'id'], $hydrator->projection(OrmRecordWithCollectionRelationAsRecord::class));
        $this->assertEquals($expectedRows, $hydrator->prepare(OrmRecordWithCollectionRelationAsRecord::class, $rows));
        $this->assertEquals(
            new OrmRecordWithCollectionRelationAsRecord('John Inc.', [new UserAsRecord(1, 'John Miller'), new UserAsRecord(3, 'Mickey Mouse')]),
            $hydrator->instantiate(OrmRecordWithCollectionRelationAsRecord::class, $expectedRows[0], $this->prime()->connection('test')->platform())
        );
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithCollectionRelationAsRecord::class, $rows, $rows));
    }

    public function test_with_implicit_type()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
            ['id_' => 2, 'name_' => 'Jane Doe', 'customer_id' => 2, 'roles_' => ',admin,user,'],
            ['id_' => 3, 'name_' => 'Mickey Mouse', 'customer_id' => 1, 'roles_' => ',user,'],
        ];

        $this->assertSame(['name', 'roles'], $hydrator->projection(OrmRecordWithImplicitType::class));
        $this->assertSame($rows, $hydrator->prepare(OrmRecordWithImplicitType::class, $rows));
        $this->assertEquals(new OrmRecordWithImplicitType('John Miller', ['admin', 'user']), $hydrator->instantiate(OrmRecordWithImplicitType::class, $rows[0], $this->prime()->connection('test')->platform()));
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithImplicitType::class, $rows, $rows));
    }

    public function test_with_embedded()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(Document::class));
        $rows = [
            ['id_' => 1, 'customer_id' => 1, 'uploader_type' => 'user', 'uploader_id' => 12, 'contact_name' => 'John', 'contact_address' => '12 rue de la Paix', 'contact_city' => 'Roubaix'],
            ['id_' => 2, 'customer_id' => 1, 'uploader_type' => 'admin', 'uploader_id' => 24, 'contact_name' => null, 'contact_address' => null, 'contact_city' => null],
        ];

        $this->assertSame(['id', 'contact.name', 'contact.location.address', 'contact.location.city'], $hydrator->projection(OrmRecordWithEmbedded::class));
        $this->assertSame($rows, $hydrator->prepare(OrmRecordWithEmbedded::class, $rows));
        $this->assertEquals(
            new OrmRecordWithEmbedded(1, new OrmContactRecord('John', new OrmLocationRecord('12 rue de la Paix', 'Roubaix'))),
            $hydrator->instantiate(OrmRecordWithEmbedded::class, $rows[0], $this->prime()->connection('test')->platform())
        );
        $this->assertEquals(
            new OrmRecordWithEmbedded(2, new OrmContactRecord(null, new OrmLocationRecord(null, null))),
            $hydrator->instantiate(OrmRecordWithEmbedded::class, $rows[1], $this->prime()->connection('test')->platform())
        );
        $this->assertSame($rows, $hydrator->finalize(OrmRecordWithEmbedded::class, $rows, $rows));
    }

    public function test_with_embedded_without_prefix()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(Document::class));
        $rows = [
            ['id_' => 1, 'customer_id' => 1, 'uploader_type' => 'user', 'uploader_id' => 12, 'contact_name' => 'John', 'contact_address' => null, 'contact_city' => null],
        ];

        $this->assertSame(['id', 'uploaderId', 'uploaderType'], $hydrator->projection(OrmRecordWithFlatEmbedded::class));
        $this->assertEquals(
            new OrmRecordWithFlatEmbedded(1, new OrmUploaderRecord(12, 'user')),
            $hydrator->instantiate(OrmRecordWithFlatEmbedded::class, $rows[0], $this->prime()->connection('test')->platform())
        );
    }

    public function test_with_embedded_custom_prefix()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(Document::class));
        $rows = [
            ['id_' => 1, 'customer_id' => 1, 'uploader_type' => 'user', 'uploader_id' => 12, 'contact_name' => 'John', 'contact_address' => '12 rue de la Paix', 'contact_city' => 'Roubaix'],
        ];

        $this->assertSame(['id', 'contact.location.address', 'contact.location.city'], $hydrator->projection(OrmRecordWithEmbeddedCustomPrefix::class));
        $this->assertEquals(
            new OrmRecordWithEmbeddedCustomPrefix(1, new OrmLocationRecord('12 rue de la Paix', 'Roubaix')),
            $hydrator->instantiate(OrmRecordWithEmbeddedCustomPrefix::class, $rows[0], $this->prime()->connection('test')->platform())
        );
    }

    public function test_with_embedded_on_entity_embedded_relation()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
        ];

        $this->assertSame(['name', 'customer.id'], $hydrator->projection(OrmRecordWithEmbeddedRelationKey::class));
        $this->assertEquals(
            new OrmRecordWithEmbeddedRelationKey('John Miller', new OrmCustomerKeyRecord(1)),
            $hydrator->instantiate(OrmRecordWithEmbeddedRelationKey::class, $rows[0], $this->prime()->connection('test')->platform())
        );
    }

    public function test_with_embedded_and_type_resolution()
    {
        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
        ];

        $this->assertSame(['id', 'roles'], $hydrator->projection(OrmRecordWithEmbeddedTypedField::class));
        $this->assertEquals(
            new OrmRecordWithEmbeddedTypedField(new OrmUserDataRecord(1, ['admin', 'user'])),
            $hydrator->instantiate(OrmRecordWithEmbeddedTypedField::class, $rows[0], $this->prime()->connection('test')->platform())
        );
    }

    public function test_error_embedded_without_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter embedded on Bdf\Prime\Record\MissingEmbeddedType must have a type or the #[Embedded] attribute must define a className.');

        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
        ];

        $hydrator->instantiate(MissingEmbeddedType::class, $rows[0], $this->prime()->connection('test')->platform());
    }

    public function test_error_no_constructor()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The record class Bdf\Prime\Record\WithoutConstructor must have a constructor');

        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
        ];

        $hydrator->instantiate(WithoutConstructor::class, $rows[0], $this->prime()->connection('test')->platform());
    }

    public function test_error_relation_without_name()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot determine relation name for parameter relation in class Bdf\Prime\Record\MissingRelationType. Set the relation name on the LoadRelation attribute, or set the relation class on the parameter type.');

        $hydrator = new RepositoryRecordHydrator($this->prime()->repository(User::class));
        $rows = [
            ['id_' => 1, 'name_' => 'John Miller', 'customer_id' => 1, 'roles_' => ',admin,user,'],
        ];

        $hydrator->instantiate(MissingRelationType::class, $rows[0], $this->prime()->connection('test')->platform());
    }
}

class SimpleOrmRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}

class OrmRecordWithNameMapping
{
    public function __construct(
        #[Field('id')]
        public readonly int $foo,
        #[Field('name')]
        public readonly string $bar,
    ) {}
}

class OrmRecordWithRelation
{
    public function __construct(
        public readonly string $name,
        #[LoadRelation(Customer::class)]
        public readonly Customer $customer,
    ) {}
}

class OrmRecordWithRelationImplicit
{
    public function __construct(
        public readonly string $name,
        #[LoadRelation]
        public readonly Customer $customer,
    ) {}
}

class OrmRecordWithRelationTransformer
{
    public function __construct(
        public readonly string $name,
        #[LoadRelation(Customer::class, transformer: [self::class, 'formatCustomer'])]
        public readonly string $customer,
    ) {}

    public static function formatCustomer(Customer $customer): string
    {
        return sprintf('%s (%d)', $customer->name, $customer->id);
    }
}

class OrmRecordWithRelationAsRecord
{
    public function __construct(
        public readonly string $name,
        #[LoadRelation('customer')]
        public readonly CustomerAsRecord $customer,
    ) {}
}

class OrmRecordWithRelationAsExplicitRecord
{
    public function __construct(
        public readonly string $name,
        #[LoadRelation(Customer::class, as: CustomerAsRecord::class)]
        public readonly CustomerRecordInterface $customer,
    ) {}
}

class OrmRecordWithRelationAsRecordAndTransformer
{
    public function __construct(
        public readonly string $name,
        #[LoadRelation(Customer::class, as: CustomerAsRecord::class, transformer: [self::class, 'formatCustomer'])]
        public readonly string $customer,
    ) {}

    public static function formatCustomer(CustomerAsRecord $customer): string
    {
        return sprintf('%s (%d)', $customer->name, $customer->id);
    }
}

class OrmRecordWithNullableRelationAsRecord
{
    public function __construct(
        public readonly string $name,
        #[LoadRelation('faction')]
        public readonly ?FactionAsRecord $faction,
    ) {}
}

class OrmRecordWithCollectionRelationAsRecord
{
    public function __construct(
        public readonly string $name,
        #[LoadRelation('users', as: UserAsRecord::class)]
        public readonly array $users,
    ) {}
}

interface CustomerRecordInterface {}

final readonly class CustomerAsRecord implements CustomerRecordInterface
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}

final readonly class FactionAsRecord
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}

final readonly class UserAsRecord
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}

class OrmRecordWithImplicitType
{
    public function __construct(
        public readonly string $name,
        public readonly array $roles,
    ) {}
}

class OrmRecordWithEmbedded
{
    public function __construct(
        public readonly int $id,
        #[Embedded]
        public readonly OrmContactRecord $contact,
    ) {}
}

class OrmContactRecord
{
    public function __construct(
        public readonly ?string $name,
        #[Embedded]
        public readonly OrmLocationRecord $location,
    ) {}
}

class OrmLocationRecord
{
    public function __construct(
        public readonly ?string $address,
        public readonly ?string $city,
    ) {}
}

class OrmRecordWithFlatEmbedded
{
    public function __construct(
        public readonly int $id,
        #[Embedded('')]
        public readonly OrmUploaderRecord $uploader,
    ) {}
}

class OrmUploaderRecord
{
    public function __construct(
        #[Field('uploaderId')]
        public readonly int $id,
        #[Field('uploaderType')]
        public readonly string $type,
    ) {}
}

class OrmRecordWithEmbeddedCustomPrefix
{
    public function __construct(
        public readonly int $id,
        #[Embedded('contact.location.')]
        public readonly OrmLocationRecord $location,
    ) {}
}

class OrmRecordWithEmbeddedRelationKey
{
    public function __construct(
        public readonly string $name,
        #[Embedded]
        public readonly OrmCustomerKeyRecord $customer,
    ) {}
}

class OrmCustomerKeyRecord
{
    public function __construct(
        public readonly int $id,
    ) {}
}

class OrmRecordWithEmbeddedTypedField
{
    public function __construct(
        #[Embedded('')]
        public readonly OrmUserDataRecord $data,
    ) {}
}

class OrmUserDataRecord
{
    public function __construct(
        public readonly int $id,
        public readonly array $roles,
    ) {}
}

class MissingEmbeddedType
{
    public function __construct(
        public readonly string $name,
        #[Embedded]
        public readonly mixed $embedded,
    ) {}
}

class WithoutConstructor {}

class MissingRelationType
{
    public function __construct(
        #[LoadRelation]
        public array $relation,
    ) {}
}
