<?php

namespace Bdf\Prime\Mapper\Jit\Visitor;

use Bdf\Prime\Mapper\Jit\CodeGenerator;
use Bdf\Prime\Mapper\Jit\JitQueryHook;
use LogicException;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

use function array_map;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

/**
 * Visitor which insert call to {@see JitQueryHook::hook()} before query execution calls
 *
 * For example, the following code :
 *     `return $repository->where('id', $search)->orWhere('name', $search)->first();`
 *
 * Will be transformed to :
 *     `return $this->hook($repository->where('id', $search)->orWhere('name', $search)->limit(1), \func_get_args())->first();`
 */
final class InsertHookCallVisitor extends NodeVisitorAbstract
{
    /**
     * {@inheritdoc}
     */
    public function enterNode(Node $node): ?Node
    {
        if (!$node instanceof MethodCall) {
            return null;
        }

        $methodName = $node->name->toString();
        $configurationCalls = CodeGenerator::EXECUTION_METHOD[$methodName] ?? null;
        $executionArguments = $node->args;
        $queryExpression = $node->var;

        // Skip method call if it's not a query execution method
        if ($configurationCalls === null) {
            return null;
        }

        // Add query configuration calls depending on the execution method (for example limit(1) on first())
        foreach ($configurationCalls as $toCall => $arguments) {
            $isForwarded = $arguments === CodeGenerator::FORWARDED_ARGS;

            if ($isForwarded && empty($executionArguments)) {
                continue;
            }

            $callingArguments = $isForwarded ?
                $executionArguments :
                array_map(fn ($arg) => new Arg(CodeGenerator::castToExpr($arg)), $arguments)
            ;

            $queryExpression = new MethodCall(
                $queryExpression,
                $toCall,
                $callingArguments
            );
        }

        // Call the hook on the query
        // Generate `$this->hook($repository->where(...), func_get_args());`
        $hookCall = new MethodCall(
            new Variable('this', ['changed' => true]), // Add changed attribute to avoid replacement on ReplaceThisToMapperVisitor
            'hook',
            [
                new Arg($queryExpression),
                new Arg(new FuncCall(new FullyQualified('func_get_args'))),
            ]
        );

        // Create a new query execution method call on the hook call
        return new MethodCall($hookCall, $methodName, $executionArguments);
    }
}
