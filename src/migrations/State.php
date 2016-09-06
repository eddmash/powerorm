<?php

namespace eddmash\powerorm\migrations;

use eddmash\powerorm\app\Registry;
use eddmash\powerorm\model\BaseModel;
use eddmash\powerorm\Object;

/**
 * {@inheritdoc}
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class StateRegistry extends Registry
{
    public function __construct($model_states = [])
    {
        parent::__construct();
        $this->model_states = $model_states;
        $this->populate();
    }

    public function _get_models()
    {
        if (!empty($this->model_states)):

            foreach ($this->model_states as $name => $state) :
                $state->to_model($this);
            endforeach;

        endif;

        return $this->all_models;
    }

    /**
     * remove the model from the registry.
     *
     * @param string $model_name
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function unregister_model($model_name)
    {
        $model_name = $this->standard_name($model_name);
        unset($this->all_models[$model_name]);
    }
}

/**
 * Represents the state of the project at any particular time.
 *
 * The project state contains the application registry which inturn contains the models etc of an application
 *
 * This state can be passed around for use.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ProjectState extends Object
{
    public $models;
    private $_registry;

    public function __construct($models = [], $options = [])
    {
        $this->models = $models;
    }

    public function add_model(ModelState $model_state)
    {
        $model_name = $model_state->name;
        $this->models[$model_name] = $model_state;
    }

    public function remove_model($model_name)
    {
        $model_name = $this->standard_name($model_name);

        if (isset($this->models[$model_name])):
            // remove model state from the project state
            unset($this->models[$model_name]);

            // remove model from the registy
            $this->registry()->unregister_model($model_name);
        endif;
    }

    public function get_model($name)
    {
        $name = $this->standard_name($name);

        if (!array_key_exists($name, $this->models)):
            return null;
        endif;

        return $this->models[$name];
    }

    /**
     * @param Registry $registry
     *
     * @return ProjectState
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function from_apps($registry)
    {
        $app_models = $registry->get_models();
        $models = [];
        foreach ($app_models as $name => $model) :
            $models[$name] = ModelState::from_model($model);
        endforeach;

        return new self($models);
    }

    /**
     * Create a new registry based on the present models in the state.
     *
     * @return StateRegistry
     */
    public function registry()
    {
        return new StateRegistry($this->models);
    }

    public function frozen_registry()
    {
        if (empty($this->_registry)):
            $this->_registry = new StateRegistry($this->models);
        endif;

        return $this->_registry;
    }

    public function deep_clone()
    {
        $models = [];
        foreach ($this->models as $name => $model) :
            $models[$name] = $model->deep_clone();
        endforeach;

        return new static($models);
    }
}

/**
 * This represents a model in the application.
 * This way we are able to make alterations to the model without affecting the actual models.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ModelState extends Object
{
    public $name;
    public $fields = [];

    public function __construct($name, $fields, $options = [])
    {
        $this->name = $this->standard_name($name);
        $this->fields = $fields;
    }

    /**
     * Creates a model state for the model passed in.
     *
     * @param BaseModel $model
     * @param bool|true $with_relations
     *
     * @return ModelState
     */
    public static function from_model(BaseModel $model, $with_relations = true)
    {
        $fields = [];

        foreach ($model->meta->local_fields as $name => $field) :
            $fields[$name] = $field;
        endforeach;

        if ($with_relations):
            foreach ($model->meta->relations_fields as $name => $field) :

                // ignore the inverse fields
                if ($field->is_inverse()):
                    continue;
                endif;

                $fields[$name] = $field;
            endforeach;
        endif;

        return new self($model->meta->model_name, $fields);
    }

    /**
     * Create a model from the current state.
     *
     * @param Registry $registry
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function to_model(Registry $registry)
    {
        $class_name = $this->_define_load_class($this->name);

        return call_user_func_array(sprintf('%s::instance', $class_name), [$registry, $this->fields]);
    }

    /**
     * @param $class_name
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function _define_load_class($class_name)
    {
        $class_name = ucfirst($class_name);
        // we create a new namespace and define new classes because,
        // we might be dealing with a model that has been dropped
        // Meaning if we try to load the model using the normal codeigniter way,
        // we will get and error of model does not exist
        $class = 'namespace eddmash\powerorm\migrations\_fake_\models;

            class %1$s extends \PModel{

                 public function fields(){}
            }';

        $full_class_name = sprintf('\eddmash\powerorm\migrations\_fake_\models\%s', $class_name);

        if (!class_exists($full_class_name, false)):
            eval(sprintf($class, $class_name));
        endif;

        return $full_class_name;
    }

    public function deep_clone()
    {
        $fields = [];
        foreach ($this->fields as $name => $field) :
            $fields[$name] = $field->deep_clone();
        endforeach;

        return new static($this->name, $fields);
    }
}
