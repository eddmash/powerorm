<?php

namespace powerorm\registry;

use powerorm\BaseOrm;
use powerorm\exceptions\LookupError;
use powerorm\model\BaseModel;
use powerorm\Object;
use powerorm\traits\BaseFileReader;

/**
 * This is the applications register.
 *
 * That is it hold all the models that are available on the application at the current time.
 *
 * Class App
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class App extends Object
{
    use BaseFileReader;

    public $all_models = [];

    /**
     * Models that extend the PModel, but extend the CI_Model.
     *
     * @var array
     */
    protected $all_non_orm_models = [];

    /**
     * Returns a list of all model files.
     *
     * @return array
     */
    public function get_model_files()
    {
        return $this->get_directory_files(BaseOrm::get_models_path());
    }

    /**
     * Returns the list of all the models that extend the PModel in the current app.
     *
     * @return array
     */
    public function get_models()
    {
        $this->_get_models();

        return $this->all_models;
    }

    public function get_non_orm_models()
    {
        $this->_get_models();

        return $this->all_non_orm_models;
    }

    public function _get_models()
    {
        $model_classes = $this->get_model_classes();

        if ($model_classes):
            foreach ($model_classes as $model_name) :
                $reflect = new \ReflectionClass(ucfirst($model_name));

                // if we cannot create an instance of a class just skip, e.g traits abstrat etc
                if (!$reflect->isInstantiable()):
                    continue;
        endif;

        $model = $this->_load_model($model_name);
        if ($model instanceof BaseModel):
                    $this->all_models[$this->lower_case($model_name)] = $model;
        endif;

        if ($model instanceof \CI_Model && !$model instanceof BaseModel):
                    $this->all_non_orm_models[$this->lower_case($model_name)] = $model;
        endif;

        endforeach;
        endif;
    }

    public function get_model($name)
    {
        $name = $this->lower_case($name);
        if (!$this->has_model($name)):
            throw new LookupError(sprintf('The model { %s } Does not exist', $name));
        endif;

        return $this->get_models()[$name];
    }

    public function has_model($name)
    {
        return array_key_exists($name, $this->get_models());
    }

    /**
     * Load the model instance.
     *
     * @param $model_name
     *
     * @return mixed
     */
    public function _load_model($model_name)
    {
        $_ci = $this->ci_instance();

        if (!isset($_ci->{$model_name})):
            $_ci->load->model($model_name);
        endif;

        return isset($_ci->{$model_name}) ? $_ci->{$model_name} : '';
    }

    /**
     * Returns a list of all model names in lowercase or false if not models were found.
     *
     * @return array
     */
    public function get_model_classes()
    {
        $models = [];

        $model_files = $this->get_model_files();

        if (empty($model_files)):
            return false;
        endif;

        foreach ($this->get_model_files() as $file) :

            $models[] = $this->get_model_name($file);
        endforeach;

        return $models;
    }

    /**
     * Gets a model name from its model file name.
     *
     * @param $file
     *
     * @return string
     */
    public function get_model_name($file)
    {
        return strtolower(trim(basename($file, '.php')));
    }

    public function register_model(BaseModel $model)
    {
        $name = $model->meta->model_name;
        if (!array_key_exists($name, $this->all_models)):
            $this->all_models[$this->lower_case($name)] = $model;
        endif;
    }

    public function unregister_model($model_name)
    {
        $model_name = $this->lower_case($model_name);
        unset($this->all_models[$model_name]);
    }
}
