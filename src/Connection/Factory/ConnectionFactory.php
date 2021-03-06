<?php

namespace Bdf\Prime\Connection\Factory;

use Bdf\Prime\Configuration;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DBALException as DoctrineDBALException;
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
     * @var array<string, array{0: class-string<\Doctrine\DBAL\Driver>, 1: class-string<\Doctrine\DBAL\Driver\Connection>}>
     */
    static private $driversMap = [
        'mongodb' => [MongoDriver::class, MongoConnection::class],
    ];

    /**
     * {@inheritDoc}
     */
    public function create(string $connectionName, array $parameters, ?Configuration $config = null): ConnectionInterface
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
     * @param array $parameters
     * @param Configuration|null $config
     * @param EventManager|null $eventManager The event manager, optional.
     *
     * @return ConnectionInterface
     * @throws DBALException
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

        if ($config === null) {
            $config = new Configuration();
        }

        try {
            return DriverManager::getConnection($parameters, $config, $eventManager);
        } catch (DoctrineDBALException $e) {
            throw new DBALException('Cannot create the connection : '.$e->getMessage(), $e->getCode(), $e);
        }
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
