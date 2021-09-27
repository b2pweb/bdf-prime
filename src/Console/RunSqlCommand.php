<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Console\ConnectionProvider\DoctrineConnectionProviderAdapter;
use Bdf\Util\Console\BdfStyle;
use Doctrine\DBAL\Connection as DoctrineConnection;
use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand as DoctrineRunSqlCommand;
use Doctrine\DBAL\Tools\Console\ConnectionProvider;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Run SQL on a doctrine connection. This will display a dbal result.
 */
class RunSqlCommand extends DoctrineRunSqlCommand
{
    protected static $defaultName = 'prime:run:sql';

    /**
     * @var ConnectionManager
     */
    private $connectionManager;

    /**
     * @param ConnectionManager $connectionManager
     */
    public function __construct(ConnectionManager $connectionManager)
    {
        if (interface_exists(ConnectionProvider::class)) {
            parent::__construct(new DoctrineConnectionProviderAdapter($connectionManager));
            $this->connectionManager = null;
        } else {
            parent::__construct();
            $this->connectionManager = $connectionManager;
        }
    }

    /**
     *
     */
    protected function configure()
    {
        parent::configure();

        // Override the doctrine name to set the prime namespace.
        $this->setName(self::$defaultName);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->connectionManager !== null) {
            $connection = $this->connectionManager->getConnection($input->getOption('connection'));

            if (!$connection instanceof DoctrineConnection) {
                $io = new BdfStyle($input, $output);
                $io->line('Connection <comment>%s</comment> is ignored: only doctrine connection can be managed', $connection->getName());
                return 1;
            }

            $helperSet = $this->getHelperSet();
            $helperSet->set(new ConnectionHelper($connection), 'db');
        }

        return parent::execute($input, $output);
    }
}
