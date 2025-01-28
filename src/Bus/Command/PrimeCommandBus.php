<?php

namespace Bdf\Prime\Bus\Command;

use Bdf\Prime\ServiceLocator;
use Throwable;

/**
 * Default implementation of the command bus
 *
 * It will try to resolve the handler using the command class name.
 * If no handler is found, it will use the {@see DefaultPrimeCommandHandler} to execute the command.
 *
 * Handlers takes as parameters:
 * - The prime service locator
 * - The command object
 * - The transaction manager, used to start the transaction. The transaction will be committed or rolled back by the bus.
 */
final class PrimeCommandBus implements PrimeCommandBusInterface
{
    public function __construct(
        private readonly ServiceLocator $prime,

        /**
         * Map of command class name to handler
         *
         * @var array<class-string, callable(ServiceLocator, object, TransactionManager):void>
         * @psalm-var class-string-map<T, callable(ServiceLocator, T, TransactionManager):void>
         */
        private array $handlers = [],
    ) {}

    /**
     * {@inheritdoc}
     */
    public function execute(object ...$commands): void
    {
        $transactions = new TransactionManager();

        try {
            foreach ($commands as $command) {
                $commandClass = $command::class;
                $handler = $this->handlers[$commandClass] ??= new DefaultPrimeCommandHandler($commandClass);

                /** @psalm-suppress InvalidArgument */
                $handler($this->prime, $command, $transactions);
            }

            $transactions->commit();
        } catch (Throwable $e) {
            $transactions->rollback();
            throw $e;
        }
    }
}
