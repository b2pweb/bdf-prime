<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\Entity\EntityGenerator;
use Bdf\Prime\Entity\EntityInterface;
use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Entity\ImportableInterface;
use Bdf\Prime\Entity\InitializableInterface;
use Bdf\Prime\Entity\Model;
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
 * EntityCommand
 * 
 * @package Bdf\Prime\Console
 */
class EntityCommand extends Command
{
    protected static $defaultName = 'prime:entity';

    /**
     * @var ServiceLocator
     */
    private $locator;

    /**
     * EntityCommand constructor.
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
            ->addArgument('mapper', InputArgument::REQUIRED, 'Mapper file or directory to parse')
            ->addOption('backup', 'b', InputOption::VALUE_NONE, 'Backup file if exists')
            ->setDescription('Generate entity class by mapper')
            ->setHelp(<<<EOF
The <info>%command.name%</info> generate entity from mapper
use mapper argument to parse a directory or a mapper file
  <info>php %command.full_name% app/models</info>
  <info>php %command.full_name% app/models/MyMapper.php</info>

Backup files with backup option
  <info>php %command.full_name% --backup app/models</info>
  <info>php %command.full_name% -b app/models</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new BdfStyle($input, $output);

        $path = realpath($io->argument('mapper'));
        
        $cache = $this->locator->mappers()->getMetadataCache();
        $this->locator->mappers()->setMetadataCache(null);

        foreach ($this->getClassIterator($path) as $classInfo) {
            $className = $classInfo->getClass();

            if (!$this->locator->mappers()->isMapper($className)) {
                $io->debug("'{$className}' is not mapper class");
                continue;
            }

            $mapper    = $this->locator->mappers()->createMapper($this->locator, $className);
            $generator = new EntityGenerator($this->locator);

            $this->runUserActions($io, $generator, $mapper, $classInfo);
        }

        $this->locator->mappers()->setMetadataCache($cache);
        return 0;
    }
    
    /**
     * @param string $path
     *
     * @return ClassFileLocator|array
     */
    protected function getClassIterator($path)
    {
        if (is_dir($path)) {
            return new ClassFileLocator($path);
        } else {
            $classFile = new PhpClassFile($path);
            $classFile->extractClassInfo();
            
            return [$classFile];
        }
    }
    
    /**
     * @param BdfStyle $io
     * @param EntityGenerator $generator
     * @param \Bdf\Prime\Mapper\Mapper $mapper
     * @param PhpClassFile $classInfo
     *
     * @return void
     */
    protected function runUserActions($io, $generator, $mapper, $classInfo)
    {
        //TODO AAAAAAAAAAAAAAAAAAAAH !!!!!!!
        $fileName  = str_replace('Mapper', '', $classInfo->getRealPath());
        $className = $mapper->getEntityClass();
        $doCreate = false;
        
        if (file_exists($fileName)) {
            $userChoice = $io->choice("'$fileName' exists. what do you want ?", [
                '1'     => 'Regenerate: will replace the existing entity',
                '2'     => 'Update: will complete entity with new properties and methods',
                '3'     => 'Cancel',
            ]);

            if ($userChoice === 'Cancel') {
                return;
            } elseif (strpos($userChoice, 'Regenerate') === 0) {
                $generator->setRegenerateEntityIfExists(true);
            } elseif (strpos($userChoice, 'Update') === 0) {
                $generator->setUpdateEntityIfExists(true);
            }
        }

        $typedPropertiesAvailable = PHP_VERSION_ID >= 70400;

        if ($typedPropertiesAvailable) {
            $generator->useTypedProperties();
        }
        
        $choices = [
            '0'     => 'Skip',
            '1'     => 'Auto+create',
            '2'     => 'Auto',
            '3'     => 'Create',
            '4'     => 'Show',
            '5'     => 'Extends class',
            '6'     => 'Implements interface',
            '7'     => 'Extensions',
            '8'     => 'Enable/disable get method shortcut',
            '9'     => 'Change field visibility',
        ];

        if ($typedPropertiesAvailable) {
            $choices[10] = 'Enable/disable typed properties (PHP >= 7.4 only)';
        }

        while (true) {
            switch ($io->choice("'$className' found", $choices, 'auto')) {
                case 'Auto+create':
                    $doCreate = true;
                case 'Auto':
                    $generator->setClassToExtend(Model::class);
                    $generator->addInterface(InitializableInterface::class);

                    if (!$doCreate) {
                        break;
                    }
                
                case 'Create':
                    $filesystem = new Filesystem();

                    if ($io->option('backup') && $filesystem->exists($fileName)) {
                        $filesystem->copy($fileName, $fileName.'.bak');
                        $io->comment('backup file "' . $fileName.'.bak' . '"');
                    }
                    
                    $filesystem->dumpFile($fileName, $generator->generate($mapper, $fileName));

                    $io->info('File "' . $fileName . '" was created');
                    return;
                    
                case 'Show':
                    $io->line('File: ' . $fileName);
                    $io->line($generator->generate($mapper, $fileName));
                    break;
                    
                case 'Extends class':
                    $generator->setClassToExtend($io->ask('Enter full name class'));
                    break;
                    
                case 'Implements interface':
                    $knownInterfaces = [
                        '1'     => EntityInterface::class,
                        '2'     => InitializableInterface::class,
                        '3'     => ImportableInterface::class,
                        '4'     => 'other',
                    ];
                    
                    $userChoice = $io->choice('Choose interface', $knownInterfaces);
                    
                    if ($userChoice === 'other') {
                        $generator->addInterface($io->ask('Enter full name class:'));
                    } else {
                        $generator->addInterface($userChoice);
                    }
                    break;

                case 'Extensions':
                    $knownTraits = [
                        '1'     => ArrayInjector::class,
                        '2'     => 'other',
                    ];
                    
                    $userChoice = $io->choice('Choose extension', $knownTraits);
                    
                    if ($userChoice === 'other') {
                        $generator->addTrait($io->ask('Enter full name class:'));
                    } else {
                        $generator->addTrait($userChoice);
                    }
                    break;
                
                case 'Enable/disable get method shortcut':
                    $generator->useGetShortcutMethod($generator->getUseGetShortcutMethod() === false);
                    break;

                case 'Change field visibility':
                    $generator->setFieldVisibility(
                        $generator->getFieldVisibility() == EntityGenerator::FIELD_VISIBLE_PROTECTED
                        ? EntityGenerator::FIELD_VISIBLE_PRIVATE
                        : EntityGenerator::FIELD_VISIBLE_PROTECTED
                    );
                    break;

                case 'Enable/disable typed properties (PHP >= 7.4 only)':
                    $generator->useTypedProperties($generator->getUseTypedProperties() === false);
                    break;

                default:
                case 'skip':
                    return;
            }
        }
    }
}