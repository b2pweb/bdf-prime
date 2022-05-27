<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

/**
 * Helper class for generate simple PHP codes
 *
 * @todo Handle use and simplify FQN
 */
class CodeGenerator
{
    /**
     * @var string
     */
    private $eol = "\n";

    /**
     * @var string
     */
    private $tab = '    ';

    /**
     * @var int
     */
    private $tmpVarCount = 0;


    /**
     * Generate the namespace statement, if the namespace is not empty
     *
     * @param string $namespace
     *
     * @return string
     */
    public function namespace($namespace)
    {
        if (empty($namespace)) {
            return '';
        }

        return 'namespace ' . $namespace . ';';
    }

    /**
     * Generate list of class properties
     *
     * @param array $names The properties names
     * @param string $visibility The visibility (public, private, protected)
     *
     * @return string
     */
    public function properties(array $names, $visibility = 'private')
    {
        $out = '';

        foreach ($names as $property) {
            $out .= $visibility.' $' . $property . ';'.$this->eol;
        }

        return $out;
    }

    /**
     * Generate a simple constructor with property assignation
     *
     * @param string[] $properties Properties to assign
     *
     * @return string
     */
    public function simpleConstructor($properties)
    {
        $parameters = implode(', ', array_map(function ($p) {
            return '$'.$p;
        }, $properties));
        $content = '';

        foreach ($properties as $property) {
            if (!empty($content)) {
                $content .= $this->eol;
            }

            $content .= '$this->' . $property . ' = $' . $property . ';';
        }


        return <<<CTR
public function __construct({$parameters})
{
{$this->indent($content, 1)}
}
CTR;
    }

    /**
     * Indent a part of code
     *
     * @param string $code
     * @param int $tabs
     *
     * @return string
     */
    public function indent($code, $tabs)
    {
        $spaces = str_repeat($this->tab, $tabs);

        return $spaces . str_replace($this->eol, $this->eol.$spaces, $code);
    }

    /**
     * Get the End Of Line character
     *
     * @return string
     */
    public function eol()
    {
        return $this->eol;
    }

    /**
     * Concatenate code lines, with adding the end of line
     *
     * @param string[] $lines
     *
     * @return string
     */
    public function lines(array $lines)
    {
        return implode($this->eol, $lines);
    }

    /**
     * Create a else if switch by instance of cases
     *
     * @param string $varName The object var name
     * @param array $cases The cases, with the class name as key, and the code as value
     *
     * @return string
     */
    public function switchIntanceOf($varName, array $cases)
    {
        $out = [];

        foreach ($cases as $className => $code) {
            $out[] = <<<PHP
if ({$varName} instanceof {$className}) {
{$this->indent($code, 1)}
}
PHP;
        }

        return implode(' else', $out);
    }

    /**
     * Create a switch
     *
     * @param string $varName The var name to switch on
     * @param array $cases The cases, with the case value as key, and the code as value
     * @param string|null $default The default code to execute (or null for not adding default case)
     *
     * @return string
     */
    public function switch($varName, array $cases, $default = null)
    {
        $code = 'switch ('.$varName.') {'.$this->eol;

        foreach ($cases as $attribute => $case) {
            $code .= $this->indent('case '.$this->export($attribute).':', 1).$this->eol;
            $code .= $this->indent($case, 2).$this->eol;

            if (
                strpos($case, 'return') === false
                && strpos($case, 'break') === false
            ) {
                $code .= $this->indent('break;', 2).$this->eol;
            }
        }

        if ($default !== null) {
            $code .= $this->indent('default:', 1).$this->eol;
            $code .= $this->indent($default, 2).$this->eol;
        }

        $code .= '}';

        return $code;
    }

    /**
     * Generate a new unique temporary variable name
     *
     * @return string
     */
    public function tmpVar()
    {
        return '$__tmp_'.$this->tmpVarCount++;
    }

    /**
     * Normalize a class name
     *
     * @param string $name
     *
     * @return string
     */
    public function className($name)
    {
        return '\\'.ltrim($name, '\\');
    }

    /**
     * Export PHP value to PHP code
     *
     * @param mixed $value
     *
     * @return string
     *
     * @see var_export()
     */
    public function export($value)
    {
        if ($value === null) {
            return 'null';
        }

        // Indexed array
        if (is_array($value) && array_values($value) === $value) {
            return '['.implode(', ', array_map([$this, 'export'], $value)).']';
        }

        return var_export($value, true);
    }

    /**
     * Generate PHP file from a stub template
     *
     * @param string $template The stub template file name
     * @param array $placeholders The placeholders
     *
     * @return string
     */
    public function generate($template, array $placeholders)
    {
        $file = file_get_contents($template);

        foreach ($placeholders as $name => $code) {
            // The placeholder is indented
            if (preg_match('#^([ \t]+)<'.$name.'>$#m', $file, $matches)) {
                $code = str_replace($this->eol, $this->eol.$matches[1], $code);
            }

            $file = str_replace('<'.$name.'>', $code, $file);
        }

        return $file;
    }
}
