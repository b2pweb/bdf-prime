<?php

namespace Php80\Mapper\Jit\Visitor;

require_once __DIR__ . '/_files/ToExtract.php';

use Bdf\Prime\Mapper\Jit\Visitor\ExtractTypesVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class ExtractTypesVisitorTest extends TestCase
{
    public function test_extract()
    {
        $visitor = new ExtractTypesVisitor();
        $traverser = new NodeTraverser();
        $stmts = (new ParserFactory())->createForHostVersion()->parse(file_get_contents(__DIR__ . '/_files/ToExtract.php'));

        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        $this->assertEquals([
            'ArrayObject' => 'ArrayObject',
            'Events' => 'Bdf\Prime\Events',
            'TestEntity' => 'Bdf\Prime\TestEntity',
            'Node' => 'PhpParser\Node',
            'Foo' => 'Php80\Mapper\Jit\Visitor\_files\Foo',
            'Foo\Bar\Baz' => 'Php80\Mapper\Jit\Visitor\_files\Foo\Bar\Baz',
            'ToExtract' => 'Php80\Mapper\Jit\Visitor\_files\ToExtract',
            'BadMethodCallException' => 'BadMethodCallException',
            'Node\Scalar\String_' => 'PhpParser\Node\Scalar\String_',
        ], $visitor->types());
    }
}
