<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration\Operation\Model;

use Eddmash\PowerOrm\Db\SchemaEditor;
use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ProjectState;
use Eddmash\PowerOrm\Model\Field\ManyToManyField;

/**
 * Renames a model's table.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AlterModelTable extends Operation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $table;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return sprintf('Rename table for %s to %s', $this->name, $this->table);
    }

    /**
     * {@inheritdoc}
     */
    public function updateState($state)
    {
        $state->modelStates[$this->name]->meta['dbTable'] = $this->table;
    }

    /**
     * {@inheritdoc}
     */
    public function databaseForwards($schemaEditor, $fromState, $toState)
    {
        $this->_alterModelTable($schemaEditor, $fromState, $toState);
    }

    /**
     * {@inheritdoc}
     */
    public function databaseBackwards($schemaEditor, $fromState, $toState)
    {
        $this->_alterModelTable($schemaEditor, $fromState, $toState);
    }

    /**
     * Does the actual alteration of the model table.
     *
     * @param SchemaEditor $schemaEditor
     * @param ProjectState $fromState
     * @param ProjectState $toState
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function _alterModelTable($schemaEditor, $fromState, $toState)
    {
        $toModel = $toState->getRegistry()->getModel($this->name);

        if ($this->allowMigrateModel($schemaEditor->connection, $toModel)):
            $fromModel = $fromState->getRegistry()->getModel($this->name);
            $schemaEditor->alterDbTable($toModel, $fromModel->meta->dbTable, $toModel->meta->dbTable);

            // Rename M2M fields whose name is based on this model's db_table

            /** @var $newField ManyToManyField */
            /* @var $oldField ManyToManyField */
            foreach ($toModel->meta->localManyToMany as $newName => $newField) :
                foreach ($fromModel->meta->localManyToMany as $oldName => $oldField) :
                    if ($newName === $oldName):
                        $schemaEditor->alterDbTable(
                            $newField->relation->through,
                            $oldField->relation->through->meta->dbTable,
                            $newField->relation->through->meta->dbTable
                        );
                    endif;
                endforeach;
            endforeach;

        endif;
    }

}
