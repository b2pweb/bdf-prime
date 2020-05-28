<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

/**
 * Generates accessors to the type registry
 */
class TypeAccessor
{
    /**
     * @var CodeGenerator
     */
    private $code;

    /**
     * @var string
     */
    private $registryVariableName;

    /**
     * @var string[]
     */
    private $declaredTypes = [];

    /**
     * TypeAccessor constructor.
     *
     * @param CodeGenerator $code
     * @param string $registryVariableName
     */
    public function __construct(CodeGenerator $code, string $registryVariableName = '$types')
    {
        $this->code = $code;
        $this->registryVariableName = $registryVariableName;
    }

    /**
     * Declare a type
     *
     * @param string $type
     */
    public function declare(string $type): void
    {
        if (isset($this->declaredTypes[$type])) {
            return;
        }

        $baseName = $type;

        if (substr($baseName, -2) === '[]') {
            $baseName = 'arrayOf'.substr($baseName, 0, -2);
        }

        $i = 0;

        do {
            $varName = '$type'.$this->getValidVariableName($baseName).($i ? $i : '');
            ++$i;
        } while (in_array($varName, $this->declaredTypes));

        $this->declaredTypes[$type] = $varName;
    }

    /**
     * Generates the types declaration
     *
     * @return string
     */
    public function generateDeclaration(): string
    {
        $out = '';

        foreach ($this->declaredTypes as $type => $varName) {
            $out .= $varName.' = '.$this->registryVariableName.'->get(\''.$type.'\');'.$this->code->eol();
        }

        return $out;
    }

    public function generateFromDatabase(string $type, string $rawData, string $options): string
    {
        $this->declare($type);

        return $this->declaredTypes[$type].'->fromDatabase('.$rawData.($options ? ', '.$options : '').');';
    }

    /**
     * Get a valid variable name
     *
     * @param string $typeName
     *
     * @return string
     */
    private function getValidVariableName(string $typeName)
    {
        return str_replace([' ', '\\', '-', '.', ':', '/', '[', ']'], '', $typeName);
    }
}
