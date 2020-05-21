<?php

namespace Bdf\Prime\Behaviors;

use Bdf\Event\EventNotifier;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\ServiceLocator;

/**
 * BehaviorInterface
 *
 * @package Bdf\Prime\Behaviors
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
    public function changeSchema(FieldBuilder $builder);

    /**
     * Subscribe events on notifier
     *
     * @param EventNotifier $notifier
     */
    public function subscribe($notifier);
    
    /**
     * Get behavior constraints
     * 
     * @return array
     */
    public function constraints();
}
