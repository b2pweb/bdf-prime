<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

use Bdf\Prime\ArrayHydratorTestEntity;
use Bdf\Prime\CustomerControlTask;
use Bdf\Prime\DocumentControlTask;
use Bdf\Prime\Name;
use Bdf\Prime\Task;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ClassAccessorTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function test_isPropertyAccessible_external()
    {
        $accessor = new ClassAccessor(ArrayHydratorTestEntity::class, ClassAccessor::SCOPE_EXTERNAL);

        $this->assertTrue($accessor->isPropertyAccessible('name'));
        $this->assertFalse($accessor->isPropertyAccessible('phone'));
        $this->assertFalse($accessor->isPropertyAccessible('password'));
    }

    /**
     * @throws \ReflectionException
     */
    public function test_isPropertyAccessible_inherit()
    {
        $accessor = new ClassAccessor(ArrayHydratorTestEntity::class, ClassAccessor::SCOPE_INHERIT);

        $this->assertTrue($accessor->isPropertyAccessible('name'));
        $this->assertTrue($accessor->isPropertyAccessible('phone'));
        $this->assertFalse($accessor->isPropertyAccessible('password'));
    }

    /**
     * @throws \ReflectionException
     */
    public function test_isPropertyAccessible_with_subclasses_redefining()
    {
        $accessor = new ClassAccessor(Task::class, ClassAccessor::SCOPE_INHERIT, [DocumentControlTask::class, CustomerControlTask::class]);

        $this->assertFalse($accessor->isPropertyAccessible('overridenProperty'));
    }

    /**
     * @throws \ReflectionException
     */
    public function test_getter()
    {
        $accessor = new ClassAccessor(ArrayHydratorTestEntity::class, ClassAccessor::SCOPE_EXTERNAL);

        $this->assertEquals('$obj->name', $accessor->getter('$obj', 'name'));
        $this->assertEquals('$obj->getPassword()', $accessor->getter('$obj', 'password'));
    }

    /**
     * @throws \ReflectionException
     */
    public function test_primitiveGetter()
    {
        $accessor = new ClassAccessor(ArrayHydratorTestEntity::class, ClassAccessor::SCOPE_EXTERNAL);

        $this->assertEquals('(($__tmpef58710101a3ed83347cbda58e158b7b = $obj->name) instanceof \Bdf\Prime\Name ? $__tmpef58710101a3ed83347cbda58e158b7b->value() : $__tmpef58710101a3ed83347cbda58e158b7b)', $accessor->primitiveGetter('$obj', 'name', Name::class));
        $this->assertEquals('(($__tmp97ee25251a936de382177b0b958573bf = $obj->getPassword()) instanceof \Bdf\Prime\Name ? $__tmp97ee25251a936de382177b0b958573bf->value() : $__tmp97ee25251a936de382177b0b958573bf)', $accessor->primitiveGetter('$obj', 'password', Name::class));
    }

    /**
     * @throws \ReflectionException
     */
    public function test_setter()
    {
        $accessor = new ClassAccessor(ArrayHydratorTestEntity::class, ClassAccessor::SCOPE_EXTERNAL);

        $this->assertEquals('$obj->setName($value)', $accessor->setter('$obj', 'name', '$value'));
        $this->assertEquals('$obj->name = $value', $accessor->setter('$obj', 'name', '$value', false));

        $this->assertEquals('$obj->setPassword($value)', $accessor->setter('$obj', 'password', '$value'));
        $this->assertEquals('$obj->setPassword($value)', $accessor->setter('$obj', 'password', '$value', false));
    }

    /**
     * @throws \ReflectionException
     */
    public function test_valueObjectSetter()
    {
        $accessor = new ClassAccessor(ArrayHydratorTestEntity::class, ClassAccessor::SCOPE_EXTERNAL);

        $this->assertEquals('$obj->name = (($__tmp7d0596c36891967f3bb9d994b4a97c19 = $value) !== null ? \Bdf\Prime\Name::from($__tmp7d0596c36891967f3bb9d994b4a97c19) : $__tmp7d0596c36891967f3bb9d994b4a97c19)', $accessor->valueObjectSetter('$obj', 'name', '$value', Name::class));
        $this->assertEquals('$obj->setPassword((($__tmp7d0596c36891967f3bb9d994b4a97c19 = $value) !== null ? \Bdf\Prime\Name::from($__tmp7d0596c36891967f3bb9d994b4a97c19) : $__tmp7d0596c36891967f3bb9d994b4a97c19))', $accessor->valueObjectSetter('$obj', 'password', '$value', Name::class));
        $this->assertEquals('$obj->setPassword((($__tmp7d0596c36891967f3bb9d994b4a97c19 = $value) !== null && !$__tmp7d0596c36891967f3bb9d994b4a97c19 instanceof \Bdf\Prime\Name ? \Bdf\Prime\Name::from($__tmp7d0596c36891967f3bb9d994b4a97c19) : $__tmp7d0596c36891967f3bb9d994b4a97c19))', $accessor->valueObjectSetter('$obj', 'password', '$value', Name::class, true));
    }
}
