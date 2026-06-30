<?php

namespace Bdf\Prime;

use _files\TestClock;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Serializer\PaginatorNormalizer;
use Bdf\Prime\Serializer\PrimeCollectionNormalizer;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\Types\ArrayObjectType;
use Bdf\Prime\Types\ArrayType;
use Bdf\Prime\Types\BackedEnumType;
use Bdf\Prime\Types\DateTimeType;
use Bdf\Prime\Types\JsonType;
use Bdf\Prime\Types\ObjectType;
use Bdf\Prime\Types\TimestampType;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\UnitEnumType;
use Bdf\Serializer\Normalizer\ObjectNormalizer;
use Bdf\Serializer\SerializerBuilder;

use function array_replace_recursive;

/**
 * PrimeTestCase
 */
trait PrimeTestCase
{
    /**
     *
     */
    public function prime()
    {
        return Prime::service();
    }

    /**
     *
     */
    public function pack()
    {
        return TestPack::pack();
    }

    /**
     * 
     */
    public function configurePrime(array $config = [])
    {
        if ($config) {
            $this->unsetPrime();
        }

        if (!Prime::isConfigured()) {
            Prime::configure(array_replace_recursive([
//                'logger' => new PsrDecorator(new Logger()),
//                'resultCache' => new \Bdf\Prime\Cache\ArrayCache(),
                'connection' => [
                    'config' => [
                        'test' => [
                            'adapter' => 'sqlite',
                            'memory' => true
                        ],
                    ]
                ],
                'types' => [
                    'searchable_array' => ArrayType::class,
                    new JsonType(),
                    new ArrayObjectType(),
                    new ObjectType(),
                    new ArrayType(),
                    'date_utc' => new DateTimeType('date_utc', 'Y-m-d H:i:s', \DateTimeImmutable::class, new \DateTimeZone('UTC')),
                    TypeInterface::TIMESTAMP => TimestampType::class,
                    UnitEnumType::UNIT_ENUM => UnitEnumType::class,
                    BackedEnumType::STRING_ENUM => BackedEnumType::class,
                    BackedEnumType::INT_ENUM => BackedEnumType::class,
                ],
                'clock' => new TestClock(),
            ], $config));

            $serializer = SerializerBuilder::create()
                ->build();
            $serializer->getLoader()
                ->addNormalizer(new PrimeCollectionNormalizer(Prime::service()))
                ->addNormalizer(new PaginatorNormalizer())
                ->addNormalizer(new ObjectNormalizer())
            ;

            Prime::service()->setSerializer($serializer);

            Model::configure(function() { return Prime::service(); });
        }
    }

    /**
     *
     */
    public function unsetPrime()
    {
        Prime::configure(null);
        Model::configure(null);
        TestClock::reset();
    }

    /**
     *
     */
    public function primeStart(array $config = [])
    {
        $this->configurePrime($config);

        if (method_exists($this, 'declareTestData')) {
            $this->declareTestData(TestPack::pack());
        }

        TestPack::pack()->initialize();
    }

    /**
     *
     */
    public function primeReset()
    {
        TestPack::pack()->clear();
        TestClock::reset();
    }

    /**
     *
     */
    public function primeStop()
    {
        TestPack::pack()->destroy();
        TestClock::reset();
    }
}
