<?php
/**
 * Created by http://eddmash.com.
 * User: eddmash
 * Date: 4/24/16
 * Time: 9:46 AM
 */

namespace powerorm\migrations\operations\field;

use powerorm\helpers\Bools;
use powerorm\migrations\operations\Operation;
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
        // give it the value temporary

        if(Bools::false($this->preserve_default)):
            $field->default = $this->field->default;
        endif;

        if($this->allow_migrate_model($connection, $model)):
            $connection->schema_editor->add_model_field($current_model, $field);
        endif;

        // reset it back
        if(Bools::false($this->preserve_default)):
            $field->default = NOT_PROVIDED::instance();
        endif;
    }

    public function rollback_database($connection, $current_state, $desired_state)
    {
        $current_model = $current_state->registry()->get_model($this->model_name);

        if($this->allow_migrate_model($connection, $current_model)):
            $connection->schema_editor->drop_model_field($current_model, $current_model->meta->get_field($this->field_name));
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

