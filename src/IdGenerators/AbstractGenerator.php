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
     */
    private ?Mapper $mapper = null;

    /**
     * The active connection
     *
     * @var C
     */
    private ?ConnectionInterface $connection = null;

    /**
     * The last generated id
     */
    protected string|int|null $lastGeneratedId;

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
     * @return Mapper|null
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
     * @return string|int|null Returns the generated id
     * @throws PrimeException
     */
    protected function doGenerate($property, array &$data, ServiceLocator $serviceLocator): string|int|null
    {
        // to overload
        return null;
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
     */
    protected function lastGeneratedId(): string|int|null
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
