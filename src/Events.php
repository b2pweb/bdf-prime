<?php

namespace Bdf\Prime;

/**
 * @package Bdf\Prime
 */
class Events
{
    const POST_LOAD = 'afterLoad';
    
    const PRE_SAVE = 'beforeSave';
    const POST_SAVE = 'afterSave';

    const PRE_INSERT = 'beforeInsert';
    const POST_INSERT = 'afterInsert';
    
    const PRE_UPDATE = 'beforeUpdate';
    const POST_UPDATE = 'afterUpdate';
    
    const PRE_DELETE = 'beforeDelete';
    const POST_DELETE = 'afterDelete';
}
