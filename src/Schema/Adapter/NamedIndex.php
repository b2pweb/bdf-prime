<?php

namespace Bdf\Prime\Schema\Adapter;

use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\Util\Name;
use Doctrine\DBAL\Schema\AbstractAsset;

/**
 * Add a name to the index, if not already provided
 *
 * The generated name will be same as doctrine generated name
 */
final class NamedIndex implements IndexInterface
{
    public const FOR_PRIMARY = 'PRIMARY';
    public const FOR_UNIQUE  = 'UNIQ';
    public const FOR_SIMPLE  = 'IDX';

    /**
     * @var IndexInterface
     */
    private $index;

    /**
     * @var string
     */
    private $tableName;


    /**
     * NamedIndex constructor.
     *
     * @param IndexInterface $index
     * @param string $tableName
     */
    public function __construct(IndexInterface $index, string $tableName)
    {
        $this->index     = $index;
        $this->tableName = $tableName;
    }

    /**
     * {@inheritdoc}
     *
     * Generate the index name if not set.
     * For compatibility purpose, use same algo as Doctrine
     *
     * @see AbstractAsset::_generateIdentifierName()
     */
    public function name(): string
    {
        $name = $this->index->name();

        if ($this->isValidName($name)) {
            return $name;
        }

        if ($this->index->primary()) {
            return self::FOR_PRIMARY;
        }

        return Name::generate(
            $this->index->unique() ? self::FOR_UNIQUE : self::FOR_SIMPLE,
            array_merge([$this->tableName], $this->fields())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unique(): bool
    {
        return $this->index->unique();
    }

    /**
     * {@inheritdoc}
     */
    public function primary(): bool
    {
        return $this->index->primary();
    }

    /**
     * {@inheritdoc}
     */
    public function type(): int
    {
        return $this->index->type();
    }

    /**
     * {@inheritdoc}
     */
    public function fields(): array
    {
        return $this->index->fields();
    }

    /**
     * {@inheritdoc}
     */
    public function isComposite(): bool
    {
        return $this->index->isComposite();
    }

    /**
     * {@inheritdoc}
     */
    public function options(): array
    {
        return $this->index->options();
    }

    /**
     * {@inheritdoc}
     */
    public function fieldOptions(string $field): array
    {
        return $this->index->fieldOptions($field);
    }

    /**
     * Check if the name is valid
     *
     * @param string $name
     *
     * @return bool
     * @psalm-assert-if-true non-empty-string $name
     */
    protected function isValidName($name)
    {
        if (empty($name)) {
            return false;
        }

        if (!is_string($name)) {
            return false;
        }

        if (
            !ctype_alpha($name[0])
            && $name[0] !== '_'
        ) {
            return false;
        }

        return true;
    }
}
