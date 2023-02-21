<?php

namespace Bdf\Prime\Connection;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\Compilable;
use Doctrine\DBAL\Exception as DoctrineDBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;

use function is_bool;
use function is_int;

/**
 * Utility class for binding values to a statement
 */
final class Binder
{
    /**
     * Bind values to a statement
     * The parameter type is guessed from the value instead of using STRING
     *
     * @param Statement $statement Statement to bind
     * @param Compilable $query Query which contains the bindings
     *
     * @return Statement Same as the first parameter
     *
     * @throws DoctrineDBALException
     * @throws PrimeException
     */
    public static function bindValues(Statement $statement, Compilable $query): Statement
    {
        $bindings = $query->getBindings();
        $bindIndex = 1;

        foreach ($bindings as $key => $value) {
            switch (true) {
                case is_int($value):
                    $type = ParameterType::INTEGER;
                    break;

                case is_bool($value):
                    $type = ParameterType::BOOLEAN;
                    break;

                case $value === null:
                    $type = ParameterType::NULL;
                    break;

                default:
                    $type = ParameterType::STRING;
            }

            $statement->bindValue(
                is_int($key) ? $bindIndex++ : $key,
                $value,
                $type
            );
        }

        return $statement;
    }

    /**
     * Extract the parameter types from the values
     *
     * Keep original keys.
     * Follows the same rules as bindValues()
     *
     * @param mixed[] $bindings
     * @return array<ParameterType::*>
     */
    public static function types(array $bindings): array
    {
        $types = [];

        foreach ($bindings as $key => $value) {
            switch (true) {
                case is_int($value):
                    $types[$key] = ParameterType::INTEGER;
                    break;

                case is_bool($value):
                    $types[$key] = ParameterType::BOOLEAN;
                    break;

                case $value === null:
                    $types[$key] = ParameterType::NULL;
                    break;

                default:
                    $types[$key] = ParameterType::STRING;
            }
        }

        return $types;
    }
}
