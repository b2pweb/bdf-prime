<?php

namespace Bdf\Prime\Entity;

use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Customer;
use Bdf\Prime\Document;
use Bdf\Prime\Folder;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Task;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class EntityGeneratorTest extends TestCase
{
    use PrimeTestCase;
    
    /**
     *
     */
    protected function setUp(): void
    {
        $this->configurePrime();
    }
    
    /**
     *
     */
    public function test_properties()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringContainsString('protected $id;', $classContent);
        $this->assertStringContainsString('protected $name;', $classContent);
        $this->assertStringContainsString('protected $roles = [];', $classContent);
    }

    /**
     *
     */
    public function test_methods_declaration()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringContainsString('function setId($id)', $classContent);
        $this->assertStringContainsString('function id()', $classContent);
        $this->assertStringContainsString('function setName($name)', $classContent);
        $this->assertStringContainsString('function name()', $classContent);
        $this->assertStringContainsString('function setRoles(array $roles)', $classContent);
    }

    /**
     *
     */
    public function test_shortcut_method()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->useGetShortcutMethod(false);
        $classContent = $generator->generate(User::repository()->mapper());
        
        $this->assertStringContainsString('function getId()', $classContent);
    }

    /**
     *
     */
    public function test_namespace()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringContainsString('namespace Bdf\Prime;', $classContent);
    }

    /**
     *
     */
    public function test_generate_methods()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->setGenerateStubMethods(false);

        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringNotContainsString('function setId($id)', $classContent);
    }

    /**
     *
     */
    public function test_properties_visibility()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->setFieldVisibility(EntityGenerator::FIELD_VISIBLE_PRIVATE);

        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringContainsString('private $id;', $classContent);
    }

    /**
     *
     */
    public function test_add_interface()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->setInterfaces([
            'Bdf\Prime\Entity\EntityInterface',
            'Bdf\Prime\Entity\InitializableInterface',
            ImportableInterface::class
        ]);

        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringContainsString('use Bdf\Prime\Entity\EntityInterface;', $classContent);
        $this->assertStringContainsString('use Bdf\Prime\Entity\InitializableInterface;', $classContent);
        $this->assertStringContainsString('use Bdf\Prime\Entity\ImportableInterface;', $classContent);
        $this->assertStringContainsString('implements EntityInterface, InitializableInterface, ImportableInterface', $classContent);
    }

    /**
     *
     */
    public function test_entity_interface_add_inherit_methods()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->addInterface(ImportableInterface::class);

        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringContainsString('$this->import($data);', $classContent);
    }

    /**
     *
     */
    public function test_set_extends()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->setClassToExtend('Bdf\Prime\Customer');

        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringContainsString('use Bdf\Prime\Customer;', $classContent);
        $this->assertStringContainsString('extends Customer', $classContent);
    }

    /**
     *
     */
    public function test_order_extends_implements()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->addInterface('Bdf\Prime\Entity\EntityInterface');
        $generator->setClassToExtend(Customer::class);

        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringContainsString('use Bdf\Prime\Customer;', $classContent);
        $this->assertStringContainsString('use Bdf\Prime\Entity\EntityInterface;', $classContent);
        $this->assertStringContainsString('extends Customer implements EntityInterface', $classContent);
    }

    /**
     *
     */
    public function test_add_trait()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->addTrait('TestTrait');

        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringContainsString('use \\TestTrait;', $classContent);
    }

    /**
     *
     */
    public function test_set_traits()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->setTraits(['TestTrait1', 'TestTrait2']);

        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringContainsString('use \\TestTrait1;', $classContent);
        $this->assertStringContainsString('use \\TestTrait2;', $classContent);
    }

    /**
     *
     */
    public function test_collection_of_buildin_type()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(User::repository()->mapper());

        $setter = <<<EOF
    /**
     * Set roles
     *
     * @param array \$roles
     *
     * @return \$this
     */
    public function setRoles(array \$roles)
    {
        \$this->roles = \$roles;

        return \$this;
    }
EOF;
        $this->assertStringContainsString($setter, $classContent);

        $getter = <<<EOF
    /**
     * Get roles
     *
     * @return array
     */
    public function roles()
    {
        return \$this->roles;
    }
EOF;

        $this->assertStringContainsString($getter, $classContent);
    }

    /**
     *
     */
    public function test_relation_has_one()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringContainsString('use Bdf\Prime\Customer;', $classContent);
        $this->assertStringContainsString('protected $customer;', $classContent);
        $this->assertStringContainsString('$this->customer = new Customer();', $classContent);
        $this->assertStringContainsString('function setCustomer(Customer $customer)', $classContent);
        $this->assertStringContainsString('function customer()', $classContent);
    }

    /**
     *
     */
    public function test_relation_has_many()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(Customer::repository()->mapper());

        $this->assertStringContainsString('use Bdf\Prime\Document;', $classContent);
        $this->assertStringContainsString('protected $documents = [];', $classContent);
        $this->assertStringContainsString('function addDocument(Document $document)', $classContent);
        $this->assertStringContainsString('function setDocuments(array $documents)', $classContent);
        $this->assertStringContainsString('function documents()', $classContent);
    }

    /**
     *
     */
    public function test_relation_has_many_through()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(Customer::repository()->mapper());

        $this->assertStringContainsString('use Bdf\Prime\Pack;', $classContent);
        $this->assertStringContainsString('protected $packs = [];', $classContent);
        $this->assertStringContainsString('function addPack(Pack $pack)', $classContent);
        $this->assertStringContainsString('function setPacks(array $packs)', $classContent);
        $this->assertStringContainsString('function packs()', $classContent);
    }

    /**
     *
     */
    public function test_relation_morph_one()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(Document::repository()->mapper());

        $this->assertStringContainsString('protected $uploader;', $classContent);
        $this->assertStringContainsString('function setUploader(Admin $uploader)', $classContent);
        $this->assertStringContainsString('function uploader()', $classContent);
    }

    /**
     *
     */
    public function test_relation_with_wrapper()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(Folder::repository()->mapper());

        $this->assertStringContainsString('use '.EntityCollection::class.';', $classContent);
        $this->assertStringContainsString('$this->files = TestFile::collection();', $classContent);

        $this->assertStringContainsString(<<<'PHP'
    /**
     * @var EntityCollection|TestFile[]
     */
    protected $files;
PHP
    , $classContent
);

        $this->assertStringContainsString(<<<'PHP'
    /**
     * Set files
     *
     * @param TestFile[]|EntityCollection $files
     *
     * @return $this
     */
    public function setFiles(EntityCollection $files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * Get files
     *
     * @return TestFile[]|EntityCollection
     */
    public function files()
    {
        return $this->files;
    }
PHP
    , $classContent
);
    }

    /**
     *
     */
    public function test_own_properties()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(Place::repository()->mapper());

        $this->assertStringContainsString('protected $id;', $classContent);
        $this->assertStringContainsString('protected $bag;', $classContent);
        $this->assertStringNotContainsString('protected $bag.foo', $classContent);
        $this->assertStringNotContainsString('protected $bagfoo', $classContent);
        $this->assertStringNotContainsString('protected $foo', $classContent);
    }

    /**
     *
     */
    public function test_default_date_instantiation()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(Task::repository()->mapper());

        $this->assertStringContainsString("\$this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));", $classContent);
        $this->assertStringContainsString("\$this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));", $classContent);
        $this->assertStringContainsString("\$this->deletedAt = new \DateTime();", $classContent);
    }

    /**
     *
     */
    public function test_mutators_getters_and_defaults()
    {
        $generator = new EntityGenerator(Prime::service());

        $this->assertSame(4, $generator->getNumSpaces());
        $generator->setNumSpaces(2);
        $this->assertSame(2, $generator->getNumSpaces());

        $this->assertSame('.php', $generator->getExtension());
        $generator->setExtension('.class.php');
        $this->assertSame('.class.php', $generator->getExtension());

        $this->assertSame(null, $generator->getClassToExtend());
        $generator->setClassToExtend(Customer::class);
        $this->assertSame(Customer::class, $generator->getClassToExtend());

        $interfaces = [
            'Bdf\Prime\Entity\EntityInterface',
            'Bdf\Prime\Entity\InitializableInterface',
            ImportableInterface::class
        ];
        $this->assertEquals([], $generator->getInterfaces());
        $generator->setInterfaces($interfaces);
        $this->assertSame($interfaces, $generator->getInterfaces());

        $this->assertEquals([], $generator->getTraits());
        $generator->addTrait('TestTrait');
        $this->assertEquals(['TestTrait' => 'TestTrait'], $generator->getTraits());

        $this->assertSame(EntityGenerator::FIELD_VISIBLE_PROTECTED, $generator->getFieldVisibility());
        $generator->setFieldVisibility(EntityGenerator::FIELD_VISIBLE_PRIVATE);
        $this->assertSame(EntityGenerator::FIELD_VISIBLE_PRIVATE, $generator->getFieldVisibility());

        $this->assertFalse($generator->getUpdateEntityIfExists());
        $generator->setUpdateEntityIfExists(true);
        $this->assertTrue($generator->getUpdateEntityIfExists());

        $this->assertFalse($generator->getRegenerateEntityIfExists());
        $generator->setRegenerateEntityIfExists(true);
        $this->assertTrue($generator->getRegenerateEntityIfExists());

        $this->assertTrue($generator->getGenerateStubMethods());
        $generator->setGenerateStubMethods(false);
        $this->assertFalse($generator->getGenerateStubMethods());

        $this->assertTrue($generator->getUseGetShortcutMethod());
        $generator->useGetShortcutMethod(false);
        $this->assertFalse($generator->getUseGetShortcutMethod());
    }
}