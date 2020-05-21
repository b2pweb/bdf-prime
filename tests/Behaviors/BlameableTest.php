<?php

namespace Bdf\Prime\Behaviors;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class BlameableTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity('Bdf\Prime\Behaviors\BlameableEntity');
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
    }

    /**
     *
     */
    public function test_configure_fields()
    {
        $mapper = new BlameableEntityMapper(Prime::service(), 'Bdf\Prime\Behaviors\BlameableEntity');

        $fields = $mapper->fields();

        $this->assertTrue(isset($fields['createdBy']));
        $this->assertTrue(isset($fields['updatedBy']));
        $this->assertEquals('string', $fields['createdBy']['type']);
        $this->assertEquals('string', $fields['updatedBy']['type']);
        $this->assertEquals('created_by', $fields['createdBy']['alias']);
        $this->assertEquals('updated_by', $fields['updatedBy']['alias']);
    }

    /**
     *
     */
    public function test_inserting()
    {
        $entity = new BlameableEntity('name');

        $entity->insert();

        $this->assertEquals(1, $entity->createdBy);
        $this->assertEquals(null, $entity->updatedBy);
    }

    /**
     *
     */
    public function test_update()
    {
        $entity = new BlameableEntity('name');

        $entity->id = 1;
        $entity->update();

        $this->assertEquals(null, $entity->createdBy);
        $this->assertEquals(1, $entity->updatedBy);
    }

    /**
     *
     */
    public function test_update_property()
    {
        $entity = new BlameableEntity('name');
        $entity->id = 1;
        $entity->insert();
        $entity->update(['name']);
        $entity = BlameableEntity::get(1);

        $this->assertEquals(1, $entity->updatedBy);
    }
}


class BlameableEntity extends Model
{
    public $id;
    public $name;
    public $createdBy;
    public $updatedBy;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

class BlameableEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table'      => 'behavior_entity',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
    {
        $builder
            ->bigint('id')->autoincrement()
            ->string('name', 60)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinedBehaviors()
    {
        return [
            new Blameable(function() {
                return 1;
            }),
        ];
    }
}