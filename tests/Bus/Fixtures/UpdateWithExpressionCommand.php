<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\Command\SetValue;
use Bdf\Prime\Bus\Command\WriteQuery\PrimeUpdateCommand;
use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\Query\Expression\Attribute;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Bdf\Prime\User;

use function implode;

#[PrimeUpdateCommand(User::class)]
class UpdateWithExpressionCommand
{
    public function __construct(
        #[Criterion('customer.id')]
        public readonly int $customerId,

        #[SetValue('roles', transformer: new PushRoleTransformer())]
        public readonly array $newRoles,
    ) {}
}

class PushRoleTransformer
{
    public function __invoke(array $roles): ExpressionInterface
    {
        return new Attribute('roles', '%s || "' . implode(',', $roles) . ',"');
    }
}
