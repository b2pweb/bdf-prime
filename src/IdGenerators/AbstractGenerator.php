<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\ServiceLocator;

/**
 * AbstractGenerator
 *
 * A basic property generator. Let inheritance set its generation algorithm.
 *
 * @template C as ConnectionInterface
 * @implements GeneratorInterface<C>
 */
abstract class AbstractGenerator implements GeneratorInterface
{
    /**
     * The associated mapper
     *
     * @var Mapper
     */
    private $mapper;

    /**
     * The active connection
     *
     * @var C
     */
    private $connection;

    /**
     * The last generated id
     *
     * @var string
     */
    protected $lastGeneratedId;

    /**
     * Le primary attribute n'est effacé que s'il est vide.
     * La valeur du last insert ID sera injecté dans entity que si
     * son attribut primary aura été vidé
     *
     * @var bool
     */
    protected $hasBeenErased = true;

    /**
     * @param Mapper|null $mapper
     */
    public function __construct(?Mapper $mapper = null)
    {
        // TODO: reference cyclique
        $this->mapper = $mapper;
    }

    /**
     * Get the mapper
     *
     * @return Mapper
     */
    public function mapper()
    {
        return $this->mapper;
    }

    /**
     * Get connection
     *
     * @return C
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentConnection(ConnectionInterface $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array &$data, ServiceLocator $serviceLocator): void
    {
        $this->hasBeenErased = false;

        $property = $this->getPropertyToHydrate();

        if (empty($data[$property])) {
            $this->lastGeneratedId = $this->doGenerate($property, $data, $serviceLocator);
            $this->hasBeenErased = true;
        }
    }

    /**
     * Do the ID generation
     *
     * @param string           $property        The aimed property
     * @param array            $data            By reference
     * @param ServiceLocator   $serviceLocator
     *
     * @return string|null   Returns the generated id
     * @throws PrimeException
     */
    protected function doGenerate($property, array &$data, ServiceLocator $serviceLocator)
    {
        // to overload
    }

    /**
     * {@inheritdoc}
     */
    public function postProcess($entity): void
    {
        if (!$this->hasBeenErased) {
            return;
        }

        $propertyName = $this->getPropertyToHydrate();
        $propertyMetadata = $this->mapper->metadata()->attributes[$propertyName];
        $value = $this->lastGeneratedId();

        if (empty($propertyMetadata['phpOptions']['ignore_generator'])) {
            $value = $this->connection->fromDatabase($value, $propertyMetadata['type'], $propertyMetadata['phpOptions'] ?? []);
        }

        $this->mapper->hydrateOne($entity, $propertyName, $value);
    }

    /**
     * Get the last generated id
     *
     * @return string
     */
    protected function lastGeneratedId()
    {
        return $this->lastGeneratedId;
    }

    /**
     * Get the property name to hydrate
     *
     * Returns by default the primary property
     *
     * @return string
     */
    protected function getPropertyToHydrate()
    {
        return $this->mapper->metadata()->primary['attributes'][0];
    }
}
