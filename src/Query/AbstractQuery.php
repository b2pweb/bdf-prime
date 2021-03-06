<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Extension\CompilableTrait;
use Bdf\Prime\Query\Extension\ProjectionableTrait;
use Bdf\Prime\Query\Extension\SimpleWhereTrait;

/**
 * Abstract query class
 * Define standard behaviors for OrmCompiler
 */
abstract class AbstractQuery extends AbstractReadCommand implements QueryInterface
{
    use CompilableTrait;
    use ProjectionableTrait;
    use SimpleWhereTrait;


    /**
     * {@inheritdoc}
     */
    public function set($column, $value, $type = null)
    {
        return $this->setValue($column, $value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($column, $value, $type = null)
    {
        $this->statements['values']['data'][$column] = $value;
        $this->statements['values']['types'][$column] = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function find(array $criteria, $attributes = null)
    {
        if ($attributes !== null) {
            $this->select($attributes);
        }

        $this->where($criteria);

        if (
            $this instanceof Paginable
            && $this instanceof Limitable
            && $this->hasPagination()
        ) {
            return $this->paginate($this->getLimit(), $this->getPage());
        }

        return $this->all();
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function findOne(array $criteria, $attributes = null)
    {
        return $this->where($criteria)->first($attributes);
    }
}
