<?php

namespace Bdf\Prime\Record;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use InvalidArgumentException;
use ReflectionClass;

use function sprintf;

/**
 * Handle a single record class instantiation
 *
 * @template R as object
 * @internal
 */
final class RecordInstantiator
{
    /**
     * @var array<string, string|ExpressionInterface>|null
     */
    private ?array $projection = null;

    public function __construct(
        /**
         * @var class-string<R>
         */
        public readonly string $recordClass,

        /**
         * @var list<RecordParameterInterface>
         */
        public readonly array $fields,
    ) {
    }

    public function projection(): array
    {
        if (($projection = $this->projection) !== null) {
            return $projection;
        }

        $projection = [];

        foreach ($this->fields as $field) {
            $projection = [...$projection, ...$field->projection()];
        }

        return $projection;
    }

    /**
     * @param array $data
     * @param PlatformInterface $platform
     * @return R
     */
    public function instantiate(array $data, PlatformInterface $platform): object
    {
        $constructorParameters = [];

        foreach ($this->fields as $field) {
            $constructorParameters[] = $field->value($platform, $data);
        }

        $recordClass = $this->recordClass;
        return new $recordClass(...$constructorParameters);
    }

    /**
     * @param class-string<T> $recordClass
     * @param array|null $attributesMetadata The metadata of attributes, if called from an ORM query. Null on DBAL query.
     * @param string|null $fieldPrefix Prefix to add to fields
     *
     * @return self<T>
     * @template T as object
     */
    public static function fromRecordClass(string $recordClass, ?array $attributesMetadata = null, ?string $fieldPrefix = null): self
    {
        $reflectionParameters = new ReflectionClass($recordClass)->getConstructor()?->getParameters();

        if ($reflectionParameters === null) {
            throw new InvalidArgumentException(sprintf('The record class %s must have a constructor', $recordClass));
        }

        $parameters = [];

        foreach ($reflectionParameters as $parameter) {
            $recordParameter = Embedded::fromReflectionParameter($parameter, $attributesMetadata, $fieldPrefix);

            if ($recordParameter === null) {
                $recordParameter = Field::fromReflectionParameter($parameter);

                if ($fieldPrefix !== null && $fieldPrefix !== '') {
                    $recordParameter = $recordParameter->with(
                        name: $fieldPrefix . $recordParameter->name,
                    );
                }

                if ($attributesMetadata) {
                    $recordParameter = $recordParameter->withAttributesMetadata($attributesMetadata);
                }
            }

            $parameters[] = $recordParameter;
        }

        return new self($recordClass, $parameters);
    }
}
