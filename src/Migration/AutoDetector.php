<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration;

use Eddmash\PowerOrm\Console\Question\Asker;
use Eddmash\PowerOrm\Migration\Operation\Field\AddField;
use Eddmash\PowerOrm\Migration\Operation\Field\RemoveField;
use Eddmash\PowerOrm\Migration\Operation\Model\CreateModel;
use Eddmash\PowerOrm\Migration\Operation\Model\DeleteModel;
use Eddmash\PowerOrm\Migration\Operation\Model\RenameModel;
use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ModelState;
use Eddmash\PowerOrm\Migration\State\ProjectState;
use Eddmash\PowerOrm\Migration\State\StateRegistry;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Object;

/**
 * Takes a pair of ProjectStates, and compares them to see what the first would need doing to
 * make it match the second (the second usually being the project's current state).
 *
 * Note that this naturally operates on entire projects at a time, as it's likely that changes interact
 * (for example, you can't add a ForeignKey without having a migration to add the table it depends on first).
 * A user interface may offer single-app usage if it wishes, with the caveat that it may not always be possible.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AutoDetector extends Object
{
    private $fromState;
    private $toState;
    /**
     * @var Asker
     */
    private $asker;

    /**
     * @var string
     */
    private $migrationNamePrefix = 'm';

    /**
     * @var StateRegistry
     */
    private $newRegistry;

    /**
     * @var StateRegistry
     */
    private $oldRegistry;

    /**
     * @var array
     */
    private $oldModelKeys;

    /**
     * @var array
     */
    private $newModelKeys;

    /**
     * @var array
     */
    private $oldProxyKeys;

    /**
     * @var array
     */
    private $newProxyKeys;

    /**
     * @var array
     */
    private $oldUnmanagedKeys;

    /**
     * @var array
     */
    private $newUnmanagedKeys;

    /**
     * Holds any renamed models.
     * @var array
     */
    private $renamedModels;

    /**
     * Holds any renamed models
     * @var
     */
    private $renamedModelsRel;

    const ACTION_CREATED = true;
    const ACTION_NOT_CREATED = false;

    const TYPE_MODEL = true;
    const TYPE__NON_MODEL = false;

    /**
     * @param ProjectState $fromState
     * @param ProjectState $toState
     * @param Asker        $asker
     */
    public function __construct($fromState, $toState, $asker) {
        $this->fromState = $fromState;
        $this->toState = $toState;
        $this->asker = empty($asker) ? new Asker() : $asker;
    }

    /**
     * @param Graph $graph
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getChanges($graph, $migrationName = null)
    {
        $changes = $this->_detectChanges($graph);
        $changes = $this->_arrangeForGraph($changes, $graph, $migrationName);

        return $changes;
    }

    private function _detectChanges($graph)
    {
        $this->generatedOperations = [];
        $this->oldRegistry = $this->fromState->getRegistry();
        $this->newRegistry = $this->toState->getRegistry();

        $this->oldModelKeys = [];
        $this->oldProxyKeys = [];
        $this->oldUnmanagedKeys = [];

        $this->newModelKeys = [];
        $this->newProxyKeys = [];
        $this->newUnmanagedKeys = [];

        // old state
        $oldModelNames = array_keys($this->fromState->getModelStates());

        foreach ($oldModelNames as $oldModelName) :
            $oldModel = $this->oldRegistry->getModel($oldModelName);
            if(!$oldModel->meta->managed):
                $this->oldUnmanagedKeys[] = $oldModelName;
            elseif($oldModel->meta->proxy):
                $this->oldProxyKeys[] = $oldModelName;
            else:
                $this->oldModelKeys[] = $oldModelName;
            endif;
        endforeach;

        // new state
        $newModelNames = array_keys($this->toState->getModelStates());

        foreach ($newModelNames as $newModelName) :
            $newModel = $this->newRegistry->getModel($newModelName);

            if(false === $newModel->meta->managed):
                $this->newUnmanagedKeys[] = $newModelName;
            elseif($newModel->meta->proxy):

                $this->newProxyKeys[] = $newModelName;

            else:
                $this->newModelKeys[] = $newModelName;
            endif;
        endforeach;

        $this->generateRenamedModels();
        $this->generateDeleteModel();
        $this->generateCreatedModel();
        $this->generateDeletedProxies();
        $this->generateCreatedProxies();
        $this->generateAlteredMeta();

        return (empty($this->generatedOperations)) ? [] : [$this->createMigration()];
    }

    /**
     * Find all new models (both managed and unmanaged) and make create operations for them as well as separate
     * operations to create any foreign key or M2M relationships .
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateCreatedModel() {
        // get old model keys
        $oldModelKeys = array_merge($this->oldModelKeys, $this->oldUnmanagedKeys);
        $addedModels = array_diff($this->newModelKeys, $oldModelKeys);
        $addedUnmanageModels = array_diff($this->newUnmanagedKeys, $oldModelKeys);

        $allAddedModels = array_merge($addedModels, $addedUnmanageModels);

        /* @var $modelState ModelState */
        /* @var $primaryKeyRel Model */
        /* @var $relationField Field */

        foreach ($allAddedModels as $addedModelName) :

            $modelState = $this->toState->modelStates[$addedModelName];
            $meta = $this->newRegistry->getModel($addedModelName)->meta;

            $localFields = $meta->localFields;
            $localM2MFields = $meta->localFields;

            $primaryKeyRel = null;
            $relatedFields = [];

            // get all the relationship fields since they will need to be created in there own operations aside from
            // the one that creates the model remember the model needs to exist first before enforcing the relationships.

            /** @var $localField Field */
            foreach ($localFields as $localField) :
                if($localField->remoteField != null && $localField->remoteField->model != null):

                    if($localField->primaryKey):
                        $primaryKeyRel = $localField->remoteField->model;
                    elseif(!$localField->remoteField->parentLink):
                        $relatedFields[$localField->name] = $localField;
                    endif;

                endif;

            endforeach;

            /** @var $localM2MField Field todo uncomment*/
//            foreach ($localM2MFields as $localM2MField) :
//                if($localField->remoteField->model != null):
//                    $relatedFields[$localM2MField->name] = $localM2MField;
//                endif;
//
//                // if field has a through model and it was not auto created, add it as a related field
//                if($localField->remoteField->hasProperty('through') && !$localField->remoteField->through->meta->autoCreated):
//                    $relatedFields[$localM2MField->name] = $localM2MField;
//                endif;
//
//            endforeach;

            // we need to keep track of which operation need to run before us

            // first, check for operations that drops a proxy version of us has been dropped
            $opDep = [['target' => $addedModelName, 'model' => true,  'create' => false]];

            // depend on related model being created if primary key is a relationship field
            if($primaryKeyRel !== null):
                $opDep[] = ['target' => $primaryKeyRel->meta->modelName, 'model' => true, 'create' => true];
            endif;

            //we need to get the unbound fields
            $boundRelationFieldKeys = array_keys($relatedFields);
            $unboundFields = $modelState->fields;
            $uFields = [];
            foreach ($unboundFields as $uname => $ufield) :
                if(in_array($uname, $boundRelationFieldKeys)):
                    continue;
                endif;
                $uFields[$uname] = $ufield;
            endforeach;

            // create operation
            $this->addOperation(
                CreateModel::createObject([
                    'name' => $modelState->name,
                    'fields' => $uFields,
                    'meta' => $modelState->meta,
                    'extends' => $modelState->extends,
                ]),
                $opDep,
                true
            );

            // at this point if we the model is un manged just stop , since we just need to have it recorded in our
            // migrations, we don't need to create the relationship fields also, its not our problem anymore
            if(!$meta->managed):
                continue;
            endif;

            // take care of relationships
            foreach ($relatedFields as $fieldName => $relationField) :
                // we need the current model to be in existence
                $opDep[] = ['target' => $addedModelName, 'model' => true,  'create' => true];

                // depend on the related model also
                $opDep[] = [
                    'target' => $relationField->remoteField->model->meta->modelName,
                    'model' => true,
                    'create' => true,
                ];

                // if the through model was not automatically created, depend on it also
                if($relationField->remoteField->hasProperty('through') &&
                    !$relationField->remoteField->through->meta->autoCreated):

                    $opDep[] = [
                        'target' => $relationField->remoteField->through->meta->modelName,
                        'model' => true,
                        'create' => true,
                    ];
                endif;

                //create the operation
                $this->addOperation(
                    AddField::createObject([
                        'modelName' => $addedModelName,
                        'name' => $fieldName,
                        'field' => $relationField,
                    ]),
                    $opDep
                );
            endforeach;

        endforeach;

    }

    /**
     * Find all deleted models (managed and unmanaged) and make delete operations for them as well as separate
     * operations to delete any foreign key or M2M relationships.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateDeleteModel() {

        $newModelKeys = array_merge($this->newModelKeys, $this->newUnmanagedKeys);
        $deletedModels = array_diff($this->oldModelKeys, $newModelKeys);
        $deletedUnmanagedModels = array_diff($this->oldUnmanagedKeys, $newModelKeys);
        $allDeletedModels = array_merge($deletedModels, $deletedUnmanagedModels);

        /* @var $modelState ModelState */
        foreach ($allDeletedModels as $deletedModel) :

            $modelState = $this->fromState->modelStates[$deletedModel];
            $meta = $this->fromState->getRegistry()->getModel($deletedModel)->meta;

            // at this point if we the model is un manged just stop , since we just need to have it recorded in our
            // migrations, we don't need to create the relationship fields also, its not our problem anymore
            if(!$meta->managed):
                continue;
            endif;

            $localFields = $meta->localFields;
            $localM2MFields = $meta->localFields;

            $relatedFields = [];

            // get all the relationship fields that we initiated since they will need to be created in there own
            // operations aside from  the one that creates the model remember the model needs to exist first before
            // enforcing the relationships.

            /** @var $localField Field */
            foreach ($localFields as $localField) :
                if($localField->remoteField != null && $localField->remoteField->model != null):
                    $relatedFields[$localField->name] = $localField;
                endif;

            endforeach;

            /** @var $localM2MField Field */
            foreach ($localM2MFields as $localM2MField) :

                if($localField->remoteField != null && $localField->remoteField->model != null):
                    $relatedFields[$localM2MField->name] = $localM2MField;
                endif;

            endforeach;

            // take care of relationships
            foreach ($relatedFields as $fieldName => $relationField) :
                //remove the operation
                $this->addOperation(
                    RemoveField::createObject([
                        'modelName' => $deletedModel,
                        'name' => $fieldName,
                    ])
                );
            endforeach;

            $opDep = [];

            // we also need to drop all relationship fields that point from us, initiated by other models.
            $relatedObjects = $meta->getReverseRelatedObjects();
//            foreach ($relatedObjects as $relatedObject) :
//
//            endforeach;

            // finally remove the model
            $this->addOperation(DeleteModel::createObject(['name' => $modelState->name]), $opDep);

        endforeach;

    }

    /**
     * Finds any renamed models, and generates the operations for them, and removes the old entry from the model lists.
     * Must be run before other model-level generation.
     *
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateRenamedModels()
    {
        $addedModels = array_diff($this->newModelKeys, $this->oldModelKeys);

        /**@var $modelState ModelState*/
        foreach ($addedModels as $addedModel) :
            $modelState = $this->toState->modelStates[$addedModel];
            $modelDefinitionList = $this->getFieldsDefinitions($modelState->fields);

            $removedModels = array_diff($this->oldModelKeys, $this->newModelKeys);
            foreach ($removedModels as $removedModel) :

                    $remModelState = $this->fromState->modelStates[$removedModel];
                    $remModelDefinitionList = $this->getFieldsDefinitions($remModelState->fields);

                    if($remModelDefinitionList == $modelDefinitionList):
                        
                        if($this->asker->ask(MigrationQuestion::hasModelRenamed($removedModel, $addedModel))):

                            $this->addOperation(RenameModel::createObject([
                                'oldName'=> $removedModel,
                                'newName'=> $addedModel,
                            ]));

                            $this->renamedModels[$addedModel] = $removedModel;

                            // remove the old name and update with the new name.
                            $pos = array_search($removedModel, $this->oldModelKeys);

                            array_splice($this->oldModelKeys, $pos, 1, [$addedModel]);

                            // you can stop here.
                            break;
                        endif;
                    endif;
            endforeach;
        endforeach;
    }

    /**
     * Makes CreateModel statements for proxy models.
     *
     * We use the same statements as that way there's less code duplication, but of course for proxy models we can skip
     * all that pointless field stuff and just chuck out an operation.
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateCreatedProxies(){
        $addedProxies = array_diff($this->newProxyKeys, $this->oldProxyKeys);

        /**@var $modelState ModelState*/
        foreach ($addedProxies as $addedProxy) :
            $modelState = $this->toState->modelStates[$addedProxy];
            assert($modelState->meta['proxy']);

            $opDep = [['target' => $addedProxy, 'model' => true,  'create' => false]];

            // create operation
            $this->addOperation(
                CreateModel::createObject([
                    'name' => $modelState->name,
                    'fields' => [],
                    'meta' => $modelState->meta,
                    'extends' => $modelState->extends,
                ]),
                $opDep
            );
        endforeach;

    }

    /**
     *  Makes DeleteModel statements for proxy models.
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateDeletedProxies(){
        $droppedProxies = array_diff($this->oldProxyKeys, $this->newProxyKeys);

        foreach ($droppedProxies as $droppedProxy) :
            $modelState = $this->fromState->modelStates[$droppedProxy];

            // create operation
            $this->addOperation(
                DeleteModel::createObject([
                    'name' => $modelState->name,
                ])
            );
        endforeach;


    }

    /**
     * Works out if any non-schema-affecting options have changed and makes an operation to represent them in state
     * changes.
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateAlteredMeta()
    {

    }
    
    /**
     * @param array  $changes
     * @param Graph  $graph
     * @param string $migrationName
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function _arrangeForGraph($changes, $graph, $migrationName = null)
    {
        $leaves = $graph->getLeafNodes();
        $leaf = (empty($leaves)) ? '' : $leaves[0];

        if (empty($leaf)):
            $migrationNo = 1;
        else:
            $migrationNo = $this->getMigrationNumber($leaf) + 1;

        endif;

        /** @var $migration Migration */
        foreach ($changes as $index => &$migration) :
            // set name for migration
            if (empty($leaf)):
                // this mean we don't have previous migrations
                $migrationName = $this->suggestName();
            else:
                // first set previous as dependency of this
                // $migration->requires = [$leaf];
                $migration->setDependency($leaf);

                $migrationNo = str_pad($migrationNo, 4, '0', STR_PAD_LEFT);
                $migrationName = $this->suggestName($migration->getOperations(), $migrationNo);
            endif;

            $migration->setName($migrationName);
        endforeach;

        return $changes;
    }

    /**
     * @param Operation  $operation
     * @param array      $dependencies
     * @param bool|false $pushToTop    some operations should come before others, use this determine which
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addOperation($operation, $dependencies = [], $pushToTop = false)
    {
        $operation->setDependency($dependencies);

        if ($pushToTop):
            array_unshift($this->generatedOperations, $operation);
        else:
            array_push($this->generatedOperations, $operation);
        endif;
    }

    private function createMigration()
    {
        $migration = new Migration('auto');
        $migration->setOperations($this->generatedOperations);

        return $migration;
    }

    /**
     * Return a definition of the fields that ignores field names and what related fields actually relate to.
     *
     * Used for detecting renames (as, of course, the related fields change during renames)
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getFieldsDefinitions($fields)
    {
        $fieldDefs = [];

        /**@var $field Field*/
        foreach ($fields as $name=>$field) :
            $def = $this->deepDeconstruct($field);

            if($field->remoteField!==null && $field->remoteField->to !==null):
                unset($def['constructorArgs']['to']);
            endif;

            //var_dump($def);
            $fieldDefs[] = $def;
        endforeach;
        return $fieldDefs;
    }

    /**
     * @param $value
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function deepDeconstruct($value)
    {
        if(!$value instanceof Object || ($value instanceof Object && !$value->hasMethod('deconstruct'))):
            return $value;
        endif;

        $deconstructed = $value->deconstruct();

        return [
            'constructorArgs'=>$this->deepDeconstruct($deconstructed['constructorArgs']),
            'fullName'=>$this->deepDeconstruct($deconstructed['fullName'])
        ];
    }

    private function checkDependency($operation, $dependency)
    {
        return true;
    }

    /**
     * Trys to guess a name for the migration that is to be created.
     *
     * @param array $operations
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function suggestName($operations = null, $id = null)
    {
        $prefix = $this->migrationNamePrefix;
        if ($operations === null):
            return sprintf('%s0001_Initial', $prefix);
        endif;
        if (count($operations) == 1):
            /** @var $op Operation */
            $op = $operations[0];
            if($op instanceof CreateModel):
                return sprintf('%s%s_%s', $prefix, $id, ucwords($op->name));
            elseif($op instanceof DeleteModel):
                return sprintf('%s%s_Delete_%s', $prefix, $id, ucwords($op->name));
            elseif($op instanceof AddField):
                return sprintf('%s%s_%s_%s', $prefix, $id, ucwords($op->modelName), ucwords($op->name));
            elseif($op instanceof RemoveField):
                return sprintf('%s%s_Remove_%s_%s', $prefix, $id, ucwords($op->modelName), ucwords($op->name));
            endif;
        endif;
        return sprintf('%s%s_Auto_%s', $prefix, $id, date('Ymd_hm'));
    }

    public function getMigrationNumber($name)
    {
        $name = explode('_', $name);

        return (int) str_replace($this->migrationNamePrefix, '', $name[0]);
    }

}
