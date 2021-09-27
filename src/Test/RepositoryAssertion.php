<?php

namespace Bdf\Prime\Test;

use Bdf\Prime\Entity\Hydrator\Exception\UninitializedPropertyException;
use Bdf\Prime\Prime;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * RepositoryAssertion
 */
trait RepositoryAssertion
{
    /**
     * @var TestPack
     */
    protected $testPack;
    
    /**
     * Get the test pack manager
     *
     * @return TestPack
     */
    public function getTestPack()
    {
        if ($this->testPack === null) {
            $this->testPack = TestPack::pack();
        }
        
        return $this->testPack;
    }
    
    /**
     * Assert that two array of entities are the same
     * 
     * @param array $expectedEntities
     * @param array $actualEntities
     * @param string $message
     *
     * @throws \Exception
     */
    public function assertSameEntities($expectedEntities, $actualEntities, $message = '')
    {
        $this->assertEquals(
            count($expectedEntities),
            count($actualEntities),
            'Failed asserting that two collection entities are the same.'.($message ? PHP_EOL.$message : '')
        );

        foreach ($expectedEntities as $index => $expectedEntity) {
            $this->assertSameEntity($expectedEntity, $actualEntities[$index], $message);
        }
    }
    
    /**
     * Assert that two entities are the same
     * Compare only fields defined in associated mapper
     * 
     * @param object        $expected
     * @param object        $entity
     * @param string        $message
     *
     * @throws \Exception
     */
    public function assertSameEntity($expected, $entity, $message = '')
    {
        $this->assertEntity($expected, $entity, 0, $message);
    }
    
     /**
     * Assert that two array of entities are equal
     * 
     * @param array $expectedEntities
     * @param array $actualEntities
     * @param int   $dateTimeDelta
     * @param string $message
      *
      * @throws \Exception
     */
    public function assertEntities($expectedEntities, $actualEntities, $dateTimeDelta = 5, $message = '')
    {
        if (is_string($dateTimeDelta)) {
            $message = $dateTimeDelta;
            $dateTimeDelta = 5;

            @trigger_error('The assertEntities interface change. Use message as 4th parameter.', E_USER_DEPRECATED);
        }

        $this->assertEquals(
            count($expectedEntities),
            count($actualEntities),
            'Failed asserting that two collection entities are the same.'.($message ? PHP_EOL.$message : '')
        );

        foreach ($expectedEntities as $index => $expectedEntity) {
            $this->assertEntity($expectedEntity, $actualEntities[$index], $dateTimeDelta, $message);
        }
    }
    
    /**
     * Assert that two entities are equal
     * Compare only fields defined in associated mapper
     * Useful for dates : add a time delta
     *
     * @param object        $expected
     * @param object        $entity
     * @param int           $dateTimeDelta
     * @param string        $message
     *
     * @throws \Exception
     */
    public function assertEntity($expected, $entity, $dateTimeDelta = 5, $message = '')
    {
        if (is_string($dateTimeDelta)) {
            $message = $dateTimeDelta;
            $dateTimeDelta = 5;

            @trigger_error('The assertEntity interface change. Use message as 4th parameter.', E_USER_DEPRECATED);
        }

        $this->compareEntity(get_class($expected), $expected, $entity, $dateTimeDelta, $message);
    }

    /**
     * Compare entity values with a map of values.
     * The map can also contain constraints
     *
     * @param string        $expectedClass
     * @param array         $expected
     * @param object|object[] $entities
     * @param int           $dateTimeDelta
     * @param string        $message
     *
     * @throws \Exception
     */
    public function assertEntityValues($expectedClass, $expected, $entities, $dateTimeDelta = 5, $message = '')
    {
        if (!is_array($entities)) {
            $this->compareEntity($expectedClass, $expected, $entities, $dateTimeDelta, $message);
            return;
        }

        $this->assertEquals(
            count($expected),
            count($entities),
            'Failed asserting that two collection entities are the same.'.($message ? PHP_EOL.$message : '')
        );

        foreach ($entities as $index => $entity) {
            $this->compareEntity($expectedClass, $expected[$index], $entity, $dateTimeDelta, $message);
        }
    }

    /**
     * Compare 2 entities
     * If strict is false, add delta on date comparison
     *
     * @param string $expectedClass
     * @param array $expected
     * @param object $entity
     * @param int $dateTimeDelta
     * @param string $message
     *
     * @throws \Exception
     */
    private function compareEntity($expectedClass, $expected, $entity, $dateTimeDelta = 0, $message = '')
    {
        if (is_string($dateTimeDelta)) {
            $message = $dateTimeDelta;
            $dateTimeDelta = 5;

            @trigger_error('The compareEntity interface change. Use message as 4th parameter.', E_USER_DEPRECATED);
        }

        if ($message == '') {
            $message = $expectedClass;
        }

        $this->assertEquals($expectedClass, get_class($entity), 'Failed asserting that two entities are the same.');

        $repository = Prime::repository($entity);

        foreach ($repository->metadata()->attributes as $attribute => $metadata) {
            $path = $repository->metadata()->entityClass.'::'.$attribute;
            $isUninitialized = false;

            try {
                $expectedValue = is_object($expected) ? $repository->extractOne($expected, $attribute) : ($expected[$attribute] ?? null);
            } catch (UninitializedPropertyException $e) {
                $isUninitialized = true;
            }

            try {
                $value = $repository->extractOne($entity, $attribute);

                if ($isUninitialized) {
                    $this->fail($message . ': Expected attribute "'.$path.'" to be not initialised');
                }
            } catch (UninitializedPropertyException $e) {
                if (!$isUninitialized) {
                    $this->fail($message . ': The attribute "'.$path.'" is not initialised');
                }
            }

            if (!is_object($expectedValue)) {
                $this->assertSame($expectedValue, $value, $message . ': Expected attribute "'.$path.'" is not the same');
                continue;
            }

            if ($expectedValue instanceof Constraint) {
                $this->assertThat($value, $expectedValue, $message . ': Expected attribute "'.$path.'" is not the same');
            } elseif ($expectedValue instanceof \DateTimeInterface) {
                $this->assertEqualsWithDelta($expectedValue, $value, $dateTimeDelta, $message . ': Expected attribute "'.$path.'" is not the same');
            } else {
                $this->assertEquals($expectedValue, $value, $message . ': Expected attribute "'.$path.'" is not the same');
            }
        }
    }
}
