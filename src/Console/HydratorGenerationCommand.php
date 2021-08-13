<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\Entity\Hydrator\Exception\HydratorGenerationException;
use Bdf\Prime\Entity\Hydrator\HydratorGeneratedInterface;
use Bdf\Prime\Entity\Hydrator\HydratorGenerator;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\ServiceLocator;
use Bdf\Util\Console\BdfStyle;
use Bdf\Util\File\ClassFileLocator;
use Bdf\Util\File\PhpClassFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * HydratorGenerationCommand
 */
class HydratorGenerationCommand extends Command
{
    protected static $defaultName = 'prime:hydrator';

    /**
     * @var ServiceLocator
     */
    private $locator;

    /**
     * @var string
     */
    private $outputDir;

    /**
     * @var string
     */
    private $loaderFile;

    /**
     * HydratorGenerationCommand constructor.
     *
     * @param ServiceLocator $locator
     * @param string $loaderFile
     */
    public function __construct(ServiceLocator $locator, string $loaderFile = null)
    {
        $this->locator = $locator;
        $this->loaderFile = $loaderFile;

        parent::__construct(static::$defaultName);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Generate optimized hydrator classes for entities')
            ->addArgument('path', InputArgument::OPTIONAL, 'The entities path. If omitted, regenerate the loader file')
            ->addOption('loader', 'l', InputOption::VALUE_REQUIRED, 'The hydrator loader file')
            ->addOption('include', 'i', InputOption::VALUE_NONE, 'Add includes into loader file ?')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new BdfStyle($input, $output);

        $this->configureOutputs($io);
        $path = realpath($io->argument('path'));

        $cache = $this->locator->mappers()->getMetadataCache();
        $this->locator->mappers()->setMetadataCache(null);

        if ($io->argument('path')) {
            $io->info('Generating hydrators...');

            foreach ($this->getClassIterator($path) as $classInfo) {
                $className = $classInfo->getClass();

                if (!$this->locator->mappers()->isEntity($className)) {
                    $io->debug("'{$className}' is not an entity class");
                    continue;
                }

                $mapper = $this->locator->mappers()->build($this->locator, $className);

                $this->generateHydrator($io, $className, $mapper);
            }
        }

        $this->generateLoader($io);

        $this->locator->mappers()->setMetadataCache($cache);
        return 0;
    }

    /**
     * Configure output file and directory from options
     *
     * @param BdfStyle $io
     */
    private function configureOutputs($io)
    {
        if ($io->option('loader') !== null) {
            $this->loaderFile = $io->option('loader');
        }

        if (empty($this->loaderFile)) {
            $io->block('You should configure "prime.hydrators.loader" or set the loader file with option -l');
            exit;
        }

        $this->outputDir = dirname($this->loaderFile).DIRECTORY_SEPARATOR;
        (new Filesystem())->mkdir($this->outputDir);
    }

    /**
     * Generate the entity hydrator file
     *
     * @param BdfStyle $io
     * @param string $className
     * @param Mapper $mapper
     */
    private function generateHydrator($io, $className, Mapper $mapper)
    {
        $io->inline("Generate hydrator for ${className} ");

        try {
            $generator = new HydratorGenerator($this->locator, $mapper, $className);

            file_put_contents(
                $this->getHydratorFilename($generator->hydratorClassName()),
                $generator->generate()
            );

            $io->info('[OK]');
        } catch (HydratorGenerationException $exception) {
            $io->error($exception->getMessage());
        }
    }

    /**
     * Generate the hydrators loader
     *
     * @param BdfStyle $io
     */
    private function generateLoader($io)
    {
        $io->info('Generating loader file...');

        $includes = [];
        $hydrators = [];
        $factories = [];

        foreach ($this->getClassIterator($this->outputDir) as $classInfo) {
            $className = $classInfo->getClass();

            if (!class_exists($className)) {
                require_once $classInfo->getPathname();
            }

            if (!is_subclass_of($className, HydratorGeneratedInterface::class)) {
                $io->debug("$className is not an hydrator");
                continue;
            }

            $includes[] = $classInfo->getFilename();

            $embeddeds = $className::embeddedPrimeClasses();

            if (empty($embeddeds)) {
                $hydrators[] = "'{$className::supportedPrimeClassName()}' => new ${className}(),";
            } else {
                $arguments = [];

                foreach ($embeddeds as $entity) {
                    $arguments[] = "\$registry->get('${entity}')";
                }

                $arguments = implode(', ', $arguments);
                $closure = "function(\$registry) {return new ${className}(${arguments});}";

                $factories[] = "'{$className::supportedPrimeClassName()}' => ${closure},";
            }
        }

        $content = "<?php\n";

        if ($io->option('include')) {
            foreach ($includes as $file) {
                $content .= "require_once __DIR__.DIRECTORY_SEPARATOR.'${file}';\n";
            }
        } else {
            $io->block([
                'Loader file do not contains includes. Don\'t forget to add the hydrators directory to composer, or specify "-i" option to the command :',
                <<<JSON
"autoload": {
    /* ... */
    "classmap": [
        /* ... */
        "{$this->outputDir}"
    ]
}
JSON
            ], 'comment', true);
        }

        $content .= "\$registry->setHydrators([\n".implode("\n", $hydrators)."\n]);\n";
        $content .= "\$registry->setFactories([\n".implode("\n", $factories)."\n]);\n";

        file_put_contents($this->loaderFile, $content);
    }

    /**
     * @param string $hydratorClassname
     *
     * @return string
     */
    private function getHydratorFilename($hydratorClassname)
    {
        return $this->outputDir.$hydratorClassname.'.php';
    }

    /**
     * @param string $path
     *
     * @return ClassFileLocator|array
     */
    private function getClassIterator($path)
    {
        if (is_dir($path)) {
            return new ClassFileLocator($path);
        } else {
            $classFile = new PhpClassFile($path);
            $classFile->extractClassInfo();

            return [$classFile];
        }
    }
}
