<?php

namespace Bdf\Prime\Schema\Transformer\Doctrine;

use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Schema\IndexInterface;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Index\IndexType;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;

use function in_array;
use function trigger_error;

/**
 * Transform Index to doctrine
 */
final readonly class IndexTransformer
{
    public function __construct(
        private IndexInterface $index,
    ) {
    }

    /**
     * @return Index
     */
    public function toDoctrine(): Index
    {
        if ($this->index->primary()) {
            @trigger_error(sprintf('Calling %s() on a primary index is deprecated. Use %s() instead.', __METHOD__, self::class.'::toDoctrinePrimaryKey'), E_USER_DEPRECATED);

            // Cannot create a primary index using the new IndexEditor API, so for this case, we use the legacy constructor
            // @todo delete in prime 4.0
            /** @psalm-suppress InternalMethod */
            return new Index(
                $this->index->name(),
                $this->index->fields(),
                $this->index->unique(),
                $this->index->primary(),
                $this->extractFlags(),
                $this->extractOptions()
            );
        }

        // @todo doctine dbal v4 has a bug on the handling of "length" option. So we must use the legacy constructor for now
        // $editor = Index::editor()
        //     ->setType($this->resolveType())
        // ;
        //
        // if ($this->index->name() !== null) {
        //     $editor->setUnquotedName($this->index->name());
        // }
        //
        // $lengths = $this->index->options()['lengths'] ?? null;
        //
        // foreach ($this->index->fields() as $i => $field) {
        //     $length = $this->index->fieldOptions($field)['length'] ?? null;
        //
        //     if ($length === null && $lengths !== null) {
        //         $length = $lengths[$i] ?? null;
        //     }
        //
        //     // Doctrine 4.4 doesn't provide public interface to define columns with length, so we must use internal constructor
        //     /** @psalm-suppress InternalMethod */
        //     $column = new Index\IndexedColumn(UnqualifiedName::unquoted($field), $length);
        //
        //     $editor->addColumn($column);
        // }
        //
        // if ($this->index->options()['clustered'] ?? false) {
        //     $editor->setIsClustered(true);
        // }
        //
        // if (isset($this->index->options()['where'])) {
        //     $editor->setPredicate($this->index->options()['where']);
        // }
        //
        // return $editor->create();

        /** @psalm-suppress InternalMethod */
        return new Index(
            $this->index->name(),
            $this->index->fields(),
            $this->index->unique(),
            $this->index->primary(),
            $this->extractFlags(),
            $this->extractOptions()
        );
    }

    /**
     * Transform the index as doctrine primary key
     */
    public function toDoctrinePrimaryKey(): PrimaryKeyConstraint
    {
        if (!$this->index->primary()) {
            throw new DBALException('The given index is not a primary key');
        }

        $editor = PrimaryKeyConstraint::editor()
            ->setUnquotedColumnNames(...$this->index->fields())
        ;

        if ($this->index->name() !== null) {
            $editor->setUnquotedName($this->index->name());
        }

        if (in_array('nonclustered', $this->extractFlags())) {
            $editor->setIsClustered(false);
        }

        return $editor->create();
    }

    /**
     * Extract the index flags (boolean option)
     *
     * @return array
     */
    private function extractFlags(): array
    {
        $flags = [];

        foreach ($this->index->options() as $name => $value) {
            if ($value === true) {
                $flags[] = $name;
            }
        }

        return $flags;
    }

    /**
     * Extract index options
     *
     * @return array
     */
    private function extractOptions(): array
    {
        $options = $this->index->options();

        // Remove flags from options
        foreach ($options as $name => $value) {
            if ($value === true) {
                unset($options[$name]);
            }
        }

        if ($lengths = $this->extractFieldsLengths()) {
            $options['lengths'] = $lengths;
        }

        return $options;
    }

    /**
     * Extract the fields prefix indexation length
     *
     * @return array|null The option, or null if not provided
     */
    private function extractFieldsLengths(): ?array
    {
        $lengths = [];
        $found = false;

        foreach ($this->index->fields() as $field) {
            $fieldOptions = $this->index->fieldOptions($field);

            if (isset($fieldOptions['length'])) {
                $lengths[] = $fieldOptions['length'];
                $found = true;
            } else {
                $lengths[] = null;
            }
        }

        return $found ? $lengths : null;
    }

    private function resolveType(): IndexType
    {
        if ($this->index->unique()) {
            return IndexType::UNIQUE;
        }

        if ($this->index->options()['fulltext'] ?? false) {
            return IndexType::FULLTEXT;
        }

        if ($this->index->options()['spatial'] ?? false) {
            return IndexType::SPATIAL;
        }

        return IndexType::REGULAR;
    }
}
