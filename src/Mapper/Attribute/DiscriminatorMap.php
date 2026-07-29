<?php

namespace Bdf\Prime\Mapper\Attribute;

use Attribute;
use BackedEnum;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\SingleTableInheritanceMapper;
use InvalidArgumentException;

use function get_class;
use function is_string;
use function is_subclass_of;
use function sprintf;

/**
 * Configure the discriminator map for a single table inheritance mapper
 *
 * Usage with map:
 * ```php
 * #[DiscriminatorMap('typeId', [
 *     'foo' => FooMapper::class,
 *     'bar' => BarMapper::class,
 * ])]
 * class BaseMapper extends SingleTableInheritanceMapper
 * {
 *     // ...
 * }
 * ```
 *
 * Usage with enum:
 * ```php
 * class MyTypeEnum: string implements DiscriminatorMapEnumInterface
 * {
 *     case Foo = 'foo';
 *     case Bar = 'bar';
 *
 *     public function mapperClass(): string
 *     {
 *         return match ($this) {
 *             self::Foo => FooMapper::class,
 *             self::Bar => BarMapper::class,
 *         };
 *     }
 * }
 *
 * #[DiscriminatorMap('typeId', MyTypeEnum::class)]
 * class BaseMapper extends SingleTableInheritanceMapper
 * {
 *     // ...
 * }
 * ```
 *
 * @see SingleTableInheritanceMapper
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DiscriminatorMap implements MapperConfigurationInterface
{
    private string $field;

    /**
     * @var array<string, class-string<Mapper>>|class-string<DiscriminatorMapEnumInterface>
     */
    private array|string $map;

    /**
     * @param string $field
     * @param array<string, class-string<Mapper>>|class-string<DiscriminatorMapEnumInterface> $map Key-value map of the discriminator values and their corresponding mapper class names
     */
    public function __construct(string $field, array|string $map)
    {
        $this->field = $field;
        $this->map = $map;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Mapper $mapper): void
    {
        if (!$mapper instanceof SingleTableInheritanceMapper) {
            throw new InvalidArgumentException(sprintf('Cannot use %s on %s: it must inherit %s', __CLASS__, get_class($mapper), SingleTableInheritanceMapper::class));
        }

        $map = $this->map;

        if (is_string($map)) {
            $values = [];

            if (is_subclass_of($map, BackedEnum::class)) {
                foreach ($map::cases() as $case) {
                    $values[$case->value] = $case->mapperClass();
                }
            } else {
                foreach ($map::cases() as $case) {
                    $values[$case->name] = $case->mapperClass();
                }
            }

            $map = $values;
        }

        $mapper->setDiscriminatorMap($this->field, $map);
    }
}
