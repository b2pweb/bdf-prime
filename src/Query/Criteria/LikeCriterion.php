<?php

namespace Bdf\Prime\Query\Criteria;

use Attribute;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Bdf\Prime\Query\Expression\Like;
use Override;

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
    /*readonly*/ private bool $startsWith;

    /**
     * If true, the LIKE expression will match for the end of the value (suffix)
     * Will generate a LIKE '%value'
     */
    /*readonly*/ private bool $endsWith;

    /**
     * If true, the LIKE expression will match any string that contains the value
     * Will generate a LIKE '%value%'
     */
    /*readonly*/ private bool $contains;

    /**
     * Whether the value should be escaped, so the input value will not be interpreted as a wildcard
     */
    /*readonly*/ private bool $escape;

    /**
     * Define custom prefix for the LIKE expression
     */
    /*readonly*/ private ?string $start;

    /**
     * Define custom suffix for the LIKE expression
     */
    /*readonly*/ private ?string $end;

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
    public function __construct($field = null, bool $startsWith = false, bool $endsWith = false, bool $contains = false, bool $escape = true, ?string $start = null, ?string $end = null, bool $skipNull = true, bool $skipEmpty = false)
    {
        parent::__construct($field, null, $skipNull, $skipEmpty);

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
    public function value($value): Like
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

    #[Override]
    public function shouldSkipValue(mixed $value): bool
    {
        if (parent::shouldSkipValue($value)) {
            return true;
        }

        // Like with empty array is not supported, so it must be skipped
        return $value === [];
    }
}
