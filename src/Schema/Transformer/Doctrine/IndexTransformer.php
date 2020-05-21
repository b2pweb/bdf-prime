<?php

namespace Bdf\Prime\Schema\Transformer\Doctrine;

use Bdf\Prime\Schema\IndexInterface;
use Doctrine\DBAL\Schema\Index;

/**
 * Transform Index to doctrine
 */
final class IndexTransformer
{
    /**
     * @var IndexInterface
     */
    private $index;


    /**
     * IndexTransformer constructor.
     *
     * @param IndexInterface $index
     */
    public function __construct(IndexInterface $index)
    {
        $this->index = $index;
    }

    /**
     * @return Index
     */
    public function toDoctrine()
    {
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
     * Extract the index flags (boolean option)
     *
     * @return array
     */
    private function extractFlags()
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
    private function extractOptions()
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
    private function extractFieldsLengths()
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
