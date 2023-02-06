<?php

namespace App\Entities;

use Bdf\Prime\Entity\Criteria;

class TestCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function name($value): self
    {
        $this->add('name', $value);
        return $this;
    }

    public function nameLike(string $search): self
    {
        $this->add('nameLike', $search);
        return $this;
    }

    /**
     * A custom criteria method
     *
     * @param string $foo
     * @return $this
     */
    public function myCustomFilter(string $foo): self
    {
        $this->add('id', $foo);
        $this->add('name', $foo);

        return $this;
    }
}
