<?php

namespace Bdf\Prime\Query\Closure;

use Bdf\Prime\Query\Closure\Filter\AndFilter;
use Bdf\Prime\Query\Closure\Parser\BooleanExpressionParser;
use Bdf\Prime\Query\Closure\Parser\EntityAccessorParser;
use Bdf\Prime\Query\Closure\Parser\FunctionCallParser;
use Bdf\Prime\Query\Closure\Parser\ValueParser;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use ReflectionFunction;
use RuntimeException;

/**
 * Locate the closure in the source code and extract the filters
 *
 * @internal
 */
final class ClosureFiltersExtractorVisitor extends NodeVisitorAbstract
{
    private ReflectionFunction $reflection;
    private string $parameterName;
    private ClassNameResolver $resolver;
    private ?AndFilter $filters = null;

    /**
     * @param ReflectionFunction $reflection
     */
    public function __construct(ReflectionFunction $reflection)
    {
        $this->reflection = $reflection;
        $this->resolver = new ClassNameResolver();
        $this->parameterName = $reflection->getParameters()[0]->getName();
    }

    /**
     * Get the extracted filters
     *
     * @return AndFilter
     */
    public function filters(): AndFilter
    {
        if (!$this->filters) {
            throw new RuntimeException('No closure found');
        }

        return $this->filters;
    }

    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->resolver->setNamespace($node->name->toString());
        }

        if ($node instanceof Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $this->resolver->addUse($use);
            }
        }

        if ($this->reflection->getStartLine() === $node->getLine()) {
            if ($node instanceof ArrowFunction) {
                $this->filters = $this->parseReturnExpression($node->expr);
                return NodeTraverser::STOP_TRAVERSAL;
            }

            if ($node instanceof Node\Expr\Closure) {
                if (!isset($node->stmts[0]) || !$node->stmts[0] instanceof Node\Stmt\Return_) {
                    throw new RuntimeException('Closure must only contains a return statement');
                }

                $this->filters = $this->parseReturnExpression($node->stmts[0]->expr);
                return NodeTraverser::STOP_TRAVERSAL;
            }
        }

        return null;
    }

    private function parseReturnExpression(Node\Expr $expr): AndFilter
    {
        $accessorParser = new EntityAccessorParser($this->parameterName);
        $valueParser = new ValueParser($this->resolver);
        $functionCallParser = new FunctionCallParser($accessorParser, $valueParser);

        return (new BooleanExpressionParser($accessorParser, $functionCallParser, $valueParser))->parse($expr);
    }
}
