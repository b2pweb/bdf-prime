<?php

namespace Bdf\Prime\Behaviors;

use Bdf\Prime\Mapper\Builder\FieldBuilder;

/**
 * Behavior
 *
 * @package Bdf\Prime\Behaviors
 */
class Behavior implements BehaviorInterface
{
    /**
     * {@inheritdoc}
     */
    public function changeSchema(FieldBuilder $builder)
    {
        
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe($notifier)
    {
        
    }
    
    /**
     * {@inheritdoc}
     */
    public function constraints()
    {
        return [];
    }
}
