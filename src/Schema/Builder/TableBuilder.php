<?php

namespace Bdf\Prime\Schema\Builder;

use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Schema\Adapter\NamedIndex;
use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\Bag\Table;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Schema\Constraint\ConstraintSet;
use Bdf\Prime\Schema\Constraint\ForeignKey;
use Bdf\Prime\Schema\Constraint\ForeignKeyInterface;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\IndexSetInterface;
use Bdf\Prime\Schema\TableInterface;

/**
 * Class TableBuilder
 * Builder @see Table objects
 *
 * To duplication a table object :
 * <code>
 * $newTable = TableBuilder::fromTable($oldTable)->build();
 * </code>
 */
final class TableBuilder implements TableBuilderInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var array
     */
    private $indexes = [];

    /**
     * @var ColumnBuilderInterface[]
     */
    private $columns = [];

    /**
     * @var ForeignKeyInterface[]
     */
    private $foreignKeys = [];

    /**
     * @var string
     * @internal
     */
    private $current;


    /**
     * TableBuilder constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function name(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function options(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function indexes(array $indexes)
    {
        foreach ($indexes as $key => $value) {
            if (!is_array($value)) {
                $value = [$value];
            }

            $name = is_string($key) ? $key : null;

            if (isset($value['fields'])) {
                $this->index($value['fields'], $value['type'] ?? IndexInterface::TYPE_SIMPLE, $name, $value['options'] ?? []);
            } else {
                $this->index($value, IndexInterface::TYPE_SIMPLE, $name);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function index($columns, int $type = IndexInterface::TYPE_SIMPLE, ?string $name = null, array $options = [])
    {
        if (is_string($columns)) {
            $normalizedColumns = [$columns => []];
        } else {
            $normalizedColumns = [];

            foreach ($columns as $columnName => $columnOption) {
                if (is_int($columnName)) {
                    /** @var string $columnOption */
                    $normalizedColumns[$columnOption] = [];
                } else {
                    /** @var string $columnName */
                    /** @var array $columnOption */
                    $normalizedColumns[$columnName] = $columnOption;
                }
            }
        }

        $index = new NamedIndex(
            new Index($normalizedColumns, $type, $name, $options),
            $this->name
        );

        $this->indexes[$index->name()] = $index;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function primary($columns = null, ?string $name = null)
    {
        return $this->index(
            $columns ?: [$this->current],
            IndexInterface::TYPE_PRIMARY,
            $name
        );
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $column, PlatformTypeInterface $type, array $options = [])
    {
        $this->current = $column;

        return $this->columns[$column] = new ColumnBuilder($column, $type, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function column(?string $name = null)
    {
        if ($name === null) {
            $name = $this->current;
        }

        return $this->columns[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function foreignKey($foreignTable, array $localColumnNames, array $foreignColumnNames, array $options = [], ?string $constraintName = null)
    {
        $foreignKey = new ForeignKey(
            $localColumnNames,
            $foreignTable,
            $foreignColumnNames,
            $constraintName,
            $options['onDelete'] ?? ForeignKeyInterface::MODE_RESTRICT,
            $options['onUpdate'] ?? ForeignKeyInterface::MODE_RESTRICT,
            $options['match'] ?? ForeignKeyInterface::MATCH_SIMPLE
        );

        $this->foreignKeys[$foreignKey->name()] = $foreignKey;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function build(): Table
    {
        return new Table(
            $this->name,
            $this->buildColumns(),
            $this->buildIndexes(),
            new ConstraintSet($this->foreignKeys),
            $this->options
        );
    }

    /**
     * @return ColumnInterface[]
     */
    protected function buildColumns()
    {
        $columns = [];

        foreach ($this->columns as $column) {
            $bag = $column->build();

            $columns[$bag->name()] = $bag;
        }

        return $columns;
    }

    /**
     * @return IndexSetInterface
     */
    protected function buildIndexes()
    {
        $indexes = [];

        foreach ($this->columns as $column) {
            foreach ($column->indexes() as $name => $type) {
                if (is_string($name) && isset($indexes[$name])) {
                    $indexes[$name]['fields'][] = $column->getName();

                    if ($type !== null) {
                        $indexes[$name]['type'] = $type;
                    }
                } else {
                    $index = [
                        'fields' => [$column->getName()],
                        'type'   => $type === null ? IndexInterface::TYPE_SIMPLE : $type
                    ];

                    if (is_string($name)) {
                        $indexes[$name] = $index;
                    } else {
                        $indexes[] = $index;
                    }
                }
            }
        }

        $built = $this->indexes;

        foreach ($indexes as $name => $definition) {
            $index = new NamedIndex(
                new Index(array_fill_keys($definition['fields'], []), $definition['type'], is_int($name) ? null : $name),
                $this->name
            );

            $built[$index->name()] = $index;
        }

        return new IndexSet($built);
    }

    /**
     * Create the builder and fill with current table definition
     *
     * @param TableInterface $table
     *
     * @return self
     */
    public static function fromTable(TableInterface $table): self
    {
        $builder = new self($table->name());

        foreach ($table->columns() as $column) {
            $builder
                ->add($column->name(), $column->type())
                ->autoincrement($column->autoIncrement())
                ->length($column->length())
                ->comment($column->comment())
                ->setDefault($column->defaultValue())
                ->precision($column->precision(), $column->scale())
                ->nillable($column->nillable())
                ->unsigned($column->unsigned())
                ->fixed($column->fixed())
                ->options($column->options())
            ;
        }

        foreach ($table->indexes()->all() as $index) {
            $fields = [];

            foreach ($index->fields() as $field) {
                $fields[$field] = $index->fieldOptions($field);
            }

            $builder->index(
                $fields,
                $index->type(),
                $index->name(),
                $index->options()
            );
        }

        $builder->options($table->options());

        // @todo handle other constraints
        $builder->foreignKeys = $table->constraints()->all();

        return $builder;
    }
}
