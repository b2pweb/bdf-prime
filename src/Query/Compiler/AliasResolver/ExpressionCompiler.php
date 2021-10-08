<?php

namespace Bdf\Prime\Query\Compiler\AliasResolver;

use Bdf\Prime\Exception\QueryBuildingException;

/**
 * Compiler for relations and attributes expressions
 *
 * Syntax :
 * =========
 * $NAME  => ($CHR | _ | #)+
 * $ATTR  => >$NAME
 * $ALIAS => $$NAME
 * $DYN   => $NAME(.$NAME)*
 * $STA   => "$DYN"
 * $EXPR =>
 *    ($ALIAS | $STA)($DYN | $ATTR)
 *    $DYN($ATTR)?
 *    $ATTR
 *
 * Definitions :
 * ==============
 * $NAME :
 *      The part name.
 *      Can be relation or attribute.
 *
 * $ATTR :
 *      Force specify that is an attribute.
 *      Should be the last part of the expression.
 *      Should not be analyzed.
 *
 * $ALIAS :
 *      A registered alias.
 *      Should be the first part of the expression.
 *      The alias represents a (possibly) deep relation path.
 *      The alias should not be analyzed, only check the metadata table.
 *      Cannot represents an attribute, so an alias should be followed by one of $DYN or $ATTR.
 *
 * $DYN :
 *      The dynamic expression.
 *      Always analyzed, can be attribute, or relation path.
 *      This expression is sufficient for most of request.
 *      This is the only expression that can be preceded AND flowed by tokens
 *
 * $STA :
 *      The "static" expression.
 *      A static expression is ALWAYS related to an alias.
 *      So, one $STA is related to one AND ONLY one $ALIAS.
 *      Also has save constrains than $ALIAS (should be the first token, cannot respresents an attribute...).
 *
 * $EXPR :
 *      The complete expression.
 *      Should represents the complete relations path plus the attribute.
 *      This is a database value.
 *
 * Example :
 * ==========
 *
 * All those examples do the same
 *
 * user.location.address.name :
 *      $DYN = user location address name
 *      Will :
 *          - resolve user, and set as t1
 *          - resolve user.location and set as t2
 *          - Find that address.name is an attribute of user.location
 *      Conclusion :
 *          The simple way to do that. But resolve too many times.
 *          So for multiple queries on same attribute path, do useless resolutions
 *
 * "user.location"address.name :
 *      $STA = user.location
 *      $ATTR = address name
 *      Will :
 *          - Check in the path table user.location
 *              - If exists, get the metadata
 *              - If not, resolve as $DYN, and store the path
 *          - Find that address.name is an attribute of user.location
 *      Conclusion :
 *          If many filters applies on user.location, the metadata and path are already loaded
 *          So do not add overhead for useless resolutions
 *          But use the $DYN algo for find the attribute, that can decreases performances
 *
 * "user.location">address.name :
 *      $STA = user.location
 *      $ATTR = address.name
 *      Will :
 *          - Check user.location in the path table
 *          - use address.name as attribute
 *      Conclusion :
 *          The best usage. Remove overhead for resolve relations path AND for resolving attribute name
 *
 * $t2>address.name
 *      $ALIAS = t2
 *      $ATTR = address.name
 *      Will :
 *          - Get t2 alias
 *          - use address.name as attribute
 *      Conclusion :
 *          Same as "user.location">address.name, but the user should care about the alias.
 *          The best for internal usages
 *
 * $t1.location>address.name :
 *      $ALIAS = t1
 *      $DYN = location
 *      $ATTR = address.name
 *      Will :
 *          - Get the t1 alias
 *          - Resolve location into t1 (via $DYN)
 *          - use address.name as attribute
 *      Conclusion ;
 *          The worst way, but can be usefull internally if (and only if)
 *              - The context is an alias
 *              - Cannot ensure that the "right part" is an attribute
 *              - The real attribute is known
 */
class ExpressionCompiler
{
    const DYN_SEPARATOR    = '.';
    const ATTR_IDENTIFIER  = '>';
    const STA_IDENTIFIER   = '"';
    const ALIAS_IDENTIFIER = '$';

    const RESERVED = [
        self::DYN_SEPARATOR    => true,
        self::ATTR_IDENTIFIER  => true,
        self::STA_IDENTIFIER   => true,
        self::ALIAS_IDENTIFIER => true,
    ];

    /**
     * @var static
     */
    static private $instance;

    /**
     * Compile the expression to expression tokens
     *
     * @param string $expression
     *
     * @return ExpressionToken[]
     */
    public function compile($expression)
    {
        $len = strlen($expression);
        $pos = 0;
        $tokens = [];

        while ($pos < $len) {
            switch ($expression[$pos]) {
                case self::ALIAS_IDENTIFIER:
                    $tokens[] = $this->compileAlias($expression, $pos, $len);
                    break;
                case self::STA_IDENTIFIER:
                    $tokens[] = $this->compileStatic($expression, $pos, $len);
                    break;
                case self::ATTR_IDENTIFIER:
                    $tokens[] = $this->compileAttribute($expression, $pos, $len);
                    break;
                default:
                    $tokens[] = $this->compileDynamic($expression, $pos, $len);
            }
        }

        return $tokens;
    }

    /**
     * Compile $ALIAS.
     *
     * $t1 => new ExpressionToken(TYPE_ALIAS, 't1')
     *
     * @param string $expression
     * @param int $pos
     * @param int $len
     *
     * @return ExpressionToken
     */
    protected function compileAlias($expression, &$pos, $len)
    {
        if ($pos !== 0) {
            throw new QueryBuildingException('Alias should be the first expression token');
        }

        ++$pos;

        return new ExpressionToken(
            ExpressionToken::TYPE_ALIAS,
            $this->compileName($expression, $pos, $len)
        );
    }

    /**
     * Compile $ATTR
     *
     * xxx>my.attribute => new ExpressionToken(TYPE_ATTR, 'my.attribute')
     *
     * @param string $expression
     * @param int $pos
     * @param int $len
     *
     * @return ExpressionToken
     */
    protected function compileAttribute($expression, &$pos, $len)
    {
        $value = substr($expression, $pos + 1);
        $pos = $len;

        return new ExpressionToken(
            ExpressionToken::TYPE_ATTR,
            $value
        );
    }

    /**
     * Compile a $STA
     *
     * "my.static.exp" => new ExpressionToken(TYPE_STA, 'my.static.exp')
     *
     * @param string $expression
     * @param int $pos
     * @param int $len
     *
     * @return ExpressionToken
     */
    protected function compileStatic($expression, &$pos, $len)
    {
        if ($pos !== 0) {
            throw new QueryBuildingException('Static expression should be the first expression token');
        }

        ++$pos;
        $end = strpos($expression, self::STA_IDENTIFIER, $pos);

        $value = substr($expression, $pos, $end - $pos);

        $pos = $end + 1;

        return new ExpressionToken(
            ExpressionToken::TYPE_STA,
            $value
        );
    }

    /**
     * Compile an $DYN
     *
     * my.super.expr => new ExpressionToken(TYPE_DYN, ['my', 'super', 'expr'])
     *
     * @param string $expression
     * @param int $pos
     * @param int $len
     *
     * @return ExpressionToken
     */
    protected function compileDynamic($expression, &$pos, $len)
    {
        if ($expression[$pos] === self::DYN_SEPARATOR) {
            ++$pos;
        }

        $names = [];

        for (;;) {
            $names[] = $this->compileName($expression, $pos, $len);

            if (
                $pos >= $len
                || $expression[$pos] !== self::DYN_SEPARATOR
            ) {
                break;
            }

            ++$pos;
        }

        return new ExpressionToken(
            ExpressionToken::TYPE_DYN,
            $names
        );
    }

    /**
     * Compile a $NAME.
     *
     * The compilation stops when encounter a reserved character, or the end of the expression
     *
     * @param string $expression
     * @param int $pos
     * @param int $len
     *
     * @return string
     */
    protected function compileName($expression, &$pos, $len)
    {
        $name = '';

        for (;$pos < $len; ++$pos) {
            $chr = $expression[$pos];

            if (array_key_exists($chr, self::RESERVED)) {
                break;
            }

            $name .= $chr;
        }

        return $name;
    }

    /**
     * Get the compiler instance
     *
     * @return static
     */
    public static function instance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }
}
