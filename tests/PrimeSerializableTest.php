<?php

namespace Bdf\Prime;

use DateTime;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class PrimeSerializableTest extends TestCase
{
    use PrimeTestCase;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->configurePrime();
    }

    /**
     *
     */
    public function test_fromJson()
    {
        $this->assertEquals(
            new PrimeSerializableEntity('toto', 'toto@email.com', DateTime::createFromFormat(DateTime::ATOM, '2016-12-21T16:20:19+01:00')),
            PrimeSerializableEntity::fromJson('{"name":"toto","email":"toto@email.com","subscriptionDate":"2016-12-21T16:20:19+01:00"}')
        );
    }

    /**
     *
     */
    public function test_toJson_all()
    {
        $this->assertEquals(
            '{"name":"toto","email":"toto@email.com","subscriptionDate":"2016-12-21T16:20:19+01:00"}',
            (new PrimeSerializableEntity('toto', 'toto@email.com', DateTime::createFromFormat(DateTime::ATOM, '2016-12-21T16:20:19+01:00')))->toJson()
        );
    }

    /**
     *
     */
    public function test_toJson_option_include()
    {
        $this->assertEquals(
            '{"name":"toto","email":"toto@email.com"}',
            (new PrimeSerializableEntity('toto', 'toto@email.com', DateTime::createFromFormat(DateTime::ATOM, '2016-12-21T16:20:19+01:00')))->toJson([
                'include' => ['name', 'email']
            ])
        );
    }

    /**
     *
     */
    public function test_toArray_all()
    {
        $this->assertEquals(
            [
                'name' => 'toto',
                'email' => 'toto@email.com',
                'subscriptionDate' => '2016-12-21T16:20:19+01:00'
            ],
            (new PrimeSerializableEntity('toto', 'toto@email.com', DateTime::createFromFormat(DateTime::ATOM, '2016-12-21T16:20:19+01:00')))->toArray()
        );
    }

    /**
     *
     */
    public function test_toArray_option_include()
    {
        $this->assertEquals(
            [
                'name' => 'toto',
                'email' => 'toto@email.com'
            ],
            (new PrimeSerializableEntity('toto', 'toto@email.com', DateTime::createFromFormat(DateTime::ATOM, '2016-12-21T16:20:19+01:00')))->toArray([
                'include' => ['name', 'email']
            ])
        );
    }
}
