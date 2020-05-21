<?php

namespace Bdf\Prime\Entity;

use Bdf\Prime\Entity\Extensions;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ArrayInjectorTest extends TestCase
{
    public function test_injection()
    {
        $entity = new ArrayEntity();
        $entity->initialize();
        $entity->import([
            'id' => 456,
            'embedded' => [
                'name' => 'test'
            ]
        ]);
        
        $this->assertEquals(456, $entity->getId());
        $this->assertEquals('set-test', $entity->embedded()->getName());
    }
}

//---------------

class ArrayEntity implements ImportableInterface, InitializableInterface
{
    use Extensions\ArrayInjector;
    
    protected $id;
    protected $embedded;
    
    public function initialize()
    {
        $this->embedded = new ArrayEmbeddedEntity();
    }
    
    public function setId($id)
    {
        $this->id = $id;
    }
    
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ArrayEmbeddedEntity
     */
    public function embedded()
    {
        return $this->embedded;
    }
}

class ArrayEmbeddedEntity implements ImportableInterface
{
    use Extensions\ArrayInjector;
    
    protected $name;
    
    public function setName($name)
    {
        $this->name = 'set-' . $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
}