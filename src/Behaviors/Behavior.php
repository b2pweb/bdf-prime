<?php

namespace Bdf\Prime\Behaviors;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Repository\RepositoryEventsSubscriberInterface;

/**
 * Default implementation for Behavior classes
 *
 * @template E as object
 * @implements BehaviorInterface<E>
 */
class Behavior implements BehaviorInterface
{
    /**
     * {@inheritdoc}
     */
    public function changeSchema(FieldBuilder $builder): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(RepositoryEventsSubscriberInterface $notifier): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function constraints(): array
    {
        return [];
    }
}
