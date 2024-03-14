<?php

namespace Bdf\Prime\Behaviors;

use _files\TestClock;
use DateTime;
use DateTimeImmutable;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SoftdeleteableTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();
        $this->configurePrime();
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity(SoftDeleteableEntity::class);
        $pack->declareEntity(SoftDeleteableEntityImmut::class);
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
        $mapper = new SoftDeleteableEntityMapper(Prime::service(), 'Bdf\Prime\Behaviors\SoftDeleteableEntity');

        $fields = $mapper->fields();

        $this->assertTrue(isset($fields['deletedAt']));

        $this->assertEquals('datetime', $fields['deletedAt']['type']);
        $this->assertEquals('deleted_at', $fields['deletedAt']['alias']);
    }

    /**
     *
     */
    public function test_deleting_update_date()
    {

        $entity = new SoftDeleteableEntity('name');
        $entity->insert();
        $this->assertEquals(null, $entity->deletedAt);
        
        
        $now = new DateTime();
        $entity->delete();
        $this->assertInstanceOf(DateTime::class, $entity->deletedAt);
        $this->assertEqualsWithDelta($now, $entity->deletedAt, 1);
    }

    /**
     *
     */
    public function test_with_fixed_date_from_clock()
    {
        TestClock::set($date = new DateTimeImmutable('2018-01-01 00:00:00'));

        $entity = new SoftDeleteableEntity('name');
        $entity->insert();
        $this->assertEquals(null, $entity->deletedAt);

        $entity->delete();
        $this->assertInstanceOf(DateTime::class, $entity->deletedAt);
        $this->assertEqualsWithDelta($date, $entity->deletedAt, 1);
    }

    /**
     *
     */
    public function test_deleting_update_date_immutable()
    {

        $entity = new SoftDeleteableEntityImmut('name');
        $entity->insert();
        $this->assertEquals(null, $entity->deletedAt);


        $now = new DateTimeImmutable();
        $entity->delete();
        $this->assertInstanceOf(DateTimeImmutable::class, $entity->deletedAt);
        $this->assertEqualsWithDelta($now, $entity->deletedAt, 1);
    }

    /**
     *
     */
    public function test_deleting_update_date_immutable_from_clock()
    {
        TestClock::set($date = new DateTimeImmutable('2018-01-01 00:00:00'));

        $entity = new SoftDeleteableEntityImmut('name');
        $entity->insert();
        $this->assertEquals(null, $entity->deletedAt);


        $entity->delete();
        $this->assertInstanceOf(DateTimeImmutable::class, $entity->deletedAt);
        $this->assertEqualsWithDelta($date, $entity->deletedAt, 1);
    }

    /**
     *
     */
    public function test_find_add_filter()
    {
        $entity = new SoftDeleteableEntity('name');
        $entity->insert();
        $this->assertEquals(1, SoftDeleteableEntity::count());
        
        $entity->delete();
        $this->assertEquals(0, SoftDeleteableEntity::count());
    }

    /**
     *
     */
    public function test_find_a_deleted_entity()
    {
        $entity = new SoftDeleteableEntity('name');
        $entity->insert();
        $entity->delete();
        
        $this->assertEquals(0, SoftDeleteableEntity::count());
        $this->assertEquals(1, SoftDeleteableEntity::withoutConstraints()->count());
    }
}


class SoftDeleteableEntity extends Model
{
    public $id;
    public $name;
    public $deletedAt;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

class SoftDeleteableEntityMapper extends Mapper
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
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinedBehaviors(): array
    {
        return [
            new SoftDeleteable(),
        ];
    }
}

class SoftDeleteableEntityImmut extends Model
{
    public $id;
    public $name;
    public $deletedAt;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

class SoftDeleteableEntityImmutMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'behavior_entity_immut',
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
            ->dateTime('deletedAt')->nillable()->phpClass(DateTimeImmutable::class)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinedBehaviors(): array
    {
        return [
            new SoftDeleteable('deletedAt'),
        ];
    }
}