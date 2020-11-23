<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\ServiceLocator;
use Bdf\Util\Console\BdfStyle;
use Bdf\Util\File\ClassFileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * UpgraderCommand
 */
class UpgraderCommand extends Command
{
    protected static $defaultName = 'prime:upgrade';

    /**
     * @var ServiceLocator
     */
    private $locator;

    /**
     * UpgraderCommand constructor.
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
            ->setDescription('Upgrade schema from mappers')
            ->addOption('execute', null, InputOption::VALUE_NONE, 'Lance les requetes d\'upgrade')
            ->addOption('useDrop', null, InputOption::VALUE_NONE, 'Lance les requetes de alter avec des drop')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force l\'utilisation du schema manager des mappers l\'ayant désactivé')
            ->addArgument('path', InputArgument::REQUIRED, 'Model path')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new BdfStyle($input, $output);

        $useDrop      = $io->option('useDrop');
        $executeQuery = $io->option('execute');
        $force        = $io->option('force');
        $nbWarning    = 0;

        foreach ((new ClassFileLocator(realpath($io->argument('path')))) as $classInfo) {
            $className = $classInfo->getClass();

            // walk on mapper only
            if (!$this->locator->mappers()->isMapper($className)) {
                $io->debug("{$className} is not mapper class");
                continue;
            }

            // get the entity class name to get the repository
            $schema = $this->locator->mappers()->createMapper($this->locator, $className)->repository()->schema($force);
            $queries = $schema->diff($useDrop);

            if (!$queries) {
                $io->line("<comment>{$className}</comment> is up to date");
                continue;
            }

            $io->line("<comment>{$className}</comment> <info>needs upgrade</info>");

            foreach ($queries as $query) {
                $nbWarning++;

                $io->line(is_string($query) ? $query : json_encode($query));
            }
            
            if ($executeQuery) {
                $io->line('launching query ', ' ');

                try {
                    $schema->migrate($useDrop);

                    $io->info('[OK]');
                } catch (\Throwable $e) {
                    $io->alert($e->getMessage());
                }
            }

            $io->newLine();
        }

        $io->info('Found ' . $nbWarning . ' upgrade(s)');

        return 0;
    }
}