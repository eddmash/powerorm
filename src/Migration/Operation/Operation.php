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

use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Db\SchemaEditor;
use Eddmash\PowerOrm\DeconstructableObject;
use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Migration\State\ProjectState;
use Eddmash\PowerOrm\Model\Model;

abstract class Operation extends DeconstructableObject implements OperationInterface
{
    /**
     * keeps track of which operation should be run before this one.
     *
     * @ignore
     *
     * @var array
     */
    private $_dependency;
    private $appLabel;

    public function __construct($params)
    {
        ClassHelper::setAttributes($this, $params);
    }

    /**
     * @ignore
     *
     * @param mixed $dependency
     */
    public function setDependency($dependency)
    {
        $this->_dependency = $dependency;
    }

    /**
     * @ignore
     *
     * @return array
     */
    public function getDependency()
    {
        return $this->_dependency;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return '';
    }

    public function deconstruct()
    {
        $path = '';
        $alias = '';

        if (StringHelper::startsWith($this->getFullClassName(), 'Eddmash\PowerOrm\Migration\Operation\Model')):
            $alias = 'modelOperation';
            $path = sprintf('Eddmash\PowerOrm\Migration\Operation\Model as %s', $alias);
        endif;

        if (StringHelper::startsWith($this->getFullClassName(), 'Eddmash\PowerOrm\Migration\Operation\Field')):
            $alias = 'fieldOperation';
            $path = sprintf('Eddmash\PowerOrm\Migration\Operation\Field as %s', $alias);
        endif;

        return [
            'name' => sprintf('%1$s\%2$s', $alias, $this->getShortClassName()),
            'path' => $path,
            'fullName' => $this->getFullClassName(),
            'constructorArgs' => $this->getConstructorArgs(),
        ];
    }

    /**
     * Migration use this method to contribute to the current state of the project.
     *
     * @param ProjectState $state
     *
     * @return mixed
     *
     * @throws NotImplemented
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function updateState($state)
    {
        throw new NotImplemented();
    }

    /**
     * @param SchemaEditor $schemaEditor
     * @param ProjectState $fromState
     * @param ProjectState $toState
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function databaseForwards($schemaEditor, $fromState, $toState)
    {
        return;
    }

    /**
     * @param SchemaEditor $schemaEditor
     * @param ProjectState $fromState
     * @param ProjectState $toState
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function databaseBackwards($schemaEditor, $fromState, $toState)
    {
        return;
    }

    /**
     * Return either a list of operations the actual operation should be
     * replaced with or a boolean that indicates whether or not the specified
     * operation can be optimized across.
     *
     * @param Operation   $operation
     * @param Operation[] $inBetween
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function reduce($operation, $inBetween)
    {
        return false;
    }

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
    public function referencesModel($modelName)
    {
        return true;
    }

    /**
     * Returns if we're allowed to migrate the model.
     *
     * it preemptively rejects any proxy, unmanaged model.
     *
     * @param ConnectionInterface $connection
     * @param Model               $model
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function allowMigrateModel(ConnectionInterface $connection, $model)
    {
        return $model->meta->canMigrate();
    }

    public function getAppLabel()
    {
        return $this->appLabel;
    }

    /**
     * @param mixed $appLabel
     */
    public function setAppLabel($appLabel)
    {
        $this->appLabel = $appLabel;
    }


    public function __debugInfo()
    {
        return [
            'appLabel' => $this->appLabel,
        ];
    }
}
