<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\InitializableInterface;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Relations\Builder\RelationBuilder;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\RepositoryEventsSubscriberInterface;
use Bdf\Prime\ValueObject\BaseInteger;
use Bdf\Prime\ValueObject\BaseString;
use Bdf\Prime\ValueObject\ValueObjectInvalidValueException;
use DateTimeImmutable;

final class TestEntityId extends BaseInteger
{
    protected function __construct(int $value)
    {
        parent::__construct($value);

        if ($value < 0) {
            throw new ValueObjectInvalidValueException(self::class, 'The id must be positive');
        }
    }
}

final class TestEntityName extends BaseString
{
    protected function __construct(string $value)
    {
        parent::__construct($value);

        if (empty($value)) {
            throw new ValueObjectInvalidValueException(self::class, 'The name must not be empty');
        }

        if (strlen($value) > 32) {
            throw new ValueObjectInvalidValueException(self::class, 'The name must be less than 32 characters');
        }
    }
}

class TestEntityWithValueObject extends Model implements InitializableInterface
{
    public ?TestEntityId $id = null;
    public TestEntityName $name;
    public ?DateTimeImmutable $dateInsert = null;

    public function __construct(array $attributes = [])
    {
        $this->initialize();
        $this->import($attributes);
    }

    public function initialize(): void
    {

    }
}

class TestEntityWithValueObjectMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'database' => 'test',
            'table' => 'test_value_object',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()->valueObject(TestEntityId::class)
            ->string('name')->valueObject(TestEntityName::class)

//            ->embedded('foreign', 'Bdf\Prime\TestEmbeddedEntity', function($builder) {
//                $builder->integer('id')->alias('foreign_key')->nillable();
//            })

            ->datetime('dateInsert')->alias('date_insert')->phpClass(DateTimeImmutable::class)->nillable()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
//        $builder->on('foreign')
//            ->belongsTo('Bdf\Prime\TestEmbeddedEntity', 'foreign.id');
    }

    /**
     * {@inheritdoc}
     */
    public function filters(): array
    {
        return [
            'idLike' => function($query, string $id) {
                $query->where(['id :like' => $id . '%']);
            },
            'nameLike' => function($query, string $search) {
                $query->where(['name :like' => '%' . $search]);
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scopes(): array
    {
        return [
            'testScope' => function($query) {
                return $query->limit(1)->execute(['test' => 1]);
            }
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function queries(): array
    {
        return [
            'testQuery' => function (EntityRepository $repository, $id) {
                return $repository->make(KeyValueQuery::class)->where('id', $id)->limit(1);
            }
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function customEvents(RepositoryEventsSubscriberInterface $notifier): void
    {
        $notifier->listen('afterLoad', function($entity) {
            if ($entity->name === 'event') {
                $entity->name = 'loaded';
            }
        });
    }
}

class Street extends BaseString
{
    protected function __construct(string $value)
    {
        parent::__construct($value);

        if (empty($value)) {
            throw new ValueObjectInvalidValueException(self::class, 'The street must not be empty');
        }
    }
}

class City extends BaseString
{
    protected function __construct(string $value)
    {
        parent::__construct($value);

        if (empty($value)) {
            throw new ValueObjectInvalidValueException(self::class, 'The city must not be empty');
        }
    }
}

class ZipCode extends BaseString
{
    protected function __construct(string $value)
    {
        parent::__construct($value);

        if (empty($value)) {
            throw new ValueObjectInvalidValueException(self::class, 'The zip code must not be empty');
        }
    }
}

class Country extends BaseString
{
    protected function __construct(string $value)
    {
        parent::__construct($value);

        if (empty($value)) {
            throw new ValueObjectInvalidValueException(self::class, 'The country must not be empty');
        }
    }
}

class Name extends BaseString
{
    protected function __construct(string $value)
    {
        parent::__construct($value);

        if (empty($value)) {
            throw new ValueObjectInvalidValueException(self::class, 'The name must not be empty');
        }
    }
}

class PersonId extends BaseInteger
{
    protected function __construct(int $value)
    {
        parent::__construct($value);

        if ($value < 0) {
            throw new ValueObjectInvalidValueException(self::class, 'The id must be positive');
        }
    }
}

class AddressWithValueObject
{
    public ?Street $street = null;
    public ?City $city = null;
    public ?ZipCode $zip = null;
    public ?Country $country = null;
}

class PersonWithValueObject extends Model implements InitializableInterface
{
    public ?PersonId $id = null;
    public ?Name $firstName = null;
    public ?Name $lastName = null;
    public AddressWithValueObject $address;

    public function __construct(array $data = [])
    {
        $this->initialize();
        $this->import($data);
    }

    public function initialize(): void
    {
        $this->address = new AddressWithValueObject();
    }
}

class PersonWithValueObjectMapper extends Mapper
{
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'person_value_object',
        ];
    }

    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()->valueObject(PersonId::class)
            ->string('firstName')->valueObject(Name::class)->alias('first_name')->setDefault('John')
            ->string('lastName')->valueObject(Name::class)->alias('last_name')->setDefault('Doe')
            ->embedded('address', AddressWithValueObject::class, function (FieldBuilder $builder) {
                $builder
                    ->string('street')->valueObject(Street::class)->alias('address_street')
                    ->string('city')->valueObject(City::class)->alias('address_city')
                    ->string('zip')->valueObject(ZipCode::class)->alias('address_zip')
                    ->string('country')->valueObject(Country::class)->alias('address_country')
                ;
            })
        ;
    }
}
