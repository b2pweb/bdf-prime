<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Console\ConnectionProvider\DoctrineConnectionProviderAdapter;
use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand as DoctrineRunSqlCommand;

/**
 * Run SQL on a doctrine connection. This will display a dbal result.
 */
class RunSqlCommand extends DoctrineRunSqlCommand
{
    protected static $defaultName = 'prime:run:sql';

    /**
     * @param ConnectionManager $connectionManager
     */
    public function __construct(ConnectionManager $connectionManager)
    {
        parent::__construct(new DoctrineConnectionProviderAdapter($connectionManager));
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
}
