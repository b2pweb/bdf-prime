<?php

namespace Entity\Hydrator\Generator;

use Bdf\Prime\Entity\Hydrator\Generator\CodeGenerator;
use Bdf\Prime\Entity\Hydrator\Generator\TypeAccessor;
use PHPUnit\Framework\TestCase;

/**
 * Class TypeAccessorTest
 */
class TypeAccessorTest extends TestCase
{
    /**
     * @var TypeAccessor
     */
    private $types;

    protected function setUp(): void
    {
        $this->types = new TypeAccessor(new CodeGenerator());
    }

    /**
     *
     */
    public function test_declaration()
    {
        $this->types->declare('integer');
        $this->types->declare('integer');
        $this->types->declare(TypeAccessor::class);
        $this->types->declare('integer[]');
        $this->types->declare('integer[][]');

        $this->assertEquals(<<<'PHP'
$typeinteger = $types->get('integer');
$typeBdfPrimeEntityHydratorGeneratorTypeAccessor = $types->get('Bdf\Prime\Entity\Hydrator\Generator\TypeAccessor');
$typearrayOfinteger = $types->get('integer[]');
$typearrayOfinteger1 = $types->get('integer[][]');

PHP
, $this->types->generateDeclaration());
    }

    /**
     *
     */
    public function test_generateFromDatabase()
    {
        $this->assertEquals('$typeinteger->fromDatabase($value);', $this->types->generateFromDatabase('integer', '$value', ''));
        $this->assertEquals('$typeobject->fromDatabase($value, my_option);', $this->types->generateFromDatabase('object', '$value', 'my_option'));
    }
}
