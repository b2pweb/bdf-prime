<?php

namespace Bdf\Prime\Query\Compiler\AliasResolver;

use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Relations\Exceptions\RelationNotFoundException;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\Types\TypesRegistryInterface;

/**
 * Create and resolve query alias and relation paths
 *
 * @internal
 */
class AliasResolver
{
    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @var TypesRegistryInterface
     */
    protected $types;

    /**
     * @var QueryInterface&EntityJoinable
     */
    protected $query;

    /**
     * The counter of alias
     *
     * @var integer
     * @internal
     */
    private $counter = 0;

    /**
     * Array of alias for relations
     * The key is the relation path (ex: customer.user)
     * The value is the generated alias (ex: t1, t2...)
     *
     * @var string[]
     */
    protected $relationAlias = [];

    /**
     * Array of path, indexed by alias
     *
     * ex: [
     *  t0 => user
     *  t1 => customer
     *  t2 => customer.driver
     * ]
     *
     * @var array<string, string>
     */
    protected $aliasToPath = [];

    /**
     * Array of metadata by alias
     * Used by the select compilation to map attribute by its field map name.
     *
     * @var array
     */
    protected $metadataByAlias = [];

    /**
     * Does the root repository (i.e. $this->repository) is already registered (i.e. has an alias)
     * The root alias must be defined before resolving any fields, so use this field to auto register the repository if not yet done
     *
     * @var bool
     */
    private $rootRepositoryRegistered = false;


    /**
     * AliasResolver constructor.
     *
     * @param RepositoryInterface $repository
     * @param TypesRegistryInterface $types
     */
    public function __construct(RepositoryInterface $repository, TypesRegistryInterface $types)
    {
        $this->repository = $repository;
        $this->metadata = $this->repository->metadata();
        $this->types = $types;
    }

    /**
     * Set the query instance
     *
     * @param QueryInterface&EntityJoinable|null $query
     */
    public function setQuery(?QueryInterface $query = null): void
    {
        $this->query = $query;
    }

    /**
     * Reset all registered aliases
     */
    public function reset()
    {
        $this->aliasToPath = [];
        $this->relationAlias = [];
        $this->counter = 0;
        $this->metadataByAlias = [];
        $this->rootRepositoryRegistered = false;
    }

    /**
     * Resolve the attribute path (i.e. user.customer.name) and get aliases and SQL valid expression.
     *
     * /!\ Don't forget to {@link AliasResolver::registerMetadata()} the root tables before resolve any attributes
     *
     * ex:
     *
     * resolve('user.customer.name', $type) => 't1.name_', $type : StringType()
     * resolve('123', $type) => Do not modify : 123 is a DBAL expression
     *
     * @param string $attribute The attribute path
     * @param mixed $type in-out : If set to true, this reference would be filled with the mapped type
     *
     * @return string The SQL valid expression, {table alias}.{table attribute}
     */
    public function resolve($attribute, &$type = null)
    {
        // The root repository is not registered
        if (!$this->rootRepositoryRegistered) {
            $this->registerMetadata($this->repository, null);
        }

        $metadata = $this->metadata;

        if (!isset($metadata->attributes[$attribute])) {
            list($alias, $attribute, $metadata) = $this->exploreExpression($attribute);

            //No metadata found => DBAL expression.
            if ($metadata === null) {
                return $attribute;
            }
        }

        if (empty($alias)) {
            $alias = $this->getPathAlias($metadata->table);
        }

        if ($type === true) {
            $type = $this->types->get($metadata->attributes[$attribute]['type']);
        }

        return $alias.'.'.$metadata->attributes[$attribute]['field'];
    }

    /**
     * Register metadata
     *
     * Used only for select query
     * If the alias is null, the method will create one
     *
     * @param string|Metadata|RepositoryInterface $repository
     * @param string|null $alias
     *
     * @return string|null Returns the metadata alias, or null is the first parameter is a DBAL value
     */
    public function registerMetadata($repository, ?string $alias): ?string
    {
        if (!$repository instanceof RepositoryInterface) {
            $repository = $this->findRepository($repository);

            if ($repository === null) {
                // No repository found. The given repository will be considered as a dbal value
                return $alias;
            }
        }

        $metadata = $repository->metadata();

        if (empty($alias)) {
            $alias = $this->getPathAlias($metadata->table);
        }

        if (!isset($this->aliasToPath[$alias])) {
            $this->aliasToPath[$alias] = $metadata->table;
        }

        if (!isset($this->relationAlias[$metadata->table])) {
            $this->relationAlias[$metadata->table] = $alias;
        }

        if ($metadata->useQuoteIdentifier) {
            $this->query->useQuoteIdentifier(true);
        }

        $this->query->where($repository->constraints('$'.$alias));
        $this->metadataByAlias[$alias] = $metadata;

        if ($repository === $this->repository) {
            $this->rootRepositoryRegistered = true;
        }

        return $alias;
    }

    /**
     * Find the associated repository
     *
     * @param mixed $search
     *
     * @return RepositoryInterface|null
     *
     * @todo find repository from table name
     */
    protected function findRepository($search): ?RepositoryInterface
    {
        if ($this->metadata->table === $search) {
            return $this->repository;
        }

        return $this->repository->repository($search);
    }

    /**
     * Extract from expression the attribute name and its metadata
     *
     * @param string $expression
     *
     * @return array  The attribute name and the owner metadata
     */
    protected function exploreExpression($expression)
    {
        $tokens = ExpressionCompiler::instance()->compile($expression);

        $state = new ExpressionExplorationState();
        $state->metadata = $this->metadata;

        foreach ($tokens as $token) {
            try {
                switch ($token->type) {
                    case ExpressionToken::TYPE_ALIAS:
                        $this->resolveAlias($token->value, $state);
                        break;

                    case ExpressionToken::TYPE_ATTR:
                        $state->attribute = $token->value;
                        break;

                    case ExpressionToken::TYPE_STA:
                        $this->resolveStatic($token->value, $state);
                        break;

                    case ExpressionToken::TYPE_DYN:
                        $this->resolveDynamic($token->value, $state);
                        break;
                }
            } catch (RelationNotFoundException $exception) {
                // SQL expression
                return [null, $expression, null];
            }
        }

        // SQL expression
        if ($state->attribute === null) {
            return [null, $expression, null];
        }

        // If no alias was given => create an alias from the path
        if ($state->alias === null) {
            $state->alias = $this->getPathAlias($state->path);
        }

        return [$state->alias, $state->attribute, $state->metadata];
    }

    /**
     * Resolve from an $ALIAS expression token
     * @see ExpressionCompiler
     *
     * @param string $alias The alias name
     * @param ExpressionExplorationState $state
     */
    protected function resolveAlias($alias, ExpressionExplorationState $state)
    {
        $state->alias = $alias;
        $state->path = $this->getRealPath($alias);
        $state->metadata = $this->metadataByAlias[$alias];
    }

    /**
     * Revolve from a $STA expression token
     * @see ExpressionCompiler
     *
     * @param string $expression The static expression
     * @param ExpressionExplorationState $state
     */
    protected function resolveStatic($expression, ExpressionExplorationState $state)
    {
        // Static expression not resolved yet
        if (!isset($this->relationAlias[$expression])) {
            $this->resolveDynamic(explode('.', $expression), $state);
        } else {
            $state->path = $expression;
            $state->alias = $this->relationAlias[$state->path];
            $state->metadata = $this->metadataByAlias[$state->alias];
        }
    }

    /**
     * Resolve from a $DYN expression
     * @see ExpressionCompiler
     *
     * @param array $expression Array of names
     * @param ExpressionExplorationState $state
     */
    protected function resolveDynamic(array $expression, ExpressionExplorationState $state)
    {
        $attribute = implode('.', $expression);

        //Expression is the attribute
        if (isset($state->metadata->attributes[$attribute])) {
            $state->attribute = $attribute;
            return;
        }

        /*
         * Attribute is the last part of the expression
         * OR the expression is a path
         *
         * i.e. $expression = $path . '.' . $attribute
         * i.e. $expression = $path
         */

        for ($i = 0, $count = count($expression); $i < $count; ++$i) {
            $part = $expression[$i];

            if ($state->path) {
                $state->path .= '.';
            }

            $state->path .= $part;

            $this->declareRelation($part, $state);

            $attribute = substr($attribute, strlen($part) + 1);

            //Attribute find in attributes
            if (isset($state->metadata->attributes[$attribute])) {
                $state->attribute = $attribute;
                return;
            }
        }
    }

    /**
     * Declare all relation in the tokens
     *
     * Manage relation like "customer.documents.contact.name"
     *
     * Use cases :
     *  - The path is already defined as an alias
     *      - Expend the alias
     *      - Get the metadata
     *      - Use the alias
     *  - Metadata not loaded
     *      - Load the relation
     *      - Create an alias
     *      - Join entity and apply constrains
     *  - Metadata loaded
     *      - Retrieve the alias
     *      - Use metadata and alias
     *
     * @param string $relationName The current relation name
     * @param ExpressionExplorationState $state
     */
    protected function declareRelation($relationName, ExpressionExplorationState $state)
    {
        // The path is an alias
        //  - Save the alias
        //  - Get the metadata from the alias
        //  - Expend the path
        if (isset($this->metadataByAlias[$state->path])) {
            $state->alias = $state->path;
            $state->metadata = $this->metadataByAlias[$state->path];
            $state->path = $this->getRealPath($state->path);
            return;
        }

        $alias = $this->getPathAlias($state->path);

        // If no metadata has been registered the alias could be:
        //   1. A relation and declare the relationship.
        //   2. A table alias of another metadata added by DBAL methods (@see Query::from, @see Query::join)
        if (!isset($this->metadataByAlias[$alias])) {
            // Get the relation name. '#' is for polymorphic relation
            $relationName = explode('#', $relationName);

            $relation = $this->repository->repository($state->metadata->entityName)->relation($relationName[0]);
            $relation->setLocalAlias($this->getParentAlias($state->path));

            foreach ($relation->joinRepositories($this->query, $alias, isset($relationName[1]) ? $relationName[1] : null) as $alias => $repository) {
                $this->registerMetadata($repository, $alias);
            }

            // If we have polymophism, add to alias
            $relation->join($this->query, $alias . (isset($relationName[1]) ? '#' . $relationName[1] : ''));
            $relation->setLocalAlias(null);
        }

        $state->alias = $alias;
        $state->metadata = $this->metadataByAlias[$alias];
    }

    /**
     * Get the alias of a path.
     *
     * If the path is not found, will create a new alias (t0, t1...)
     *
     * @param string|null $path The relation path, or null to use the root table
     *
     * @return string
     */
    public function getPathAlias($path = null)
    {
        if (empty($path)) {
            $path = $this->metadata->table;
        }

        if (!isset($this->relationAlias[$path])) {
            $alias = 't'.$this->counter++;
            $this->relationAlias[$path] = $alias;
            $this->aliasToPath[$alias] = $path;
        }

        return $this->relationAlias[$path];
    }

    /**
     * Get the real attribute path
     *
     * If the arguments is not found in alias table, concider the argument as path
     *
     * @param string $path The alias, or path
     *
     * @return string
     */
    protected function getRealPath($path)
    {
        return isset($this->aliasToPath[$path]) ? $this->aliasToPath[$path] : $path;
    }

    /**
     * Get the alias of the parent relation
     *
     * @param string $path
     *
     * @return string
     */
    protected function getParentAlias($path)
    {
        $path = $this->getRealPath($path);

        $pos = strrpos($path, '.');

        if ($pos === false) {
            return $this->getPathAlias($this->metadata->table);
        }

        $path = substr($path, 0, $pos);

        return $this->getPathAlias($path);
    }

    /**
     * Check if the alias is registered
     *
     * @param string $alias
     *
     * @return bool
     */
    public function hasAlias($alias)
    {
        return isset($this->metadataByAlias[$alias]);
    }

    /**
     * Get the metadata from the alias (or entity name)
     *
     * @param string $alias
     *
     * @return Metadata
     */
    public function getMetadata($alias)
    {
        return $this->metadataByAlias[$alias];
    }
}
