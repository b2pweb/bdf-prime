<?php

namespace Bdf\Prime\Schema\Constraint;

/**
 * Visitor interface for constraints.
 * Constraints are not homogeneous types, visitors should be used
 */
interface ConstraintVisitorInterface
{
    /**
     * Handle a foreign key constraint
     *
     * @param ForeignKeyInterface $foreignKey
     *
     * @return void
     */
    public function onForeignKey(ForeignKeyInterface $foreignKey);

    /**
     * Handle a check constraint
     *
     * @param CheckInterface $check
     *
     * @return void
     */
    public function onCheck(CheckInterface $check);
}
