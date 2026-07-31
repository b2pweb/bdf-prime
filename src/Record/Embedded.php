<?php

namespace Bdf\Prime\Record;

use Attribute;
use Bdf\Prime\Platform\PlatformInterface;
use InvalidArgumentException;
use Override;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Define a mapping for an embedded sub-record to a record constructor parameter
 *
 * The sub-record is built from the fields of the same database row, prefixed by {@see Embedded::$prefix}.
 * It follows the same rules as the root record, so it can declare {@see Field} attributes, and other
 * embedded sub-records.
 *
 * Note: sub-records does not supports relations, so do not declare parameters with #[LoadRelation] attributes.
 *
 * Usage:
 * ```php
 * class MyRecord
 * {
 *     public function __construct(
 *         public readonly int $id,
 *
 *         // Map the embedded entity "contact" to the $contact parameter
 *         // The prefix is resolved from the parameter name : "contact." on an ORM query, "contact_" on a DBAL one
 *         // So the fields "contact.name" and "contact.location.city" will be used on an ORM query
 *         #[Embedded]
 *         public readonly ContactRecord $contact,
 *
 *         // The prefix can be defined explicitly
 *         // Here the fields "billing.name" and "billing.location.city" will be used
 *         #[Embedded('billing.')]
 *         public readonly ContactRecord $billing,
 *
 *         // Use an empty prefix to build the sub-record from the fields of the root record
 *         // Here the fields "uploaderId" and "uploaderType" will be used
 *         #[Embedded('')]
 *         public readonly UploaderRecord $uploader,
 *
 *         // The sub-record class can be defined explicitly, when it cannot be resolved from the parameter type
 *         #[Embedded(className: ContactRecord::class)]
 *         public readonly ContactRecordInterface $other,
 *     ) {}
 * }
 *
 * class ContactRecord
 * {
 *     public function __construct(
 *         public readonly ?string $name,
 *
 *         // Sub-records can be nested : the prefixes are concatenated
 *         // So the fields "contact.location.*" will be used for the $contact parameter of MyRecord
 *         #[Embedded]
 *         public readonly LocationRecord $location,
 *     ) {}
 * }
 *
 * class UploaderRecord
 * {
 *     public function __construct(
 *         public readonly int $uploaderId,
 *         public readonly string $uploaderType,
 *     ) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Embedded implements RecordParameterInterface
{
    public function __construct(
        /**
         * The prefix of all fields defined in the embedded sub-record.
         *
         * In case of ORM, use the dot notation (e.g. embedded.).
         * In case of raw DBAL query, use the prefix of columns, if present (e.g. embedded_).
         * If you use an empty string, no prefix will be used, so all database values will be used (can be used to create an embedded from flat data).
         * If null, the parameter name will be used, suffixed by "." or "_" depending on if the query is ORM or DBAL.
         */
        public ?string $prefix = null,

        /**
         * The sub-record class name to use.
         * If not set, will be resolved from the parameter type.
         *
         * @var class-string|null
         */
        public ?string $className = null,

        /**
         * The instantiator to use for the embedded sub-record.
         * It will be resolved automatically by using the record class name.
         */
        public ?RecordInstantiator $instantiator = null,
    ) {
    }

    #[Override]
    public function projection(): array
    {
        return $this->instantiator->projection();
    }

    #[Override]
    public function value(PlatformInterface $platform, array $data): mixed
    {
        return $this->instantiator->instantiate($data, $platform);
    }

    /**
     * Create the corresponding embedded from a reflection parameter
     *
     * @param ReflectionParameter $parameter
     * @param array|null $attributesMetadata The metadata of attributes, if called from an ORM query. Null on DBAL query.
     * @param string|null $fieldPrefix The prefix to use for the fields in case of recursive sub-record
     *
     * @return self|null The parsed embedded, or null if the parameter is not annotated with Embedded
     */
    public static function fromReflectionParameter(ReflectionParameter $parameter, ?array $attributesMetadata = null, ?string $fieldPrefix = null): ?self
    {
        foreach ($parameter->getAttributes(self::class) as $attribute) {
            $embedded = $attribute->newInstance();
            $className = $embedded->className;

            if ($className === null) {
                $type = $parameter->getType();

                if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    throw new InvalidArgumentException(sprintf(
                        'The parameter %s on %s must have a type or the #[Embedded] attribute must define a className.',
                        $parameter->getName(),
                        $parameter->getDeclaringClass()->getName(),
                    ));
                }

                $className = $type->getName();
            }

            $prefix = ($fieldPrefix ?? '') . ($embedded->prefix ?? $parameter->name . ($attributesMetadata ? '.' : '_'));

            return new self(
                prefix: $prefix,
                className: $className,
                instantiator: $embedded->instantiator ?? RecordInstantiator::fromRecordClass($className, $attributesMetadata, $prefix),
            );
        }

        return null;
    }
}
