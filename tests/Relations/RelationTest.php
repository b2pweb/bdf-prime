<?php

namespace Bdf\Prime\Relations;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class RelationTest extends TestCase
{
    /**
     * 
     */
    public function test_sanitize()
    {
        $result = Relation::sanitizeRelations([
            'customer.packs',
            'customer.document.owner' => ['status' => 1],
            'customer' => ['id' => 2],
            'permissions',
        ]);
        
        $expected = [
            'customer' => [
                'constraints' => ['id' => 2],
                'relations'   => [
                    'packs' => [],
                    'document.owner' => ['status' => 1],
                ],
            ],
            'permissions' => [
                'constraints' => [],
                'relations'   => [],
            ]
        ];
        
        $this->assertEquals($expected, $result);
    }
    
    /**
     * 
     */
    public function test_sanitize_single_relation()
    {
        $result = Relation::sanitizeRelations([
            'customer.document' => ['status' => 1],
        ]);
        
        $expected = [
            'customer' => [
                'constraints' => [],
                'relations'   => [
                    'document' => ['status' => 1],
                ],
            ],
        ];
        
        $this->assertEquals($expected, $result);
    }
    
    /**
     * 
     */
    public function test_sanitize_dont_overwrite_parent_constraints()
    {
        $result = Relation::sanitizeRelations([
            'customer' => ['enabled' => true],
            'customer.document' => ['status' => 1],
        ]);
        
        $expected = [
            'customer' => [
                'constraints' => ['enabled' => true],
                'relations'   => [
                    'document' => ['status' => 1],
                ],
            ],
        ];
        
        $this->assertEquals($expected, $result);
    }

    /**
     *
     */
    public function test_set_options()
    {
        $relation = $this->getMockForAbstractClass('Bdf\Prime\Relations\Relation', [], '', false);

        $relation->setOptions([
            'constraints'  => ['filter' => true],
            'detached'     => true,
            'saveStrategy' => 2,
            'discriminator' => 'type',
            'discriminatorValue' => 'value',
            'wrapper' => 'my_wrapper',
        ]);
        $expected = [
            'constraints'  => ['filter' => true],
            'detached'     => true,
            'saveStrategy' => 2,
            'discriminator' => 'type',
            'discriminatorValue' => 'value',
            'wrapper' => 'my_wrapper',
        ];

        $this->assertEquals($expected, $relation->getOptions());
    }

    /**
     *
     */
    public function test_set_get_save_strategy()
    {
        $expected = Relation::SAVE_STRATEGY_REPLACE;
        $relation = $this->getMockForAbstractClass('Bdf\Prime\Relations\Relation', [], '', false);
        $relation->setSaveStrategy($expected);

        $this->assertEquals($expected, $relation->getSaveStrategy());
    }

    /**
     *
     */
    public function test_set_get_wrapper()
    {
        $expected = 'myWrapper';
        $relation = $this->getMockForAbstractClass('Bdf\Prime\Relations\Relation', [], '', false);
        $relation->setWrapper($expected);

        $this->assertEquals($expected, $relation->getWrapper());
    }

    /**
     *
     */
    public function test_set_get_detached()
    {
        $expected = true;
        $relation = $this->getMockForAbstractClass('Bdf\Prime\Relations\Relation', [], '', false);
        $relation->setDetached($expected);

        $this->assertEquals($expected, $relation->isDetached());
    }

    /**
     *
     */
    public function test_set_get_constraints()
    {
        $expected = ['filter' => true];
        $relation = $this->getMockForAbstractClass('Bdf\Prime\Relations\Relation', [], '', false);
        $relation->setConstraints($expected);

        $this->assertEquals($expected, $relation->getConstraints());
    }
}
