<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Behaviors\Versionable;

/**
 * Class VersionableMapper
 *
 * Mapper utilisé par le Behavior Versionable
 *
 * @package Bdf\Prime\Mapper
 */
abstract class VersionableMapper extends Mapper
{
    /**
     * Get versioned class name
     *
     * @return string
     */
    abstract public function getVersionedClass();

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return $this->getVersionedClass();
    }

    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        $entitySchema = $this->getVersionedMapper()->schema();

        $entitySchema['table'] .= '_version';

        return $entitySchema;
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
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
    private function getVersionedMapper()
    {
        return $this->serviceLocator->repository($this->getVersionedClass())->mapper();
    }
}
