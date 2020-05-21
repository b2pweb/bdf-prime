<?php

namespace Bdf\Prime\Query\Contract;

/**
 * Base type for self-compile queries
 * Those queries can be auto-executed by connections
 *
 * /!\ Compilable queries are connection specific, and should be used by the declared connection
 */
interface Compilable
{
    const TYPE_SELECT = 'select';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';
    const TYPE_INSERT = 'insert';

    /**
     * Compile the query to connection specific object
     *
     * @param boolean $forceRecompile Force recompile the query
     *
     * @return mixed
     */
    public function compile($forceRecompile = false);

    /**
     * Get the query bindings
     *
     * @return mixed
     */
    public function getBindings();

    /**
     * Get the query type
     * Must return one of the constants Compilable::TYPE_*
     *
     * @return string
     */
    public function type();
}
