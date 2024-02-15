<?php

namespace Bdf\Prime\Mapper\Jit\Ast;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use ReflectionClass;

use function array_push;

/**
 * @internal
 */
final class ClassAst
{
    private NodeFinder $finder;
    private ReflectionClass $class;

    /**
     * @var list<Stmt>
     */
    private array $stmts;

    private ?Class_ $classNode = null;

    /**
     * @param ReflectionClass $class
     * @param Stmt[] $stmts
     */
    public function __construct(ReflectionClass $class, array $stmts)
    {
        $this->finder = new NodeFinder();
        $this->class = $class;
        $this->stmts = $stmts;
    }

    /**
     * Get the class node
     */
    public function class(): ?Class_
    {
        if ($this->classNode) {
            return $this->classNode;
        }

        return $this->classNode = $this->finder->findFirst(
            $this->stmts,
            fn (Node $node) => $node instanceof Class_ && $node->name && $node->name->toString() === $this->class->getShortName()
        );
    }

    /**
     * Extract all namespace and use statements from the AST
     *
     * @param array<Stmt> $stmts Statements to search in. If null, use the file statements.
     *
     * @return list<Namespace_|Use_>
     */
    public function namespaceAndUses(?array $stmts = null): array
    {
        $stmts ??= $this->stmts;
        $out = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof Namespace_) {
                array_push($out, new Namespace_($stmt->name), ...$this->namespaceAndUses($stmt->stmts));
            } elseif ($stmt instanceof Use_) {
                $out[] = $stmt;
            }
        }

        return $out;
    }

    /**
     * Get the method defined in the class
     *
     * @param string $name The method name
     *
     * @return MethodAst|null The method, or null if not found
     */
    public function method(string $name): ?MethodAst
    {
        if (!$classNode = $this->class()) {
            return null;
        }

        $node = $this->finder->findFirst(
            $classNode->stmts,
            static fn (Node $node) => $node instanceof ClassMethod && $node->name->toString() === $name
        );

        if (!$node) {
            return null;
        }

        return new MethodAst($this, $node);
    }

    /**
     * Visit all statements of the file
     *
     * @param NodeVisitor $visitor
     *
     * @return Node[] The transformed AST
     */
    public function visit(NodeVisitor $visitor): array
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);

        return $traverser->traverse($this->stmts);
    }
}
