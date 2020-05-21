<?php

namespace Bdf\Prime\Migration\Console;

use Bdf\Prime\Migration\MigrationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * AbstractCommand
 */
abstract class AbstractCommand extends Command
{
    /**
     * @var MigrationManager
     */
    private $manager;

    /**
     * Migration command constructor.
     *
     * @param MigrationManager $manager
     */
    public function __construct(MigrationManager $manager)
    {
        $this->manager = $manager;

        parent::__construct(static::$defaultName);
    }

    /**
     * Add console context on manager
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return MigrationManager
     */
    public function manager(InputInterface $input, OutputInterface $output)
    {
        $this->manager->setOutput($output);
        $this->manager->setInput($input);
        $this->manager->setHelper($this->getHelperSet());

        return $this->manager;
    }
}
