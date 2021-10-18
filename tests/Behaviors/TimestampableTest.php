<?php

namespace Bdf\Prime\Behaviors;

use DateTime;
use DateTimeImmutable;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class TimestampableTest extends TestCase
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
        $pack->declareEntity(TimestampableEntity::class);
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
        $mapper = new TimestampableEntityMapper(Prime::service(), TimestampableEntity::class);

        $fields = $mapper->fields();

        $this->assertTrue(isset($fields['dateInsert']));
        $this->assertTrue(isset($fields['updatedAt']));

        $this->assertEquals('datetime', $fields['dateInsert']['type']);
        $this->assertEquals('datetime', $fields['updatedAt']['type']);
        $this->assertEquals('date_insert', $fields['dateInsert']['alias']);
        $this->assertEquals('updated_at', $fields['updatedAt']['alias']);
    }

    /**
     *
     */
    public function test_inserting()
    {
        $entity = new TimestampableEntity('name');

        $now = new DateTime();
        $entity->insert();

        $this->assertEqualsWithDelta($now, $entity->dateInsert, 1);
        $this->assertEquals(null, $entity->updatedAt);
        $this->assertInstanceOf(DateTime::class, $entity->dateInsert);
    }

    /**
     *
     */
    public function test_update()
    {
        $entity = new TimestampableEntity('name');

        $now = new DateTime();
        $entity->id = 1;
        $entity->update();

        $this->assertEquals(null, $entity->dateInsert);
        $this->assertEqualsWithDelta($now, $entity->updatedAt, 1);
        $this->assertInstanceOf(DateTimeImmutable::class, $entity->updatedAt);
    }

    /**
     *
     */
    public function test_update_property()
    {
        $now = new DateTime();

        $entity = new TimestampableEntity('name');
        $entity->id = 1;
        $entity->insert();
        $entity->update(['name']);

        $this->assertEqualsWithDelta($now, $entity->updatedAt, 1);
        $this->assertInstanceOf(DateTimeImmutable::class, $entity->updatedAt);

        $entity = TimestampableEntity::repository()->refresh($entity);

        $this->assertEqualsWithDelta($now, $entity->updatedAt, 1);
        $this->assertInstanceOf(DateTimeImmutable::class, $entity->updatedAt);
    }
}


class TimestampableEntity extends Model
{
    public $id;
    public $name;
    public $dateInsert;
    public $updatedAt;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

class TimestampableEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'behavior_entity',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')->autoincrement()
            ->string('name', 60)
            ->dateTime('updatedAt')->alias('updated_at')->nillable()->phpClass(DateTimeImmutable::class)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinedBehaviors(): array
    {
        return [
            new Timestampable(['dateInsert', 'date_insert'], 'updatedAt'),
        ];
    }
}