<?php

namespace Query\Expression;

use Bdf\Prime\Faction;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Aggregate;
use Bdf\Prime\Test\TestPack;
use PHPUnit\Framework\TestCase;

/**
 * Class AggregateTest
 */
class AggregateTest extends TestCase
{
    use PrimeTestCase;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->primeStart();
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();

        parent::tearDown();
    }

    /**
     * @param TestPack $pack
     */
    public function declareTestData(TestPack $pack)
    {
        $pack->declareEntity(Faction::class)
            ->persist([
                new Faction([
                    'id' => 3,
                    'name' => 'foo',
                ]),
                new Faction([
                    'id' => 7,
                    'name' => 'bar',
                ]),
                new Faction([
                    'id' => 8,
                    'name' => 'oof',
                ]),
            ])
        ;
    }

    /**
     *
     */
    public function test_min()
    {
        $query = Faction::select(['a' => Aggregate::min('id')]);

        $this->assertEquals('SELECT MIN(t0.id_) as a FROM faction_ t0 WHERE t0.enabled_ = ?', $query->toSql());
        $this->assertEquals(3, $query->execute()->current()['a']);
    }

    /**
     *
     */
    public function test_max()
    {
        $query = Faction::select(['a' => Aggregate::max('id')]);

        $this->assertEquals('SELECT MAX(t0.id_) as a FROM faction_ t0 WHERE t0.enabled_ = ?', $query->toSql());
        $this->assertEquals(8, $query->execute()->current()['a']);
    }

    /**
     *
     */
    public function test_avg()
    {
        $query = Faction::select(['a' => Aggregate::avg('id')]);

        $this->assertEquals('SELECT AVG(t0.id_) as a FROM faction_ t0 WHERE t0.enabled_ = ?', $query->toSql());
        $this->assertEquals(6, $query->execute()->current()['a']);
    }

    /**
     *
     */
    public function test_count()
    {
        $query = Faction::select(['a' => Aggregate::count('id')]);

        $this->assertEquals('SELECT COUNT(t0.id_) as a FROM faction_ t0 WHERE t0.enabled_ = ?', $query->toSql());
        $this->assertEquals(3, $query->execute()->current()['a']);

        $query = Faction::select(['a' => Aggregate::count('id')]);
        $query->useQuoteIdentifier(true);
        $this->assertEquals('SELECT COUNT("t0"."id_") as "a" FROM "faction_" "t0" WHERE "t0"."enabled_" = ?', $query->toSql());
    }

    /**
     *
     */
    public function test_count_wildcard()
    {
        $query = Faction::select(['a' => Aggregate::count()]);
        $query->useQuoteIdentifier(true);

        $this->assertEquals('SELECT COUNT(*) as "a" FROM "faction_" "t0" WHERE "t0"."enabled_" = ?', $query->toSql());
        $this->assertEquals(3, $query->execute()->current()['a']);
    }

    /**
     *
     */
    public function test_sum()
    {
        $query = Faction::select(['a' => Aggregate::sum('id')]);

        $this->assertEquals('SELECT SUM(t0.id_) as a FROM faction_ t0 WHERE t0.enabled_ = ?', $query->toSql());
        $this->assertEquals(18, $query->execute()->current()['a']);
    }
}
