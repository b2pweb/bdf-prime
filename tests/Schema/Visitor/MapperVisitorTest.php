<?php

namespace Bdf\Prime\Schema\Visitor;

use Bdf\Prime\Document;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MapperVisitorTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    public function setUp(): void
    {
        $this->primeStart();
    }

    /**
     *
     */
    public function tearDown(): void
    {
        $this->primeStop();
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([
            Document::class,
        ]);
    }

    /**
     *
     */
    public function test_functionnal()
    {
        $connection = $this->prime()->connection();
        $schemaManager = $connection->schema();

        $schema = $schemaManager->schema(
            $schemaManager->load('document_')
        );

        $visitor = new MapperVisitor($connection->getName());
        $schema->visit($visitor);

        $this->assertEquals($this->getExpectedDocumentMapper(), $visitor->getOutput());
    }

    /**
     * @return string
     */
    private function getExpectedDocumentMapper()
    {
        return <<<'EOF'
<?php

use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Builder\FieldBuilder;

/**
 *
 */
class DocumentMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'database'   => 'test',
            'table'      => 'document_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder->integer('id')->autoincrement()->alias('id_');
        $builder->bigint('customerId')->alias('customer_id');
        $builder->string('uploaderType', 60)->alias('uploader_type');
        $builder->bigint('uploaderId')->alias('uploader_id');
        $builder->string('contactName', 255)->nillable()->alias('contact_name');
        $builder->string('contactAddress', 255)->nillable()->alias('contact_address');
        $builder->string('contactCity', 255)->nillable()->alias('contact_city');
    }
}


EOF;
    }
}
