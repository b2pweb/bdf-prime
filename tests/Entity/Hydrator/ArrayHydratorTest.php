<?php

namespace Bdf\Prime\Entity\Hydrator;

require_once __DIR__ . '/../../_files/array_hydrator_entities.php';

use Bdf\Prime\ArrayHydratorTestEntity;
use Bdf\Prime\EmbeddedEntity;
use Bdf\Prime\Folder;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestFile;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ArrayHydratorTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var ArrayHydrator
     */
    protected $hydrator;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->hydrator = new ArrayHydrator();
    }

    /**
     *
     */
    public function test_hydrate_scalar()
    {
        $obj = new ArrayHydratorTestEntity();

        $this->hydrator->hydrate($obj, [
            'name' => 'Bob',
            'phone' => '0123654987',
            'password' => 'bob'
        ]);

        $this->assertEquals('Bob', $obj->getName());
        $this->assertEquals('0123654987', $obj->getPhone());
        $this->assertEquals('bob', $obj->getPassword());

        $this->assertEquals(new EmbeddedEntity(), $obj->getRef());
        $this->assertNull($obj->getRef2());
    }

    /**
     *
     */
    public function test_hydrate_set_entity()
    {
        $obj = new ArrayHydratorTestEntity();

        $ref = new EmbeddedEntity('123');
        $ref2 = new EmbeddedEntity('987');

        $this->hydrator->hydrate($obj, [
            'ref' => $ref,
            'ref2' => $ref2
        ]);

        $this->assertSame($ref, $obj->getRef());
        $this->assertSame($ref2, $obj->getRef2());
    }

    /**
     *
     */
    public function test_hydrate_hydrate_entity()
    {
        $obj = new ArrayHydratorTestEntity();

        $this->hydrator->hydrate($obj, [
            'ref' => [
                'id' => '123'
            ]
        ]);

        $this->assertEquals('123', $obj->getRef()->getId());
    }

    /**
     *
     */
    public function test_extract_all()
    {
        $entity = new ArrayHydratorTestEntity();

        $entity->setName('Bob');
        $entity->setPassword('Coucou');
        $entity->setPhone('0147852369');
        $entity->setRef(new EmbeddedEntity(123));

        $this->assertEquals([
            'name' => 'Bob',
            'password' => 'Coucou',
            'phone' => '0147852369',
            'ref' => [
                'id' => 123
            ],
            'ref2' => null
        ], $this->hydrator->extract($entity));
    }

    /**
     *
     */
    public function test_extract_selected()
    {
        $entity = new ArrayHydratorTestEntity();

        $entity->setName('Bob');
        $entity->setPassword('Coucou');
        $entity->setPhone('0147852369');
        $entity->setRef(new EmbeddedEntity(123));

        $this->assertEquals([
            'name' => 'Bob',
            'phone' => '0147852369'
        ], $this->hydrator->extract($entity, ['name', 'phone']));
    }

    /**
     *
     */
    public function test_hydrate_with_collection()
    {
        $folder = new Folder();
        $hydrator = new ArrayHydrator();

        $hydrator->hydrate($folder, [
            'files' => [
                ['name' => 'file1'],
                ['name' => 'file2'],
                ['name' => 'file3'],
            ]
        ]);

        $this->assertCount(3, $folder->files);
        $this->assertEquals([
            new TestFile(['name' => 'file1']),
            new TestFile(['name' => 'file2']),
            new TestFile(['name' => 'file3']),
        ], $folder->files->all());
    }

    /**
     *
     */
    public function test_extract_with_collection()
    {
        $hydrator = new ArrayHydrator();
        $folder = new Folder([
            'files' => [
                ['name' => 'file1'],
                ['name' => 'file2'],
                ['name' => 'file3'],
            ]
        ]);

        $folder->files = TestFile::collection([
            new TestFile(['name' => 'file1']),
            new TestFile(['name' => 'file2']),
            new TestFile(['name' => 'file3']),
        ]);

        $this->assertEquals([
            'id'       => null,
            'name'     => null,
            'parentId' => null,
            'parent'   => null,
            'files'    => [
                [
                    'id'       => null,
                    'folderId' => null,
                    'name'     => 'file1',
                    'owner'    => ['name' => null, 'groups' => null],
                    'group'    => ['name' => null, 'users' => null]
                ],
                [
                    'id'       => null,
                    'folderId' => null,
                    'name'     => 'file2',
                    'owner'    => ['name' => null, 'groups' => null],
                    'group'    => ['name' => null, 'users' => null]
                ],
                [
                    'id'       => null,
                    'folderId' => null,
                    'name'     => 'file3',
                    'owner'    => ['name' => null, 'groups' => null],
                    'group'    => ['name' => null, 'users' => null]
                ],
            ],
        ], $hydrator->extract($folder));
    }
}
