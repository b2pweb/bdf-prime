<?php

namespace Bdf\Prime\Mapper\Jit\Ast;

use PhpParser\Node;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;

/**
 * @internal
 */
final class MethodAst
{
    private ClassAst $declaringClass;
    private ClassMethod $methodNode;

    /**
     * @param ClassAst $declaringClass
     * @param ClassMethod $methodNode
     */
    public function __construct(ClassAst $declaringClass, ClassMethod $methodNode)
    {
        $this->declaringClass = $declaringClass;
        $this->methodNode = $methodNode;
    }

    /**
     * Get the method definition node.
     *
     * @return ClassMethod
     */
    public function node(): ClassMethod
    {
        return $this->methodNode;
    }

    /**
     * Get the parameters of the method.
     *
     * @return Param[]
     */
    public function params(): array
    {
        return $this->methodNode->params;
    }

    /**
     * Get the first parameter of the method, if any.
     *
     * @return Param|null
     */
    public function firstParameter(): ?Param
    {
        return $this->methodNode->params[0] ?? null;
    }

    /**
     * Get the return type of the method, if any.
     *
     * @return \PhpParser\Node\ComplexType|\PhpParser\Node\Identifier|\PhpParser\Node\Name|null
     */
    public function returnType(): ?Node
    {
        return $this->methodNode->returnType;
    }

    /**
     * Apply visitors to the method body.
     *
     * @param NodeVisitor ...$visitors
     *
     * @return Stmt[] Transformed statements
     */
    public function visitBody(NodeVisitor... $visitors): array
    {
        $traverser = new NodeTraverser();

        foreach ($visitors as $visitor) {
            $traverser->addVisitor($visitor);
        }

        return $traverser->traverse($this->methodNode->stmts);
    }
}
