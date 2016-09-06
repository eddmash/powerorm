<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 3:33 PM.
 */
namespace powerorm\migrations\operations\field;

use powerorm\helpers\Bools;
use powerorm\migrations\operations\Operation;
use powerorm\migrations\ProjectState;
use powerorm\NOT_PROVIDED;

/**
 * Class AlterField.
 *
 * @since 1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AlterField extends Operation
{
    public $model_name;
    private $field_name;
    private $field;
    public $preserve_default;

    public function __construct($opts = [])
    {
        parent::__construct($opts);

        $this->model_name = $opts['model'];
        $this->field = $opts['field'];
        $this->field_name = $opts['name'];
        $this->preserve_default = (array_key_exists('preserve_default', $opts) && $opts['preserve_default']) ? true : false;
    }

    public function update_database($connection, $current_state, $desired_state)
    {
        $this->_alter_field($connection, $current_state, $desired_state);
    }

    public function rollback_database($connection, $current_state, $desired_state)
    {
        $this->_alter_field($connection, $current_state, $desired_state);
    }

    public function _alter_field($connection, $current_state, $desired_state)
    {
        $desired_model = $desired_state->registry()->get_model($this->model_name);
        $current_model = $current_state->registry()->get_model($this->model_name);
        $current_field = $current_model->meta->get_field($this->field_name);
        $desired_field = $desired_model->meta->get_field($this->field_name);

        // if we are not preserving the default
        if (Bools::false($this->preserve_default)):
            $desired_field->default = $this->field->default;
        endif;

        if ($this->allow_migrate_model($connection, $desired_model)):
            $connection->schema_editor->alter_model_field($current_model, $current_field, $desired_field);
        endif;


        // reset it back
        if (Bools::false($this->preserve_default)):
            $desired_field->default = NOT_PROVIDED::instance();
        endif;
    }

    public function describe()
    {
        return sprintf('altered_%s', $this->field_name);
    }

    public function update_state(ProjectState $state)
    {
        if (false === $this->preserve_default):
            $field = $this->field;
        $field->default = new NOT_PROVIDED(); else:
            $field = $this->field;
        endif;

        $state->models[$this->model_name]->fields[$this->field_name] = $field;
    }

    public function constructor_args()
    {
        $args = parent::constructor_args();

        foreach ($args as &$arg) :

            if (array_key_exists('preserve_default', $arg) && $arg['preserve_default'] === true):
                unset($arg['preserve_default']);
        endif;
        endforeach;



        return $args;
    }
}
