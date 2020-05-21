<?php

namespace Bdf\Prime\Schema\Inflector;

use Doctrine\Common\Inflector\Inflector;

/**
 * SimpleInfector
 */
class SimpleInfector implements InflectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClassName($table)
    {
        return Inflector::classify(Inflector::singularize(strtolower($table)));
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyName($table, $field)
    {
        return Inflector::camelize(strtolower($field));
    }

    /**
     * {@inheritdoc}
     */
    public function getSequenceName($table)
    {
        return "${table}_seq";
    }
}