<?php

namespace Bdf\Prime\Connection;

use Bdf\Prime\ConnectionManager;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver;
use LogicException;

/**
 * MasterSlaveConnection
 *
 * The master / slave connection is a connection to a master server with a connection wrapper to a slave server.
 * Only method SimpleConnection#executeQuery will be redirect to the salve.
 *
 * SimpleConnection#quote also use slave.
 *
 * Becareful those methods are used on master:
 *
 *   SimpleConnection#prepare
 *   SimpleConnection#query
 *
 * @package Bdf\Prime\Connection
 */
class MasterSlaveConnection extends SimpleConnection implements SubConnectionManagerInterface
{
    /**
     * The connection specifically for read operations
     * 
     * This connection is used only for the method SimpleConnection#executeQuery
     * 
     * @var SimpleConnection
     */
    private $readConnection;

    /**
     * Force the read on master
     *
     * @var boolean
     */
    private $force = false;

    /**
     * Initializes a new instance of the Connection class.
     * 
     * Here's a read connection configuration
     * 
     * @example
     *
     * $conn = DriverManager::getConnection([
     *    'driver' => 'pdo_mysql',
     *    'user'     => '',
     *    'password' => '',
     *    'host'     => '',
     *    'dbname'   => '',
     *    'read' => [
     *        'user'     => 'slave',
     *        'password' => '',
     *        'host'     => '',
     *        'dbname'   => '',
     *    ]
     * ]);
     *
     * @param array                              $params       The connection parameters.
     * @param \Doctrine\DBAL\Driver              $driver       The driver to use.
     * @param \Doctrine\DBAL\Configuration|null  $config       The configuration, optional.
     * @param \Doctrine\Common\EventManager|null $eventManager The event manager, optional.
     */
    public function __construct(array $params, Driver $driver, Configuration $config = null, EventManager $eventManager = null)
    {
        if (!isset($params['read'])) {
            throw new LogicException('Master/slave connection needs readable connection in parameters');
        }

        $readParameters = $params['read'];
        unset($params['read']);
        unset($params['wrapperClass']);

        $this->readConnection = ConnectionManager::createConnection(array_merge($params, $readParameters), $config, $eventManager);

        parent::__construct($params, $driver, $config, $eventManager);
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->readConnection->setName($name.'.read');
        return parent::setName($name);
    }

    /**
     * Get the read connection
     * 
     * @return SimpleConnection
     */
    public function getReadConnection()
    {
        return $this->readConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection($name)
    {
        if ($name === 'read') {
            return $this->readConnection;
        }

        // Force the read on master if it is the awaiting connection
        if ($name === 'master') {
            return $this->force();
        }

        throw new LogicException('The sub connection "'.$name.'" is unknown in the master / slave connection');
    }

    /**
     * Force next read on master connection once
     * This flag will change after the execution of method executeQuery
     *
     * @return $this
     */
    public function force()
    {
        $this->force = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function executeQuery($query, array $params = [], $types = [], QueryCacheProfile $qcp = null)
    {
        if ($this->getTransactionNestingLevel() <= 0 && $this->force !== true) {
            return $this->readConnection->executeQuery($query, $params, $types, $qcp);
        }

        $this->force = false;

        return parent::executeQuery($query, $params, $types, $qcp);
    }

    /**
     * {@inheritdoc}
     */
    public function quote($input, $type = null)
    {
        return $this->readConnection->quote($input, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        parent::close();

        $this->readConnection->close();
    }
}
