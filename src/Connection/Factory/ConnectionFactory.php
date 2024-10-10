<?php

namespace Bdf\Prime\Connection\Factory;

use Bdf\Prime\Configuration;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\MongoDB\Driver\MongoConnection;
use Bdf\Prime\MongoDB\Driver\MongoDriver;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Exception as DoctrineDBALException;
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
     *
     * @psalm-suppress UndefinedClass
     * @psalm-suppress InvalidPropertyAssignmentValue
     */
    private static $driversMap = [
        'mongodb' => [MongoDriver::class, MongoConnection::class],
    ];

    /**
     * {@inheritDoc}
     */
    public function create(string $connectionName, array $parameters, ?Configuration $config = null): ConnectionInterface
    {
        if (!$config) {
            $config = new Configuration(['name' => $connectionName]);
        } else {
            $config = $config->withName($connectionName);
        }

        $connection = $this->createConnection($parameters, $config);

        // Store connection and return adapter instance
        $connection->setName($connectionName);

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
     * @param array{wrapperClass?: class-string<T>, ...} $parameters
     * @param EventManager|null $eventManager The event manager, optional.
     *
     * @return ConnectionInterface
     * @throws DBALException
     *
     * @template T as ConnectionInterface
     */
    private function createConnection(array $parameters, Configuration $config, ?EventManager $eventManager = null): ConnectionInterface
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

        try {
            /**
             * @var T
             * @psalm-suppress InvalidArgument
             */
            return DriverManager::getConnection($parameters, $config, $eventManager);
        } catch (DoctrineDBALException $e) {
            /** @psalm-suppress InvalidScalarArgument */
            throw new DBALException('Cannot create the connection : '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Register a global driver map
     *
     * @param string $name
     * @param class-string<\Doctrine\DBAL\Driver> $driver
     * @param class-string<\Doctrine\DBAL\Driver\Connection>|null $wrapper
     */
    public static function registerDriverMap(string $name, string $driver, ?string $wrapper = null): void
    {
        self::$driversMap[$name] = [$driver, $wrapper];
    }

    /**
     * Get a global driver map
     *
     * @param string $name
     *
     * @return array{0: class-string<\Doctrine\DBAL\Driver>, 1: class-string<\Doctrine\DBAL\Driver\Connection>}|null
     */
    public static function getDriverMap(string $name): ?array
    {
        return self::$driversMap[$name] ?? null;
    }

    /**
     * Unregister a global driver map
     *
     * @param string $name
     */
    public static function unregisterDriverMap(string $name): void
    {
        unset(self::$driversMap[$name]);
    }
}
