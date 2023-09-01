<?php

namespace Bdf\Prime\Relations\Builder;

use Bdf\Prime\Relations\DistantEntityForCustomRelation;
use Bdf\Prime\Relations\EntityForeignIn;
use Bdf\Prime\Relations\ForeignInRelation;
use Bdf\Prime\Relations\MyCustomRelation;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class RelationBuilderTest extends TestCase
{
    /**
     * 
     */
    public function test_belongs_to()
    {
        $builder = new RelationBuilder();
        
        $builder->on('customer')
                ->belongsTo('Customer', 'customerId');

        $expected = [
            'type'          => 'belongsTo',
            'localKey'      => 'customerId',
            'entity'        => 'Customer',
            'distantKey'    => 'id',
        ];

        $this->assertEquals(['customer' => $expected], $builder->relations());
    }

    /**
     *
     */
    public function test_belongs_to_with_foreign()
    {
        $builder = new RelationBuilder();

        $builder->on('customer')
                ->belongsTo('Customer::id_', 'customerId');

        $expected = [
            'type'          => 'belongsTo',
            'localKey'      => 'customerId',
            'entity'        => 'Customer',
            'distantKey'    => 'id_',
        ];

        $this->assertEquals(['customer' => $expected], $builder->relations());
    }

    /**
     *
     */
    public function test_has_one()
    {
        $builder = new RelationBuilder();

        $builder->on('contact')
                ->hasOne('Contact::customerId');

        $expected = [
            'type'          => 'hasOne',
            'localKey'      => 'id',
            'entity'        => 'Contact',
            'distantKey'    => 'customerId',
        ];

        $this->assertEquals(['contact' => $expected], $builder->relations());
    }

    /**
     * 
     */
    public function test_has_one_with_key()
    {
        $builder = new RelationBuilder();
        
        $builder->on('contact')
            ->hasOne('Contact::customerId', 'id_');

        $expected = [
            'type'          => 'hasOne',
            'localKey'      => 'id_',
            'entity'        => 'Contact',
            'distantKey'    => 'customerId',
        ];

        $this->assertEquals(['contact' => $expected], $builder->relations());
    }

    /**
     * 
     */
    public function test_has_many()
    {
        $builder = new RelationBuilder();
        
        $builder->on('documents')
                ->hasMany('Document::customerId');

        $expected = [
            'type'          => 'hasMany',
            'localKey'      => 'id',
            'entity'        => 'Document',
            'distantKey'    => 'customerId',
        ];

        $this->assertEquals(['documents' => $expected], $builder->relations());
    }

    /**
     *
     */
    public function test_has_many_with_key()
    {
        $builder = new RelationBuilder();

        $builder->on('documents')
                ->hasMany('Document::customerId', 'id_');

        $expected = [
            'type'          => 'hasMany',
            'localKey'      => 'id_',
            'entity'        => 'Document',
            'distantKey'    => 'customerId',
        ];

        $this->assertEquals(['documents' => $expected], $builder->relations());
    }

    /**
     * 
     */
    public function test_belongs_to_many()
    {
        $builder = new RelationBuilder();
        
        $builder->on('packs')
                ->belongsToMany('Pack')
                ->through('CustomerPack', 'customerId', 'packId');

        $expected = [
            'type'              => 'belongsToMany',
            'localKey'          => 'id',
            'entity'            => 'Pack',
            'distantKey'        => 'id',
            'through'           => 'CustomerPack',
            'throughLocal'      => 'customerId',
            'throughDistant'    => 'packId',
        ];

        $this->assertEquals(['packs' => $expected], $builder->relations());
    }

    /**
     *
     */
    public function test_belongs_to_many_with_key()
    {
        $builder = new RelationBuilder();

        $builder->on('packs')
                ->belongsToMany('Pack', 'id_');

        $expected = [
            'type'              => 'belongsToMany',
            'localKey'          => 'id_',
            'entity'            => 'Pack',
            'distantKey'        => 'id',
        ];

        $this->assertEquals(['packs' => $expected], $builder->relations());
    }

    /**
     *
     */
    public function test_belongs_to_many_with_foreign_and_key()
    {
        $builder = new RelationBuilder();

        $builder->on('packs')
                ->belongsToMany('Pack::foreign', 'id_');

        $expected = [
            'type'              => 'belongsToMany',
            'localKey'          => 'id_',
            'entity'            => 'Pack',
            'distantKey'        => 'foreign',
        ];

        $this->assertEquals(['packs' => $expected], $builder->relations());
    }

    /**
     *
     */
    public function test_morph_to()
    {
        $builder = new RelationBuilder();

        $builder->on('uploader')
                ->morphTo('uploaderId', 'uploaderType', [
                    'admin' => 'Admin::id',
                    'user' => [
                        'entity'            => 'User',
                        'distantKey'        => 'id',
                    ],
                ]);

        $expected = [
            'type'              => 'morphTo',
            'localKey'          => 'uploaderId',
            'discriminator'     => 'uploaderType',
            'map'               => [
                'admin' => 'Admin::id',
                'user'  => [
                    'entity'     => 'User',
                    'distantKey' => 'id',
                ],
            ],
        ];

        $this->assertEquals(['uploader' => $expected], $builder->relations());
    }

    /**
     *
     */
    public function test_morph_one()
    {
        $builder = new RelationBuilder();

        $builder->on('uploader')
            ->morphOne('Document::uploaderId', 'uploaderType=user');

        $expected = [
            'type'               => 'hasOne',
            'localKey'           => 'id',
            'entity'             => 'Document',
            'distantKey'         => 'uploaderId',
            'discriminator'      => 'uploaderType',
            'discriminatorValue' => 'user',
        ];

        $this->assertEquals(['uploader' => $expected], $builder->relations());
    }

    /**
     * 
     */
    public function test_morph_many()
    {
        $builder = new RelationBuilder();

        $builder->on('uploader')
            ->morphMany('Document::uploaderId', 'uploaderType=user');

        $expected = [
            'type'               => 'hasMany',
            'localKey'           => 'id',
            'entity'             => 'Document',
            'distantKey'         => 'uploaderId',
            'discriminator'      => 'uploaderType',
            'discriminatorValue' => 'user',
        ];

        $this->assertEquals(['uploader' => $expected], $builder->relations());
    }
    
    /**
     * 
     */
    public function test_inherit()
    {
        $builder = new RelationBuilder();
        
        $builder->on('target')
                ->inherit('targetId');

        $expected = [
            'type'     => 'byInheritance',
            'localKey' => 'targetId',
        ];

        $this->assertEquals(['target' => $expected], $builder->relations());
    }

    /**
     *
     */
    public function test_inherit_should_keep_previous_config()
    {
        $builder = new RelationBuilder();

        $builder->on('target')
            ->inherit('targetId')
            ->eager()
            ->option('foo', 'bar')
        ;

        $builder->on('target')
            ->belongsTo('Target', 'targetId');

        $expected = [
            'type'     => 'belongsTo',
            'localKey' => 'targetId',
            'entity' => 'Target',
            'foo' => 'bar',
            'mode' => 'EAGER',
            'distantKey' => 'id',
        ];

        $this->assertEquals(['target' => $expected], $builder->relations());
    }

    /**
     *
     */
    public function test_custom()
    {
        $builder = new RelationBuilder();

        $builder->on('target')
            ->custom(MyCustomRelation::class, ['keys' => ['l1' => 'd1', 'l2' => 'l1'], 'entity' => DistantEntityForCustomRelation::class]);

        $expected = [
            'type'          => 'custom',
            'relationClass' => MyCustomRelation::class,
            'entity'        => DistantEntityForCustomRelation::class,
            'keys'          => ['l1' => 'd1', 'l2' => 'l1']
        ];

        $this->assertEquals(['target' => $expected], $builder->relations());
    }

    /**
     *
     */
    public function test_null()
    {
        $builder = new RelationBuilder();

        $builder->on('target')->null();

        $expected = [
            'type'          => 'null',
        ];

        $this->assertEquals(['target' => $expected], $builder->relations());
    }

    /**
     *
     */
    public function test_entity()
    {
        $builder = new RelationBuilder();

        $builder->on('target')
            ->custom(ForeignInRelation::class)
            ->entity(EntityForeignIn::class)
        ;

        $expected = [
            'type'          => 'custom',
            'relationClass' => ForeignInRelation::class,
            'entity'        => EntityForeignIn::class,
            'distantKey'    => 'id',
        ];

        $this->assertEquals(['target' => $expected], $builder->relations());

        $builder->entity(EntityForeignIn::class.'::pk');
        $this->assertEquals('pk', $builder->relations()['target']['distantKey']);
    }

    /**
     *
     */
    public function test_option()
    {
        $builder = new RelationBuilder();

        $builder->on('target')
            ->custom(ForeignInRelation::class)
            ->option('localKeys', ['k1', 'k2'])
        ;

        $expected = [
            'type'          => 'custom',
            'relationClass' => ForeignInRelation::class,
            'localKeys'     => ['k1', 'k2']
        ];

        $this->assertEquals(['target' => $expected], $builder->relations());
    }
    
    /**
     * 
     */
    public function test_detached()
    {
        $builder = new RelationBuilder();
        
        $builder->on('documents')
                ->hasMany('Document::customerId')
                ->detached();

        $this->assertEquals(true, $builder['documents']['detached']);
    }
    
    /**
     * 
     */
    public function test_constraints()
    {
        $builder = new RelationBuilder();
        
        $builder->on('documents')
                ->hasMany('Document::customerId')
                ->constraints(['enabled' => true]);

        $this->assertEquals(['enabled' => true], $builder['documents']['constraints']);
    }

    /**
     *
     */
    public function test_saveStrategy()
    {
        $builder = new RelationBuilder();

        $builder->on('documents')
                ->hasMany('Document::customerId')
                ->saveStrategy(2);

        $this->assertEquals(2, $builder['documents']['saveStrategy']);
    }

    /**
     * 
     */
    public function test_iterator()
    {
        $builder = new RelationBuilder();
        
        $builder->on('relation1')->hasOne('Entity1::distant');
        $builder->on('relation2')->hasOne('Entity2::distant');

        $items = ['relation1', 'relation2'];
        $i = 0;
        
        foreach ($builder as $key => $meta) {
            $this->assertEquals($items[$i++], $key);
        }
        
        $this->assertEquals(2, $i);
    }
    
    /**
     * 
     */
    public function test_array_access()
    {
        $builder = new RelationBuilder();

        $builder->on('relation1')->hasOne('Entity1::distant');

        $this->assertTrue(isset($builder['relation1']));
        
        unset($builder['relation1']);
        $this->assertTrue(isset($builder['relation1']));
        
        $builder['relation1'] = null;
        $this->assertTrue(isset($builder['relation1']));
    }

    /**
     *
     */
    public function test_mode_eager()
    {
        $builder = new RelationBuilder();

        $builder->on('relation1')->hasOne('Entity1::distant')->mode(RelationBuilder::MODE_EAGER);

        $this->assertEquals(RelationBuilder::MODE_EAGER, $builder['relation1']['mode']);
    }

    /**
     *
     */
    public function test_eager()
    {
        $builder = new RelationBuilder();

        $builder->on('relation1')->hasOne('Entity1::distant')->eager();

        $this->assertEquals(RelationBuilder::MODE_EAGER, $builder['relation1']['mode']);
    }

    /**
     *
     */
    public function test_lazy()
    {
        $builder = new RelationBuilder();

        $builder->on('relation1')->hasOne('Entity1::distant')->lazy();

        $this->assertEquals(RelationBuilder::MODE_LAZY, $builder['relation1']['mode']);
    }

    /**
     *
     */
    public function test_wrapAs()
    {
        $builder = new RelationBuilder();

        $builder->on('relation1')->hasMany('Entity1::distant')->wrapAs('collection');

        $this->assertEquals('collection', $builder['relation1']['wrapper']);
    }
}
