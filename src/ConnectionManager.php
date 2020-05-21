<?php

namespace Bdf\Prime;

use Bdf\Dsn\Dsn;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\MasterSlaveConnection;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Connection\SubConnectionManagerInterface;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Sharding\ShardingConnection;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use LogicException;

/**
 * ConnectionManager
 * 
 * doctrine dbal connection storage
 */
class ConnectionManager
{
    /**
     * Default configuration to use
     * 
     * @var Configuration
     */
    protected $defaultConfig;
    
    /**
     * Default connection to use
     * 
     * @var string 
     */
    protected $defaultConnection;
    
    /**
     * Connections list
     * 
     * @var ConnectionInterface[] 
     */
    protected $connections = [];

    /**
     * The drivers map
     *
     * @var array
     */
    static protected $driversMap;

    /**
     * The drive name alias
     *
     * @var array
     */
    static private $driverSchemeAliases = [
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
     * @param Configuration|array $config
     */
    public function __construct($config = null)
    {
        if ($config === null) {
            $config = new Configuration();
        } elseif (is_array($config)) {
            $config = new Configuration($config);
        }
        
        $this->defaultConfig = $config;
    }
    
    /**
     * Add database connection
     *
     * @param string        $name     Unique name for the connection
     * @param string|array  $dsn      DSN string for this connection
     * @param Configuration $config
     * @param boolean       $default  Use this connection as the default? The first connection added is automatically set as the default, even if this flag is false.
     * 
     * @return ConnectionInterface
     * 
     * @throws LogicException if connection exists
     */
    public function addConnection($name, $dsn, Configuration $config = null, $default = false)
    {
        // Connection name must be unique
        if (isset($this->connections[$name])) {
            throw new LogicException('Connection for "'.$name.'" already exists. Connection name must be unique.');
        }

        if ($dsn instanceof ConnectionInterface) {
            $connection = $dsn;
        } else {
            $connection = static::createConnection(
                $this->createConnectionConfig($dsn),
                $config ?: $this->defaultConfig
            );
        }

        // Set as default connection?
        if (true === $default || null === $this->defaultConnection) {
            $this->defaultConnection = $name;
        }

        // Store connection and return adapter instance
        if ($connection instanceof ConnectionInterface) {
            $connection->setName($name);
        }
        
        $this->connections[$name] = $connection;

        return $connection;
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
    public static function createConnection(array $parameters, Configuration $config = null, EventManager $eventManager = null)
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
            if (isset($parameters['read'])) {
                $parameters['wrapperClass'] = MasterSlaveConnection::class;
            } elseif (isset($parameters['shards'])) {
                $parameters['wrapperClass'] = ShardingConnection::class;
            } else {
                $parameters['wrapperClass'] = SimpleConnection::class;
            }
        }

        return DriverManager::getConnection($parameters, $config, $eventManager);
    }

    /**
     * Remove a connection by its name
     *
     * @param string $name
     */
    public function removeConnection($name)
    {
        if (!isset($this->connections[$name])) {
            return;
        }
        
        $this->connections[$name]->close();
        unset($this->connections[$name]);
    }

    /**
     * Set the default connection name
     * 
     * @param string $name
     */
    public function setDefaultConnection($name)
    {
        $this->defaultConnection = $name;
    }

    /**
     * Get the default connection name
     * 
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->defaultConnection;
    }
    
    /**
     * Get connection by name
     *
     * @param string $name Unique name of the connection to be returned
     * 
     * @return ConnectionInterface
     * 
     * @throws DBALException
     */
    public function connection($name = null)
    {
        if ($name === null) {
            $name = $this->defaultConnection;
        }

        // Connection name must be unique
        if (!isset($this->connections[$name]) && !$this->loadSubConnection($name) && !$this->loadConnectionFromConfig($name)) {
            throw new DBALException('Connection name "' . $name . '" is not set');
        }
        
        return $this->connections[$name];
    }

    /**
     * Get all connections
     *
     * @return ConnectionInterface[] Array of connection objects
     */
    public function connections()
    {
        return $this->connections;
    }

    /**
     * Get the loaded connection name
     *
     * @return array Array of connection name
     */
    public function connectionNames()
    {
        return array_keys($this->connections);
    }

    /**
     * Get all connection name
     *
     * @return array Array of connection name
     */
    public function allConnectionNames()
    {
        $lazyConnectionNames = array_keys($this->config()->getDbConfig()->all());

        return array_merge($this->connectionNames(), $lazyConnectionNames);
    }

    /**
     * Get global config
     *
     * @return Configuration
     */
    public function config()
    {
        return $this->defaultConfig;
    }

    /**
     * Try to load a sub connection
     *
     * This method allows connection as "name.otherName".
     * Works only if connection "name" is a SubConnectionManagerInterface.
     *
     * @param string $connectionName
     * 
     * @return bool  The connection has been loaded
     */
    protected function loadSubConnection($connectionName)
    {
        $names = explode('.', $connectionName, 2);

        if (!isset($names[1])) {
            return false;
        }

        $connection = $this->connection($names[0]);

        if ($connection instanceof SubConnectionManagerInterface) {
            //TODO doit on concerver une reference sur la sous connection ?
            $this->connections[$connectionName] = $connection->getConnection($names[1]);
            return true;
        }

        return false;
    }

    /**
     * Load connection from global config
     * If the global config is a config file (is_string).
     *
     * @param string $connectionName
     *
     * @return bool  The connection has been loaded
     */
    protected function loadConnectionFromConfig($connectionName)
    {
        $config = $this->config()->getDbConfig();

        if (!$config || !$config->has($connectionName)) {
            return false;
        }

        $this->addConnection($connectionName, $config->get($connectionName));

        return true;
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

    /**
     * Create the doctrine config for the connection
     *
     * @param string|array $config
     *
     * @return array
     */
    private function createConnectionConfig($config): array
    {
        // Manage string configuration as dsn
        if (is_string($config)) {
            return $this->parseDsn($config);
        }

        // Url key describe a dsn. Extract item from dsn and merge info the current config
        if (isset($config['url'])) {
            $config = array_merge($config, $this->parseDsn($config['url']));
            // Remove url: don't let doctrine parse the url
            unset($config['url']);
        }

        return $config;
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

        $config = $request->getQuery() + [
            'host' => $request->getHost(),
            'port' => $request->getPort(),
            'user' => $request->getUser(),
            'password' => $request->getPassword(),
        ];

        // Get drive from alias or manage synthax 'pdo+mysql' because '_' are not allowed in scheme
        $config['driver'] = self::$driverSchemeAliases[$request->getScheme()] ?? str_replace('+', '_', $request->getScheme());

        // SQLite option: dont create dbname key use by many drivers
        // Sqlite drive needs memory or path key
        // Remove the 'path' if not used by sqlite
        if (strpos($config['driver'], 'sqlite') !== false) {
            if ($request->getPath() === ':memory:') {
                $config['memory'] = true;
            } else {
                $config['path'] = $request->getPath();
            }
        } elseif (!isset($config['dbname'])) {
            $config['dbname'] = trim($request->getPath(), '/');
        }

        return $config;
    }
}
