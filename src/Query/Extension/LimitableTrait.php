<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Contract\Limitable;

/**
 * Trait for limits
 * @see Limitable
 *
 * @psalm-require-implements Limitable
 *
 * @property array $statements
 * @property CompilerState $compilerState
 */
trait LimitableTrait
{
    /**
     * {@inheritdoc}
     *
     * @see Limitable::limit()
     */
    public function limit(?int $limit, ?int $offset = null)
    {
        $this->compilerState->invalidate();

        $this->statements['limit'] = $limit;

        if ($offset !== null) {
            $this->statements['offset'] = $offset;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see Limitable::limitPage()
     */
    public function limitPage(int $page, int $rowCount = 1)
    {
        $page     = ($page > 0) ? $page : 1;
        $rowCount = ($rowCount > 0) ? $rowCount : 1;

        $this->limit($rowCount, $rowCount * ($page - 1));

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see Limitable::getPage()
     */
    public function getPage(): int
    {
        if ($this->statements['limit'] <= 0) {
            return 1;
        }

        return (int) ceil($this->statements['offset'] / $this->statements['limit']) + 1;
    }

    /**
     * {@inheritdoc}
     *
     * @see Limitable::getLimit()
     */
    public function getLimit(): ?int
    {
        return $this->statements['limit'];
    }

    /**
     * {@inheritdoc}
     *
     * @see Limitable::offset()
     */
    public function offset(?int $offset)
    {
        $this->compilerState->invalidate();

        $this->statements['offset'] = $offset;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see Limitable::getOffset()
     */
    public function getOffset(): ?int
    {
        return $this->statements['offset'];
    }

    /**
     * {@inheritdoc}
     *
     * @see Limitable::isLimitQuery()
     */
    public function isLimitQuery(): bool
    {
        return $this->statements['limit'] !== null || $this->statements['offset'] !== null;
    }

    /**
     * {@inheritdoc}
     *
     * @see Limitable::hasPagination()
     */
    public function hasPagination(): bool
    {
        return $this->statements['limit'] !== null && $this->statements['offset'] !== null;
    }
}
