<?php
/**
 * Created by http://eddmash.com.
 * User: eddmash
 * Date: 4/24/16
 * Time: 9:46 AM
 */

namespace powerorm\migrations\operations;

use powerorm\migrations\ProjectState;
use powerorm\NOT_PROVIDED;

/**
 * Class AddField
 * @package powerorm\migrations\operations
 *
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AddField extends Operation
{
    public $model_name;
    public $field_name;
    public $field;
    public $preserve_default;

    public function __construct($opts = []){ 

        parent::__construct($opts);
        
        $this->model_name = $opts['model'];
        $this->field = $opts['field'];
        $this->field_name = $opts['name'];

        $this->preserve_default = (array_key_exists('preserve_default', $opts) && $opts['preserve_default']) ?  TRUE: FALSE;

    }

    public function update_database($connection, $current_state, $desired_state)
    {
        $model = $desired_state->registry()->get_model($this->model_name);

        $field = $model->meta->get_field($this->field_name);

        $current_model = $current_state->registry()->get_model($this->model_name);

        // if we are not preserving the default
        if(!$this->preserve_default):
            $field->default = $this->field->default;
        endif;

        if($this->allow_migrate_model($connection, $model)):
            $connection->add_model_field($current_model, $field);
        endif;
    }

    public function rollback_database($connection, $current_state, $desired_state)
    {
        $current_model = $current_state->registry()->get_model($this->model_name);

        if($this->allow_migrate_model($connection, $current_model)):
            $connection->drop_model_field($current_model, $current_model->meta->get_field($this->field_name));
        endif;
    }

    public function describe()
    {
        return sprintf('%1$s_add_%2$s', $this->model_name, $this->field_name);
    }

    public function constructor_args()
    {
        $args = parent::constructor_args();

        foreach ($args as &$arg) :

            if(array_key_exists('preserve_default', $arg) && $arg['preserve_default']===TRUE):
                unset($arg['preserve_default']);
            endif;
        endforeach;



        return $args;
    }

    public function update_state(ProjectState $state)
    {
       $state->get_model($this->model_name)->fields[$this->field_name] = $this->field;

    }
}

/**
 * Class DropField
 * @package powerorm\migrations\operations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class DropField extends Operation{

    public $model_name;
    private $field_name;

    public function __construct($opts = []){

        parent::__construct($opts);
        $this->model_name = $opts['model'];
        $this->field_name = $opts['name'];

    }

    public function update_database($connection, $current_state, $desired_state)
    {
        $current_model = $current_state->registry()->get_model($this->model_name);
        if($this->allow_migrate_model($connection, $current_model)):
            $connection->drop_model_field($current_model, $current_model->meta->get_field($this->field_name));
        endif;
    }

    public function rollback_database($connection, $current_state, $desired_state)
    {
        $desired_model = $desired_state->registry()->get_model($this->model_name);

        $current_model = $current_state->registry()->get_model($this->model_name);

        if($this->allow_migrate_model($connection, $current_model)):
            $connection->add_model_field($current_model, $desired_model->meta->get_field($this->field_name));
        endif;
    }

    public function describe()
    {
        return sprintf('%1$s_drop_%2$s', $this->model_name, $this->field_name);
    }

    public function update_state(ProjectState $state)
    {
        $new_model_fields = [];
        $model_state = $state->get_model($this->model_name);

        foreach ($model_state->fields as $field_name => $field_object) :
            if($field_name !== $this->field_name):
                continue;
            endif;

            $new_model_fields[$field_name] = $field_object;
        endforeach;

        $state->get_model($this->model_name)->fields = $new_model_fields;
    }
}

/**
 * Class AlterField
 * @package powerorm\migrations\operations
 *
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AlterField extends Operation{
    public $model_name;
    private $field_name;
    private $field;
    public $preserve_default;

    public function __construct($opts = []){

        parent::__construct($opts);

        $this->model_name = $opts['model'];
        $this->field = $opts['field'];
        $this->field_name = $opts['name'];
        $this->preserve_default = (array_key_exists('preserve_default', $opts) && $opts['preserve_default']) ?  TRUE: FALSE;


    }

    public function update_database($connection, $current_state, $desired_state)
    {
        $this->_alter_field($connection, $current_state, $desired_state);
    }

    public function rollback_database($connection, $current_state, $desired_state)
    {
        $this->_alter_field($connection, $current_state, $desired_state);
    }

    public function _alter_field($connection, $current_state, $desired_state){
        $desired_model = $desired_state->registry()->get_model($this->model_name);
        $current_model = $current_state->registry()->get_model($this->model_name);
        $current_field = $current_model->meta->get_field($this->field_name);
        $desired_field = $desired_model->meta->get_field($this->field_name);

        // if we are not preserving the default
        if(!$this->preserve_default):
            $desired_field->default = $this->field->default;
        endif;

        if($this->allow_migrate_model($connection, $desired_model)):
            $connection->alter_model_field($current_model, $current_field, $desired_field);
        endif;
    }

    public function describe()
    {
        return sprintf('altered_%s', $this->field_name);
    }

    public function update_state(ProjectState $state)
    {
        if(FALSE === $this->preserve_default):
            $field = $this->field ;
            $field->default = new NOT_PROVIDED;
        else:
            $field = $this->field;
        endif;

        $state->models[$this->model_name]->fields[$this->field_name] = $field ;
    }

    public function constructor_args()
    {
        $args = parent::constructor_args();

        foreach ($args as &$arg) :

            if(array_key_exists('preserve_default', $arg) && $arg['preserve_default']===TRUE):
                unset($arg['preserve_default']);
            endif;
        endforeach;



        return $args;
    }
}

/**
 * Class RenameField
 * @package powerorm\migrations\operations
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RenameField extends Operation{

    public $model_name;
    public $old_namedb;
    public $new_name;

    public function __construct($opts = []){

        parent::__construct($opts);

        $this->model_name = $opts['model'];
        $this->old_name = $opts['old_name'];
        $this->new_name = $opts['new_name'];

    }

    public function update_state(ProjectState $state)
    {
        $old_fields = $state->models[$this->model_name]->fields;

        $new_fields = [];
        foreach ($old_fields as $name => $field) :
            if($this->lower_case($name) === $this->lower_case($this->old_name)):
                $new_fields[$this->new_name] = $field;
                continue;
            endif;
            $new_fields[$name] = $field;
        endforeach;

        $state->models[$this->model_name]->fields = $new_fields;
    }

    public function update_database($connection, $current_state, $desired_state)
    {
        $desired_model = $desired_state->registry()->get_model($this->model_name);

        if ($this->allow_migrate_model($connection, $desired_model)):
            $current_model = $current_state->registry()->get_model($this->model_name);

            $current_field = $current_model->meta->get_field($this->old_name);
            $desired_field = $desired_model->meta->get_field($this->new_name);

            $connection->alter_model_field($desired_model, $current_field, $desired_field);

        endif;
    }

    public function rollback_database($connection, $current_state, $desired_state)
    {
        $desired_model = $desired_state->registry()->get_model($this->model_name);

        if ($this->allow_migrate_model($connection, $desired_model)):
            $current_model = $current_state->registry()->get_model($this->model_name);

            $connection->alter_model_field(
                $desired_model,
                $current_model->meta->get_field($this->new_name),
                $desired_model->meta->get_field($this->old_name)
            );

        endif;
    }

    public function describe()
    {
        return sprintf('field_%s_rename', $this->old_name);
    }
}