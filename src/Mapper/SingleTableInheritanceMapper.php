<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Platform\PlatformInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Bdf\Prime\Relations\Relation;

/**
 * SingleTableInheritanceMapper
 * 
 * @package Bdf\Prime\Mapper
 */
abstract class SingleTableInheritanceMapper extends Mapper implements MapperFactoryAwareInterface
{
    /**
     * The mapper factory
     *
     * @var MapperFactory
     */
    protected $mapperFactory;

    /**
     * The discriminator column
     *
     * @var string
     */
    protected $discriminatorColumn;

    /**
     * The discriminator map of mappers
     *
     * @var array
     */
    protected $discriminatorMap = [];


    /**
     * @todo voir pour retirer le mapper factory: passer par les depots
     * {@inheritdoc}
     */
    public function setMapperFactory(MapperFactory $mapperFactory)
    {
        $this->mapperFactory = $mapperFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function relation($relationName)
    {
        $relation = parent::relation($relationName);
        
        if ($this->isDiscriminatedMapper() && $relation['type'] == Relation::BY_INHERITANCE) {
            throw new \RuntimeException('Relation type not allowed from relation "' . $relationName . '" in ' . $this->getEntityClass());
        }

        return $relation;
    }

    /**
     * {@inheritdoc}
     */
    public function customConstraints()
    {
        if ($this->isDiscriminatedMapper()) {
            return [$this->discriminatorColumn => array_search(get_called_class(), $this->discriminatorMap)];
        }

        return parent::customConstraints();
    }

    /**
     * {@inheritDoc}
     */
    public function prepareFromRepository(array $data, PlatformInterface $platform)
    {
        if ($this->isDiscriminatedMapper()) {
            return parent::prepareFromRepository($data, $platform);
        }

        return $this->getMapperByDiscriminatorValue($this->getDiscriminatorValueByRawData($data))->prepareFromRepository($data, $platform);
    }

    /**
     * Get the discriminator map of mappers
     *
     * @return array
     */
    public function getDiscriminatorMap()
    {
        return $this->discriminatorMap;
    }

    /**
     * Get the discriminator map of entities
     *
     * @return array
     */
    public function getEntityMap()
    {
        $map = [];
        $resolver = $this->mapperFactory->getNameResolver();

        foreach ($this->discriminatorMap as $key => $mapperClass) {
            $map[$key] = $resolver->reverse($mapperClass);
        }

        return $map;
    }

    /**
     * Get the discriminator column
     *
     * @return string
     */
    public function getDiscriminatorColumn()
    {
        return $this->discriminatorColumn;
    }

    /**
     * Get the mapper by a discriminator value
     *
     * @param mixed $value
     *
     * @return Mapper
     * @psalm-suppress InvalidNullableReturnType
     */
    public function getMapperByDiscriminatorValue($value)
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->mapperFactory->createMapper(
            $this->serviceLocator,
            $this->getDiscriminatorType($value)
        );
    }

    /**
     * Get the discriminator value from array of data
     *
     * @param array $data
     *
     * @return mixed
     *
     * @throws \Exception if discriminator field not present in $data
     */
    public function getDiscriminatorValueByRawData(array $data)
    {
        $discriminatorField = $this->metadata()->attributes[$this->discriminatorColumn]['field'];

        if (empty($data[$discriminatorField])) {
            throw new \Exception('Discriminator field "' . $discriminatorField . '" not found');
        }

        return $data[$discriminatorField];
    }

    /**
     * Get the mapper class from discriminator value
     *
     * @param mixed $discriminatorValue
     *
     * @return string
     *
     * @throws \Exception if discriminator value is unknown
     */
    public function getDiscriminatorType($discriminatorValue)
    {
        if (empty($this->discriminatorMap[$discriminatorValue])) {
            throw new \Exception('Unknown discriminator type "' . $discriminatorValue . '"');
        }

        return $this->discriminatorMap[$discriminatorValue];
    }

    /**
     * Check whether the class is a discriminated mapper
     *
     * @return boolean
     */
    protected function isDiscriminatedMapper()
    {
        return in_array(get_called_class(), $this->discriminatorMap);
    }
}
