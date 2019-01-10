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
use Eddmash\PowerOrm\Exception\CircularDependencyError;
use Eddmash\PowerOrm\Exception\ClassNotFoundException;
use Eddmash\PowerOrm\Exception\KeyError;
use Eddmash\PowerOrm\Exception\LookupError;
use Eddmash\PowerOrm\Exception\OrmException;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Eddmash\PowerOrm\Helpers\Tools;
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
    protected $allModels = [];

    private $_pendingOps = [];

    protected $modelsReady;

    public $ready;

    /***
     * @var Model[][]
     */
    private $appModels;

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
        if (false == $this->ready) {
            try {
                $this->hydrateRegistry();
                $this->ready = true;
            } catch (\Exception $e) {
                throw $e;
            }
        }

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

        foreach (BaseOrm::getInstance()->getComponents() as $component) {
            if ($component instanceof AppInterface) {
                $fileHandler = new FileHandler($component->getModelsPath());
                $files[$component->getName()] = $fileHandler->readDir('php');
            }
        }

        return $files;
    }

    /**
     * Returns the list of all the models that extend the PModel in the current
     * app.
     *
     * @return Model[]
     *
     * @throws \Eddmash\PowerOrm\Exception\AppRegistryNotReady
     */
    public function getModels($includeAutoCreated = false, $app = null): array
    {
        $this->isAppReady();

        if ($includeAutoCreated && is_null($app)) {
            return $this->allModels;
        }

        if (!is_null($app)) {
            $appModels = $this->appModels[$app];
            if (!$includeAutoCreated) {
                $rModels = [];
                foreach ($appModels as $name => $appModel) {
                    if (!$appModel->getMeta()->autoCreated) {
                        $rModels[$name] = $appModel;
                    }
                }
                $appModels = $rModels;
            }

            return $appModels;
        }

        $rModels = [];
        /** @var $model Model */
        foreach ($this->allModels as $name => $model) {
            if (!$includeAutoCreated && $model->getMeta()->autoCreated) {
                continue;
            }
            $rModels[$name] = $model;
        }

        return $rModels;
    }

    /**
     * Loads all the models in the current application.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws AppRegistryNotReady
     * @throws ClassNotFoundException
     * @throws OrmException
     * @throws \Eddmash\PowerOrm\Exception\FieldError
     * @throws \Eddmash\PowerOrm\Exception\ImproperlyConfigured
     * @throws \Eddmash\PowerOrm\Exception\MethodNotExtendableException
     * @throws \Eddmash\PowerOrm\Exception\TypeError
     */
    protected function hydrateRegistry()
    {
        if ($this->ready) {
            return;
        }

        $modelClasses = $this->getModelClasses();

        $callback = function (\ReflectionClass $reflect) use (
            &$callback,
            &$classList
        ) {
            $parentClass = $reflect->getParentClass()->getName();
            //            $classList[] = $reflect->getName();
            if (Model::class === $parentClass) {
                $extends = [];
            } else {
                if ($reflect->getParentClass()->isAbstract()) {
                    $extends = $callback($reflect->getParentClass());
                } else {
                    $extends = [$parentClass];
                }
            }
            $classList = array_merge($classList, $extends);

            return $extends;
        };

        /* @var $obj Model */

        if (!empty($modelClasses)) {
            $classPopulationOrder = [];
            $classToAppMap = [];
            $classList = [];

            foreach ($modelClasses as $appName => $classes) {
                foreach ($classes as $class) {
                    $classToAppMap[$class] = $appName;
                    $reflect = new \ReflectionClass($class);

                    // if we cannot create an instance of a class just skip,
                    // e.g traits abstract etc

                    if (!$reflect->isInstantiable()) {
                        continue;
                    }

                    if ($this->hasModel($class) ||
                        !$reflect->isSubclassOf(Model::class)) {
                        continue;
                    }

                    // callback to get non-abstract parent, since this needs to
                    // created before we can create this child class instance
                    // if none is found return empty array
                    $classList[] = $reflect->getName();
                    $classPopulationOrder[$class] = $callback($reflect);
                }
            }
            $classList = array_unique($classList);

            foreach ($classList as $class) {
                if (!ArrayHelper::hasKey($classToAppMap, $class)) {
                    throw new OrmException(
                        "Make '$class' abstract or register it as " .
                        'an application model'
                    );
                }
            }

            try {
                $classPopulationOrder = Tools::topologicalSort($classPopulationOrder);
            } catch (CircularDependencyError $e) {
                throw new OrmException($e->getMessage());
            }

            foreach ($classPopulationOrder as $class) {
                $obj = new $class();

                $obj->setupClassInfo(
                    null,
                    ['meta' => ['appName' => $classToAppMap[$class]]]
                );
            }
        }
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
     *
     * @throws AppRegistryNotReady
     */
    public function getModel($name)
    {
        $this->isAppReady();

        if (!$this->hasModel($name)) {
            throw new LookupError(
                sprintf('The model { %s } Does not exist', $name)
            );
        }

        return $this->allModels[$name];
    }

    /**
     * Checks model has been loaded by the orm.
     *
     * @param $name model name to check if it has been loaded
     *
     * @return bool
     */
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

        foreach ($this->getModelFiles() as $appName => $files) {
            foreach ($files as $file) {
                $className = ClassHelper::getClassFromFile($file);

                if (!class_exists($className)) {
                    throw new ClassNotFoundException(
                        sprintf('The class [ %s ] could not be located', $className)
                    );
                }
                $models[$appName][] = $className;
            }
        }

        return $models;
    }

    public function registerModel(Model $model)
    {
        $name = $model->getMeta()->getNSModelName();
        if (!ArrayHelper::hasKey($this->allModels, $name)) {
            $this->allModels[$name] = $model;
            $this->appModels[$model->getMeta()->getAppName()][$name] = $model;
        }
        $this->resolvePendingOps($model);
    }

    /**
     * @param callable $callback the callback to invoke when a model
     *                                  has been created
     * @param array $modelsToResolve the model we are waiting for to be
     *                                  created, the model object is passed to
     *                                  the callback as the first argument
     * @param array $callableArgs an associative array to be passed to
     *                                  the callback
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function lazyModelOps(callable $callback, array $modelsToResolve, array $callableArgs)
    {
        // get the first
        $modelName = $modelsToResolve[0];

        // recurse the others
        if (isset($modelsToResolve[1]) &&
            !empty(array_slice($modelsToResolve, 1))) {
            $this->lazyModelOps(
                $callback,
                array_slice($modelsToResolve, 1),
                $callableArgs
            );
        }

        try {
            $model = $this->getRegisteredModel($modelName);
            $callableArgs['relatedModel'] = $model;
            $callback($callableArgs);
        } catch (LookupError $err) {
            $this->_pendingOps[$modelName][] = [$callback, $callableArgs];
        }
    }

    /**
     * Gets a registered model. This method is used internally to get a
     * registered model without the possibility of side effects incase it not
     * registerd.
     *
     * @param $modelName
     *
     * @return mixed
     *
     * @internal
     *
     * @throws LookupError
     */
    public function getRegisteredModel($modelName)
    {
        try {
            $model = ArrayHelper::getValue($this->allModels, $modelName);
        } catch (KeyError $e) {
            $model = null;
        }
        if (null == $model) {
            throw new LookupError(
                sprintf("Models '%s' not registered.", $modelName)
            );
        }

        return $model;
    }

    /**
     * @param Model $model
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function resolvePendingOps($model)
    {
        $name = $model->getMeta()->getNSModelName();
        if (isset($this->_pendingOps[$name])) {
            $todoActions = $this->_pendingOps[$name];
            foreach ($todoActions as $todoAction) {
                list($callback, $kwargs) = $todoAction;
                $kwargs['relatedModel'] = $model;
                $callback($kwargs);
            }
            unset($this->_pendingOps[$name]);
        }
    }

    public function getPendingOperations()
    {
        return $this->_pendingOps;
    }

    public function __toString()
    {
        return (string)sprintf('%s Object', $this->getFullClassName());
    }
}
