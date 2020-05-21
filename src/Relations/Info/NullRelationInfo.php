<?php

namespace Bdf\Prime\Relations\Info;

/**
 * Null object for storing relation information
 */
final class NullRelationInfo implements RelationInfoInterface
{
    static private $instance;

    /**
     * {@inheritdoc}
     */
    public function isLoaded($entity)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($entity)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function markAsLoaded($entity)
    {
    }

    /**
     * @return NullRelationInfo
     */
    public static function instance()
    {
        if (self::$instance === null)  {
            return self::$instance = new self;
        }

        return self::$instance;
    }
}
