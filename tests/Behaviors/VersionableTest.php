<?php

namespace Bdf\Prime\Behaviors;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Mapper\VersionableMapper;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class VersionableTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([
            'Bdf\Prime\Behaviors\Book',
            'Bdf\Prime\Behaviors\BookVersion',
            'Bdf\Prime\Behaviors\BookAuthor',
            'Bdf\Prime\Behaviors\BookAuthorVersion',
        ]);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
    }

    /**
     *
     */
    public function test_configure_fields()
    {
        $mapper = new BookMapper(Prime::service(), 'Bdf\Prime\Behaviors\Book');

        $fields = $mapper->fields();

        $this->assertTrue(isset($fields['version']));
    }

    /**
     *
     */
    public function test_inserting()
    {
        $book = new Book('1984');

        $book->insert();

        $this->assertEquals(1, $book->version);

        $bookVersions = Prime::repository('Bdf\Prime\Behaviors\BookVersion')->all();

        $this->assertEquals(1, count($bookVersions));
        $this->assertEquals($book->title, $bookVersions[0]->title);
    }

    /**
     *
     */
    public function test_update()
    {
        $book = new Book('1983');
        $book->insert();

        $book->title = '1984';
        $book->update();

        $this->assertEquals(2, $book->version);

        $bookVersions = Prime::repository('Bdf\Prime\Behaviors\BookVersion')->order('version')->all();

        $this->assertEquals(2, count($bookVersions));
        $this->assertEquals('1983', $bookVersions[0]->title);
        $this->assertEquals('1984', $bookVersions[1]->title);
    }

    /**
     *
     */
    public function test_update_property()
    {
        $book = new Book('1983');
        $book->insert();

        $book->title = '1984';
        $book->update(['title']);
        $book = Book::get(1);

        $this->assertEquals(2, $book->version);

        $bookVersions = Prime::repository('Bdf\Prime\Behaviors\BookVersion')->order('version')->all();

        $this->assertEquals(2, count($bookVersions));
        $this->assertEquals('1983', $bookVersions[0]->title);
        $this->assertEquals('1984', $bookVersions[1]->title);
    }

    /**
     *
     */
    public function test_delete_disabled_version_deletion()
    {
        $book = new Book('1983');
        $book->insert();

        $book->title = '1984';
        $book->update();

        $book->delete();

        $this->assertEquals(2, Prime::repository('Bdf\Prime\Behaviors\BookVersion')->count());
    }

    /**
     *
     */
    public function test_delete_enabled_version_deletion()
    {
        $repository = Prime::repository('Bdf\Prime\Behaviors\BookAuthor');
        $versionRepository = Prime::repository('Bdf\Prime\Behaviors\BookAuthorVersion');

        $bookAuthor = new \stdClass();
        $bookAuthor->bookId = 1;
        $bookAuthor->authorId = 12;
        $bookAuthor->pseudo = 'Arthur';
        $repository->insert($bookAuthor);
        $this->assertEquals(1, $versionRepository->count());

        $bookAuthor->pseudo = 'George';
        $repository->update($bookAuthor);
        $this->assertEquals(2, $versionRepository->count());

        $repository->delete($bookAuthor);
        $this->assertEquals(0, $versionRepository->count());
    }

    /**
     *
     */
    public function test_integration_with_other_behavior()
    {
        $now = new \DateTime();
        $repository = Prime::repository('Bdf\Prime\Behaviors\BookAuthor');
        $versionRepository = Prime::repository('Bdf\Prime\Behaviors\BookAuthorVersion');

        $bookAuthor = new \stdClass();
        $bookAuthor->bookId = 1;
        $bookAuthor->authorId = 12;
        $bookAuthor->pseudo = 'Arthur';
        $repository->insert($bookAuthor);

        $bookAuthorVersions = $versionRepository->order('version')->all();
        $this->assertEqualsWithDelta($now, $bookAuthorVersions[0]->createdAt, 1);
        $this->assertEquals(null, $bookAuthorVersions[0]->updatedAt);

        $bookAuthor->pseudo = 'George';
        $repository->update($bookAuthor);

        $bookAuthorVersions = $versionRepository->order('version')->all();
        $this->assertEqualsWithDelta($now, $bookAuthorVersions[1]->createdAt, 1);
        $this->assertEqualsWithDelta($now, $bookAuthorVersions[1]->updatedAt, 1);
    }

    /**
     *
     */
    public function test_getEntityClass()
    {
        $this->assertEquals('Bdf\Prime\Behaviors\Book', Prime::repository('Bdf\Prime\Behaviors\BookVersion')->mapper()->getEntityClass());
        $this->assertEquals('Bdf\Prime\Behaviors\BookAuthor', Prime::repository('Bdf\Prime\Behaviors\BookAuthorVersion')->mapper()->getEntityClass());
    }
}

class Book extends Model
{
    public $id;
    public $title;
    public $version;

    public function __construct($title = null)
    {
        $this->title = $title;
    }
}

class BookMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table'      => 'book_entity',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
    {
        $builder
            ->bigint('id')->autoincrement()
            ->string('title', 60)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinedBehaviors()
    {
        return [
            new Versionable('Bdf\Prime\Behaviors\BookVersion'),
        ];
    }
}

class BookVersionMapper extends VersionableMapper
{
    /**
     * {@inheritdoc}
     */
    public function getVersionedClass()
    {
        return 'Bdf\Prime\Behaviors\Book';
    }
}

class BookAuthorMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table'      => 'book_author_entity',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
    {
        $builder
            ->bigint('bookId')->alias('book_id')->primary(Metadata::PK_AUTO)
            ->integer('authorId')->alias('author_id')->primary(Metadata::PK_AUTO)
            ->string('pseudo', 60)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinedBehaviors()
    {
        return [
            new Versionable('Bdf\Prime\Behaviors\BookAuthorVersion', true),
            new Timestampable(),
        ];
    }
}

class BookAuthorVersionMapper extends VersionableMapper
{
    /**
     * {@inheritdoc}
     */
    public function getVersionedClass()
    {
        return 'Bdf\Prime\Behaviors\BookAuthor';
    }
}