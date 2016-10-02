<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\App;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Exception\AppRegistryNotReady;
use Eddmash\PowerOrm\Exception\LookupError;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Object;

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
    private $allModels = [];
    private $_pendingOps = [];

    protected $modelsReady;
    public $ready;

    public function __construct()
    {
        // Whether the registry is populated.
        $this->ready = false;
    }

    public static function createObject($config = []) {
        return new static();
    }

    public function populate()
    {
        if ($this->ready == false) :
            $this->_populateRegistry();
            $this->ready = true;
        endif;

        return;
    }

    public function isAppReady()
    {
        if (!$this->ready) {
            return new AppRegistryNotReady('Registry has not been loaded yet.');
        }
    }

    /**
     * Models that extend the PModel, but extend the CI_Model.
     *
     * @var array
     */
    protected $allNonOrmModels = [];

    /**
     * Returns a list of all model files.
     *
     * @return array
     */
    public function getModelFiles()
    {
        $fileHandler = new FileHandler(BaseOrm::getModelsPath());

        return $fileHandler->readDir('php');
    }

    /**
     * Returns the list of all the models that extend the PModel in the current app.
     *
     * @return array
     */
    public function getModels()
    {
        $this->_populateRegistry();

        return $this->allModels;
    }

    /**
     * Loads all the models in the current application.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _populateRegistry()
    {
        if($this->ready):
            return;
        endif;

        $modelClasses = $this->getModelClasses();

        if (!empty($modelClasses)) :
            foreach ($modelClasses as $modelName) :
                $reflect = new \ReflectionClass(ucfirst($modelName));

                // if we cannot create an instance of a class just skip, e.g traits abstrat etc
                if (!$reflect->isInstantiable()) :
                    continue;
                endif;

                if ($this->hasModel($this->normalizeKey($modelName))):
                    continue;
                endif;
                new $modelName();
            endforeach;
        endif;
    }

    /**
     * @param $name
     *
     * @return Model
     *
     * @throws LookupError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getModel($name)
    {
        $this->isAppReady();

        if (!$this->hasModel($name)) {
            throw new LookupError(sprintf('The model { %s } Does not exist', $name));
        }

        return $this->allModels[$name];
    }

    public function hasModel($name)
    {
        return array_key_exists($name, $this->allModels);
    }

    /**
     * Returns a list of all model names in lowercase or false if not models were found.
     *
     * @return array
     */
    public function getModelClasses()
    {
        $models = [];

        $modelFiles = $this->getModelFiles();

        if (empty($modelFiles)) {
            return false;
        }

        foreach ($this->getModelFiles() as $file) {
            $models[] = $this->getModelName($file);
        }

        return $models;
    }

    /**
     * Gets a model name from its model file name.
     *
     * @param $file
     *
     * @return string
     */
    public function getModelName($file)
    {
        return strtolower(trim(basename($file, '.php')));
    }

    public function registerModel(Model $model)
    {
        $name = $model->meta->modelName;
        if (!array_key_exists($name, $this->allModels)) {
            $this->allModels[$name] = $model;
        }
        $this->resolvePendingOps($model);
    }

    /**
     * @param callback $callback   the callback to invoke when a model has been created
     * @param array    $modelNames the model we are waiting for to be created, the model object is passed to
     *                             the callback as the first argument
     * @param array    $kwargs     an associative array to be passed to the callback
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function lazyModelOps($callback, $modelNames, $kwargs)
    {

        // get the first
        $modelName = $modelNames[0];

        // recurse the others
        if (isset($modelNames[1]) && !empty(array_slice($modelNames, 1))) {
            $this->lazyModelOps($callback, array_slice($modelNames, 1), $kwargs);
        }

        try {
            $model = $this->getModel($modelName);
            $kwargs['related'] = $model;
            $callback($kwargs);
        } catch (LookupError $err) {
            $this->_pendingOps[$modelName][] = [$callback, $kwargs];
        }
    }

    /**
     * @param Model $model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function resolvePendingOps($model)
    {
        if (isset($this->_pendingOps[$model->meta->modelName])) {
            $todoActions = $this->_pendingOps[$model->meta->modelName];
            foreach ($todoActions as $todoAction) {
                list($callback, $kwargs) = $todoAction;
                $kwargs['related'] = $model;
                $callback($kwargs);
            }
        }
    }

    public function __toString()
    {
        return sprintf('%s Object', $this->getFullClassName());
    }
}
