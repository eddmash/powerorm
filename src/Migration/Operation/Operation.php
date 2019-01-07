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

use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\DeconstructableObject;
use Eddmash\PowerOrm\Exception\ComponentException;
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

    /**
     * @var bool true if this operation can be reduced into an sql statement
     */
    private $reducibleToSql = true;

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
        $opsModelName = "Eddmash\PowerOrm\Migration\Operation\Model";
        $opsFieldName = "Eddmash\PowerOrm\Migration\Operation\Field";
        if (StringHelper::startsWith($this->getFullClassName(), $opsModelName)) {
            $alias = 'ModelOps';
            $path = sprintf('%s as %s', $opsModelName, $alias);
        }

        if (StringHelper::startsWith($this->getFullClassName(), $opsFieldName)) {
            $alias = 'FieldOps';
            $path = sprintf('%s as %s', $opsFieldName, $alias);
        }

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
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function updateState(ProjectState $state)
    {
        throw new NotImplemented();
    }

    /**
     * Return either a list of operations the actual operation should be
     * replaced with or a boolean that indicates whether or not the specified
     * operation can be optimized across.
     *
     * @param Operation $operation
     * @param Operation[] $inBetween
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function reduce(Operation $operation, $inBetween)
    {
        return false;
    }

    /**
     * Returns True if there is a chance this operation references the given
     * model name (as a string).
     *
     * Used for optimization. If in doubt, return True;
     * returning a false positive will merely make the optimizer a little less
     * efficient, while returning a false
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
     * @param Model $model
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function allowMigrateModel(ConnectionInterface $connection, $model)
    {
        return $model->getMeta()->canMigrate();
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

    /**
     * @return \Eddmash\PowerOrm\Components\AppInterface|null
     */
    public function getApp()
    {
        try {
            $app = BaseOrm::getInstance()->getComponent($this->getAppLabel());

            /* @var $app \Eddmash\PowerOrm\Components\AppInterface */
            return $app;
        } catch (ComponentException $e) {
            return null;
        }
    }

    public function __debugInfo()
    {
        return [
            'appLabel' => $this->appLabel,
        ];
    }

    /**
     * @return bool
     */
    public function isReducibleToSql()
    {
        return $this->reducibleToSql;
    }
}
