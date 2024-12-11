<?php

namespace StaticAnalysis;

use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Relations\EntityRelation;

class Query
{
    public function getFromRepository(): void
    {
        $this->checkPerson(Person::repository()->findById('123'));
        $this->checkPerson(Person::repository()->get('123'));
        $this->checkPerson(Person::repository()->findOne(['firstName' => 'John']));
        $this->checkPersonNotNull(Person::repository()->getOrFail('123'));
        $this->checkPersonNotNull(Person::repository()->getOrNew('123'));
    }

    public function getFromStaticCall(): void
    {
        $this->checkPerson(Person::findById('123'));
        $this->checkPerson(Person::findById(123));
        $this->checkPerson(Person::get('123'));
        $this->checkPerson(Person::findOne(['firstName' => 'John']));
        $this->checkPersonNotNull(Person::getOrFail('123'));
        $this->checkPersonNotNull(Person::getOrNew('123'));
    }

    public function simpleQuery(): void
    {
        $this->checkPerson(Person::repository()->where('firstName', 'John')->first());
        $this->checkPerson(Person::where('firstName', 'John')->first());
        $this->checkPerson(Person::repository()->with('foo')->first());
        $this->checkPerson(Person::with('foo')->first());
        $this->checkPerson(Person::repository()->by('firstName')->first());
        $this->checkPerson(Person::by('firstName')->first());
        $this->checkPerson(Person::where('firstName', 'John')->findById(42));
        $this->checkPersonNotNull(Person::where('firstName', 'John')->findByIdOrFail(42));
        $this->checkPersonNotNull(Person::where('firstName', 'John')->findByIdOrNew(42));
        $this->checkPersonNotNull(Person::by('firstName')->firstOrFail());
        $this->checkPersonNotNull(Person::by('firstName')->firstOrNew());
    }

    public function complexQuery(): void
    {
        $this->checkPerson(
            Person::where('foo', 'bar')
                ->orWhere(function (QueryInterface $query) {
                    $query->where('firstName', '');
                })
                ->by('id')
                ->first()
        );
        $this->checkPerson(
            Person::where('foo', 'bar')
                ->orWhere(function (QueryInterface $query) {
                    $query->where('firstName', '');
                })
                ->get('123')
        );
        $this->checkPerson(
            Person::repository()->where('foo', 'bar')
                ->orWhere(function (QueryInterface $query) {
                    $query->where('firstName', '');
                })
                ->get('123')
        );
        $this->checkPerson(
            Person::repository()
                ->filter(fn (Person $person) => $person->getFirstName() === 'John' || $person->getLastName() === 'Doe')
                ->get('123')
        );
        $this->checkPerson(
            Person::filter(fn (Person $person) => $person->getFirstName() === 'John' || $person->getLastName() === 'Doe')
                ->get('123')
        );
    }

    public function collection(): void
    {
        $this->checkPersonCollection(Person::collection([new Person()]));
        $this->checkPersonCollection(Person::repository()->all());
        $this->checkPersonCollection(Person::where('firstName', 'John')->all());
        $this->checkAddressCollection(Person::collection([new Person()])->link(Address::class)->all());
    }

    public function test_pagination(): void
    {
        /** @var \Bdf\Prime\Query\Query<SimpleConnection, Person> $query */
        $query = Person::where('firstName', 'John');

        foreach ($query->paginate() as $entity) {
            $this->checkPersonNotNull($entity);
        }

        foreach ($query->walk() as $entity) {
            $this->checkPersonNotNull($entity);
        }
    }

    public function utilityMethods(): void
    {
        $this->checkInt(Person::count());
        $this->checkInt(Person::repository()->count());
        $this->checkInt(Person::updateBy(['firstName' => 'XXX'], ['firstName' => 'John']));
        $this->checkInt(Person::repository()->updateBy(['firstName' => 'XXX'], ['firstName' => 'John']));
        $this->checkBool(Person::exists(new Person()));
        $this->checkBool(Person::repository()->exists(new Person()));
        $this->checkPerson(Person::refresh(new Person()));
        $this->checkPerson(Person::repository()->refresh(new Person()));
    }

    public function test_relation(): void
    {
        /** @var EntityRelation<Person, Address> */
        $relation = Person::getOrFail(123)->relation('address');

        $this->checkAddress($relation->first());
        $this->checkAddress($relation->get(123));
        $this->checkAddress($relation->findById(123));
        $this->checkAddressNotNull($relation->findByIdOrFail(123));
        $this->checkAddressNotNull($relation->findByIdOrNew(123));
        $this->checkAddressNotNull($relation->getOrFail(123));
        $this->checkAddressNotNull($relation->getOrNew(123));
        $this->checkAddressCollection($relation->all());
        $this->checkAddress($relation->with('foo')->first());
        $this->checkAddress($relation->by('zipCode')->first());
        $this->checkAddress($relation->where('zipCode', '84660')->first());
        $this->checkAddressNotNull($relation->where('zipCode', '84660')->firstOrFail());
        $this->checkAddressNotNull($relation->where('zipCode', '84660')->firstOrNew());
        $this->checkAddressCollection($relation->by('zipCode')->all());
        $this->checkAddress($relation->create());
        $this->checkInt($relation->count());
    }

    public function test_relation_with_class(): void
    {
        $relation = Person::getOrFail(123)->relation(Address::class);

        $this->checkAddress($relation->first());
        $this->checkAddress($relation->get(123));
        $this->checkAddress($relation->findById(123));
        $this->checkAddressNotNull($relation->findByIdOrFail(123));
        $this->checkAddressNotNull($relation->findByIdOrNew(123));
        $this->checkAddressNotNull($relation->getOrFail(123));
        $this->checkAddressNotNull($relation->getOrNew(123));
        $this->checkAddressCollection($relation->all());
        $this->checkAddress($relation->with('foo')->first());
        $this->checkAddress($relation->by('zipCode')->first());
        $this->checkAddress($relation->where('zipCode', '84660')->first());
        $this->checkAddressNotNull($relation->where('zipCode', '84660')->firstOrFail());
        $this->checkAddressNotNull($relation->where('zipCode', '84660')->firstOrNew());
        $this->checkAddressCollection($relation->by('zipCode')->all());
        $this->checkAddress($relation->create());
        $this->checkInt($relation->count());
    }

    public function checkPerson(?Person $person): void {}
    public function checkPersonNotNull(Person $person): void {}

    /**
     * @param iterable<Person> $person
     */
    public function checkPersonCollection(iterable $person): void {}
    public function checkInt(int $v): void {}
    public function checkBool(bool $v): void {}

    public function checkAddress(?Address $address): void {}
    public function checkAddressNotNull(Address $address): void {}

    /**
     * @param iterable<Address> $addresses
     */
    public function checkAddressCollection(iterable $addresses): void {}
}
