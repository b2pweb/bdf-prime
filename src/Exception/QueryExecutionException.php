<?php

namespace Bdf\Prime\Exception;

use Throwable;

/**
 * Raised by an error during execute a query
 * The exception should contain the query and there parameters
 */
class QueryExecutionException extends DBALException
{
    /**
     * @var mixed
     */
    private $query;

    /**
     * @var array|null
     */
    private ?array $parameters;

    /**
     * @param string $message The error message
     * @param int|mixed $code The exception code
     * @param Throwable|null $previous The base exception
     * @param mixed|null $query The executed query
     * @param array|null $parameters The query parameters
     */
    public function __construct(string $message, $code = 0, ?Throwable $previous = null, $query = null, ?array $parameters = null)
    {
        parent::__construct($message, $code, $previous);

        $this->query = $query;
        $this->parameters = $parameters;
    }

    /**
     * Get the executed query
     *
     * @return mixed
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * Get the query parameters
     *
     * @return array|null
     */
    public function parameters(): ?array
    {
        return $this->parameters;
    }
}
