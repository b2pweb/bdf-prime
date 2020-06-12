<?php

namespace Bdf\Prime\Connection\Factory;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\SimpleConnection;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

/**
 * ConnectionFactory
 *
 * Create simple connection instance
 */
class ConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * The drivers map
     *
     * @var array
     */
    static private $driversMap;

    /**
     * {@inheritDoc}
     */
    public function create(string $connectionName, array $parameters, Configuration $config): ConnectionInterface
    {
        $connection = $this->createConnection($parameters, $config);

        // Store connection and return adapter instance
        if ($connection instanceof ConnectionInterface) {
            $connection->setName($connectionName);
        }

        return $connection;
    }

    /**
     * {@inheritDoc}
     */
    public function support(string $connectionName, array $parameters): bool
    {
        return true;
    }

    /**
     * Create the instance of the connection
     *
     * @param array              $parameters
     * @param Configuration|null $config
     * @param EventManager|null  $eventManager The event manager, optional.
     *
     * @return ConnectionInterface
     */
    private function createConnection(array $parameters, Configuration $config = null, EventManager $eventManager = null): ConnectionInterface
    {
        // Set the custom driver class + wrapper
        if (isset($parameters['driver']) && isset(self::$driversMap[$parameters['driver']])) {
            list($parameters['driverClass'], $parameters['wrapperClass']) = self::$driversMap[$parameters['driver']];
            unset($parameters['driver']);
        }

        // Replace 'adapter' with 'driver' and add 'pdo_'
        if (isset($parameters['adapter'])) {
            $parameters['driver'] = 'pdo_' . $parameters['adapter'];
            unset($parameters['adapter']);
        }

        // default charset
        if (!isset($parameters['charset'])) {
            $parameters['charset'] = 'utf8';
        }

        // default wrapper
        if (!isset($parameters['wrapperClass'])) {
            $parameters['wrapperClass'] = SimpleConnection::class;
        }

        return DriverManager::getConnection($parameters, $config, $eventManager);
    }

    /**
     * Register a global driver map
     *
     * @param string $name
     * @param string $driver
     * @param string|null $wrapper
     */
    public static function registerDriverMap($name, $driver, $wrapper = null)
    {
        self::$driversMap[$name] = [$driver, $wrapper];
    }

    /**
     * Get a global driver map
     *
     * @param string $name
     *
     * @return string|null
     */
    public static function getDriverMap($name)
    {
        return isset(self::$driversMap[$name])
            ? self::$driversMap[$name]
            : null;
    }

    /**
     * Unregister a global driver map
     *
     * @param string $name
     */
    public static function unregisterDriverMap($name)
    {
        unset(self::$driversMap[$name]);
    }
}
