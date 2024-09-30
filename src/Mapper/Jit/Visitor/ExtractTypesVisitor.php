<?php

namespace Bdf\Prime\Mapper\Jit\Visitor;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitorAbstract;

use function class_exists;
use function function_exists;
use function in_array;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Extract all imported and used types from the AST
 */
final class ExtractTypesVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<string, string>
     */
    private array $types = [];
    private ?string $namespace = null;

    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node): void
    {
        if ($node instanceof Namespace_) {
            $this->namespace = $node->name->toString();
            return;
        }

        if ($node instanceof Class_ && $node->name) {
            $className = $node->name->toString();
            $this->types[$className] = $this->namespace ? $this->namespace.'\\'.$className : $className;
            return;
        }

        if ($node instanceof UseUse && in_array($node->type, [Use_::TYPE_UNKNOWN, Use_::TYPE_NORMAL])) {
            if (function_exists($node->getAlias()->name)) { // Ignore use function
                return;
            }

            $this->types[$node->getAlias()->name] = $node->name->toString();
            return;
        }

        if ($node instanceof Name) {
            $typeString = $node->toString();

            // Type already resolved
            if (isset($this->types[$typeString])) {
                return;
            }

            // Fully qualified class name
            if ($node->isFullyQualified() && class_exists($typeString)) {
                $this->types[$typeString] = $typeString;
                return;
            }

            // Class name relative to the current namespace
            if ($this->namespace && class_exists($this->namespace . '\\' . $typeString)) {
                $this->types[$typeString] = $this->namespace . '\\' . $typeString;
                return;
            }

            // Class name relative to the imported namespaces
            foreach ($this->types as $alias => $type) {
                if (!str_starts_with($typeString, $alias . '\\')) {
                    continue;
                }

                $fqcn = $type . substr($typeString, strlen($alias));

                if (class_exists($fqcn)) {
                    $this->types[$typeString] = $fqcn;
                    return;
                }
            }
        }
    }

    /**
     * Get all  resolved types, with key as type alias, and value as resolved FQCN
     *
     * @return array<string, string>
     */
    public function types(): array
    {
        return $this->types;
    }
}
