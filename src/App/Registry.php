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

use Eddmash\PowerOrm\BaseObject;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Components\AppInterface;
use Eddmash\PowerOrm\Exception\AppRegistryNotReady;
use Eddmash\PowerOrm\Exception\ClassNotFoundException;
use Eddmash\PowerOrm\Exception\LookupError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Eddmash\PowerOrm\Model\Model;

/**
 * This is the applications register.
 *
 * That is it hold all the models and any other information about an application.
 *
 * Class Registry
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Registry extends BaseObject
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

    public static function createObject($config = [])
    {
        return new static();
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\ClassNotFoundException
     */
    public function populate()
    {
        if (false == $this->ready) :
            $this->hydrateRegistry();
            $this->ready = true;
        endif;

        return;
    }

    /**
     * @throws \Eddmash\PowerOrm\Exception\AppRegistryNotReady
     */
    public function isAppReady()
    {
        if (!$this->ready) {
            throw new AppRegistryNotReady('Registry has not been loaded yet.');
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
        $files = [];

        foreach (BaseOrm::getInstance()->getComponents() as $component) :

            if ($component instanceof AppInterface):
                $fileHandler = new FileHandler($component->getModelsPath());
                $files[$component->getName()] = $fileHandler->readDir('php');
            endif;

        endforeach;

        return $files;
    }

    /**
     * Returns the list of all the models that extend the PModel in the current app.
     *
     * @return array
     *
     * @throws \Eddmash\PowerOrm\Exception\AppRegistryNotReady
     */
    public function getModels($includeAutoCreated = false)
    {
        $this->isAppReady();

        if ($includeAutoCreated):
            return $this->allModels;
        endif;

        $models = [];
        /** @var $model Model */
        foreach ($this->allModels as $name => $model) :
            if ($model->meta->autoCreated):
                continue;
            endif;
            $models[$name] = $model;
        endforeach;

        return $models;
    }

    /**
     * Loads all the models in the current application.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws ClassNotFoundException
     */
    protected function hydrateRegistry()
    {
        if ($this->ready):
            return;
        endif;

        $modelClasses = $this->getModelClasses();

        /* @var $obj Model */

        if (!empty($modelClasses)) :
            foreach ($modelClasses as $appName => $classes) :

                foreach ($classes as $class) :

                    $reflect = new \ReflectionClass($class);

                    // if we cannot create an instance of a class just skip,
                    // e.g traits abstrat etc

                    if (!$reflect->isInstantiable()) :
                        continue;
                    endif;

                    if ($this->hasModel($class)):
                        continue;
                    endif;

                    $obj = new $class();
                    $obj->setupClassInfo(
                        null,
                        ['meta' => ['appName' => $appName]]
                    );
                endforeach;
            endforeach;
        endif;
    }

    /**
     * @param string $name
     *
     * @return Model
     *
     * @throws LookupError
     *
     * @since  1.1.0
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
        return ArrayHelper::hasKey($this->allModels, $name);
    }

    /**
     * Returns a list of all model names in lowercase or false if not models were found.
     *
     * @return array|bool
     *
     * @throws ClassNotFoundException
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getModelClasses()
    {
        $models = [];

        $modelFiles = $this->getModelFiles();

        if (empty($modelFiles)) {
            return false;
        }

        foreach ($this->getModelFiles() as $appName => $files) :
            foreach ($files as $file) :
                $className = ClassHelper::getClassFromFile($file);

                if (!class_exists($className)):
                    throw new ClassNotFoundException(
                        sprintf('The class [ %s ] could not be located', $className)
                    );
                endif;
                $models[$appName][] = $className;
            endforeach;
        endforeach;

        return $models;
    }

    public function registerModel(Model $model)
    {
        $name = $model->meta->getNamespacedModelName();
        if (!ArrayHelper::hasKey($this->allModels, $name)) {
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
     * @since  1.1.0
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
            $model = $this->getRegisteredModel($modelName);
            $kwargs['relatedModel'] = $model;
            $callback($kwargs);
        } catch (LookupError $err) {
            $this->_pendingOps[$modelName][] = [$callback, $kwargs];
        }
    }

    public function getRegisteredModel($name)
    {
        $model = ArrayHelper::getValue($this->allModels, $name);
        if (null == $model):
            throw new LookupError(sprintf("Model '%s' not registered.", $name));
        endif;

        return $model;
    }

    /**
     * @param Model $model
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function resolvePendingOps($model)
    {
        if (isset($this->_pendingOps[$model->meta->getNamespacedModelName()])) {
            $todoActions = $this->_pendingOps[$model->meta->getNamespacedModelName()];
            foreach ($todoActions as $todoAction) {
                list($callback, $kwargs) = $todoAction;
                $kwargs['relatedModel'] = $model;
                $callback($kwargs);
            }
        }
    }

    public function getPendingOperations()
    {
        return $this->_pendingOps;
    }

    public function __toString()
    {
        return (string) sprintf('%s Object', $this->getFullClassName());
    }
}
