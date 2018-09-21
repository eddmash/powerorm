<?php
/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration\Operation;

use Eddmash\PowerOrm\Db\SchemaEditor;
use Eddmash\PowerOrm\Migration\State\ProjectState;

interface OperationInterface
{
    /**
     * Migration use this method to contribute to the current state of the project.
     *
     * @param ProjectState $state
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function updateState(ProjectState $state);

    /**
     * @param SchemaEditor $schemaEditor
     * @param ProjectState $fromState
     * @param ProjectState $toState
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function databaseForwards(SchemaEditor $schemaEditor, ProjectState $fromState, ProjectState $toState);

    /**
     * @param SchemaEditor $schemaEditor
     * @param ProjectState $fromState
     * @param ProjectState $toState
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function databaseBackwards(SchemaEditor $schemaEditor, ProjectState $fromState, ProjectState $toState);

    /**
     * Return either a list of operations the actual operation should be
     * replaced with or a boolean that indicates whether or not the specified
     * operation can be optimized across.
     *
     * @param Operation $operation
     * @param array $inBetween
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function reduce(Operation $operation, $inBetween);

    /**
     * Returns True if there is a chance this operation references the given  model name (as a string).
     *
     * Used for optimization. If in doubt, return True;
     * returning a false positive will merely make the optimizer a little less efficient, while returning a false
     * negative may result in an unusable optimized migration.
     *
     * @param $modelName
     *
     * @return mixed
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function referencesModel($modelName);
}
