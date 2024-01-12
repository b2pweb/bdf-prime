<?php

namespace Bdf\Prime\Query\Expression\Json;

use Bdf\Prime\Query\CompilableClause as Q;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use LogicException;

use function implode;

/**
 * Generate a function call for perform JSON operations
 * The call must take as first parameter the JSON document (can be an attribute or any SQL expression), and the other parameters are the arguments.
 *
 * Note: It's advised to use a subclass of this class for each function, to avoid errors on the arguments count.
 */
class JsonFunction implements ExpressionInterface
{
    private string $function;

    /**
     * @var string|ExpressionInterface
     */
    private $document;

    /**
     * @var array<scalar|ExpressionInterface>
     */
    private array $arguments;

    /**
     * @param string $function The function name to call
     * @param ExpressionInterface|string $document The JSON document to pass to the function. Can be an attribute name, or a SQL expression.
     * @param ExpressionInterface|scalar ...$arguments The arguments to pass to the function. Can be a scalar which will be quoted, or a SQL expression.
     */
    public function __construct(string $function, $document, ...$arguments)
    {
        $this->function = $function;
        $this->document = $document;
        $this->arguments = $arguments;
    }

    /**
     * {@inheritdoc}
     */
    final public function build(Q $query, object $compiler): string
    {
        if (!$compiler instanceof QuoteCompilerInterface || !$compiler instanceof CompilerInterface) {
            throw new LogicException(static::class . ' expression is not supported by the current compiler');
        }

        $document = $this->document instanceof ExpressionInterface
            ? $this->document->build($query, $compiler)
            : $compiler->quoteIdentifier($query, $query->preprocessor()->field($this->document))
        ;

        $arguments = [$document];

        foreach ($this->arguments as $argument) {
            $arguments[] = $argument instanceof ExpressionInterface
                ? $argument->build($query, $compiler)
                : $compiler->quote($argument)
            ;
        }

        return $this->function . '(' . implode(', ', $arguments) . ')';
    }
}
