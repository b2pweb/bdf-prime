<?php

namespace Bdf\Prime\Mapper\Jit\Visitor;

use Bdf\Prime\Mapper\Jit\CodeGenerator;
use Bdf\Prime\Query\Compiled\CompiledSqlQuery;
use Bdf\Prime\Repository\RepositoryQueryFactory;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

/**
 * Visitor for replace the query builder with a compiled SQL query
 * It also call extra configuration methods on the compiled query
 *
 * The inlined SQL query is created using `$repository->queries()->compiled('SELECT ...')`
 *
 * @see CompiledSqlQuery
 * @see RepositoryQueryFactory::compiled()
 */
final class ReplaceQueryBuilderWithCompiledVisitor extends NodeVisitorAbstract
{
    private Expr $repositoryParameter;
    private string $compiledQuery;

    /**
     * @var array<string, Expr>
     */
    private array $calls;

    /**
     * @param Expr $repositoryParameter
     * @param string $compiledQuery
     * @param array<string, Expr> $calls Method to call for configure the query before execution. The method name is the key, and the value is the parameter expression
     */
    public function __construct(Expr $repositoryParameter, string $compiledQuery, array $calls)
    {
        $this->repositoryParameter = $repositoryParameter;
        $this->compiledQuery = $compiledQuery;
        $this->calls = $calls;
    }

    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof MethodCall) {
            return null;
        }

        $methodName = $node->name->toString();

        if (!isset(CodeGenerator::EXECUTION_METHOD[$methodName])) {
            return null;
        }

        // Generate `$repository->queries()->compiled('SELECT ...')` expression
        $compiledQuery = new MethodCall(
            new MethodCall(
                $this->repositoryParameter,
                'queries'
            ),
            'compiled',
            [
                new Arg(new String_($this->compiledQuery)),
            ]
        );

        // Call ->withBindings([...]) and other methods on the compiled query
        foreach ($this->calls as $method => $parameter) {
            $compiledQuery = new MethodCall($compiledQuery, $method, [new Arg($parameter)]);
        }

        // Create a new query execution method call on the hook call
        return new MethodCall($compiledQuery, $methodName, $node->args);
    }
}
