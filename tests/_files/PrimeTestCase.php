<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Serializer\PaginatorNormalizer;
use Bdf\Prime\Serializer\PrimeCollectionNormalizer;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\Types\ArrayObjectType;
use Bdf\Prime\Types\ArrayType;
use Bdf\Prime\Types\DateTimeType;
use Bdf\Prime\Types\JsonType;
use Bdf\Prime\Types\ObjectType;
use Bdf\Prime\Types\TimestampType;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Serializer\Normalizer\ObjectNormalizer;
use Bdf\Serializer\SerializerBuilder;

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
        if (!Prime::isConfigured()) {
            Prime::configure($config + [
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
                ]
            ]);

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
    }

    /**
     *
     */
    public function primeStart()
    {
        $this->configurePrime();

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
    }

    /**
     *
     */
    public function primeStop()
    {
        TestPack::pack()->destroy();
    }
}