<?php

namespace Php80\Mapper\Jit\Visitor;

use Bdf\Prime\Mapper\Jit\Visitor\ExtractTypesVisitor;
use Bdf\Prime\Mapper\Jit\Visitor\FullyQualifyNameVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

class FullyQualifyNameVisitorTest extends TestCase
{
    public function test_apply()
    {
        $visitor = new ExtractTypesVisitor();
        $stmts = (new ParserFactory())->createForHostVersion()->parse(file_get_contents(__DIR__ . '/_files/ToExtract.php'));

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new FullyQualifyNameVisitor($visitor->types()));
        $newStmts = $traverser->traverse($stmts);

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\Jit\Visitor\_files;

require_once __DIR__ . '/OtherDef.php';
use \ArrayObject;
use Bdf\Prime\Events;
use Bdf\Prime\TestEntity;
use PhpParser\Node;
use function var_dump;
class ToExtract extends \ArrayObject
{
    public function test(\Bdf\Prime\TestEntity $foo) : int
    {
        $h = new \Bdf\Prime\Events();
        $b = new \Php80\Mapper\Jit\Visitor\_files\Foo();
        $c = new \BadMethodCallException();
        var_dump($h, $b, $c);
        new \Php80\Mapper\Jit\Visitor\_files\Foo\Bar\Baz();
        $str = new \PhpParser\Node\Scalar\String_('test');
        $pi4 = M_PI_4;
        return 1;
    }
}
PHP
        , (new Standard())->prettyPrintFile($newStmts)
);
    }
}
