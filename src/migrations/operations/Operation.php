<?php

namespace eddmash\powerorm\migrations\operations;

use eddmash\powerorm\DeConstructable;
use eddmash\powerorm\helpers\Strings;
use eddmash\powerorm\helpers\Tools;
use eddmash\powerorm\migrations\ProjectState;
use eddmash\powerorm\model\BaseModel;
use eddmash\powerorm\Object;

/**
 * Class Operation
 * @package eddmash\powerorm\migrations\operations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Operation extends Object implements DeConstructable
{
    private $constructor_args;

    public function __construct($opts = [])
    {
        $this->constructor_args = func_get_args();
    }

    /**
     * @param $connection
     * @param ProjectState $current_state the state of the project before the operation is applied
     * @param ProjectState $desired_state the state of the project after operation is applied
     *
     */
    abstract public function update_database($connection, $current_state, $desired_state);

    abstract public function rollback_database($connection, $current_state, $desired_state);

    abstract public function describe();

    /**
     * Updates the state based on what this operation needs done.
     * @param ProjectState $state
     * @return mixed
     */
    abstract public function update_state(ProjectState $state);

    /**
     * @inheritdoc
     * @return array
     */
    public function constructor_args()
    {
        return $this->constructor_args;
    }

    public function skeleton()
    {
        $path = '';
        $alias = '';

        if (Strings::starts_with($this->full_class_name(), 'eddmash\powerorm\migrations\operations\model')):
            $alias = 'model_operations';
            $path = sprintf('eddmash\powerorm\migrations\operations\model as %s', $alias);
        endif;

        if (Strings::starts_with($this->full_class_name(), 'eddmash\powerorm\migrations\operations\field')):
            $alias = 'field_operations';
            $path = sprintf('eddmash\powerorm\migrations\operations\field as %s', $alias);
        endif;

        return [
            'name' => sprintf('%1$s\%2$s', $alias, $this->get_class_name()),
            'path' => $path,
            'full_name' => $this->full_class_name(),
            'constructor_args' => $this->constructor_args(),
        ];
    }

    /**
     * @param $connection
     * @param BaseModel $model
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function allow_migrate_model($connection, BaseModel $model)
    {
        return $model->meta->can_migrate();
    }

    public function __toString()
    {
        return PHP_EOL . Tools::stringify($this);
//        return sprintf(' <%1$s(%2$s) >'.PHP_EOL,
//            $this->get_class_name(),
//            Tools::stringify($this->constructor_args()[0], FALSE));
    }
}
