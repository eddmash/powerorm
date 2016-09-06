<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 3:33 PM.
 */
namespace eddmash\powerorm\migrations\operations\field;

use eddmash\powerorm\migrations\operations\Operation;
use eddmash\powerorm\migrations\ProjectState;

/**
 * Class RenameField.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RenameField extends Operation
{
    public $model_name;
    public $old_namedb;
    public $new_name;

    public function __construct($opts = [])
    {
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
            if ($this->standard_name($name) === $this->standard_name($this->old_name)):
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

            $connection->schema_editor->alter_model_field($desired_model, $current_field, $desired_field);

        endif;
    }

    public function rollback_database($connection, $current_state, $desired_state)
    {
        $desired_model = $desired_state->registry()->get_model($this->model_name);

        if ($this->allow_migrate_model($connection, $desired_model)):
            $current_model = $current_state->registry()->get_model($this->model_name);

            $connection->schema_editor->alter_model_field(
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
