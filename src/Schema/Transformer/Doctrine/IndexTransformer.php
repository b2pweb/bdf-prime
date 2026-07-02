<?php

namespace Bdf\Prime\Schema\Transformer\Doctrine;

use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Schema\IndexInterface;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;

use function in_array;
use function trigger_error;

/**
 * Transform Index to doctrine
 */
final class IndexTransformer
{
    public function __construct(
        private readonly IndexInterface $index,
    ) {
    }

    /**
     * @return Index
     */
    public function toDoctrine(): Index
    {
        if ($this->index->primary()) {
            @trigger_error(sprintf('Calling %s() on a primary index is deprecated. Use %s() instead.', __METHOD__, self::class.'::toDoctrinePrimaryKey'), E_USER_DEPRECATED);
        }

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
}
