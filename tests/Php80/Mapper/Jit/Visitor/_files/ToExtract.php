<?php

namespace Php80\Mapper\Jit\Visitor\_files;

require_once __DIR__ . '/OtherDef.php';

use ArrayObject;
use Bdf\Prime\Events;
use Bdf\Prime\TestEntity;
use PhpParser\Node;

use function var_dump;

class ToExtract extends ArrayObject
{
    public function test(TestEntity $foo): int
    {
        $h = new Events();
        $b = new Foo();
        $c = new \BadMethodCallException();

        var_dump($h, $b, $c);

        new Foo\Bar\Baz();

        $str = new Node\Scalar\String_('test');
        $pi4 = M_PI_4;

        return 1;
    }
}
