<?php

namespace Bdf\Prime\Behaviors;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Repository\RepositoryEventsSubscriberInterface;

/**
 * Change behavior of the repository
 */
interface BehaviorInterface
{
    /**
     * Change the field definition of the entity
     *
     * @param FieldBuilder $builder
     *
     * @return void
     */
    public function changeSchema(FieldBuilder $builder): void;

    /**
     * Subscribe events on notifier
     *
     * @param RepositoryEventsSubscriberInterface $notifier
     */
    public function subscribe(RepositoryEventsSubscriberInterface $notifier): void;

    /**
     * Get behavior constraints
     * 
     * @return array
     */
    public function constraints(): array;
}
