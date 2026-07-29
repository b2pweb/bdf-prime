<?php

namespace Bdf\Prime\Query\Criteria;

use Attribute;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Bdf\Prime\Query\Expression\Like;

/**
 * Define the property as a LIKE filter
 *
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class LikeCriterion extends Criterion
{
    /**
     * If true, the LIKE expression will match for the start of the value (prefix)
     * Will generate a LIKE 'value%'
     */
    private bool $startsWith;

    /**
     * If true, the LIKE expression will match for the end of the value (suffix)
     * Will generate a LIKE '%value'
     */
    private bool $endsWith;

    /**
     * If true, the LIKE expression will match any string that contains the value
     * Will generate a LIKE '%value%'
     */
    private bool $contains;

    /**
     * Whether the value should be escaped, so the input value will not be interpreted as a wildcard
     */
    private bool $escape;

    /**
     * Define custom prefix for the LIKE expression
     */
    private ?string $start;

    /**
     * Define custom suffix for the LIKE expression
     */
    private ?string $end;

    /**
     * @param string|ExpressionInterface|null $field
     * @param bool $startsWith
     * @param bool $endsWith
     * @param bool $contains
     * @param bool $escape
     * @param string|null $start
     * @param string|null $end
     * @param bool $skipNull
     */
    public function __construct(string|ExpressionInterface|null $field = null, bool $startsWith = false, bool $endsWith = false, bool $contains = false, bool $escape = true, ?string $start = null, ?string $end = null, bool $skipNull = true)
    {
        parent::__construct($field, null, $skipNull);

        $this->startsWith = $startsWith;
        $this->endsWith = $endsWith;
        $this->contains = $contains;
        $this->escape = $escape;
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress ImpureMethodCall
     */
    public function value(mixed $value): Like
    {
        $value = new Like(parent::value($value));

        if ($this->startsWith) {
            $value->startsWith();
        }

        if ($this->endsWith) {
            $value->endsWith();
        }

        if ($this->contains) {
            $value->contains();
        }

        if ($this->escape) {
            $value->escape();
        }

        if ($this->start !== null) {
            $value->start($this->start);
        }

        if ($this->end !== null) {
            $value->end($this->end);
        }

        return $value;
    }
}
