<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

use Bdf\Prime\Location;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class CodeGeneratorTest extends TestCase
{
    /**
     * @var CodeGenerator
     */
    private $code;

    protected function setUp(): void
    {
        $this->code = new CodeGenerator();
    }

    /**
     *
     */
    public function test_namespace()
    {
        $this->assertEquals('namespace My\Namespace;', $this->code->namespace('My\Namespace'));
        $this->assertEquals('', $this->code->namespace(''));
    }

    /**
     *
     */
    public function test_properties()
    {
        $this->assertEquals(<<<'PHP'
protected $prop1;
protected $prop2;

PHP
            , $this->code->properties(['prop1', 'prop2'], 'protected')
);
    }

    /**
     *
     */
    public function test_simpleConstructor()
    {
        $this->assertEquals(<<<'PHP'
public function __construct($prop1, $prop2)
{
    $this->prop1 = $prop1;
    $this->prop2 = $prop2;
}
PHP
            , $this->code->simpleConstructor(['prop1', 'prop2'])
);
    }

    /**
     *
     */
    public function test_indent()
    {
        $this->assertEquals(<<<'PHP'
        public function __construct($prop1, $prop2)
        {
            $this->prop1 = $prop1;
            $this->prop2 = $prop2;
        }
PHP
            , $this->code->indent(<<<'PHP'
public function __construct($prop1, $prop2)
{
    $this->prop1 = $prop1;
    $this->prop2 = $prop2;
}
PHP
, 2)
);
    }

    /**
     *
     */
    public function test_switchInstanceOf()
    {
        $this->assertEquals(<<<'PHP'
if ($object instanceof \Bdf\Prime\TestEntity) {
    $object->doSomething();
} elseif ($object instanceof \Bdf\Prime\Contact) {
    return $object->location;
} elseif ($object instanceof \Bdf\Prime\Location) {
    $object->test();
}
PHP
, $this->code->switchIntanceOf('$object', [
    '\Bdf\Prime\TestEntity' => '$object->doSomething();',
    '\Bdf\Prime\Contact'    => 'return $object->location;',
    '\Bdf\Prime\Location'    => '$object->test();',
])
);
    }

    /**
     *
     */
    public function test_switch()
    {
        $this->assertEquals(<<<'PHP'
switch ($value) {
    case 'foo':
        $object->doSomething();
        break;
    case 'bar':
        return $object->location;
    case 123:
        break;
}
PHP
, $this->code->switch('$value', [
    'foo' => '$object->doSomething();',
    'bar' => 'return $object->location;',
    123   => 'break;',
])
);
    }

    /**
     *
     */
    public function test_switch_with_default()
    {
        $this->assertEquals(<<<'PHP'
switch ($value) {
    case 'foo':
        $object->doSomething();
        break;
    case 'bar':
        return $object->location;
    default:
        $object->defaultAction();
}
PHP
, $this->code->switch('$value', [
    'foo' => '$object->doSomething();',
    'bar' => 'return $object->location;',
], '$object->defaultAction();')
);
    }

    /**
     *
     */
    public function test_tmpVar()
    {
        $this->assertEquals('$__tmp_0', $this->code->tmpVar());
        $this->assertEquals('$__tmp_1', $this->code->tmpVar());
    }

    /**
     *
     */
    public function test_generate()
    {
        $this->assertEquals(<<<'PHP'
not_indented();

class A
{
    public function foo()
    {
        indented_2();
        multilines();
    }

    public function bar()
    {
        return inline_not_indented();
    }
}

PHP
, $this->code->generate(__DIR__.'/_files/stub_test', [
    'placeHolderNotIndented' => 'not_indented();',
    'placeholderIndented'    => "indented_2();\nmultilines();",
    'placeholderInline'      => 'inline_not_indented()'
])
);
    }

    /**
     *
     */
    public function test_className()
    {
        $this->assertEquals('\Bdf\Prime\Location', $this->code->className(Location::class));
    }

    /**
     *
     */
    public function test_export()
    {
        $this->assertSame('null', $this->code->export(null));
        $this->assertSame('123', $this->code->export(123));
        $this->assertSame("'hello world'", $this->code->export('hello world'));
        $this->assertSame("['hello', 'world', '!']", $this->code->export(['hello', 'world', '!']));
    }
}
