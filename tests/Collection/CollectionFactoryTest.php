<?php

namespace Bdf\Prime\Collection;

use Bdf\Prime\Repository\RepositoryInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class CollectionFactoryTest extends TestCase
{
    /**
     *
     */
    public function test_forDbal()
    {
        $this->assertInstanceOf(CollectionFactory::class, CollectionFactory::forDbal());
        $this->assertSame(CollectionFactory::forDbal(), CollectionFactory::forDbal());
    }

    /**
     *
     */
    public function test_forRepository()
    {
        $repository = $this->createMock(RepositoryInterface::class);
        $repository->expects($this->any())
            ->method('collection')
            ->willReturn($return = new EntityCollection($repository))
        ;

        $factory = CollectionFactory::forRepository($repository);

        $this->assertInstanceOf(CollectionFactory::class, $factory);

        $collection = $factory->wrap([], 'collection');

        $this->assertSame($repository, $collection->repository());
        $this->assertSame($return, $collection);
        $this->assertEquals($collection, $factory->wrap([], EntityCollection::class));
    }

    /**
     *
     */
    public function test_register_wrapper_alias()
    {
        $factory = CollectionFactory::forDbal();

        $factory->registerWrapperAlias('object', CustomWrapper::class);

        $collection = $factory->wrap([
            ['id' => 1]
        ], 'object');

        $this->assertInstanceOf(CustomWrapper::class, $collection);
        $this->assertEquals(1, $collection[0]['id']);
    }

    /**
     *
     */
    public function test_register_wrapper_alias_with_factory()
    {
        $factory = CollectionFactory::forDbal();

        $factory->registerWrapperAlias('my_custom_collection_with_factory', CustomWrapper::class, function () {
            return new CustomWrapper(['hello', 'world']);
        });

        $this->assertEquals(new CustomWrapper(['hello', 'world']), $factory->wrap([['id' => 1]], 'my_custom_collection_with_factory'));
        $this->assertEquals(new CustomWrapper(['hello', 'world']), $factory->wrap([['id' => 1]], CustomWrapper::class));
    }

    /**
     *
     */
    public function test_with_closure()
    {
        $factory = CollectionFactory::forDbal();

        $isCalled = false;
        $argument = null;

        $collection = $factory->wrap([['id' => 1]], function ($data) use(&$isCalled, &$argument) {
            $isCalled = true;
            $argument = $data;
            return new CustomWrapper($data);
        });

        $this->assertTrue($isCalled);
        $this->assertInstanceOf(CustomWrapper::class, $collection);
        $this->assertEquals(1, $collection[0]['id']);
    }

    /**
     *
     */
    public function test_wrapperClass_closure()
    {
        $this->expectException(\InvalidArgumentException::class);

        $factory = CollectionFactory::forDbal();
        $factory->registerWrapperAlias('my_wrapper', function () {});
        $factory->wrapperClass('my_wrapper');
    }

    /**
     *
     */
    public function test_wrapperClass_className()
    {
        $factory = CollectionFactory::forDbal();

        $factory->registerWrapperAlias('my_wrapper', ArrayCollection::class);

        $this->assertEquals(ArrayCollection::class, $factory->wrapperClass('my_wrapper'));
    }

    /**
     *
     */
    public function test_wrapperClass_className_with_factory()
    {
        $factory = CollectionFactory::forDbal();

        $factory->registerWrapperAlias('my_wrapper_with_factory', CustomWrapper::class, function ($data) {
            return new CustomWrapper(['hello' => 'world']);
        });

        $this->assertEquals(CustomWrapper::class, $factory->wrapperClass('my_wrapper_with_factory'));
    }

    /**
     *
     */
    public function test_wrapperClass_no_alias()
    {
        $factory = CollectionFactory::forDbal();

        $this->assertEquals(ArrayCollection::class, $factory->wrapperClass(ArrayCollection::class));
    }
}

class CustomWrapper extends ArrayCollection {}
