<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:10 PM
 */

namespace eddmash\powerorm\model\field;

use eddmash\powerorm\checks\Checks;
use eddmash\powerorm\model\field\accessor\ForwardManyToManyAccessor;
use eddmash\powerorm\model\field\relation\ManyToManyObject;
use Orm;

/**
 * Class ManyToMany
 */
class ManyToManyField extends RelatedField
{
    public $M2M = true;

    /**
     * {@inheritdoc}
     */
    public function __construct($field_options = [])
    {
        parent::__construct($field_options);


        $this->relation = new ManyToManyObject([
            'model' => $field_options['model'],
            'through' => array_key_exists('through', $field_options) ? $field_options['through'] : null,
            'field' => $this
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        $checks = [];
        $checks = parent::check();
        $checks = $this->add_check($checks, $this->_unique_check());
        $checks = $this->add_check($checks, $this->_ignored_options());
        return $checks;
    }

    /**
     * @ignore
     * @return mixed
     */
    public function _unique_check()
    {
        if ($this->unique):
            return [
                Checks::error([
                    "message" => sprintf('%s field cannot be unique', $this->get_class_name()),
                    "hint" => null,
                    "context" => $this,
                    "id" => "fields.E330"
                ])
            ];
        endif;

        return [];
    }

    public function __through_model_exists_check()
    {
        $models = Orm::get_registry()->get_models();
        $model_names = array_keys($models);

        if ($this->through !== null && !in_array($this->standard_name($this->through), $model_names)):
            return [
                Checks::error([
                    "message" => sprintf('Field specifies a many-to-many relation through model %s,
                    which does not exist.', ucfirst($this->through)),
                    "hint" => null,
                    "context" => $this,
                    "id" => "fields.E331"
                ])
            ];
        endif;

        return [];
    }

    /**
     * @ignore
     * @return mixed
     */
    public function _ignored_options()
    {
        if ($this->null):
            return [
                Checks::warning([
                    "message" => sprintf('`null` has no effect on %s', $this->get_class_name()),
                    "hint" => null,
                    "context" => $this,
                    "id" => "fields.W340"
                ])
            ];
        endif;
        return [];
    }

    public function db_type($connection)
    {
        return null;
    }

    public function contribute_to_class($field_name, $model_obj)
    {
        parent::contribute_to_class($field_name, $model_obj);

        if (empty($this->relation->through)):
            $this->relation->through = $this->create_intermidiate_model($this, $model_obj);
        endif;

        if (is_string($this->relation->through)):
            $this->relation->through = $this->set_through_model($this->relation->through);
        endif;


        $this->container_model->{$field_name} = ForwardManyToManyAccessor::instance($this->container_model, $this);
    }

    public function set_through_model($model_name)
    {
        return Orm::get_registry()->get_model($model_name);
    }

    public function create_intermidiate_model($field, $owner_model)
    {
        $owner_model_name = $owner_model->meta->model_name;
        $inverse_model_name = $field->relation->model;

        $owner_model_name = $this->standard_name($owner_model_name);
        $inverse_model_name = $this->standard_name($inverse_model_name);

        $class_name = sprintf('%1$s_%2$s', ucfirst($owner_model_name), ucfirst($field->name));

        $intermediary_class = 'use eddmash\powerorm\model\BaseModel;
        class %1$s extends BaseModel{
            public function fields(){}
        }';
        $intermediary_class = sprintf($intermediary_class, $class_name);

        if (!class_exists($class_name, false)):
            eval($intermediary_class);
        endif;

        $class_name = "\\" . $class_name;
        $fields = [
            $owner_model_name => new ManyToOneField(['model' => $owner_model_name]),
            $inverse_model_name => new ManyToOneField(['model' => $inverse_model_name])
        ];

        $intermediary_obj = call_user_func_array(sprintf('%s::instance', $class_name), [null, $fields]);
        $intermediary_obj->meta->auto_created = true;
        return $intermediary_obj;
    }
}
