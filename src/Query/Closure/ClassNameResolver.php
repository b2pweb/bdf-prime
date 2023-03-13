<?php

namespace Bdf\Prime\Query\Closure;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\UseUse;

/**
 * Utility class for resolve fully qualified class name from name node
 */
final class ClassNameResolver
{
    private ?string $namespace = null;
    private array $uses = [];

    /**
     * Define the current namespace
     *
     * @param string|null $namespace
     */
    public function setNamespace(?string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * Declare a use statement
     *
     * @param UseUse $use
     * @return void
     */
    public function addUse(UseUse $use): void
    {
        $alias = $use->alias ? $use->alias->toString() : $use->name->getLast();
        $this->uses[$alias] = $use->name->toString();
    }

    /**
     * Try to resolve the fully qualified class name
     *
     * @param Name $name The name node to resolve
     * @return string Fully qualified class name
     */
    public function resolve(Name $name): string
    {
        if ($name instanceof Name\FullyQualified) {
            return $name->toString();
        }

        $nameStr = $name->toString();

        if (isset($this->uses[$nameStr])) {
            return $this->uses[$nameStr];
        }

        if ($this->namespace) {
            return $this->namespace . '\\' . $nameStr;
        }

        return $name;
    }
}
