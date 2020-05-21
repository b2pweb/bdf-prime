<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Bench\DummyPlatform;
use Bdf\Prime\EntityArrayOf;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\Types\SqlFloatType;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Value;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ArrayOfTypeTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var ArrayOfType
     */
    private $type;

    /**
     * @var PlatformInterface
     */
    private $platform;


    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->platform = new DummyPlatform();
        $this->type = new ArrayOfType(new ArrayType(), new SqlFloatType($this->platform));
    }

    /**
     *
     */
    public function test_fromDatabase_will_filter()
    {
        $this->assertSame(null, $this->type->fromDatabase(null));
        $this->assertSame([12.0, 1.2, 4.56], $this->type->fromDatabase(',12,1.2,4.56,'));
    }

    /**
     *
     */
    public function test_toDatabaseValue()
    {
        $this->assertNull($this->type->toDatabase(null));
        $this->assertSame('', $this->type->toDatabase([]));
        $this->assertSame(',12,1.2,4.56,', $this->type->toDatabase([12.0, 1.2, 4.56]));
    }

    /**
     *
     */
    public function test_to_platform_type()
    {
        $type = $this->type->toPlatformType($this->platform);

        $this->assertInstanceOf(SqlStringType::class, $type);
    }

    /**
     *
     */
    public function test_php_type()
    {
        $this->assertSame(PhpTypeInterface::TARRAY, $this->type->phpType());
    }

    /**
     *
     */
    public function test_name()
    {
        $this->assertEquals('float[]', $this->type->name());
    }

    /**
     *
     */
    public function test_orm_functional()
    {
        $this->pack()->nonPersist(
            $entity = new EntityArrayOf([
                'floats' => [1.23, 4.56],
                'booleans' => [true, true, false],
                'dates' => [new \DateTime('2018-10-23 14:02:45'), new \DateTime('2018-02-25 15:41:32')]
            ])
        );

        $entity = EntityArrayOf::refresh($entity);

        $this->assertSame([1.23, 4.56], $entity->floats);
        $this->assertSame([true, true, false], $entity->booleans);
        $this->assertEquals([new \DateTime('2018-10-23 14:02:45'), new \DateTime('2018-02-25 15:41:32')], $entity->dates);

        $this->assertEquals($entity, EntityArrayOf::where('floats', new Value([1.23, 4.56]))->first());
    }
}
