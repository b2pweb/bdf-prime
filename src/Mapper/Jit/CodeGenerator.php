<?php

namespace Bdf\Prime\Mapper\Jit;

use Bdf\Prime\Mapper\Jit\Ast\AstParser;
use Bdf\Prime\Mapper\Jit\Visitor\ExtractTypesVisitor;
use Bdf\Prime\Mapper\Jit\Visitor\FullyQualifyNameVisitor;
use Bdf\Prime\Mapper\Jit\Visitor\InsertHookCallVisitor;
use Bdf\Prime\Mapper\Jit\Visitor\ReplaceQueryBuilderWithCompiledVisitor;
use Bdf\Prime\Mapper\Jit\Visitor\ReplaceThisToMapperVisitor;
use Bdf\Prime\Repository\RepositoryQueryFactory;
use LogicException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use ReflectionClass;

use function array_is_list;
use function array_map;
use function class_exists;
use function get_class;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function ksort;

/**
 * @internal
 */
final class CodeGenerator
{
    public const FORWARDED_ARGS = '...';

    /**
     * List of method which trigger an execution of the query
     * the key is the method name, and the value is the map of method which should be called on the query before execution.
     *
     * Use '...' as parameter to forward all arguments of the execution method to the called method.
     * In this case, the method is only called if the execution method is called with at least one argument.
     *
     * The format is :
     * [
     *     'executionMethod' => [
     *         'methodToCall' => [arg1, arg2, ...],
     *         'withForwardedArgs' => '...',
     *     ],
     *     // ...
     * ]
     */
    public const EXECUTION_METHOD = [
        'all' => [
            'select' => self::FORWARDED_ARGS,
        ],
        'first' => [
            'limit' => [1],
            'select' => self::FORWARDED_ARGS,
        ],
        'inRow' => [
            'limit' => [1],
            'select' => self::FORWARDED_ARGS,
        ],
        'inRows' => [
            'select' => self::FORWARDED_ARGS,
        ],
        'execute' => [
            'select' => self::FORWARDED_ARGS,
        ],
        // @todo aggregate ?
    ];

    private AstParser $parser;
    private PrettyPrinterAbstract $printer;

    public function __construct(?AstParser $parser = null, ?PrettyPrinterAbstract $printer = null)
    {
        if (!class_exists(ParserFactory::class)) {
            throw new LogicException('The packet "nikic/php-parser" is required to use the JIT');
        }

        $this->parser = $parser ?? new AstParser();
        $this->printer = $printer ?? new Standard([
            'shortArraySyntax' => true,
        ]);
    }

    /**
     * Generate the repository method hook class file
     *
     * @param object $mapper The object containing the method. Generally a Mapper instance
     * @param string $method The method name to hook
     * @param string $hookClassName The name of the hook class. Node this is the simple class name, not the FQCN. The class will be generated into the namespace of the mapper.
     *
     * @return string|null The class file code, or null if the method cannot be hooked
     */
    public function generateHook(object $mapper, string $method, string $hookClassName, bool $ignoreErrors = true): ?string
    {
        $reflection = new ReflectionClass($mapper);

        if (!$classAst = $this->parser->class($reflection)) {
            if ($ignoreErrors) {
                return null;
            }

            throw new JitException(get_class($mapper), $method, 'Cannot parse the mapper class');
        }

        if (!($methodAst = $classAst->method($method)) || $methodAst->node()->returnsByRef()) {
            if ($ignoreErrors) {
                return null;
            }

            throw new JitException(get_class($mapper), $method, $methodAst ? 'Returns by ref is not supported' : 'Method not found');
        }

        // Add the call to $this->hook() method on method body, and replace all $this calls to $this->mapper
        $hookedStatements = $methodAst->visitBody(new ReplaceThisToMapperVisitor(), new InsertHookCallVisitor());

        // Generate the invoke method of the hooked repository method
        $invokeMethod = new ClassMethod(
            '__invoke',
            [
                'flags' => Class_::MODIFIER_PUBLIC,
                'params' => $methodAst->params(),
                'returnType' => $methodAst->returnType(),
                'stmts' => $hookedStatements,
            ]
        );

        // Generate the method `public function method(): string { return 'xxx'; }`
        $methodMethod = new ClassMethod(
            'method',
            [
                'flags' => Class_::MODIFIER_PUBLIC,
                'returnType' => new Identifier('string'),
                'stmts' => [new Return_(new String_($method))],
            ]
        );

        // Generate the method `public function class(): string { return MapperClass::class; }`
        $classMethod = new ClassMethod(
            'class',
            [
                'flags' => Class_::MODIFIER_PUBLIC,
                'returnType' => new Identifier('string'),
                'stmts' => [new Return_(new ClassConstFetch(new FullyQualified(get_class($mapper)), 'class'))],
            ]
        );

        // Create the hook class with __invoke method
        $queryHookClass = new Class_(
            $hookClassName,
            [
                'flags' => Class_::MODIFIER_FINAL,
                'extends' => new Name('JitQueryHook'),
                'stmts' => [$invokeMethod, $methodMethod, $classMethod],
            ]
        );

        // Resolve types from original class
        $classAst->visit($visitor = new ExtractTypesVisitor());
        $types = $visitor->types() + ['JitQueryHook' => JitQueryHook::class];

        // Replace all types by their FQCN
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new FullyQualifyNameVisitor($types));

        $queryHookClass = $traverser->traverse([$queryHookClass])[0];

        // Build file statements : add namespace and the hook class
        $queryHookFileStatements = [];

        if ($mapperNamespace = $reflection->getNamespaceName()) {
            $queryHookFileStatements[] = new Namespace_(new Name($mapperNamespace));
        }

        $queryHookFileStatements[] = $queryHookClass;

        return $this->printer->prettyPrintFile($queryHookFileStatements);
    }

    /**
     * Replace query building by inlining the SQL query,
     * using {@see RepositoryQueryFactory::compiled()} to create the compiled query
     *
     * @param JitQueryHook $hook The hook to compile
     *
     * @return string|null The closure code, or null if failed
     *
     * @throws \ReflectionException
     */
    public function generateInlinedQuery(JitQueryHook $hook): ?string
    {
        $reflection = new ReflectionClass($hook->class());

        if (!$classAst = $this->parser->class($reflection)) {
            $hook->invalidate('Cannot parse the mapper class');

            return null;
        }

        if (!$methodAst = $classAst->method($hook->method())) {
            $hook->invalidate('The method is not found');

            return null;
        }

        $methodParameters = $methodAst->params();
        $calls = [];

        // Generate the ->withBindings([...]) call
        $bindings = $this->parameterMappingToArrayExpression(
            $methodParameters,
            $hook->argumentsMapping,
            $hook->constants
        );

        if ($bindings) {
            $calls['withBindings'] = $bindings;
        }

        // Generate the ->withMetadata([...]) call
        $metadata = $this->parameterMappingToArrayExpression(
            $methodParameters,
            $hook->metadataMapping,
            $hook->metadataConstants
        );

        if ($metadata) {
            $calls['withMetadata'] = $metadata;
        }

        // Generate the ->withExtensionMetadata([...]) call
        $extension = $this->parameterMappingToArrayExpression(
            $methodParameters,
            null,
            $hook->extensionParameters
        );

        if ($extension) {
            $calls['withExtensionMetadata'] = $extension;
        }

        $repositoryParameter = $methodAst->firstParameter();

        if ($repositoryParameter === null) {
            $hook->invalidate('The method should have at least the repository as first parameter');

            return null;
        }

        // Replace query builder to inlined SQL query
        $compiledBody = $methodAst->visitBody(new ReplaceQueryBuilderWithCompiledVisitor(
            $repositoryParameter->var,
            $hook->query,
            $calls
        ));

        // Create the closure which will replace the mapper method
        $closureExpression = new Closure(
            [
                'params' => $methodAst->params(),
                'returnType' => $methodAst->returnType(),
                'stmts' => $compiledBody,
            ]
        );

        $typesExtractor = new ExtractTypesVisitor();
        $classAst->visit($typesExtractor);

        $types = $typesExtractor->types();

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new FullyQualifyNameVisitor($types));

        // Resolve all types
        return $this->printer->prettyPrint($traverser->traverse([$closureExpression]));
    }

    /**
     * @param Param[] $methodParameters Actual method parameters
     * @param array|null $parametersMapping Mapping of result array index to parameter index
     * @param array|null $constants Constants to add to the result array
     *
     * @return Array_|null The array expression, or null if no parameters
     */
    private function parameterMappingToArrayExpression(array $methodParameters, ?array $parametersMapping, ?array $constants): ?Array_
    {
        $result = [];

        // Resolve mapping of arguments to bindings
        if ($parametersMapping) {
            foreach ($parametersMapping as $binding => $argumentIndex) {
                $result[$binding] = $methodParameters[$argumentIndex]->var;
            }
        }

        // Revolve constant bindings
        if ($constants) {
            foreach ($constants as $binding => $value) {
                $result[$binding] = self::castToExpr($value);
            }
        }

        if (!$result) {
            return null;
        }

        // Check if the binding is a list (i.e. array without explicit keys)
        ksort($result, SORT_NATURAL);
        $isList = array_is_list($result);

        // Build the bindings array expression, only if there are bindings
        $resultExpression = new Array_();

        foreach ($result as $key => $value) {
            if ($isList) {
                $resultExpression->items[] = new ArrayItem($value);
            } else {
                $resultExpression->items[] = new ArrayItem(
                    $value,
                    is_int($key) ? new LNumber($key) : new String_($key)
                );
            }
        }

        return $resultExpression;
    }

    /**
     * Transform a PHP value to an expression
     *
     * @param mixed $value Value to transform
     *
     * @return Expr
     */
    public static function castToExpr($value): Expr
    {
        if ($value === null) {
            return new Expr\ConstFetch(new Name('null'));
        } elseif (is_bool($value)) {
            return new Expr\ConstFetch(new Name($value ? 'true' : 'false'));
        } elseif (is_int($value)) {
            return new LNumber($value);
        } elseif (is_float($value)) {
            return new DNumber($value);
        } elseif (is_string($value)) {
            return new String_($value);
        } elseif (is_array($value)) {
            // @todo test
            if (array_is_list($value)) {
                return new Array_(array_map(fn ($item) => new ArrayItem(self::castToExpr($item)), $value));
            }

            $items = [];

            foreach ($value as $key => $item) {
                $items[] = new ArrayItem(self::castToExpr($item), self::castToExpr($key));
            }

            return new Array_($items);
        } else {
            throw new LogicException('Unsupported value type');
        }
    }
}
