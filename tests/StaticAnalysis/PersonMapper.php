<?php

namespace StaticAnalysis;

use Bdf\Prime\Behaviors\BehaviorInterface;
use Bdf\Prime\Behaviors\Timestampable;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use Bdf\Prime\Query\Contract\Whereable;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\RepositoryEventsSubscriberInterface;
use Bdf\Prime\Repository\RepositoryInterface;
use Ramsey\Uuid\Type\Time;

/**
 * @extends Mapper<Person>
 */
class PersonMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'person',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')->autoincrement()
            ->string('firstName')->alias('first_name')
            ->string('lastName')->alias('last_name')
            ->dateTime('birthDate')->alias('birth_date')->nillable()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function scopes(): array
    {
        return [
            'formattedName' =>
                /**
                 * @param QueryInterface<SimpleConnection, Person> $query
                 * @param numeric $id
                 */
                function (QueryInterface $query, $id): ?string {
                    if ($person = $query->where('id', $id)->first(['firstName', 'lastName'])) {
                        return $person->getFirstName() . ' ' . $person->getLastName();
                    }

                    return null;
                }
            ,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function queries(): array
    {
        return [
            'byFormattedName' => function (RepositoryInterface $repository, string $name): ?Person {
                [$firstName, $lastName] = explode(' ', $name);

                /** @var KeyValueQueryInterface<ConnectionInterface, Person> $query */
                $query = $repository->queries()->make(KeyValueQueryInterface::class);

                return $query
                    ->where('firstName', $firstName)
                    ->where('lastName', $lastName)
                    ->first()
                ;
            },
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function filters(): array
    {
        return [
            'name' => function (Whereable $query, string $name): void {
                [$firstName, $lastName] = explode(' ', $name);

                $query
                    ->where('firstName', $firstName)
                    ->where('lastName', $lastName)
                ;
            }
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function customEvents(RepositoryEventsSubscriberInterface $notifier): void
    {
        $notifier->inserting(function (Person $person): bool {
            return $person->getBrithDate() > new \DateTime();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinedBehaviors(): array
    {
        /** @var Timestampable<Person> $ts */
        $ts = new Timestampable();

        return [$ts];
    }
}
