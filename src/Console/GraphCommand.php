<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Schema\RepositoryUpgrader;
use Bdf\Prime\Schema\SchemaManagerInterface;
use Bdf\Prime\Schema\Transformer\Doctrine\TableTransformer;
use Bdf\Prime\Schema\Visitor\Graphviz;
use Bdf\Prime\ServiceLocator;
use Bdf\Util\Console\BdfStyle;
use Bdf\Util\File\ClassFileLocator;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * GraphCommand
 */
#[AsCommand('prime:graph', 'Get the schema graphic from mappers')]
class GraphCommand extends Command
{
    protected static $defaultName = 'prime:graph';

    /**
     * @var ServiceLocator
     */
    private $locator;

    /**
     * GraphCommand constructor.
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
            ->setDescription('Get the schema graphic from mappers')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Dot file to output graph')
            ->addOption('fromdb', null, InputOption::VALUE_NONE, 'Generate graph from database')
            ->addArgument('path', InputArgument::REQUIRED, 'The model path')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new BdfStyle($input, $output);

        $schema = $io->option('fromdb')
            ? $this->getSchemaFromDatabase()
            : $this->getSchemaFromModel($io, $io->argument('path'));

        $graph = new Graphviz();
        $schema->visit($graph);

        if (!$io->option('output')) {
            $io->line($graph->getOutput());
        } else {
            $io->line('Writting graph file <info>'.$io->option('output').'</info>');
            $graph->write($io->option('output'));
        }

        return 0;
    }

    /**
     * Get the tables collection from model
     *
     * @param BdfStyle $io
     * @param string $path
     *
     * @return Schema
     *
     * @throws PrimeException
     */
    protected function getSchemaFromModel($io, $path)
    {
        $tables = [];

        foreach ((new ClassFileLocator(realpath($path))) as $classInfo) {
            $className = $classInfo->getClass();

            // walk on mapper only
            if (!$this->locator->mappers()->isMapper($className)) {
                $io->debug("{$className} is not a mapper class");
                continue;
            }

            $mapper = $this->locator->mappers()->createMapper($this->locator, $className);
            /** @var RepositoryUpgrader $schemaManager */
            $schemaManager = $mapper->repository()->schema(true);
            $platform = $mapper->repository()->connection()->platform();

            $io->debug('Loading table info from '.$className);

            $table = $schemaManager->table(true);
            $tables[$table->name()] = (new TableTransformer($table, $platform))->toDoctrine();

            if (($sequence = $schemaManager->sequence()) !== null) {
                $tables[$sequence->name()] = (new TableTransformer($sequence, $platform))->toDoctrine();
            }
        }

        return new Schema($tables);
    }

    /**
     * Get the tables collection from database
     *
     * @return Schema
     */
    protected function getSchemaFromDatabase()
    {
        $manager = $this->locator->connection()->schema();

        if (!$manager instanceof SchemaManagerInterface) {
            throw new \InvalidArgumentException('The default connection do not support schema loading');
        }

        // TODO parcourir les connections (database unique) et merger les tables ?
        return $manager->loadSchema();
    }
}
