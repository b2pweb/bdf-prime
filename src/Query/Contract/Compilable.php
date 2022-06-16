<?php

namespace Bdf\Prime\Query\Contract;

use Bdf\Prime\Exception\PrimeException;

/**
 * Base type for self-compile queries
 * Those queries can be auto-executed by connections
 *
 * /!\ Compilable queries are connection specific, and should be used by the declared connection
 */
interface Compilable
{
    public const TYPE_SELECT = 'select';
    public const TYPE_UPDATE = 'update';
    public const TYPE_DELETE = 'delete';
    public const TYPE_INSERT = 'insert';

    /**
     * Compile the query to connection specific object
     *
     * @param boolean $forceRecompile Force recompile the query
     *
     * @return mixed
     * @throws PrimeException When compile fail
     */
    public function compile(bool $forceRecompile = false);

    /**
     * Get the query bindings
     *
     * @return array
     * @throws PrimeException
     */
    public function getBindings(): array;

    /**
     * Get the query type
     * Must return one of the constants Compilable::TYPE_*
     *
     * @return Compilable::TYPE_*
     */
    public function type(): string;
}
