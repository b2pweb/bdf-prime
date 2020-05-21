<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ExpressionTest extends TestCase
{
    use PrimeTestCase;

    protected $repository;
    protected $query;
    
    protected function setUp(): void
    {
        $this->primeStart();
        
        $this->repository = Prime::repository('Bdf\Prime\TestEntity');
        $this->table = $this->repository->mapper()->metadata()->table();
        $this->query = $this->repository->builder();
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
    public function test_expression_in_where_part()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0 WHERE t0.date_insert = CURRENT_DATE",
            $this->query
            ->where([
                'dateInsert' => new Expression\Now(),
            ])
            ->toSql()
        );
    }
    
    /**
     * 
     */
    public function test_expression_in_field_part()
    {
        $this->assertEquals(
            "SELECT t0.date_insert, CURRENT_DATE FROM $this->table t0",
            $this->query->select(['dateInsert', new Expression\Now()])->toSql()
        );
    }
    
    /**
     * 
     */
    public function test_simple_attribute_expression()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0 WHERE t0.name = t0.date_insert",
            $this->query
            ->where([
                'name' => new Expression\Attribute('dateInsert'),
            ])
            ->toSql()
        );
    }
    
    /**
     * 
     */
    public function test_simple_attribute_with_pattern()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0 WHERE t0.date_insert = t0.date_insert + 1",
            $this->query
            ->where([
                'dateInsert' => new Expression\Attribute('dateInsert', '%s + 1'),
            ])
            ->toSql()
        );
    }
    
    /**
     * 
     */
    public function test_attribute_expression()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0 INNER JOIN foreign_ t1 ON t1.pk_id = t0.foreign_key WHERE t0.name = t1.name_",
            $this->query
            ->where([
                'name' => new Expression\Attribute('foreign.name'),
            ])
            ->toSql()
        );
    }
    
    /**
     * 
     */
    public function test_field_expression()
    {
        $this->assertEquals(
            "SELECT FIELD(t0.name,1,2) FROM $this->table t0",
            $this->query->select([new Expression\Field('name', [1,2])])
                ->toSql()
        );
    }
    
    /**
     * 
     */
    public function test_match_expression()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0 WHERE MATCH(t0.name AGAINST('test'))",
            
            $this->query
            ->where([new Expression\Match('name', 'test')])
            ->toSql()
        );
    }
    
    /**
     * 
     */
    public function test_match_expression_in_boolean_mode()
    {
        $this->assertEquals(
            "SELECT t0.* FROM $this->table t0 WHERE MATCH(t0.name AGAINST('test') IN BOOLEAN MODE)",
            
            $this->query
            ->where([new Expression\Match('name', 'test', true)])
            ->toSql()
        );
    }
}