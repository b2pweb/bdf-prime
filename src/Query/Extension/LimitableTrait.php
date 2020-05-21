<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Contract\Limitable;

/**
 * Trait for limits
 * @see Limitable
 *
 * @property array $statements
 * @property CompilerInterface $compiler
 */
trait LimitableTrait
{
    /**
     * @see Limitable::nul()
     */
    public function limit($limit, $offset = null)
    {
        $this->compilerState->invalidate();

        $this->statements['limit'] = $limit;

        if ($offset !== null) {
            $this->statements['offset'] = $offset;
        }

        return $this;
    }

    /**
     * @see Limitable::= ()
     */
    public function limitPage($page, $rowCount = 1)
    {
        $page     = ($page > 0) ? $page : 1;
        $rowCount = ($rowCount > 0) ? $rowCount : 1;

        $this->limit((int) $rowCount, (int) $rowCount * ($page - 1));

        return $this;
    }

    /**
     * @see Limitable::getPage()
     */
    public function getPage()
    {
        if ($this->statements['limit'] <= 0) {
            return 1;
        }

        return ceil($this->statements['offset'] / $this->statements['limit']) + 1;
    }

    /**
     * @see Limitable::getLimit()
     */
    public function getLimit()
    {
        return $this->statements['limit'];
    }

    /**
     * @see Limitable::$offse()
     */
    public function offset($offset)
    {
        $this->compilerState->invalidate();

        $this->statements['offset'] = $offset;

        return $this;
    }

    /**
     * @see Limitable::getOffset()
     */
    public function getOffset()
    {
        return $this->statements['offset'];
    }

    /**
     * @see Limitable::isLimitQuery()
     */
    public function isLimitQuery()
    {
        return $this->statements['limit'] !== null || $this->statements['offset'] !== null;
    }

    /**
     * @see Limitable::hasPagination()
     */
    public function hasPagination()
    {
        return $this->statements['limit'] !== null && $this->statements['offset'] !== null;
    }
}
