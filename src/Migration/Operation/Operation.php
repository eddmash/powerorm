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

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\DeconstructableObject;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Migration\State\ProjectState;

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

    public function __construct($params) {
        BaseOrm::configure($this, $params);
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

    public function deconstruct() {
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
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    abstract public function updateState($state);
}
