<?php

namespace eddmash\powerorm\app;

use eddmash\powerorm\BaseOrm;
use eddmash\powerorm\exceptions\AppRegistryNotReady;
use eddmash\powerorm\exceptions\LookupError;
use eddmash\powerorm\helpers\FileHandler;
use eddmash\powerorm\helpers\Tools;
use eddmash\powerorm\model\BaseModel;
use eddmash\powerorm\Object;
use Orm;

/**
 * This is the applications register.
 *
 * That is it hold all the models and any other information about an application.
 *
 * Class Registry
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Registry extends Object
{
    public $all_models = [];
    private $_pending_ops = [];

    protected $models_ready;
    protected $ready;

    public function __construct()
    {
        // Whether the registry is populated.
        $this->ready = false;
    }

    public function populate()
    {
        if ($this->ready === true):
            return;
        endif;

        $this->get_models();
        $this->ready = true;
    }

    public function is_app_ready()
    {
        if (!$this->ready):
            return new AppRegistryNotReady('Registry has not been loaded yet.');
        endif;
    }

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
        $fileHandler = new FileHandler(BaseOrm::get_models_path());

        return $fileHandler->read_dir('php');
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

    /**
     * Loads all the models in the current application.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function _get_models()
    {
        $model_classes = $this->get_model_classes();

        if (!empty($model_classes)):
            foreach ($model_classes as $model_name) :

                $reflect = new \ReflectionClass(ucfirst($model_name));

                // if we cannot create an instance of a class just skip, e.g traits abstrat etc
                if (!$reflect->isInstantiable()):
                    continue;
                endif;

                $model = $this->_load_model($model_name);

                if ($model instanceof BaseModel):
                    $this->all_models[$this->standard_name($model_name)] = $model;
                endif;

                if ($model instanceof \CI_Model && !$model instanceof BaseModel):
                    $this->all_non_orm_models[$this->standard_name($model_name)] = $model;
                endif;

            endforeach;
        endif;
    }

    /**
     * @param $name
     *
     * @return BaseModel
     *
     * @throws LookupError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function get_model($name)
    {
        $this->is_app_ready();

        $name = $this->standard_name($name);

        if (!$this->has_model($name)):
            throw new LookupError(sprintf('The model { %s } Does not exist', $name));
        endif;

        return $this->all_models[$name];
    }

    public function has_model($name)
    {
        return array_key_exists($name, $this->all_models);
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
        $_ci = Orm::ci_instance();

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
            $this->all_models[$name] = $model;
        endif;
        $this->resolve_pending_ops($model);
    }

    /**
     * @param callback $callback    the callback to invoke when a model has been created
     * @param array    $model_names the model we are waiting for to be created, the model object is passed to
     *                              the callback as the first argument
     * @param array    $kwargs      an associative array to be passed to the callback
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function lazy_model_ops($callback, $model_names, $kwargs)
    {

        // get the first
        $model_name = $this->standard_name($model_names[0]);

        // recurse the others
        if (isset($model_names[1]) && !empty(array_slice($model_names, 1))):
            $this->lazy_model_ops($callback, array_slice($model_names, 1), $kwargs);
        endif;

        try {
            $model = $this->get_model($model_name);
            Tools::invoke_callback($callback, $model, $kwargs);
        } catch (LookupError $err) {
            $this->_pending_ops[$model_name][] = $callback;
        }
    }

    /**
     * @param BaseModel $model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function resolve_pending_ops($model)
    {
        if (isset($this->_pending_ops[$model->meta->model_name])):

            $callbacks = $this->_pending_ops[$model->meta->model_name];
            foreach ($callbacks as $callback) :
                Tools::invoke_callback($callback, $model);
            endforeach;
        endif;
    }
}
