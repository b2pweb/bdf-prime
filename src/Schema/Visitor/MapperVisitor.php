<?php

namespace Bdf\Prime\Schema\Visitor;

use Bdf\Prime\Mapper\NameResolver\ResolverInterface;
use Bdf\Prime\Mapper\NameResolver\SuffixResolver;
use Bdf\Prime\Schema\Inflector\InflectorInterface;
use Bdf\Prime\Schema\Inflector\SimpleInfector;
use Bdf\Prime\Types\TypeInterface;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Visitor\AbstractVisitor;

/**
 * Create a mapper output from a Schema.
 */
class MapperVisitor extends AbstractVisitor
{
    /**
     * The connection name
     *
     * @var string|null
     */
    private $connectionName;

    /**
     * The mappers string representation
     *
     * @var array<string, array{
     *    class:class-string,
     *    table:string,
     *    primaries:array<string,'autoincrement'|'sequence'|'primary'>,
     *    sequence:string|null,
     *    properties:array,
     *    indexes:array
     * }>
     */
    private $mappers = [];

    /**
     * The doctrine schema
     *
     * @var Schema
     */
    private $schema;

    /**
     * The stubs content
     *
     * @var string
     */
    private $prototype;

    /**
     * The mapper name resolver
     *
     * @var ResolverInterface
     */
    private $nameResolver;

    /**
     * The stubs content
     *
     * @var InflectorInterface
     */
    private $inflector;

    /**
     * The mapping between db types and builder methods
     *
     * @var array
     */
    private $typeAlias = [
        TypeInterface::STRING => 'string',
        TypeInterface::TEXT => 'text',
        TypeInterface::INTEGER => 'integer',
        TypeInterface::BIGINT => 'bigint',
        TypeInterface::SMALLINT => 'smallint',
        TypeInterface::TINYINT => 'tinyint',
        TypeInterface::FLOAT => 'float',
        TypeInterface::DOUBLE => 'double',
        TypeInterface::DECIMAL => 'decimal',
        TypeInterface::BOOLEAN => 'boolean',
        TypeInterface::DATE => 'date',
        TypeInterface::DATETIME => 'dateTime',
        TypeInterface::DATETIMETZ => 'dateTimeTz',
        TypeInterface::TIME => 'time',
        TypeInterface::TIMESTAMP => 'timestamp',
        TypeInterface::BINARY => 'binary',
        TypeInterface::BLOB => 'blob',
        TypeInterface::GUID => 'guid',
        TypeInterface::JSON => 'json',
        TypeInterface::TARRAY => 'simpleArray',
        TypeInterface::ARRAY_OBJECT => 'arrayObject',
        TypeInterface::OBJECT => 'object',
    ];

    /**
     * MapperVisitor constructor.
     *
     * @param string|null $connectionName
     * @param ResolverInterface|null $nameResolver
     * @param InflectorInterface|null $inflector
     */
    public function __construct($connectionName = null, ResolverInterface $nameResolver = null, InflectorInterface $inflector = null)
    {
        $this->connectionName = $connectionName;
        $this->nameResolver = $nameResolver ?: new SuffixResolver();
        $this->inflector = $inflector ?: new SimpleInfector();
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSchema(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function acceptTable(Table $table)
    {
        $primaries = [];
        $sequence = null;
        $tableName = $table->getName();

        // Evaluate metadata for primary keys
        if ($table->hasPrimaryKey()) {
            // prepare sequence info for the method Mapper::sequence() and the metadata primary
            $sequence = $this->inflector->getSequenceName($tableName);
            if (!$this->schema->hasTable($sequence)) {
                $sequence = null;
            }

            // get the type of primary
            foreach ($table->getPrimaryKeyColumns() as $primary) {
                $primary = $primary->getName();
                $column = $table->getColumn($primary);

                if ($column->getAutoincrement()) {
                    $primaries[$primary] = 'autoincrement';
                } elseif ($sequence !== null && empty($primaries)) {
                    $primaries[$primary] = 'sequence';
                } else {
                    $primaries[$primary] = 'primary';
                }
            }
        }

        $this->mappers[$tableName] = [
            'class'      => $this->nameResolver->resolve($this->inflector->getClassName($tableName)),
            'table'      => $tableName,
            'primaries'  => $primaries,
            'sequence'   => $sequence,
            'properties' => [],
            'indexes'    => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function acceptColumn(Table $table, Column $column)
    {
        $field = $column->getName();
        $type = $column->getType()->getName();
        $default = $column->getDefault();
        $length = $column->getLength();
        $tableName = $table->getName();

        $property = $this->inflector->getPropertyName($tableName, $field);
        $primaries = $this->mappers[$tableName]['primaries'];

        // default
        if ($default !== null) {
            switch ($type) {
                case TypeInterface::BOOLEAN:
                    $default = (bool)$default;
                    break;

                case TypeInterface::TINYINT:
                case TypeInterface::SMALLINT:
                case TypeInterface::INTEGER:
                    $default = (int)$default;
                    break;

                case TypeInterface::FLOAT:
                    $default = (float)$default;
                    break;

                case TypeInterface::DOUBLE:
                    $default = (float)$default;
                    break;
            }

            $default = ', '.var_export($default, true);
        }

        // type
        if ($type === TypeInterface::STRING) {
            $builder = "\$builder->string('$property', {$length}$default)";
            // string method has a length property.
            // We set the length to null to avoid the call of length() method
            $length = null;
        } elseif (isset($this->typeAlias[$type])) {
            $type = $this->typeAlias[$type];
            $builder = "\$builder->$type('$property'$default)";
        } else {
            $builder = "\$builder->add('$property', '$type'$default)";
        }

        // primary
        if (isset($primaries[$field])) {
            $builder .= "->{$primaries[$field]}()";
        }

        // TODO unique

        // length
        if ($length !== null) {
            $builder .= "->length($length)";
        }

        // nillable
        if (!$column->getNotnull()) {
            $builder .= "->nillable()";
        }

        // unsigned
        if ($column->getUnsigned()) {
            $builder .= "->unsigned()";
        }

        // precision and scale
        if ($column->getPrecision() !== 10 || $column->getScale() !== 0) {
            $builder .= "->precision({$column->getPrecision()}, {$column->getScale()})";
        }

        // fixed
        if ($column->getFixed()) {
            $builder .= "->fixed()";
        }

        // alias
        if ($property !== $field) {
            $builder .= "->alias('$field')";
        }

        $this->mappers[$tableName]['properties'][] = $builder.';';
    }

    /**
     * {@inheritdoc}
     */
    public function acceptIndex(Table $table, Index $index)
    {
        if (!$index->isSimpleIndex()) {
            return;
        }

        $columns = [];
        $tableName = $table->getName();

        foreach ($index->getColumns() as $column) {
            $columns[] = $this->inflector->getPropertyName($tableName, $column);
        }

        $name = $index->getName();
        $indexes = implode("', '", $columns);

        $this->mappers[$tableName]['indexes'][] = "'$name' => ['$indexes'],";
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSequence(Sequence $sequence)
    {
    }

    /**
     * Get mappers output
     *
     * @return string
     */
    public function getOutput()
    {
        $output = '';

        foreach ($this->mappers as $metadata) {
            $output .= $this->createOutput($metadata).PHP_EOL;
        }

        return $output;
    }

    /**
     * Writes mappers files in path directory
     *
     * @param string $path
     *
     * @return void
     */
    public function write($path): void
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        foreach ($this->mappers as $metadata) {
            file_put_contents($path.$metadata['class'].'.php', $this->createOutput($metadata));
        }
    }

    /**
     * Create output from table metadata
     *
     * @param array $metadata
     *
     * @return string
     */
    private function createOutput($metadata)
    {
        if ($this->prototype === null) {
            $this->prototype = file_get_contents(__DIR__.'/stubs/mapper.stub');
        }

        return strtr($this->prototype, [
            '<className>'  => $metadata['class'],
            '<connection>' => $this->connectionName,
            '<database>'   => $this->connectionName,
            '<tableName>'  => $metadata['table'],
            '<fields>'     => implode("\n        ", $metadata['properties']),
            '<sequence>'   => $this->getSequenceOutput($metadata['sequence']),
            '<indexes>'    => $this->getIndexOutput($metadata['indexes']),
        ]);
    }

    /**
     * Get the sequence output
     *
     * @param string $table
     *
     * @return string
     */
    private function getSequenceOutput($table)
    {
        if ($table === null) {
            return '';
        }

        return <<<EOF
        
        
    /**
     * {@inheritdoc}
     */
    public function sequence(): array
    {
        return [
            'table' => {$table},
        ];
    }
EOF;
    }

    /**
     * Get the index output
     *
     * @param array $indexes
     *
     * @return string
     */
    private function getIndexOutput(array $indexes)
    {
        if (empty($indexes)) {
            return '';
        }

        $indexes = implode("\n            ", $indexes);
        return <<<EOF
        
        
    /**
     * {@inheritdoc}
     */
    public function indexes(): array
    {
        return [
            $indexes
        ];
    }
EOF;
    }
}
