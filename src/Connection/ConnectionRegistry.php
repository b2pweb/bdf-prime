<?php

namespace Bdf\Prime\Connection;

use Bdf\Dsn\Dsn;
use Bdf\Prime\Connection\Configuration\ConfigurationResolver;
use Bdf\Prime\Connection\Configuration\ConfigurationResolverInterface;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\Connection\Factory\ConnectionFactoryInterface;
use Bdf\Prime\ConnectionRegistryInterface;
use Bdf\Prime\Exception\DBALException;

/**
 * ConnectionRegistry
 */
class ConnectionRegistry implements ConnectionRegistryInterface
{
    /**
     * The connection factory
     *
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * The configuration resolver
     *
     * @var ConfigurationResolverInterface
     */
    private $configResolver;

    /**
     * The configuration map
     * Contains configuration of some connections
     *
     * @var array
     */
    private $parametersMap;

    /**
     * The drive name alias
     *
     * @var array
     */
    private static $driverSchemeAliases = [
        'db2'        => 'ibm_db2',
        'mssql'      => 'pdo_sqlsrv',
        'mysql'      => 'pdo_mysql',
        'mysql2'     => 'pdo_mysql', // Amazon RDS, for some weird reason
        'postgres'   => 'pdo_pgsql',
        'postgresql' => 'pdo_pgsql',
        'pgsql'      => 'pdo_pgsql',
        'sqlite'     => 'pdo_sqlite',
        'sqlite3'    => 'pdo_sqlite',
    ];

    /**
     * Set default configuration
     *
     * @param array $parametersMap
     * @param ConnectionFactoryInterface|null $connectionFactory
     * @param ConfigurationResolverInterface|null $configResolver
     */
    public function __construct(array $parametersMap = [], ConnectionFactoryInterface $connectionFactory = null, ConfigurationResolverInterface $configResolver = null)
    {
        $this->parametersMap = $parametersMap;
        $this->connectionFactory = $connectionFactory ?? new ConnectionFactory();
        $this->configResolver = $configResolver ?? new ConfigurationResolver();
    }

    /**
     * {@inheritDoc}
     */
    public function getConnection(string $name): ConnectionInterface
    {
        return $this->connectionFactory->create($name, $this->getConnectionParameters($name), $this->configResolver->getConfiguration($name));
    }

    /**
     * Associate configuration to connection
     *
     * @param string $connectionName
     * @param string|array $parameters
     *
     * @return void
     */
    public function declareConnection(string $connectionName, $parameters): void
    {
        $this->parametersMap[$connectionName] = $parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function getConnectionNames(): array
    {
        return array_keys($this->parametersMap);
    }

    /**
     * Create the doctrine config for the connection
     *
     * @param string $connectionName
     *
     * @return array
     */
    private function getConnectionParameters(string $connectionName): array
    {
        if (!isset($this->parametersMap[$connectionName])) {
            throw new DBALException('Connection name "' . $connectionName . '" is not set');
        }

        $parameters = $this->parametersMap[$connectionName];

        // Manage string configuration as dsn
        if (is_string($parameters)) {
            $parameters = ['url' => $parameters];
        }

        //@todo move in factory ? Allows shard / slave to use url as parameter. Otherwise doctrine will evaluate the url
        // Url key describe a dsn. Extract item from dsn and merge info the current config
        if (isset($parameters['url'])) {
            $parameters = array_merge($parameters, $this->parseDsn($parameters['url']));
            // Remove url: don't let doctrine parse the url
            unset($parameters['url']);
        }

        return $parameters;
    }

    /**
     * Parse the dsn string
     *
     * @param string $dsn
     *
     * @return array
     */
    private function parseDsn(string $dsn): array
    {
        $request = Dsn::parse($dsn);

        $parameters = $request->getQuery() + [
                'host' => $request->getHost(),
                'port' => $request->getPort(),
                'user' => $request->getUser(),
                'password' => $request->getPassword(),
            ];

        // Get drive from alias or manage synthax 'pdo+mysql' because '_' are not allowed in scheme
        $parameters['driver'] = self::$driverSchemeAliases[$request->getScheme()] ?? str_replace('+', '_', $request->getScheme());

        // SQLite option: dont create dbname key use by many drivers
        // Sqlite drive needs memory or path key
        // Remove the 'path' if not used by sqlite
        if (strpos($parameters['driver'], 'sqlite') !== false) {
            if ($request->getPath() === ':memory:') {
                $parameters['memory'] = true;
            } else {
                $parameters['path'] = $request->getPath();
            }
        } elseif (!isset($parameters['dbname'])) {
            $parameters['dbname'] = trim($request->getPath(), '/');
        }

        return $parameters;
    }
}
