<?php

namespace Php81\Query\Criteria;

use Bdf\Prime\Query\Criteria\AttributeCriteriaLoader;
use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\Query\Criteria\LikeCriterion;
use Bdf\Prime\Query\Criteria\StartsWithCriterion;
use Bdf\Prime\Query\Expression\Like;
use Php81\Query\Criteria\Fixtures\NullableCriteria;
use Php81\Query\Criteria\Fixtures\SimpleCriteria;
use Php81\Query\Criteria\Fixtures\WithLikeCriteria;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

class AttributeCriteriaLoaderTest extends TestCase
{
    private AttributeCriteriaLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new AttributeCriteriaLoader();
    }

    public function test_load()
    {
        $this->assertSame($this->loader->load(SimpleCriteria::class), $this->loader->load(SimpleCriteria::class));
        $this->assertEquals(['name' => new Criterion(), 'value' => new Criterion(operator: '>=')], $this->loader->load(SimpleCriteria::class));
        $this->assertEquals(['name' => new StartsWithCriterion(), 'domain' => new LikeCriterion(field: 'email', start: '%@')], $this->loader->load(WithLikeCriteria::class));
    }

    public function test_criteria()
    {
        $this->assertEquals(['name' => 'foo', 'value >=' => 42], iterator_to_array($this->loader->criteria(new SimpleCriteria('foo', 42))));
        $this->assertEquals(['name' => (new Like('foo'))->escape()->startsWith(), 'email' => (new Like('bar'))->escape()->start('%@')], iterator_to_array($this->loader->criteria(new WithLikeCriteria('foo', 'bar'))));
        $this->assertEquals(['bar' => null], iterator_to_array($this->loader->criteria(new NullableCriteria())));
    }
}
