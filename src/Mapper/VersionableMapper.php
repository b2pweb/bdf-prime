<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Behaviors\Versionable;
use Bdf\Prime\Mapper\Builder\FieldBuilder;

/**
 * Class VersionableMapper
 *
 * Mapper utilisé par le Behavior Versionable
 *
 * @package Bdf\Prime\Mapper
 *
 * @template E as object
 * @extends Mapper<E>
 */
abstract class VersionableMapper extends Mapper
{
    /**
     * Get versioned class name
     *
     * @return class-string
     */
    abstract public function getVersionedClass(): string;

    /**
     * {@inheritdoc}
     */
    public function getEntityClass(): string
    {
        return $this->getVersionedClass();
    }

    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        $entitySchema = $this->getVersionedMapper()->schema();

        $entitySchema['table'] .= '_version';

        return $entitySchema;
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder->fill($this->getVersionedMapper()->fields());

        foreach ($builder as $name => $definition) {
            if (isset($definition['primary']) || ($name === Versionable::COLUMN_NAME)) {
                $builder->field($name)->primary();
            }
        }
    }

    /**
     * Get versioned mapper
     *
     * @return Mapper
     */
    private function getVersionedMapper(): Mapper
    {
        return $this->serviceLocator->repository($this->getVersionedClass())->mapper();
    }
}
