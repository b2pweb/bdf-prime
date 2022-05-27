<?php

namespace Bdf\Prime;

/**
 * @package Bdf\Prime
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
}
