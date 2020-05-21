<?php

namespace Bdf\Prime\DataCollector;

use Bdf\Prime\ServiceLocator;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Logging\LoggerChain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * PrimeDataCollector
 */
class PrimeDataCollector extends DataCollector
{
    /**
     * @var ServiceLocator 
     */
    protected $prime;
    
    /**
     * @var DebugStack 
     */
    protected $debugStack;
    
    /**
     * PrimeDataCollector constructor
     *
     * @param ServiceLocator $prime
     */
    public function __construct(ServiceLocator $prime)
    {
        $this->prime = $prime;
        $this->debugStack = new DebugStack();
        
        $chain = new LoggerChain();
        
        if (($current = $this->prime->config()->getSQLLogger()) !== null) {
            $chain->addLogger($current);
        }
        
        $chain->addLogger($this->debugStack);
        
        $this->prime->config()->setSQLLogger($chain);

        $this->reset();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'prime';
    }
    
    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null)
//    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [
            'connections'  => $this->prime->connections()->connectionNames(),
            'repositories' => $this->prime->repositoryNames(),
            'queries'      => $this->debugStack->queries,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [
            'connections'  => [],
            'repositories' => [],
            'queries'      => [],
        ];

        $this->debugStack->queries = [];
    }

    /**
     * Get connection names
     *
     * @return string[]
     */
    public function getConnections()
    {
        return $this->data['connections'];
    }
    
    /**
     * Get repository names
     *
     * @return string[]
     */
    public function getRepositories()
    {
        return $this->data['repositories'];
    }
    
    /**
     * Get queries infos
     *
     * @return array
     */
    public function getQueries()
    {
        return $this->data['queries'];
    }
    
    /**
     * Get the sum of query time
     *
     * @return int
     */
    public function getTime()
    {
        $time = 0;
        
        foreach ($this->data['queries'] as $query) {
            $time += $query['executionMS'];
        }
        
        return $time;
    }
}