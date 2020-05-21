<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

use Bdf\Prime\ArrayHydratorTestEntity;
use Bdf\Prime\CustomerControlTask;
use Bdf\Prime\DocumentControlTask;
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
    public function test_setter()
    {
        $accessor = new ClassAccessor(ArrayHydratorTestEntity::class, ClassAccessor::SCOPE_EXTERNAL);

        $this->assertEquals('$obj->setName($value)', $accessor->setter('$obj', 'name', '$value'));
        $this->assertEquals('$obj->name = $value', $accessor->setter('$obj', 'name', '$value', false));

        $this->assertEquals('$obj->setPassword($value)', $accessor->setter('$obj', 'password', '$value'));
        $this->assertEquals('$obj->setPassword($value)', $accessor->setter('$obj', 'password', '$value', false));
    }
}
