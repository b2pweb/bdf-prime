<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\ServiceLocator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cache command
 *
 * permet de manipuler le cache de result query et de metadata
 */
#[AsCommand('prime:cache', 'Manage all prime caches')]
class CacheCommand extends Command
{
    protected static $defaultName = 'prime:cache';

    /**
     * @var ServiceLocator
     */
    private $locator;

    /**
     * CacheCommand constructor.
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
        $this
            ->setDescription('Manage all prime caches')
            ->addOption('clear', 'c', InputOption::VALUE_NONE, 'Clear all cache')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $caches = [
            'result cache'   => $this->locator->mappers()->getResultCache(),
            'metadata cache' => $this->locator->mappers()->getMetadataCache(),
        ];

        foreach ($caches as $name => $cache) {
            if (!$cache) {
                $output->writeln("<error>$name is not available</error>");
                continue;
            }

            $output->writeln("<comment>loading $name</comment>");

            if ($input->getOption('clear')) {
                $output->writeln("Clearing $name...");
                $cache->clear();
                $output->writeln('<info>OK</info>');
            } else {
                $output->writeln('nothing to do');
            }
        }

        return 0;
    }
}
