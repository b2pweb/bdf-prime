<?php

namespace Bdf\Prime\Bus\Command;

use Bdf\Prime\Bus\Query\PrimeQueryBusInterface;

/**
 * Base type for perform prime write commands from a bus
 *
 * This bus follow the CQRS pattern, and is used to execute write commands only.
 * The term "command" here refers to a DTO object that presents a write operation to be executed.
 * Commands will be executed using a command handler, resolved using the DTO class name.
 * A default command handler should be used when no specific handler are not associated with the DTO class.
 *
 * Commands DTOs should not contain any logic, and should be immutable. If you want to add logic to a command execution,
 * define a custom command handler for the command class.
 *
 * @see PrimeQueryBusInterface For perform read operations
 */
interface PrimeCommandBusInterface
{
    /**
     * Execute all write commands into a single transaction
     *
     * If any of the commands fail, the transaction will be rolled back, and no changes will be made to the database,
     * unless the database does not support transactions, or the handler does not use transactions.
     *
     * To execute command, the handler will be resolved from the command class name. If not handler are associated with the command,
     * a default handler will be used.
     *
     * Note: When commands are executed on multiple databases, multiple transactions will be created, so atomicity is not guaranteed.
     *       If a failure occurs during the execution of commands, all transactions will be rolled back.
     *       If a failure occurs during the commit of transactions, previous transactions cannot be rolled back, and the database may be in an inconsistent state.
     *
     * @param object ...$commands Commands to execute.
     */
    public function execute(object ...$commands): void;
}
