<?php

namespace Bdf\Prime\Serializer;

use Bdf\Prime\Collection\ArrayCollection;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Pagination\EmptyPaginator;
use Bdf\Prime\Query\Pagination\Paginator;
use Bdf\Prime\Query\Pagination\Walker;
use Bdf\Prime\Query\Query;
use Bdf\Serializer\Metadata\Driver\StaticMethodDriver;
use Bdf\Serializer\Metadata\MetadataFactory;
use Bdf\Serializer\Normalizer\PropertyNormalizer;
use Bdf\Serializer\Serializer;
use Bdf\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 *
 */
class PaginatorNormalizerTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->configurePrime();
    }

    /**
     *
     */
    public function test_normalize_empty_paginator()
    {
        $this->assertEquals(
            [
                'items' => [],
                'page' => 1,
                'maxRows' => 0,
                'size' => 0,
            ],
            $this->getSerializer()->toArray(
                new EmptyPaginator()
            )
        );
    }

    /**
     *
     */
    public function test_normalize_paginator_all_properties()
    {
        $collection = new ArrayCollection([
            ['id' => 1, 'name' => 'name1'],
            ['id' => 2, 'name' => 'name2'],
        ]);

        $query = $this->createMock(Query::class);

        $query->expects($this->any())->method('all')
            ->will($this->returnValue($collection));

        $query->expects($this->any())->method('getPage')
            ->will($this->returnValue(5));

        $query->expects($this->any())->method('getLimit')
            ->will($this->returnValue(10));

        $this->assertEquals(
            [
                'items' => $collection->all(),
                'page' => 5,
                'maxRows' => 10,
                'size' => count($collection),
            ],
            $this->getSerializer()->toArray(
                new Paginator($query)
            )
        );
    }

    /**
     *
     */
    public function test_normalize_paginator_some_properties_by_include()
    {
        $collection = new ArrayCollection([
            ['id' => 1, 'name' => 'name1'],
            ['id' => 2, 'name' => 'name2'],
        ]);

        $query = $this->createMock(Query::class);

        $query->expects($this->any())->method('all')
            ->will($this->returnValue($collection));

        $query->expects($this->any())->method('getPage')
            ->will($this->returnValue(5));

        $query->expects($this->any())->method('getLimit')
            ->will($this->returnValue(10));

        $this->assertEquals(
            [
                'page' => 5,
                'size' => count($collection),
            ],
            $this->getSerializer()->toArray(
                new Paginator($query), ['include' => ['page', 'size']]
            )
        );
    }

    /**
     *
     */
    public function test_normalize_paginator_some_properties_by_exclude()
    {
        $collection = new ArrayCollection([
            ['id' => 1, 'name' => 'name1'],
            ['id' => 2, 'name' => 'name2'],
        ]);

        $query = $this->createMock(Query::class);

        $query->expects($this->any())->method('all')
            ->will($this->returnValue($collection));

        $query->expects($this->any())->method('getPage')
            ->will($this->returnValue(5));

        $query->expects($this->any())->method('getLimit')
            ->will($this->returnValue(10));

        $this->assertEquals(
            [
                'items' => $collection->all(),
                'maxRows' => 10,
                'size' => count($collection),
            ],
            $this->getSerializer()->toArray(
                new Paginator($query), ['exclude' => ['page']]
            )
        );
    }

    /**
     *
     */
    public function test_denormalize()
    {
        $this->expectException('Bdf\Serializer\Exception\UnexpectedValueException');

        $this->getSerializer()->fromArray([], Paginator::class);
    }

    /**
     *
     */
    public function test_supports()
    {
        $normalizer = new PaginatorNormalizer();

        $this->assertTrue($normalizer->supports(Walker::class));
        $this->assertFalse($normalizer->supports(stdClass::class));
    }

    /**
     * @return Serializer
     */
    private function getSerializer()
    {
        return SerializerBuilder::create()
            ->setNormalizers([
                new PaginatorNormalizer(),
                new PrimeCollectionNormalizer($this->prime()),
                new PropertyNormalizer(new MetadataFactory([new StaticMethodDriver()]))
            ])
            ->build();
    }
}
