<?php

namespace Php81;

use Bdf\Prime\Bench\HydratorGeneration;
use Bdf\Prime\Entity\EntityGenerator;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\Types\BackedEnumType;
use Bdf\Prime\Types\UnitEnumType;
use LogicException;
use Php81\Fixtures\DirectionEnum;
use Php81\Fixtures\DuckMovement;
use Php81\Fixtures\MovementMeanEnum;
use Php81\Fixtures\SpeedEnum;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    use PrimeTestCase;
    use HydratorGeneration;

    private array $movements;

    protected function setUp(): void
    {
        $this->primeStart();
    }

    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }

    protected function declareTestData(TestPack $pack): void
    {
        $pack->persist($this->movements = [
            new DuckMovement(
                duckId: 1,
                fromX: 0,
                fromY: 0,
                direction: DirectionEnum::South,
                speed: SpeedEnum::Normal,
                distance: 10,
                mean: MovementMeanEnum::Walk
            ),
            new DuckMovement(
                duckId: 1,
                fromX: 0,
                fromY: -10,
                direction: DirectionEnum::West,
                speed: SpeedEnum::Slow,
                distance: 4,
                mean: MovementMeanEnum::Swim
            ),
        ]);
    }

    public function test_dbal_value()
    {
        $values = DuckMovement::repository()->builder()->execute()->all();

        $this->assertEquals([
            [
                'id' => 1,
                'duck_id' => 1,
                'from_x' => 0,
                'from_y' => 0,
                'distance' => 10,
                'direction' => 'South',
                'speed' => 2,
                'mean' => 'walk',
            ],
            [
                'id' => 2,
                'duck_id' => 1,
                'from_x' => 0,
                'from_y' => -10,
                'distance' => 4,
                'direction' => 'West',
                'speed' => 1,
                'mean' => 'swim',
            ],
        ], $values);
    }

    public function test_from_database()
    {
        $entities = DuckMovement::all();

        $this->assertEquals($this->movements, $entities);

        $this->assertSame(DirectionEnum::South, $entities[0]->direction);
        $this->assertSame(SpeedEnum::Normal, $entities[0]->speed);
        $this->assertSame(MovementMeanEnum::Walk, $entities[0]->mean);

        $this->assertSame(DirectionEnum::West, $entities[1]->direction);
        $this->assertSame(SpeedEnum::Slow, $entities[1]->speed);
        $this->assertSame(MovementMeanEnum::Swim, $entities[1]->mean);
    }

    public function test_query()
    {
        $this->assertEquals($this->movements[0], DuckMovement::where('direction', DirectionEnum::South)->first());
        $this->assertEquals($this->movements[1], DuckMovement::where('speed', SpeedEnum::Slow)->first());
        $this->assertEquals($this->movements[1], DuckMovement::where('mean', MovementMeanEnum::Swim)->first());
    }

    public function test_query_dbal()
    {
        $conn = DuckMovement::repository()->connection();

        $this->assertEquals(4, $conn->from('duck_movement')->where('direction', DirectionEnum::West)->inRow('distance'));
        $this->assertEquals(10, $conn->from('duck_movement')->where('speed', SpeedEnum::Normal)->inRow('distance'));
        $this->assertEquals(10, $conn->from('duck_movement')->where('mean', MovementMeanEnum::Walk)->inRow('distance'));
    }

    public function test_unit_enum_type()
    {
        $type = new UnitEnumType(UnitEnumType::UNIT_ENUM);

        $this->assertSame('East', $type->toDatabase(DirectionEnum::East));
        $this->assertSame('Foo', $type->toDatabase('Foo'));
        $this->assertNull($type->toDatabase(null));

        $this->assertSame(DirectionEnum::East, $type->fromDatabase('East', ['className' => DirectionEnum::class]));
        $this->assertNull($type->fromDatabase(null, ['className' => DirectionEnum::class]));
        $this->assertNull($type->fromDatabase('invalid', ['className' => DirectionEnum::class]));

        try {
            $type->fromDatabase('East', ['className' => 'Foo']);
            $this->fail();
        } catch (LogicException $e) {
        }

        try {
            $type->fromDatabase('East');
            $this->fail();
        } catch (LogicException $e) {
        }
    }

    public function test_backed_enum_type()
    {
        $type = new BackedEnumType(BackedEnumType::STRING_ENUM);

        $this->assertSame('fly', $type->toDatabase(MovementMeanEnum::Fly));
        $this->assertSame('Foo', $type->toDatabase('Foo'));
        $this->assertNull($type->toDatabase(null));

        $this->assertSame(MovementMeanEnum::Fly, $type->fromDatabase('fly', ['className' => MovementMeanEnum::class]));
        $this->assertNull($type->fromDatabase(null, ['className' => MovementMeanEnum::class]));
        $this->assertNull($type->fromDatabase('invalid', ['className' => MovementMeanEnum::class]));

        try {
            $type->fromDatabase('East', ['className' => 'Foo']);
            $this->fail();
        } catch (LogicException $e) {
        }

        try {
            $type->fromDatabase('East', ['className' => DirectionEnum::class]);
            $this->fail();
        } catch (LogicException $e) {
        }

        try {
            $type->fromDatabase('East');
            $this->fail();
        } catch (LogicException $e) {
        }
    }

    public function test_entity_generator()
    {
        $generator = new EntityGenerator($this->prime());
        $generator->setClassToExtend(Model::class);
        $generator->useTypedProperties();
        $generator->useConstructorPropertyPromotion();
        $generator->useGetShortcutMethod();

        $code = $generator->generate(DuckMovement::repository()->mapper());

        $this->assertStringContainsString(<<<'PHP'
            public function __construct(
                /**
                 * @var integer
                 */
                protected ?int $id = null,
                /**
                 * @var integer
                 */
                protected ?int $duckId = null,
                /**
                 * @var integer
                 */
                protected ?int $fromX = null,
                /**
                 * @var integer
                 */
                protected ?int $fromY = null,
                /**
                 * @var integer
                 */
                protected ?int $distance = null,
                /**
                 * @var DirectionEnum
                 */
                protected ?DirectionEnum $direction = null,
                /**
                 * @var MovementMeanEnum
                 */
                protected ?MovementMeanEnum $mean = null,
                /**
                 * @var SpeedEnum
                 */
                protected ?SpeedEnum $speed = null,
            ) {
            }

        PHP
            , $code);
    }

    public function test_generated_hydrator()
    {
        $this->setUpGeneratedHydrators(DuckMovement::class);

        $this->assertEquals($this->movements, DuckMovement::all());

        $entity = new DuckMovement(
            duckId: 1,
            fromX: -4,
            fromY: -10,
            direction: DirectionEnum::North,
            speed: SpeedEnum::Fast,
            distance: 8,
            mean: MovementMeanEnum::Fly
        );
        $entity->insert();

        $this->assertEquals($entity, DuckMovement::where('mean', MovementMeanEnum::Fly)->first());
    }
}
