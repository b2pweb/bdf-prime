<?php

namespace Bdf\Prime\Mapper\Jit\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeVisitorAbstract;

/**
 * Replace all $this calls to $this->mapper
 */
final class ReplaceThisToMapperVisitor extends NodeVisitorAbstract
{
    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node): ?Node
    {
        if ($node instanceof Variable && $node->name === 'this' && !$node->getAttribute('changed')) {
            $node->setAttribute('changed', true);

            return new PropertyFetch($node, 'mapper');
        }

        return null;
    }
}
