<?php
/**
 * Created by http://eddmash.com.
 * User: eddmash
 * Date: 4/17/16
 * Time: 10:30 AM.
 */
namespace powerorm\migrations\operations\model;

use powerorm\migrations\ModelState;
use powerorm\migrations\operations\Operation;
use powerorm\migrations\ProjectState;

/**
 * Class CreateModel.
 *
 * @since 1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class CreateModel extends Operation
{
    public $model_name;
    public $fields;
    public $depends_on;
    public $options;

    public function __construct($opts = [])
    {
        parent::__construct($opts);
        $this->model_name = $opts['model'];
        $this->fields = $opts['fields'];
    }

    public function update_database($connection, $current_state, $desired_state)
    {
        $model = $desired_state->registry()->get_model($this->model_name);

        if ($this->allow_migrate_model($connection, $model)):

            $connection->schema_editor->create_model($model);
        endif;
    }

    public function rollback_database($connection, $current_state, $desired_state)
    {
        $model = $current_state->registry()->get_model($this->model_name);

        if ($this->allow_migrate_model($connection, $model)):
            $connection->schema_editor->drop_model($model);
        endif;
    }

    public function describe()
    {
        return sprintf('add_%s', $this->model_name);
    }

    public function update_state(ProjectState $state)
    {
        $model_state = new ModelState($this->model_name, $this->fields);
        $state->add_model($model_state);
    }
}
