<?php

namespace Bdf\Prime\Entity;

use Bdf\Prime\Admin;
use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Company;
use Bdf\Prime\Customer;
use Bdf\Prime\Document;
use Bdf\Prime\Faction;
use Bdf\Prime\Folder;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Project;
use Bdf\Prime\Task;
use Bdf\Prime\TestEntity;
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
    public function test_properties_with_type()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->useTypedProperties();
        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringContainsString('protected string $id;', $classContent);
        $this->assertStringContainsString('protected string $name;', $classContent);
        $this->assertStringContainsString('protected array $roles = [];', $classContent);

        // Should use bool instead of boolean
        $classContent = $generator->generate(Faction::repository()->mapper());
        $this->assertStringContainsString('protected bool $enabled = true;', $classContent);
        $this->assertStringContainsString('protected ?string $domain = null;', $classContent);

        // Should use int instead of integer
        $classContent = $generator->generate(Project::repository()->mapper());
        $this->assertStringContainsString('protected int $id;', $classContent);

        // Autoincrement should be null by default
        $classContent = $generator->generate(Task::repository()->mapper());
        $this->assertStringContainsString('protected ?int $id = null;', $classContent);
    }

    /**
     *
     */
    public function test_properties_with_type_on_embedded()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->useTypedProperties();
        $classContent = $generator->generate(Document::repository()->mapper());

        $this->assertStringContainsString('protected Contact $contact;', $classContent);
    }

    /**
     *
     */
    public function test_methods_declaration()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(User::repository()->mapper());

        $this->assertStringContainsString('function setId(string $id): self', $classContent);
        $this->assertStringContainsString('function id(): string', $classContent);
        $this->assertStringContainsString('function setName(string $name): self', $classContent);
        $this->assertStringContainsString('function name(): string', $classContent);
        $this->assertStringContainsString('function setRoles(array $roles): self', $classContent);
    }

    /**
     *
     */
    public function test_shortcut_method()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->useGetShortcutMethod(false);
        $classContent = $generator->generate(User::repository()->mapper());
        
        $this->assertStringContainsString('function getId(): string', $classContent);
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

        $this->assertStringNotContainsString('function setId(?int $id): self', $classContent);
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
    public function setRoles(array \$roles): self
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
    public function roles(): array
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

        $this->assertStringContainsString('protected $customer;', $classContent);
        $this->assertStringContainsString('$this->customer = new Customer();', $classContent);
        $this->assertStringContainsString('function setCustomer(Customer $customer): self', $classContent);
        $this->assertStringContainsString('function customer(): Customer', $classContent);

        $generator->useTypedProperties();
        $classContent = $generator->generate(User::repository()->mapper());
        $this->assertStringContainsString('protected ?Customer $customer = null;', $classContent);
    }

    /**
     *
     */
    public function test_relation_has_many()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(Customer::repository()->mapper());

        $this->assertStringContainsString('protected $documents = [];', $classContent);
        $this->assertStringContainsString('function addDocument(Document $document): self', $classContent);
        $this->assertStringContainsString('function setDocuments(array $documents): self', $classContent);
        $this->assertStringContainsString('function documents(): array', $classContent);

        $generator->useTypedProperties();
        $classContent = $generator->generate(Customer::repository()->mapper());
        $this->assertStringContainsString('protected ?array $documents = [];', $classContent);
    }

    /**
     *
     */
    public function test_relation_has_many_through()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(Customer::repository()->mapper());

        $this->assertStringContainsString('protected $packs = [];', $classContent);
        $this->assertStringContainsString('function addPack(Pack $pack): self', $classContent);
        $this->assertStringContainsString('function setPacks(array $packs): self', $classContent);
        $this->assertStringContainsString('function packs(): array', $classContent);

        $generator->useTypedProperties();
        $classContent = $generator->generate(Customer::repository()->mapper());
        $this->assertStringContainsString('protected ?array $packs = [];', $classContent);
    }

    /**
     *
     */
    public function test_relation_morph_one()
    {
        $generator = new EntityGenerator(Prime::service());
        $classContent = $generator->generate(Document::repository()->mapper());

        $this->assertStringContainsString('protected $uploader;', $classContent);
        $this->assertStringContainsString('function setUploader(Admin $uploader): self', $classContent);
        $this->assertStringContainsString('function uploader(): Admin', $classContent);

        // Normal ?
        $generator->useTypedProperties();
        $classContent = $generator->generate(Document::repository()->mapper());
        $this->assertStringContainsString('protected ?Admin $uploader = null;', $classContent);
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
    public function setFiles(EntityCollection $files): self
    {
        $this->files = $files;

        return $this;
    }

    /**
     * Get files
     *
     * @return TestFile[]|EntityCollection
     */
    public function files(): EntityCollection
    {
        return $this->files;
    }
PHP
    , $classContent
);

        $generator->useTypedProperties();
        $classContent = $generator->generate(Folder::repository()->mapper());
        $this->assertStringContainsString('protected ?EntityCollection $files = null;', $classContent);
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

    public function test_useConstructorPropertyPromotion()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->useConstructorPropertyPromotion();
        $generator->useTypedProperties();

        $classContent = $generator->generate(TestEntity::repository()->mapper());
        $this->assertStringContainsString(<<<'PHP'
    public function __construct(
        /**
         * @var integer
         */
        protected ?int $id = null,
        /**
         * @var string
         */
        protected ?string $name = null,
        /**
         * @var \DateTime
         */
        protected ?\DateTime $dateInsert = null,
        /**
         * @var TestEmbeddedEntity
         */
        protected ?TestEmbeddedEntity $foreign = null,
    ) {
        $this->foreign ??= new TestEmbeddedEntity();
    }
PHP
        , $classContent);
    }

    /**
     *
     */
    public function test_default_date_instantiation_constructorPropertyPromotion()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->useConstructorPropertyPromotion();
        $generator->useTypedProperties();

        $classContent = $generator->generate(Task::repository()->mapper());
        $this->assertStringContainsString(<<<'PHP'
    public function __construct(
        /**
         * @var integer
         */
        protected ?int $id = null,
        /**
         * @var string
         */
        protected ?string $name = null,
        /**
         * @var string
         */
        protected ?string $type = null,
        /**
         * @var string
         */
        protected ?string $targetId = '0',
        /**
         * @var string
         */
        protected ?string $overridenProperty = null,
        /**
         * @var \DateTimeImmutable
         */
        protected ?\DateTimeImmutable $createdAt = null,
        /**
         * @var \DateTimeImmutable
         */
        protected ?\DateTimeImmutable $updatedAt = null,
        /**
         * @var \DateTime
         */
        protected ?\DateTime $deletedAt = null,
    ) {
        $this->createdAt ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->updatedAt ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->deletedAt ??= new \DateTime();
    }

PHP
            , $classContent);
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

    /**
     * @param class-string<Model>
     * @dataProvider provideEntities
     */
    public function test_generated_code_compile(string $entity)
    {
        $generator = new EntityGenerator(Prime::service());

        $this->assertValidPHP($generator->generate($entity::repository()->mapper()));

        $generator->useTypedProperties();
        $this->assertValidPHP($generator->generate($entity::repository()->mapper()));

        if (PHP_MAJOR_VERSION >= 8) {
            $generator->useConstructorPropertyPromotion();
            $this->assertValidPHP($generator->generate($entity::repository()->mapper()));
        }
    }

    public function test_indentation()
    {
        $generator = new EntityGenerator(Prime::service());
        $entity = $generator->generateEntityClass(Company::repository()->mapper());

        $this->assertEquals(<<<'PHP'
<?php

namespace Bdf\Prime;

/**
 * Company
 */
class Company
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
}

PHP
        , $entity);


        $generator->setNumSpaces(2);
        $entity = $generator->generateEntityClass(Company::repository()->mapper());

        $this->assertEquals(<<<'PHP'
<?php

namespace Bdf\Prime;

/**
 * Company
 */
class Company
{
  /**
   * @var integer
   */
  protected $id;

  /**
   * @var string
   */
  protected $name;

  /**
   * Set id
   *
   * @param integer $id
   *
   * @return $this
   */
  public function setId(int $id): self
  {
    $this->id = $id;

    return $this;
  }

  /**
   * Get id
   *
   * @return integer
   */
  public function id(): int
  {
    return $this->id;
  }

  /**
   * Set name
   *
   * @param string $name
   *
   * @return $this
   */
  public function setName(string $name): self
  {
    $this->name = $name;

    return $this;
  }

  /**
   * Get name
   *
   * @return string
   */
  public function name(): string
  {
    return $this->name;
  }
}

PHP
            , $entity);
    }

    public function test_generate_update_already_up_to_date()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->setUpdateEntityIfExists(true);

        $entity = $generator->generate(Company::repository()->mapper(), __DIR__.'/_files/company_up_to_date.php');
        $this->assertStringEqualsFile(__DIR__.'/_files/company_up_to_date.php', $entity);
    }

    public function test_generate_update_with_missing_property()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->setUpdateEntityIfExists(true);

        $entity = $generator->generate(Company::repository()->mapper(), __DIR__ . '/_files/company_with_missing_property.php');
        $this->assertStringEqualsFile(__DIR__.'/_files/company_up_to_date.php', $entity);
    }

    public function test_generate_update_with_missing_accessors()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->setUpdateEntityIfExists(true);

        $entity = $generator->generate(Company::repository()->mapper(), __DIR__ . '/_files/company_with_missing_accessors.php');
        $this->assertStringEqualsFile(__DIR__.'/_files/company_up_to_date.php', $entity);
    }

    public function test_generate_update_with_custom_methods()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->setUpdateEntityIfExists(true);

        $entity = $generator->generate(Company::repository()->mapper(), __DIR__ . '/_files/company_with_custom_method_and_missing_properties.php');
        $this->assertStringEqualsFile(__DIR__.'/_files/company_up_to_date_with_custom_method.php', $entity);
    }

    public function test_generate_update_already_up_to_date_with_promoted_properties()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->setUpdateEntityIfExists(true);
        $generator->useTypedProperties();
        $generator->useConstructorPropertyPromotion();

        $entity = $generator->generate(Company::repository()->mapper(), __DIR__.'/_files/company_up_to_date_with_promoted_properties.php');
        $this->assertStringEqualsFile(__DIR__.'/_files/company_up_to_date_with_promoted_properties.php', $entity);
    }

    public function test_generate_update_with_missing_property_with_promoted_properties()
    {
        $generator = new EntityGenerator(Prime::service());
        $generator->setUpdateEntityIfExists(true);
        $generator->useTypedProperties();
        $generator->useConstructorPropertyPromotion();

        $entity = $generator->generate(Company::repository()->mapper(), __DIR__ . '/_files/company_up_to_date_with_promoted_properties_missing_property.php');
        $this->assertStringEqualsFile(__DIR__.'/_files/company_up_to_date_with_promoted_properties.php', $entity);
    }

    public function provideEntities(): array
    {
        return [
            [User::class], [Customer::class], [Document::class],  [Task::class], [Admin::class], [Faction::class],
        ];
    }

    public function assertValidPHP($str): void
    {
        $result = trim(shell_exec('echo ' . escapeshellarg($str) . ' | ' . PHP_BINARY . ' -l'));

        $this->assertStringStartsWith('No syntax errors detected', $result, 'On generated file ' . $str);
    }
}
