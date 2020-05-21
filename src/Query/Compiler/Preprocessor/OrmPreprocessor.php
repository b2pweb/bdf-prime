<?php

namespace Bdf\Prime\Query\Compiler\Preprocessor;

use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\AliasResolver\AliasResolver;
use Bdf\Prime\Query\Contract\Joinable;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Bdf\Prime\Query\Expression\ExpressionTransformerInterface;
use Bdf\Prime\Query\Expression\TypedExpressionInterface;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\Types\TypeInterface;
use LogicException;

/**
 * Preprocessor for Orm operation (i.e. with EntityRepository and Alias resolver)
 */
class OrmPreprocessor implements PreprocessorInterface
{
    /**
     * @var AliasResolver
     */
    protected $aliasResolver;

    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * The query repository
     *
     * @var RepositoryInterface
     * @internal
     */
    private $repository;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var PlatformInterface
     */
    protected $platform;


    /**
     * OrmPreprocessor constructor.
     *
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->metadata = $repository->metadata();
        $this->platform = $repository->connection()->platform();
    }

    /**
     * {@inheritdoc}
     */
    public function forInsert(CompilableClause $clause)
    {
        if ($this->repository->isReadOnly()) {
            throw new LogicException('Repository "'.$this->metadata->entityName.'" is read only. Cannot execute write query');
        }

        $this->type = 'insert';

        return $clause;
    }

    /**
     * {@inheritdoc}
     */
    public function forUpdate(CompilableClause $clause)
    {
        if ($this->repository->isReadOnly()) {
            throw new LogicException('Repository "'.$this->metadata->entityName.'" is read only. Cannot execute write query');
        }

        $this->type = 'update';
        $toCompile = clone $clause;
        $toCompile->where($this->repository->constraints());

        return $toCompile;
    }

    /**
     * {@inheritdoc}
     */
    public function forDelete(CompilableClause $clause)
    {
        if ($this->repository->isReadOnly()) {
            throw new LogicException('Repository "'.$this->metadata->entityName.'" is read only. Cannot execute write query');
        }

        $this->type = 'delete';
        $toCompile = clone $clause;
        $toCompile->where($this->repository->constraints());

        return $toCompile;
    }

    /**
     * {@inheritdoc}
     */
    public function forSelect(CompilableClause $clause)
    {
        $this->type = 'select';

        if ($clause instanceof Joinable) {
            $compilerQuery = clone $clause;

            if (!$this->aliasResolver) {
                $needReset = false;
                $this->aliasResolver = new AliasResolver($this->repository, $this->platform->types());
            } else {
                $needReset = true;
            }

            $this->aliasResolver->setQuery($compilerQuery);

            if ($clause->state()->needsCompile('from')) {
                if ($needReset) {
                    $this->aliasResolver->reset();
                }

                foreach ($compilerQuery->statements['tables'] as &$table) {
                    $table['alias'] = $this->aliasResolver->registerMetadata($table['table'], $table['alias']);
                }
            }

            if ($clause->state()->needsCompile('joins')) {
                foreach ($compilerQuery->statements['joins'] as &$join) {
                    $join['alias'] = $this->aliasResolver->registerMetadata($join['table'], $join['alias']);
                }
            }

            return $compilerQuery;
        } else {
            return $clause;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function field($attribute, &$type = null)
    {
        if ($this->type === 'select' && $this->aliasResolver !== null) {
            return $this->aliasResolver->resolve($attribute, $type);
        }

        return $this->fieldForWriteQuery($attribute, $type);
    }

    /**
     * Get formatted field from attribute alias
     *
     * @param string $attribute
     * @param null|true $type
     *
     * @return string
     */
    protected function fieldForWriteQuery($attribute, &$type = null)
    {
        // @fixme Throw exception if wants to write on undefined attribute ?
        if (!isset($this->metadata->attributes[$attribute])) {
            return $attribute;
        }

        $meta = $this->metadata->attributes[$attribute];

        if ($type === true) {
            $type = $this->platform->types()->get($meta['type']);
        }

        return $meta['field'];
    }

    /**
     * {@inheritdoc}
     *
     * Perform conversion on value, according to the field type and the value
     *
     * Example :
     * $query->where('roles', ':in', [[5, 2], [3]]); => IN(',5,2,', ',3,') OK
     * $query->where('roles', [[5, 2], [3]]);        => IN(',5,2,', ',3,') OK
     * $query->where('roles', [5, 2]);               => IN('5', '2')       KO
     * $query->where('roles', ':in', [5, 2]);        => IN('5', '2')       KO
     * $query->where('roles', ',5,2,');              => = ',5,2,'          OK
     *
     * To ensure that the expression is converted as needed, use Expressions instead of plain value
     */
    public function expression(array $expression)
    {
        if (isset($expression['column'])) {
            $type = true;

            /** @var TypeInterface $type */
            $expression['column'] = $this->field($expression['column'], $type);

            if ($type !== true) {
                $value = $expression['value'];

                if ($value instanceof TypedExpressionInterface) {
                    $value->setType($type);
                } elseif (is_array($value)) {
                    /* The value is an array :
                     * - Will result to "into" expression => convert each elements
                     * - Will result to "array" expression (i.e. IN, BETWEEN...) => convert each elements
                     * - Will result to "equal" expression => converted to IN => convert each elements
                     */
                    foreach ($value as &$v) {
                        $v = $this->tryConvertValue($v, $type);
                    }
                } else {
                    $value = $this->tryConvertValue($value, $type);
                }

                $expression['value'] = $value;
                $expression['converted'] = true;
            }
        }

        return $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function table(array $table)
    {
        if ($this->aliasResolver === null) {
            return $table;
        }

        if (!$this->aliasResolver->hasAlias($table['alias'])) {
            $table['alias'] = $this->aliasResolver->registerMetadata($table['table'], $table['alias']);
        }

        if ($this->aliasResolver->hasAlias($table['alias'])) {
            $table['table'] = $this->aliasResolver->getMetadata($table['alias'])->table;
        }

        return $table;
    }

    /**
     * {@inheritdoc}
     */
    public function root()
    {
        if ($this->aliasResolver) {
            return $this->aliasResolver->getPathAlias();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        if ($this->aliasResolver !== null) {
            $this->aliasResolver->setQuery(null);
        }
    }

    /**
     * Try to convert the value to DB value
     *
     * @param mixed $value
     * @param TypeInterface $type
     *
     * @return mixed
     */
    protected function tryConvertValue($value, TypeInterface $type)
    {
        if (
            $value instanceof QueryInterface
            || $value instanceof ExpressionInterface
            || $value instanceof ExpressionTransformerInterface
        ) {
            return $value;
        }

        return $type->toDatabase($value);
    }
}
