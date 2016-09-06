<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 3:32 PM.
 */
namespace powerorm\migrations\operations\field;

use powerorm\migrations\operations\Operation;
use powerorm\migrations\ProjectState;

/**
 * Class DropField.
 *
 * @since 1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class DropField extends Operation
{
    public $model_name;
    private $field_name;

    public function __construct($opts = [])
    {
        parent::__construct($opts);
        $this->model_name = $opts['model'];
        $this->field_name = $opts['name'];
    }

    public function update_database($connection, $current_state, $desired_state)
    {
        $current_model = $current_state->registry()->get_model($this->model_name);
        if ($this->allow_migrate_model($connection, $current_model)):
            $connection->schema_editor->drop_model_field($current_model, $current_model->meta->get_field($this->field_name));
        endif;
    }

    public function rollback_database($connection, $current_state, $desired_state)
    {
        $desired_model = $desired_state->registry()->get_model($this->model_name);

        $current_model = $current_state->registry()->get_model($this->model_name);

        if ($this->allow_migrate_model($connection, $current_model)):
            $connection->schema_editor->add_model_field($current_model, $desired_model->meta->get_field($this->field_name));
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
            if ($field_name !== $this->field_name):
                continue;
        endif;

        $new_model_fields[$field_name] = $field_object;
        endforeach;

        $state->get_model($this->model_name)->fields = $new_model_fields;
    }
}
