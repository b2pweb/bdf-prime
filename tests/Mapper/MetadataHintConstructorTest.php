<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Entity\Instantiator\InstantiatorInterface;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 *
 */
class MetadataHintConstructorTest extends TestCase
{
    /**
     *
     */
    public function test_optional_arg()
    {
        $meta = TestEntity::repository()->metadata();

        $this->assertEquals(TestEntity::class, $meta->entityClass);
        $this->assertEquals(InstantiatorInterface::USE_CONSTRUCTOR_HINT, $meta->instantiatorHint);
    }

    /**
     *
     */
    public function test_stdclass()
    {
        $meta = Model::locator()->repository(__NAMESPACE__.'\\TestStdClass')->metadata();

        $this->assertEquals(stdClass::class, $meta->entityClass);
        $this->assertEquals(InstantiatorInterface::USE_CONSTRUCTOR_HINT, $meta->instantiatorHint);
    }

    /**
     *
     */
    public function test_no_constructor()
    {
        $meta = TestNoConstructor::repository()->metadata();

        $this->assertEquals(TestNoConstructor::class, $meta->entityClass);
        $this->assertEquals(InstantiatorInterface::USE_CONSTRUCTOR_HINT, $meta->instantiatorHint);
    }

    /**
     *
     */
    public function test_no_optional()
    {
        $meta = TestNoOptional::repository()->metadata();

        $this->assertEquals(TestNoOptional::class, $meta->entityClass);
        $this->assertNull($meta->instantiatorHint);
    }
}



class TestStdClassMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection'   => 'test',
            'table'        => 'test',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder->string('id');
    }
}

class TestNoConstructor extends Model
{
    public $id;
}
class TestNoConstructorMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection'   => 'test',
            'table'        => 'test',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder->string('id');
    }
}

class TestNoOptional extends Model
{
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }
}
class TestNoOptionalMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection'   => 'test',
            'table'        => 'test',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder->string('id');
    }
}