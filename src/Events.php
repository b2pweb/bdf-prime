<?php

namespace Bdf\Prime;

use Bdf\Prime\Repository\Event\AfterDelete;
use Bdf\Prime\Repository\Event\AfterInsert;
use Bdf\Prime\Repository\Event\AfterLoad;
use Bdf\Prime\Repository\Event\AfterSave;
use Bdf\Prime\Repository\Event\AfterUpdate;
use Bdf\Prime\Repository\Event\BeforeDelete;
use Bdf\Prime\Repository\Event\BeforeInsert;
use Bdf\Prime\Repository\Event\BeforeSave;
use Bdf\Prime\Repository\Event\BeforeUpdate;

/**
 * @package Bdf\Prime
 * @deprecated Will be removed in prime 3.0
 */
class Events
{
    public const POST_LOAD = 'afterLoad';

    public const PRE_SAVE = 'beforeSave';
    public const POST_SAVE = 'afterSave';

    public const PRE_INSERT = 'beforeInsert';
    public const POST_INSERT = 'afterInsert';

    public const PRE_UPDATE = 'beforeUpdate';
    public const POST_UPDATE = 'afterUpdate';

    public const PRE_DELETE = 'beforeDelete';
    public const POST_DELETE = 'afterDelete';

    /**
     * Convert legacy event name to new event class name
     * @internal Will be removed in prime 3.0
     */
    public static function eventNameToClass(string $eventName): string
    {
        switch ($eventName) {
            case self::POST_LOAD:
                return AfterLoad::class;

            case self::PRE_SAVE:
                return BeforeSave::class;

            case self::POST_SAVE:
                return AfterSave::class;

            case self::PRE_INSERT:
                return BeforeInsert::class;

            case self::POST_INSERT:
                return AfterInsert::class;

            case self::PRE_UPDATE:
                return BeforeUpdate::class;

            case self::POST_UPDATE:
                return AfterUpdate::class;

            case self::PRE_DELETE:
                return BeforeDelete::class;

            case self::POST_DELETE:
                return AfterDelete::class;

            default:
                return $eventName;
        }
    }
}
