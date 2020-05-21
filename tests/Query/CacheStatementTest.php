<?php

namespace Bdf\Prime\Query;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class CacheStatementTest extends TestCase
{
    protected $statement;
    protected $data = [
        ['id' => 1, 'name' => 'item1'], 
        ['id' => 2, 'name' => 'item2'], 
    ];
    
    /**
     * 
     */
    protected function setUp(): void
    {
        $this->statement = new CacheStatement($this->data);
    }
    
    /**
     * 
     */
    public function test_count()
    {
        $this->assertEquals(2, $this->statement->columnCount());
    }
    
    /**
     * 
     */
    public function test_close()
    {
        $this->statement->closeCursor();
        
        $this->assertEquals(0, $this->statement->columnCount());
    }
    
    /**
     * 
     */
    public function test_fetch_assoc()
    {
        $this->statement->setFetchMode(PDO::FETCH_ASSOC);
        
        $this->assertEquals($this->data[0], $this->statement->fetch());
    }
    
    /**
     * 
     */
    public function test_fetch_all()
    {
        $this->statement->setFetchMode(PDO::FETCH_ASSOC);
        
        $this->assertEquals($this->data, $this->statement->fetchAll());
    }
    
    /**
     * 
     */
    public function test_fetch_all_column()
    {
        $this->assertEquals([1, 2], $this->statement->fetchAll(PDO::FETCH_COLUMN));
    }
    
    /**
     * 
     */
    public function test_fetch_column()
    {
        $this->assertEquals('item1', $this->statement->fetchColumn(1));
    }
    
    /**
     * 
     */
    public function test_fetch_unknown_column()
    {
        $this->assertEquals(null, $this->statement->fetchColumn(1000));
    }
}