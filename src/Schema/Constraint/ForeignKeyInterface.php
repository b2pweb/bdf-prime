<?php

namespace Bdf\Prime\Schema\Constraint;

use Bdf\Prime\Schema\ConstraintInterface;

/**
 * Interface for foreign key
 *
 * @todo Deferred  : http://sqlpro.developpez.com/cours/sqlaz/ddl/?page=partie2#L7.4
 */
interface ForeignKeyInterface extends ConstraintInterface
{
    /** Applied ONLY if there is no NULL values */
    const MATCH_SIMPLE  = 'SIMPLE';
    /** Applied on all NOT NULL values */
    const MATCH_PARTIAL = 'PARTIAL';
    /** Always applied unless all values are NULL */
    const MATCH_FULL    = 'FULL';

    const MODE_NO_ACTION   = 'NO ACTION';
    const MODE_CASCADE     = 'CASCADE';
    const MODE_SET_NULL    = 'SET NULL';
    const MODE_SET_DEFAULT = 'SET DEFAULT';
    const MODE_RESTRICT    = 'RESTRICT';


    /**
     * Get list of "local" fields
     *
     * @return string[]
     */
    public function fields(): array;

    /**
     * Get the match mode
     *
     * @return ForeignKeyInterface::MATCH_*
     *
     * @link http://sqlpro.developpez.com/cours/sqlaz/ddl/?page=partie2#L7.3.1
     */
    public function match(): string;

    /**
     * Get the referred table name
     *
     * @return string
     */
    public function table(): string;

    /**
     * Get list of referred column (i.e. the "source" field of the constraint)
     *
     * @return string[]
     */
    public function referred(): array;

    /**
     * Get the ON DELETE mode
     *
     * @return string
     */
    public function onDelete(): string;

    /**
     * Get the ON UPDATE mode
     *
     * @return string
     */
    public function onUpdate(): string;
}
