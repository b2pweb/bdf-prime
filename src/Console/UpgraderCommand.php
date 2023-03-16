<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\Migration\MigrationInterface;
use Bdf\Prime\Migration\MigrationManager;
use Bdf\Prime\Schema\RepositoryUpgraderResolver;
use Bdf\Prime\Schema\StructureUpgraderResolverInterface;
use Bdf\Prime\ServiceLocator;
use Bdf\Util\Console\BdfStyle;
use Bdf\Util\File\ClassFileLocator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * UpgraderCommand
 */
#[AsCommand('prime:upgrade', 'Upgrade schema from mappers')]
class UpgraderCommand extends Command
{
    protected static $defaultName = 'prime:upgrade';

    private StructureUpgraderResolverInterface $resolver;
    private ?MigrationManager $migrationManager;

    /**
     * UpgraderCommand constructor.
     *
     * @param StructureUpgraderResolverInterface|ServiceLocator $resolver
     * @param MigrationManager|null $migrationManager
     */
    public function __construct($resolver, ?MigrationManager $migrationManager = null)
    {
        if ($resolver instanceof ServiceLocator) {
            $resolver = new RepositoryUpgraderResolver($resolver);
        }

        $this->resolver = $resolver;
        $this->migrationManager = $migrationManager;

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
            ->addOption('migration', null, InputOption::VALUE_REQUIRED, 'Migration name to generate. Cannot be used with --execute option.')
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
        $migration    = $io->option('migration');
        $nbWarning    = 0;

        if ($migration) {
            if ($executeQuery) {
                $io->error('Cannot use --migration option with --execute option');

                return 1;
            }

            if (!$this->migrationManager) {
                $io->error('Migration manager is not configured');

                return 1;
            }
        }

        $upQueries = [];
        $downQueries = [];

        foreach ((new ClassFileLocator(realpath($io->argument('path')))) as $classInfo) {
            $className = $classInfo->getClass();

            // Get the upgrader from the mapper class name
            $schema = $this->resolver->resolveByMapperClass($className, $force);

            // walk on mapper only
            if (!$schema) {
                $io->debug("{$className} is not mapper class");
                continue;
            }

            $queries = $schema->diff($useDrop);

            if (!$queries) {
                $io->line("<comment>{$className}</comment> is up to date");
                continue;
            }

            $io->line("<comment>{$className}</comment> <info>needs upgrade</info>");

            if ($migration) {
                $migrationQueries = $schema->queries($useDrop);

                $upQueries = array_merge_recursive($upQueries, $migrationQueries['up']);
                $downQueries = array_merge_recursive($downQueries, $migrationQueries['down']);
            }

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

        if ($migration) {
            if ($upQueries) {
                $this->migrationManager->createMigration($migration, MigrationInterface::STAGE_PREPARE, $upQueries, $downQueries);
            } else {
                $io->warning('Migration not created, no queries found');
            }
        }

        return 0;
    }
}
