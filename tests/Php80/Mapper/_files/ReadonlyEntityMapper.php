<?php

namespace Php80\Mapper\_files;

use Bdf\Prime\Mapper\Attribute\DisableSchemaManager;
use Bdf\Prime\Mapper\Attribute\DisableWrite;
use Bdf\Prime\Mapper\Attribute\Filter;
use Bdf\Prime\Mapper\Attribute\RepositoryMethod;
use Bdf\Prime\Mapper\Attribute\Scope;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Repository\EntityRepository;

#[DisableSchemaManager, DisableWrite]
class ReadonlyEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'readonly_entity',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('name')
        ;
    }

    #[Scope]
    public function foo(QueryInterface $query, string $bar): QueryInterface
    {
        return $query->where('foo', $bar);
    }

    #[Scope('oof')]
    protected function bar(QueryInterface $query, string $bar): QueryInterface
    {
        return $query->where('bar', $bar);
    }

    #[Scope]
    public static function baz(QueryInterface $query, string $bar): QueryInterface
    {
        return $query->where('baz', $bar);
    }

    #[Filter]
    public function myFilter(QueryInterface $query, string $bar): void
    {
        $query
            ->where('id', crc32($bar))
            ->orWhere('name', $bar)
        ;
    }

    #[Filter('other')]
    protected static function otherFilter(QueryInterface $query, string $bar): void
    {
        $query->where('name', md5($bar));
    }

    #[RepositoryMethod]
    public function search(EntityRepository $repository, string $term): string
    {
        return 'foo';
    }

    #[RepositoryMethod('rechercher')]
    protected static function search2(EntityRepository $repository, string $term): string
    {
        return 'bar';
    }
}
