<?php

namespace Bdf\Prime\Serializer;

use Bdf\Prime\Query\Pagination\EmptyPaginator;
use Bdf\Prime\Query\Pagination\Paginator;
use Bdf\Prime\Query\Pagination\PaginatorInterface;
use Bdf\Prime\Query\Pagination\Walker;
use Bdf\Serializer\Context\DenormalizationContext;
use Bdf\Serializer\Context\NormalizationContext;
use Bdf\Serializer\Exception\UnexpectedValueException;
use Bdf\Serializer\Normalizer\AutoRegisterInterface;
use Bdf\Serializer\Normalizer\NormalizerInterface;
use Bdf\Serializer\Normalizer\NormalizerLoaderInterface;
use Bdf\Serializer\Type\Type;

/**
 * Class PaginatorNormalizer
 */
class PaginatorNormalizer implements NormalizerInterface, AutoRegisterInterface
{
    /**
     * @param PaginatorInterface $data
     *
     * {@inheritdoc}
     */
    public function normalize($data, NormalizationContext $context)
    {
        $class = get_class($data);
        $values = [];

        if ($context->shouldNormalizeProperty($class, 'items')) {
            $values['items'] = $context->root()->normalize($data->collection(), $context);
        }

        if ($context->shouldNormalizeProperty($class, 'page')) {
            $values['page'] = $data->page();
        }

        if ($context->shouldNormalizeProperty($class, 'maxRows')) {
            $values['maxRows'] = $data->pageMaxRows();
        }

        if ($context->shouldNormalizeProperty($class, 'size')) {
            $values['size'] = $data->size();
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, Type $type, DenormalizationContext $context)
    {
        throw new UnexpectedValueException('Paginator cannot be denormalized');
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $className): bool
    {
        return is_subclass_of($className, PaginatorInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function registerTo(NormalizerLoaderInterface $loader): void
    {
        $loader
            ->associate(EmptyPaginator::class, $this)
            ->associate(Paginator::class, $this)
            ->associate(Walker::class, $this)
        ;
    }
}
