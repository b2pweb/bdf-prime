<?php

namespace Bdf\Prime\Serializer;

use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\ServiceLocator;
use Bdf\Serializer\Context\DenormalizationContext;
use Bdf\Serializer\Normalizer\AutoRegisterInterface;
use Bdf\Serializer\Normalizer\NormalizerLoaderInterface;
use Bdf\Serializer\Normalizer\TraversableNormalizer;
use Bdf\Serializer\Type\Type;
use Bdf\Serializer\Type\TypeFactory;

/**
 * Class PrimeCollectionNormalizer
 */
class PrimeCollectionNormalizer extends TraversableNormalizer implements AutoRegisterInterface
{
    /**
     * @var ServiceLocator
     */
    private $prime;


    /**
     * PrimeCollectionNormalizer constructor.
     *
     * @param ServiceLocator $prime
     */
    public function __construct(ServiceLocator $prime)
    {
        $this->prime = $prime;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, Type $type, DenormalizationContext $context)
    {
        foreach ($data as $key => $value) {
            $data[$key] = $context->root()->denormalize(
                $value,
                $type->isParametrized() ? $type->subType() : TypeFactory::mixedType(),
                $context
            );
        }

        if ($type->isParametrized() && $repository = $this->prime->repository($type->subType()->name())) {
            $factory = $repository->collectionFactory();
        } else {
            $factory = CollectionFactory::forDbal();
        }

        return $factory->wrap($data, $type->name());
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $className): bool
    {
        return is_subclass_of($className, CollectionInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function registerTo(NormalizerLoaderInterface $loader): void
    {
        foreach (CollectionFactory::collections() as $collection) {
            $loader->associate($collection, $this);
        }
    }
}
