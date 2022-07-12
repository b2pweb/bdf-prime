<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\Schema\SchemaManagerInterface;
use Bdf\Prime\Schema\Visitor\MapperVisitor;
use Bdf\Prime\ServiceLocator;
use Bdf\Util\Console\BdfStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * MapperCommand
 */
#[AsCommand('prime:mapper', 'Generate mapper prototype class')]
class MapperCommand extends Command
{
    protected static $defaultName = 'prime:mapper';

    /**
     * @var ServiceLocator
     */
    private $locator;

    /**
     * MapperCommand constructor.
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
            ->setDescription('Generate mapper prototype class')
            ->addOption('table', 't', InputOption::VALUE_REQUIRED, 'the table name to import. Default: all tables from database.')
            ->addOption('connection', 'c', InputOption::VALUE_REQUIRED, 'Connection name. Default: the default prime connection.')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Path to output mappers files.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new BdfStyle($input, $output);

        $connection = $this->locator->connection($io->option('connection'));
        $schemaManager = $connection->schema();

        if (!$schemaManager instanceof SchemaManagerInterface) {
            $io->error('The connection "%s" do not handle schema', $io->option('connection'));
            return 1;
        }

        if ($io->option('table')) {
            $schema = $schemaManager->schema(
                $schemaManager->load($io->option('table'))
            );
        } else {
            $schema = $schemaManager->loadSchema();
        }

        $visitor = new MapperVisitor($connection->getName(), $this->locator->mappers()->getNameResolver());
        $schema->visit($visitor);

        if (!$io->option('output')) {
            $io->line($visitor->getOutput());
        } else {
            $io->line('Writting mapper files in directory <info>'.$io->option('output').'</info>');
            $visitor->write($io->option('output'));
        }

        return 0;
    }
}
