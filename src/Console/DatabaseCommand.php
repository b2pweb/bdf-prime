<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ChainFactory;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\Connection\Factory\ConnectionFactoryInterface;
use Bdf\Prime\Connection\Factory\MasterSlaveConnectionFactory;
use Bdf\Prime\Connection\Factory\ShardingConnectionFactory;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\ConnectionRegistryInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\ServiceLocator;
use Bdf\Util\Console\BdfStyle;
use Doctrine\DBAL\Connection as DoctrineConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @todo manage Shard and Master/slave connection
 * @todo remonve doctrine dependency
 */
abstract class DatabaseCommand extends Command
{
    /**
     * @var ConnectionRegistryInterface
     */
    private $registry;

    /**
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * @var BdfStyle
     */
    protected $io;

    /**
     * DatabaseCommand constructor.
     *
     * @param ConnectionRegistryInterface $registry
     * @param ConnectionFactoryInterface $connectionFactory
     */
    public function __construct(ConnectionRegistryInterface $registry, ConnectionFactoryInterface $connectionFactory = null)
    {
        $this->registry = $registry;
        $this->connectionFactory = $connectionFactory;

        parent::__construct(static::$defaultName);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'Interacts only on the database from this connection');
        $this->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Set the user name.', 'root');
        $this->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Set the user password.', '');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new BdfStyle($input, $output);

        $name = $this->io->option('connection');

        $connections = $name ? [$name] : $this->registry->getConnectionNames();

        foreach ($connections as $connectionName) {
            $connection = $this->registry->getConnection($connectionName);

            if (!$connection instanceof DoctrineConnection) {
                $this->io->line('Connection <comment>%s</comment> is ignored: only doctrine connection can be managed', $connectionName);
                continue;
            }

            /** @psalm-suppress InternalMethod */
            $parameters = $connection->getParams();

            // Skip connections marked as "ignore" on configuration
            // Permit to declare SQLite connections, which do not supports database management
            if (!empty($parameters['ignore'])) {
                $this->io->line('Connection <comment>%s</comment> is ignored.', $connectionName);
                continue;
            }

            $dbName = $this->prepareConnectionConfig($parameters);
            /** @var ConnectionInterface&DoctrineConnection $connectionTmp */
            $connectionTmp = $this->connectionFactory->create($connectionName, $parameters, $connection->getConfiguration());
            $schema = $connectionTmp->schema();

            if ($schema->hasDatabase($dbName)) {
                $this->interactWithDatabase($connectionTmp, $dbName);
                continue;
            }

            $this->interactWithNoDatabase($connectionTmp, $dbName);

            $connectionTmp->close();
        }

        return 0;
    }

    /**
     * Prepare the configuration of the connection
     * Change the user and remove db name.
     *
     * @param array $parameters
     *
     * @return string The db name to interact
     */
    protected function prepareConnectionConfig(&$parameters): string
    {
        $user = $this->io->option('user');

        if (!isset($parameters['user']) || $parameters['user'] !== $user) {
            $parameters['user'] = $user;
            $parameters['password'] = $this->io->option('password');
        }

        // Force the connection with no database to allow doctrine to connect.
        $dbName = $parameters['dbname'] ?? $parameters['path'];
        $parameters['dbname'] = $parameters['path'] = $parameters['url'] = null;

        return $dbName;
    }

    /**
     * Interact if the database exists
     *
     * @param ConnectionInterface $connection
     * @param string $dbName
     *
     * @throws PrimeException
     */
    abstract protected function interactWithDatabase($connection, $dbName);

    /**
     * Interact if the database does not exist
     *
     * @param ConnectionInterface $connection
     * @param string $dbName
     *
     * @throws PrimeException
     */
    abstract protected function interactWithNoDatabase($connection, $dbName);
}
