<?php

namespace Bdf\Prime\Schema\Visitor;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Visitor\AbstractVisitor;

/**
 * Create a Graphviz output of a Schema.
 *
 * @package Bdf\Prime\Schema\Visitor
 */
class Graphviz extends AbstractVisitor
{
    /**
     * @var string
     */
    protected $output = '';

    /**
     * {@inheritdoc}
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        $this->output .= $this->createNodeRelation(
            $fkConstraint->getLocalTableName().':col'.current($fkConstraint->getLocalColumns()).':se',
            $fkConstraint->getForeignTableName().':col'.current($fkConstraint->getForeignColumns()).':se',
            [
                'dir'       => 'back',
                'arrowtail' => 'dot',
                'arrowhead' => 'normal',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSchema(Schema $schema)
    {
        $this->output  = 'digraph "' . sha1((string) mt_rand()) . '" {' . "\n";
        $this->output .= 'graph [fontname="helvetica", fontsize=12];' . "\n";
        $this->output .= 'node [fontname="helvetica", fontsize=12];' . "\n";
        $this->output .= 'edge [fontname="helvetica", fontsize=12];' . "\n";
//        $this->output .= 'splines = true;' . "\n";
//        $this->output .= 'overlap = false;' . "\n";
//        $this->output .= 'outputorder=edgesfirst;'."\n";
//        $this->output .= 'mindist = 0.6;' . "\n";
//        $this->output .= 'sep = .2;' . "\n";
    }

    /**
     * {@inheritdoc}
     */
    public function acceptTable(Table $table)
    {
        $this->output .= $this->createNode(
            $table->getName(),
            array(
                'label' => $this->createTableLabel($table),
                'shape' => 'plaintext',
            )
        );
    }

    /**
     * @param \Doctrine\DBAL\Schema\Table $table
     *
     * @return string
     */
    protected function createTableLabel(Table $table)
    {
        // The title
        $label = '<tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">'
            . $table->getName()
            . '</td></tr>';

        // The attributes block
        foreach ($table->getColumns() as $column) {
            $columnName = $column->getName();

            if ($table->hasPrimaryKey() && in_array($column->getName(), $table->getPrimaryKey()->getColumns())) {
                $columnName = '<b>'.$columnName.'</b>';
            }

            $label .=
            '<tr>'
                . '<td border="0" align="left">'
                    . $columnName
                . '</td>'
                . '<td border="0" align="left">'
                    . '<font point-size="10">'.lcfirst($column->getType()->getName()).'</font>'
                . '</td>'
            . '</tr>';
        }

        return '<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec">'.$label.'</table>>';
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return string
     */
    protected function createNode($name, $options)
    {
        $node = '';

        foreach ($options as $key => $value) {
            $node .= $key.'='.$value.' ';
        }

        return $name.' ['.$node."]\n";
    }

    /**
     * @param string $node1
     * @param string $node2
     * @param array  $options
     *
     * @return string
     */
    protected function createNodeRelation($node1, $node2, $options)
    {
        return $this->createNode($node1.' -> '.$node2, $options);
    }

    /**
     * Get Graphviz Output
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output."}";
    }

    /**
     * Writes dot language output to a file. This should usually be a *.dot file.
     *
     * You have to convert the output into a viewable format. For example use "neato" on linux systems
     * and execute:
     *
     *  neato -Tpng -o er.png er.dot
     *
     * @param string $filename
     *
     * @return void
     */
    public function write($filename)
    {
        file_put_contents($filename, $this->getOutput());
    }
}
