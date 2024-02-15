<?php

namespace Bdf\Prime\Mapper\Jit\Visitor;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeVisitorAbstract;

/**
 * Replace all short class names with fully qualified class names
 */
final class FullyQualifyNameVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<string, string>
     */
    private array $types;

    /**
     * @param array<string, string> $types Mapping of short class names to fully qualified class names
     */
    public function __construct(array $types)
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof Name) {
            return null;
        }

        if ($node->isFullyQualified()) {
            return null;
        }

        $typeString = $node->toString();

        if (!isset($this->types[$typeString])) {
            return null;
        }

        return new FullyQualified($this->types[$typeString]);
    }
}
