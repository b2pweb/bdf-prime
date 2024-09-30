<?php

namespace Bdf\Prime\Mapper\Jit\Ast;

use PhpParser\Parser;
use PhpParser\ParserFactory;
use ReflectionClass;

use function file_get_contents;

/**
 * Utility class for parse AST
 *
 * @internal
 */
final class AstParser
{
    private ?Parser $parser = null;

    /**
     * Parse the AST of a class file
     *
     * @param ReflectionClass $class
     *
     * @return ClassAst|null The class AST, or null if the file cannot be parsed
     */
    public function class(ReflectionClass $class): ?ClassAst
    {
        if (!$file = $class->getFileName()) {
            return null;
        }

        if (!$code = file_get_contents($file)) {
            return null;
        }

        if (!$stmts = $this->parser()->parse($code)) {
            return null;
        }

        return new ClassAst($class, $stmts);
    }

    private function parser(): Parser
    {
        return $this->parser ??= (new ParserFactory())->createForHostVersion();
    }
}
