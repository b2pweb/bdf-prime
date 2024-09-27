<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\ServiceLocator;

/**
 * ComposableGenerator
 *
 * @template C as ConnectionInterface
 * @implements GeneratorInterface<C>
 */
class ComposableGenerator implements GeneratorInterface
{
    /**
     * @var GeneratorInterface[]
     */
    private $generators;

    /**
     * ComposableGenerator constructor.
     *
     * @param GeneratorInterface[] $generators
     */
    public function __construct(array $generators)
    {
        $this->generators = $generators;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array &$data, ServiceLocator $serviceLocator): void
    {
        foreach ($this->generators as $generator) {
            $generator->generate($data, $serviceLocator);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postProcess($entity): void
    {
        foreach ($this->generators as $generator) {
            $generator->postProcess($entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setCurrentConnection(ConnectionInterface $connection): void
    {
        foreach ($this->generators as $generator) {
            $generator->setCurrentConnection($connection);
        }
    }
}
