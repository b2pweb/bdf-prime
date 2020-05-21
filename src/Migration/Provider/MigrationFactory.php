<?php

namespace Bdf\Prime\Migration\Provider;

use Bdf\Prime\Migration\MigrationInterface;
use Bdf\Prime\Migration\MigrationFactoryInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

/**
 * Instantiate a Migration class
 */
class MigrationFactory implements MigrationFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * BasicMigrationFactory constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $className, string $version): MigrationInterface
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException('Could not find class "'.$className.'"');
        }

        $migration = new $className($version, $this->container);

        if (!($migration instanceof MigrationInterface)) {
            throw new InvalidArgumentException('Requested class must implement '.MigrationInterface::class);
        }

        return $migration;
    }
}
