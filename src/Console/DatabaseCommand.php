<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\ConnectionManager;
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
     * @var ServiceLocator
     */
    private $locator;

    /**
     * @var BdfStyle
     */
    protected $io;

    /**
     * DatabaseCommand constructor.
     *
     * @param ServiceLocator $locator
     */
    public function __construct(ServiceLocator $locator)
    {
        $this->locator = $locator;

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

        /** @var ConnectionManager $connectionManager */
        $connectionManager = $this->locator->connections();
        $name = $this->io->option('connection');

        $connections = $name ? [$name] : $connectionManager->allConnectionNames();

        foreach ($connections as $connectionName) {
            $connection = $connectionManager->connection($connectionName);

            if (!$connection instanceof DoctrineConnection) {
                $this->io->line('Connection <comment>%s</comment> is ignored: only doctrine connection can be managed', $connectionName);
                continue;
            }

            $config = $connection->getParams();

            // Skip connections marked as "ignore" on configuration
            // Permit to declare SQLite connections, which do not supports database management
            if (!empty($config['ignore'])) {
                $this->io->line('Connection <comment>%s</comment> is ignored.', $connectionName);
                continue;
            }

            $dbName = $this->prepareConnectionConfig($config);
            $connectionTmp = $connectionManager->createConnection($config, $connectionManager->config());
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
     * @param array $config
     *
     * @return string The db name to interact
     */
    protected function prepareConnectionConfig(&$config): string
    {
        $user = $this->io->option('user');

        if (!isset($config['user']) || $config['user'] !== $user) {
            $config['user'] = $user;
            $config['password'] = $this->io->option('password');
        }

        // Force the connection with no database to allow doctrine to connect.
        $dbName = $config['dbname'] ?? $config['path'];
        $config['dbname'] = $config['path'] = $config['url'] = null;

        return $dbName;
    }

    /**
     * Interact if the database exists
     *
     * @param ConnectionInterface $connection
     * @param string $dbName
     */
    abstract protected function interactWithDatabase($connection, $dbName);

    /**
     * Interact if the database does not exist
     *
     * @param ConnectionInterface $connection
     * @param string $dbName
     */
    abstract protected function interactWithNoDatabase($connection, $dbName);
}
