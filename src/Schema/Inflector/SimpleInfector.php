<?php

namespace Bdf\Prime\Schema\Inflector;

use Doctrine\Inflector\Inflector as InflectorObject;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\Inflector\Inflector;

/**
 * SimpleInfector
 */
final class SimpleInfector implements InflectorInterface
{
    /**
     * The inflector instance
     */
    private Inflector $inflector;

    public function __construct(?InflectorObject $inflector = null)
    {
        $this->inflector = $inflector ?? InflectorFactory::create()->build();
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName(string $table): string
    {
        return $this->inflector->classify($this->inflector->singularize(strtolower($table)));
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyName(string $table, string $field): string
    {
        return $this->inflector->camelize(strtolower($field));
    }

    /**
     * {@inheritdoc}
     */
    public function getSequenceName(string $table): string
    {
        return "{$table}_seq";
    }
}
