<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Console\ConnectionProvider\DoctrineConnectionProviderAdapter;
use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand as DoctrineRunSqlCommand;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Run SQL on a doctrine connection. This will display a dbal result.
 */
#[AsCommand('prime:run:sql', 'Executes arbitrary SQL directly from the command line.')]
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
