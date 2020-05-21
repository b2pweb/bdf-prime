<?php

namespace Bdf\Prime;

use Bdf\Prime\Connection\ConnectionConfig;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ConfigurationTest extends TestCase
{
    /**
     * 
     */
    public function test_default_values()
    {
        $configuration = new Configuration();
        
        $this->assertNull($configuration->getResultCache());
        $this->assertNull($configuration->getMetadataCache());
        
        $this->assertEquals(new ConnectionConfig(), $configuration->getDbConfig());
    }
    
    /**
     * @todo
     */
    public function test_set_parameters_from_constructor()
    {
        $configuration = new Configuration($values = [
            'resultCache'       => 'cache object',
            'metadataCache'     => 'cache object',
            'dbConfig'          => ['foo' => 'bar'],
            'environment'       => 'prod',
        ]);
        
        $this->assertEquals('cache object', $configuration->getResultCache());
        $this->assertEquals('cache object', $configuration->getMetadataCache());
        $this->assertEquals(new ConnectionConfig(['foo' => 'bar']), $configuration->getDbConfig());
    }
    
    /**
     * 
     */
    public function test_set_get_result_cache()
    {
        $configuration = new Configuration();
        $configuration->setResultCache('cache object');
        
        $this->assertEquals('cache object', $configuration->getResultCache());
    }
    
    /**
     * 
     */
    public function test_set_get_metadata_cache()
    {
        $configuration = new Configuration();
        $configuration->setMetadataCache('cache object');
        
        $this->assertEquals('cache object', $configuration->getMetadataCache());
    }
    
    /**
     * 
     */
    public function test_set_get_object_config()
    {
        $config = new ConnectionConfig();
        
        $configuration = new Configuration();
        $configuration->setDbConfig($config);
        
        $this->assertSame($config, $configuration->getDbConfig());
    }
    
    /**
     * 
     */
    public function test_set_get_array_config()
    {
        $config = new ConnectionConfig($values = [
            'foo' => 'bar'
        ]);
        
        $configuration = new Configuration();
        $configuration->setDbConfig($values);
        
        $this->assertEquals($config, $configuration->getDbConfig());
    }
}
