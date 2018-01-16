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

use Eddmash\PowerOrm\BaseObject;
use Eddmash\PowerOrm\Console\Question\Asker;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Migration\Operation\Field\AddField;
use Eddmash\PowerOrm\Migration\Operation\Field\AlterField;
use Eddmash\PowerOrm\Migration\Operation\Field\RemoveField;
use Eddmash\PowerOrm\Migration\Operation\Field\RenameField;
use Eddmash\PowerOrm\Migration\Operation\Model\AlterModelMeta;
use Eddmash\PowerOrm\Migration\Operation\Model\AlterModelTable;
use Eddmash\PowerOrm\Migration\Operation\Model\CreateModel;
use Eddmash\PowerOrm\Migration\Operation\Model\DeleteModel;
use Eddmash\PowerOrm\Migration\Operation\Model\RenameModel;
use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ModelState;
use Eddmash\PowerOrm\Migration\State\ProjectState;
use Eddmash\PowerOrm\Migration\State\StateRegistry;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\ManyToManyField;
use Eddmash\PowerOrm\Model\Field\RelatedField;
use Eddmash\PowerOrm\Model\Model;

/**
 * Takes a pair of ProjectStates, and compares them to see what the first would need doing to
 * make it match the second (the second usually being the project's current state).
 *
 * Note that this naturally operates on entire projects at a time, as it's likely that changes interact
 * (for example, you can't add a ForeignKey without having a migration to add the table it depends on first).
 * A user interface may offer single-app usage if it wishes, with the caveat that it may not always be possible.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AutoDetector extends BaseObject
{
    const ACTION_CREATED = 'created';
    const ACTION_DROPPED = 'dropped';
    const ACTION_ALTER = 'alter';
    const TYPE_MODEL = 'model';
    const TYPE_FIELD = 'field';

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
     *
     * @var array
     */
    private $renamedModels = [];

    private $keptProxyKeys;
    private $keptUnmanagedKeys;
    private $keptModelKeys;
    private $oldFieldKeys;
    private $newFieldKeys;
    private $renamedFields;

    /**
     * @var Operation[]
     */
    private $generatedOperations;

    /**
     * @param ProjectState $fromState
     * @param ProjectState $toState
     * @param Asker        $asker
     */
    public function __construct(
        ProjectState $fromState,
        ProjectState $toState,
        Asker $asker
    ) {
        $this->fromState = $fromState;
        $this->toState = $toState;
        $this->asker = $asker;
    }

    /**
     * @param Graph $graph
     *
     * @return array
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getChanges($graph, $migrationName = null)
    {
        $changes = $this->detectChanges($graph);

        $changes = $this->arrangeForGraph($changes, $graph, $migrationName);

        return $changes;
    }

    /**
     * @param $graph
     *
     * @return array
     */
    private function detectChanges($graph)
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
            if (!$oldModel->getMeta()->managed):
                $this->oldUnmanagedKeys[] = $oldModelName;
            elseif ($oldModel->getMeta()->proxy):
                $this->oldProxyKeys[] = $oldModelName;
            else:
                $this->oldModelKeys[] = $oldModelName;
            endif;
        endforeach;

        // new state
        $newModelNames = array_keys($this->toState->getModelStates());

        foreach ($newModelNames as $newModelName) :
            $newModel = $this->newRegistry->getModel($newModelName);

            if (false === $newModel->getMeta()->managed):
                $this->newUnmanagedKeys[] = $newModelName;
            elseif ($newModel->getMeta()->proxy):

                $this->newProxyKeys[] = $newModelName;
            else:
                $this->newModelKeys[] = $newModelName;
            endif;
        endforeach;

        $this->generateRenamedModels();

        // find anything that was kept
        $this->keptModelKeys = array_intersect(
            $this->oldModelKeys,
            $this->newModelKeys
        );
        $this->keptProxyKeys = array_intersect(
            $this->oldProxyKeys,
            $this->newProxyKeys
        );

        $this->keptUnmanagedKeys = array_intersect(
            $this->oldUnmanagedKeys,
            $this->newUnmanagedKeys
        );

        // get fields from both the new and the old models
        /* @var $oldState ModelState */
        /* @var $newState ModelState */
        foreach ($this->keptModelKeys as $modelName) :

            $oldModelName = $this->getOldModelName($modelName);
            $oldState = $this->fromState->getModelState($oldModelName);
            $newState = $this->toState->getModelState($modelName);

            foreach ($newState->fields as $newName => $newField) :
                $this->newFieldKeys[$modelName][] = $newName;
            endforeach;

            foreach ($oldState->fields as $oldName => $oldField) :
                $this->oldFieldKeys[$modelName][] = $oldName;
            endforeach;

        endforeach;

        // *** models
        $this->generateDeleteModel();
        $this->generateCreatedModel();
        $this->generateDeletedProxies();
        $this->generateCreatedProxies();
        $this->generateAlteredMeta();

        // *** fields
        $this->generateRenamedFields();
        $this->generateRemovedFields();
        $this->generateAddedFields();
        $this->generateAlteredFields();
        $this->generateAlteredDbTable();

        return ($this->generatedOperations) ? $this->createMigration() : [];
    }

    /**
     * @param array  $changes
     * @param Graph  $graph
     * @param string $migrationName
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function arrangeForGraph($changes, $graph, $migrationName = null)
    {
        $leaves = $graph->getLeafNodes();

        /** @var $migration Migration */
        foreach ($changes as $appName => &$migration) :
            if (empty($appName)):
                continue;
            endif;
            $appLeaves = (empty($leaves[$appName])) ? [] : $leaves[$appName];
            $leaf = (empty($appLeaves)) ? '' : $appLeaves[0];
            if (empty($leaf)):
                $migrationNo = 1;
            else:
                $migrationNo = $this->getMigrationNumber(
                        Migration::createShortName($leaf)
                    ) + 1;

            endif;

            // set name for migration
            if (empty($leaf)):
                // this mean we don't have previous migrations
                $migrationName = $this->suggestName();
            else:
                // first set previous as dependency of this
                $migration->setDependency($appName, $leaf);

                $migrationNo = str_pad(
                    $migrationNo,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
                $migrationName = $this->suggestName(
                    $migration->getOperations(),
                    $migrationNo
                );
            endif;

            $migration->setName($migrationName);

        endforeach;

        return $changes;
    }

    /**
     * @param Operation  $operation
     * @param array      $dependencies
     * @param bool|false $pushToTop some operations should come before
     *                              others, use this determine which
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addOperation(
        $appLabel,
        $operation,
        $dependencies = [],
        $pushToTop = false
    ) {
        $operation->setDependency($dependencies);
        $operation->setAppLabel($appLabel);

        if ($pushToTop):
            array_unshift($this->generatedOperations, $operation);
        else:
            array_push($this->generatedOperations, $operation);
        endif;
    }

    private function createMigration()
    {
        $this->sortOperations();

        $operations = Optimize::run($this->generatedOperations);

        $app = [];

        /** @var $operations \Eddmash\PowerOrm\Migration\Operation\Operation[] */
        foreach ($operations as $operation) :
            $app[$operation->getAppLabel()][] = $operation;
        endforeach;

        $migrations = [];
        foreach ($app as $label => $ops) :
            $migration = new Migration('auto');
            // optimize the migrations
            $migration->setOperations($ops);
            $migration->setAppLabel($label);
            $migrations[$label] = $migration;
        endforeach;

        return $migrations;
    }

    /**
     * Return a definition of the fields that ignores field names and what related fields actually relate to.
     *
     * Used for detecting renames (as, of course, the related fields change during renames)
     *
     * @param $fields
     *
     * @return array
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getFieldsDefinitions($fields)
    {
        $fieldDefs = [];

        /** @var $field Field */
        foreach ($fields as $name => $field) :
            $def = $this->deepDeconstruct($field);

            if (null !== $field->relation && null !== $field->relation->toModel):
                unset($def['constructorArgs']['to']);
            endif;

            $fieldDefs[] = $def;
        endforeach;

        return $fieldDefs;
    }

    /**
     * @param $value
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function deepDeconstruct($value)
    {
        if (!$value instanceof BaseObject || ($value instanceof BaseObject && !$value->hasMethod(
                    'deconstruct'
                ))):
            return $value;
        endif;

        $deconstructed = $value->deconstruct();

        return [
            'constructorArgs' => $this->deepDeconstruct(
                $deconstructed['constructorArgs']
            ),
            'fullName' => $this->deepDeconstruct($deconstructed['fullName']),
        ];
    }

    /**
     * @param $operation
     * @param $dependency
     *
     * @return bool
     *
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     * @throws \Eddmash\PowerOrm\Exception\ValueError
     */
    private function checkDependency($operation, $dependency)
    {
        //['target' => $addedModelName, 'type' => true, 'action' => false]
        $target = ArrayHelper::getValue($dependency, 'target');
        $type = ArrayHelper::getValue($dependency, 'type');
        $action = ArrayHelper::getValue($dependency, 'action');
        $model = ArrayHelper::getValue($dependency, 'model', null);

        if (self::TYPE_MODEL === $type && self::ACTION_CREATED === $action):
            // add model
            return
                $operation instanceof CreateModel &&
                strtolower($operation->name) === strtolower($target);
        elseif (self::TYPE_FIELD === $type && self::ACTION_CREATED === $action):
            // add field
            return

                $operation instanceof AddField &&
                strtolower($operation->name) === strtolower($target) &&
                strtolower($operation->modelName) === strtolower($model);
        //            ||(
        //                $operation instanceof CreateModel) &&
        //            strtolower($operation->name) === strtolower($target) &&
        //        any(dependency[2] == x for x, y in operation.fields)
        //                )
        //            )
        elseif (self::TYPE_FIELD === $type && self::ACTION_DROPPED === $action):
            // remove field
            return
                $operation instanceof RemoveField &&
                strtolower($operation->modelName) === strtolower($model) &&
                strtolower($operation->name) === strtolower($target);
        elseif (self::TYPE_MODEL === $type && self::ACTION_DROPPED === $action):
            //dropped model
            return
                $operation instanceof DeleteModel &&
                strtolower($operation->name) === strtolower($target);
        elseif (self::TYPE_FIELD === $type && self::ACTION_ALTER === $action):
            // altered field
            return
                $operation instanceof AlterField &&
                strtolower($operation->modelName) === strtolower($model) &&
                strtolower($operation->name) === strtolower($target);
        // Unknown dependency. Raise an error.
        else:
            throw new ValueError(
                sprintf(
                    "Can't handle dependency %s %s '%s' ",
                    $action,
                    $target,
                    $type
                )
            );
        endif;
    }

    /**
     * Try to guess a name for the migration that is to be created.
     *
     * @param Operation[] $operations
     *
     * @return string
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function suggestName($operations = null, $id = null)
    {
        $prefix = $this->migrationNamePrefix;
        if (null === $operations):
            return sprintf('%s0001_Initial', $prefix);
        endif;
        if (1 == count($operations)):

            $op = $operations[0];
            if ($op instanceof CreateModel):
                return sprintf(
                    '%s%s_%s',
                    $prefix,
                    $id,
                    $this->formatName($op->name, $op->getAppLabel())
                );
            elseif ($op instanceof DeleteModel):
                return sprintf(
                    '%s%s_Delete_%s',
                    $prefix,
                    $id,
                    $this->formatName($op->name, $op->getAppLabel())
                );
            elseif ($op instanceof AddField):
                return sprintf(
                    '%s%s_%s_%s',
                    $prefix,
                    $id,
                    $this->formatName($op->modelName, $op->getAppLabel()),
                    $this->formatName($op->name)
                );
            elseif ($op instanceof RemoveField):
                return sprintf(
                    '%s%s_Remove_%s_%s',
                    $prefix,
                    $id,
                    $this->formatName($op->modelName, $op->getAppLabel()),
                    $this->formatName($op->name)
                );
            endif;
        endif;

        return sprintf('%s%s_Auto_%s', $prefix, $id, date('Ymd_hm'));
    }

    public function formatName($name, $appLabel = null)
    {
        $name = str_replace('\\', '_', $name);
        if ($appLabel):
            $name = str_replace(
                ''.$appLabel.'_models_',
                '',
                strtolower($name)
            );
        endif;

        return ucwords($name);
    }

    public function getMigrationNumber($name)
    {
        $name = explode('_', $name);

        return (int) str_replace($this->migrationNamePrefix, '', $name[0]);
    }

    private function getOldModelName($modelName)
    {
        return (in_array(
            $modelName,
            $this->renamedModels
        )) ? $this->renamedModels[$modelName] : $modelName;
    }

    /**
     * @param $modelName
     * @param $fieldName
     *
     * @return mixed
     *
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     */
    private function getOldFieldName($modelName, $fieldName)
    {
        $modelRenamedFields = ArrayHelper::getValue(
            $this->renamedFields,
            $modelName,
            []
        );
        if ($modelRenamedFields) :
            $fieldName = ArrayHelper::getValue(
                $modelRenamedFields,
                $fieldName,
                $fieldName
            );
        endif;

        return $fieldName;
    }

    // ******************** GENERATIONS CHANGES ***********************

    /**
     * Find all new models (both managed and unmanaged) and make create operations for them as well as separate
     * operations to create any foreign key or M2M relationships .
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Eddmash\PowerOrm\Exception\LookupError
     * @throws \Eddmash\PowerOrm\Exception\OrmException
     */
    public function generateCreatedModel()
    {
        // get old model keys
        $oldModelKeys = array_merge(
            $this->oldModelKeys,
            $this->oldUnmanagedKeys
        );

        $addedModels = array_diff($this->newModelKeys, $oldModelKeys);
        $addedUnmanageModels = array_diff(
            $this->newUnmanagedKeys,
            $oldModelKeys
        );

        $allAddedModels = array_merge($addedModels, $addedUnmanageModels);

        /* @var $modelState ModelState */
        /* @var $primaryKeyRel Model */
        /* @var $relationField Field */

        foreach ($allAddedModels as $addedModelName) :
            $modelState = $this->toState->getModelState($addedModelName);
            $meta = $this->newRegistry->getModel($addedModelName)->getMeta();

            $primaryKeyRel = null;
            $relatedFields = [];

            // get all the relationship fields since they will need to

            // be created in there own operations aside from

            // the one that creates the model remember the model needs

            // to exist first before enforcing the relationships.

            /** @var $localField Field */
            foreach ($meta->localFields as $localField) :
                if (null != $localField->relation && null != $localField->relation->toModel):

                    if ($localField->primaryKey):
                        $primaryKeyRel = $localField->relation->toModel;
                    elseif (!$localField->relation->parentLink):
                        $relatedFields[$localField->getName()] = $localField;
                    endif;

                endif;

            endforeach;

            /** @var $localM2MField RelatedField */
            foreach ($meta->localManyToMany as $name => $localM2MField) :

                if (null != $localM2MField->relation->toModel):
                    $relatedFields[$localM2MField->getName()] = $localM2MField;
                endif;

                // if related field has a through model and it was not auto created
                if ($localM2MField->relation->hasProperty('through') &&
                    !$localM2MField->relation->through->getMeta()->autoCreated
                ):

                    $relatedFields[$localM2MField->getName()] = $localM2MField;
                endif;

            endforeach;

            // we need to keep track of which operation need to run the create one i.e us

            // first, check for operations that drops a proxy version of us has been dropped
            $opDep = [
                [
                    'target' => $addedModelName,
                    'type' => self::TYPE_MODEL,
                    'action' => self::ACTION_DROPPED,
                ],
            ];

            // depend on related model being created if primary key is a relationship field
            if (null !== $primaryKeyRel):
                $opDep[] = [
                    'target' => $primaryKeyRel->getMeta()->getNSModelName(),
                    'type' => self::TYPE_MODEL,
                    'action' => self::ACTION_CREATED,
                ];
            endif;

            //we need to get the unbound fields
            $boundRelationFieldKeys = array_keys($relatedFields);
            $unboundFields = $modelState->fields;
            $uFields = [];
            foreach ($unboundFields as $uname => $ufield) :
                if (in_array($uname, $boundRelationFieldKeys)):
                    continue;
                endif;
                $uFields[$uname] = $ufield;
            endforeach;

            // create operation
            $this->addOperation(
                $meta->getAppName(),
                CreateModel::createObject(
                    [
                        'name' => $modelState->name,
                        'fields' => $uFields,
                        'meta' => $modelState->getMeta(),
                        'extends' => $modelState->extends,
                    ]
                ),
                $opDep,
                true
            );

            // at this point if we the model is un manged just stop ,
            // since we just need to have it recorded in our
            // migrations, we don't need to create the relationship fields
            // also, its not our problem anymore
            if (!$meta->managed):
                continue;
            endif;

            // take care of relationships
            foreach ($relatedFields as $fieldName => $relationField) :
                // we need the current model to be in existence
                $opDep[] = [
                    'target' => $addedModelName,
                    'type' => self::TYPE_MODEL,
                    'action' => self::ACTION_CREATED,
                ];

                // depend on the related model also
                $opDep[] = [
                    'target' => $relationField->relation->toModel->getMeta()->getNSModelName(),
                    'type' => self::TYPE_MODEL,
                    'action' => self::ACTION_CREATED,
                ];

                // if the through model was not automatically created, depend on it also
                if ($relationField->relation->hasProperty(
                        'through'
                    ) && null != !$relationField->relation &&
                    !$relationField->relation->through->getMeta()->autoCreated
                ):

                    $opDep[] = [
                        'target' => $relationField->relation->through->getMeta()->getNSModelName(),
                        'type' => self::TYPE_MODEL,
                        'action' => self::ACTION_CREATED,
                    ];
                endif;

                //create the operation
                $this->addOperation(
                    $meta->getAppName(),
                    AddField::createObject(
                        [
                            'modelName' => $addedModelName,
                            'name' => $fieldName,
                            'field' => $relationField,
                        ]
                    ),
                    $opDep
                );
            endforeach;

        endforeach;
    }

    /**
     * Find all deleted models (managed and unmanaged) and make delete operations for them as well as separate
     * operations to delete any foreign key or M2M relationships.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateDeleteModel()
    {
        $newModelKeys = array_merge(
            $this->newModelKeys,
            $this->newUnmanagedKeys
        );
        $deletedModels = array_diff($this->oldModelKeys, $newModelKeys);
        $deletedUnmanagedModels = array_diff(
            $this->oldUnmanagedKeys,
            $newModelKeys
        );
        $allDeletedModels = array_merge(
            $deletedModels,
            $deletedUnmanagedModels
        );

        /* @var $modelState ModelState */
        foreach ($allDeletedModels as $deletedModel) :

            $modelState = $this->fromState->getModelState($deletedModel);
            $meta = $this->fromState->getRegistry()
                ->getModel($deletedModel)->getMeta();

            // at this point if we the model is un manged just stop ,
            // since we just need to have it recorded in our
            // migrations, we don't need to create the relationship fields also,
            // its not our problem anymore
            if (!$meta->managed):
                continue;
            endif;

            $localFields = $meta->localFields;
            $localM2MFields = $meta->localManyToMany;

            $relatedFields = [];

            // get all the relationship fields that we initiated since they
            // will need to be created on there own  operations aside from
            //  the one that creates the model
            // remember the model needs to exist first before enforcing the
            // relationships.

            /** @var $localField Field */
            foreach ($localFields as $localField) :
                if ($localField->relation && $localField->relation->toModel):
                    $relatedFields[$localField->getName()] = $localField;
                endif;

            endforeach;

            /** @var $localM2MField Field */
            foreach ($localM2MFields as $localM2MField) :
                if ($localField->relation && $localField->relation->toModel):
                    $relatedFields[$localM2MField->getName()] = $localM2MField;
                endif;

            endforeach;

            // take care of relationships
            foreach ($relatedFields as $fieldName => $relationField) :
                //remove the operation
                $this->addOperation(
                    $meta->getAppName(),
                    RemoveField::createObject(
                        [
                            'modelName' => $deletedModel,
                            'name' => $fieldName,
                        ]
                    )
                );
            endforeach;

            $opDep = [];

            // we also need to drop all relationship fields that point to us,
            // initiated by other models.
            $reverseRelatedFields = $meta->getReverseRelatedObjects();

            /** @var $reverseRelatedField RelatedField */
            foreach ($reverseRelatedFields as $reverseRelatedField) :
                $modelName = $reverseRelatedField
                    ->relation->getFromModel()->getMeta()->getNSModelName();
                $fieldName = $reverseRelatedField->relation->fromField->getName();
                $opDep[] = [
                    'target' => $fieldName,
                    'model' => $modelName,
                    'type' => self::TYPE_FIELD,
                    'action' => self::ACTION_DROPPED,
                ];
                if (!$reverseRelatedField->relation->isManyToMany()):
                    $opDep[] = [
                        'target' => $fieldName,
                        'model' => $modelName,
                        'type' => self::TYPE_FIELD,
                        'action' => self::ACTION_ALTER,
                    ];
                endif;
            endforeach;

            // finally remove the model
            $this->addOperation(
                $meta->getAppName(),
                DeleteModel::createObject(['name' => $modelState->name]),
                $opDep
            );
            
        endforeach;
    }

    /**
     * Finds any renamed models, and generates the operations for them, and removes the old entry from the model lists.
     * Must be run before other model-level generation.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateRenamedModels()
    {
        $addedModels = array_diff($this->newModelKeys, $this->oldModelKeys);

        /* @var $modelState ModelState */
        foreach ($addedModels as $addedModel) :
            $modelState = $this->toState->getModelState($addedModel);
            $meta = $this->toState->getRegistry()->getModel($addedModel)->getMeta();
            $modelDefinitionList = $this->getFieldsDefinitions(
                $modelState->fields
            );

            $removedModels = array_diff(
                $this->oldModelKeys,
                $this->newModelKeys
            );
            foreach ($removedModels as $removedModel) :

                $remModelState = $this->fromState->getModelState($removedModel);
                $remModelDefinitionList = $this->getFieldsDefinitions(
                    $remModelState->fields
                );

                if ($remModelDefinitionList == $modelDefinitionList):

                    if (MigrationQuestion::hasModelRenamed(
                        $this->asker,
                        $removedModel,
                        $addedModel
                    )):

                        $this->addOperation(
                            $meta->getAppName(),
                            RenameModel::createObject(
                                [
                                    'oldName' => $removedModel,
                                    'newName' => $addedModel,
                                ]
                            )
                        );

                        $this->renamedModels[$addedModel] = $removedModel;

                        // remove the old name and update with the new name.
                        $pos = array_search($removedModel, $this->oldModelKeys);

                        array_splice(
                            $this->oldModelKeys,
                            $pos,
                            1,
                            [$addedModel]
                        );

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
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateCreatedProxies()
    {
        $addedProxies = array_diff($this->newProxyKeys, $this->oldProxyKeys);

        /* @var $modelState ModelState */
        foreach ($addedProxies as $addedProxy) :
            $meta = $this->toState->getRegistry()->getModel($addedProxy)->getMeta();
            $modelState = $this->toState->getModelState($addedProxy);
            assert($modelState->getMeta()['proxy']);

            $opDep = [
                [
                    'target' => $addedProxy,
                    'type' => self::TYPE_MODEL,
                    'action' => self::ACTION_DROPPED,
                ],
            ];
            // create operation
            $this->addOperation(
                $meta->getAppName(),
                CreateModel::createObject(
                    [
                        'name' => $modelState->name,
                        'fields' => [],
                        'meta' => $modelState->getMeta(),
                        'extends' => $modelState->extends,
                    ]
                ),
                $opDep
            );
        endforeach;
    }

    /**
     *  Makes DeleteModel statements for proxy models.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateDeletedProxies()
    {
        $droppedProxies = array_diff($this->oldProxyKeys, $this->newProxyKeys);

        foreach ($droppedProxies as $droppedProxy) :
            $modelState = $this->fromState->getModelState($droppedProxy);
            $meta = $this->fromState->getRegistry()->getModel($droppedProxy)
                ->getMeta();

            // create operation
            $this->addOperation(
                $meta->getAppName(),
                DeleteModel::createObject(
                    [
                        'name' => $modelState->name,
                    ]
                )
            );
        endforeach;
    }

    /**
     * Works out if any non-schema-affecting options have changed and makes an operation to represent them in state
     * changes.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateAlteredMeta()
    {
        //get unmanaged converted to managed
        $managed = array_intersect(
            $this->oldUnmanagedKeys,
            $this->newModelKeys
        );

        //get managed converted to unmanaged
        $unmanaged = array_intersect(
            $this->oldModelKeys,
            $this->newUnmanagedKeys
        );

        $modelsToCheck = array_merge(
            $this->keptProxyKeys,
            $this->keptUnmanagedKeys,
            $managed,
            $unmanaged
        );

        $modelsToCheck = array_unique($modelsToCheck);

        /* @var $oldState ModelState */
        /* @var $newState ModelState */
        foreach ($modelsToCheck as $modelName) :
            $oldModelName = $this->getOldModelName($modelName);
            $oldState = $this->fromState->getModelState($oldModelName);
            $newState = $this->toState->getModelState($modelName);

            $oldMeta = [];

            if ($oldState->getMeta()):
                foreach ($oldState->getMeta() as $name => $opt) :
                    if (AlterModelMeta::isAlterableOption($name)):
                        $oldMeta[$name] = $opt;
                    endif;
                endforeach;
            endif;

            $newMeta = [];
            if ($newState->getMeta()):
                foreach ($newState->getMeta() as $name => $opt) :
                    if (AlterModelMeta::isAlterableOption($name)):
                        $newMeta[$name] = $opt;
                    endif;
                endforeach;
            endif;

            if ($oldMeta !== $newMeta):
                $this->addOperation(
                    $newMeta->getAppName(),
                    AlterModelMeta::createObject(
                        [
                            'name' => $modelName,
                            'meta' => $newMeta,
                        ]
                    )
                );
            endif;
        endforeach;
    }

    /**
     * Works out renamed fields.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateRenamedFields()
    {
        /* @var $oldModelState ModelState */
        /* @var $field Field */
        foreach ($this->keptModelKeys as $modelName) :
            $oldModelName = $this->getOldModelName($modelName);
            $meta = $this->toState->getRegistry()->getModel($modelName)->getMeta();
            $oldModelState = $this->fromState->getModelState($oldModelName);

            $newModel = $this->newRegistry->getModel($modelName);

            $oldFieldKeys = $this->oldFieldKeys[$modelName];
            $newFieldKeys = $this->newFieldKeys[$modelName];

            $addedFields = array_diff($newFieldKeys, $oldFieldKeys);

            foreach ($addedFields as $addedField) :
                $field = $newModel->getMeta()->getField($addedField);
                $fieldDef = $this->deepDeconstruct($field);

                $removedFields = array_diff($oldFieldKeys, $newFieldKeys);
                foreach ($removedFields as $remField) :
                    $oldFieldDef = $this->deepDeconstruct(
                        $oldModelState->getFieldByName($remField)
                    );

                    if (null !== $field->relation && null !== $field->relation->toModel &&
                        isset($oldFieldDef['constructorArgs']['to'])
                    ):
                        $oldRelTo = $oldFieldDef['constructorArgs']['to'];
                        if (in_array($oldRelTo, $this->renamedModels)):
                            $oldFieldDef['constructorArgs']['to'] = $this->getOldModelName(
                                $oldRelTo
                            );
                        endif;
                    endif;

                    if ($fieldDef === $oldFieldDef):
                        if (MigrationQuestion::hasFieldRenamed(
                            $this->asker,
                            $modelName,
                            $remField,
                            $addedField,
                            $field
                        )
                        ):

                            $this->addOperation(
                                $meta->getAppName(),
                                RenameField::createObject(
                                    [
                                        'modelName' => $modelName,
                                        'oldName' => $remField,
                                        'newName' => $addedField,
                                    ]
                                )
                            );

                            // remove the old name and update with the new name.
                            $pos = array_search(
                                $remField,
                                $this->oldFieldKeys[$modelName]
                            );

                            array_splice(
                                $this->oldFieldKeys[$modelName],
                                $pos,
                                1,
                                [$addedField]
                            );
                            $this->renamedFields[$modelName][$addedField] = $remField;

                            break;

                        endif;
                    endif;
                endforeach;

            endforeach;

        endforeach;
    }

    /**
     * Fields that have been added.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateAddedFields()
    {
        foreach ($this->keptModelKeys as $modelName) :

            $oldFieldKeys = $this->oldFieldKeys[$modelName];
            $newFieldKeys = $this->newFieldKeys[$modelName];

            $addedFields = array_diff($newFieldKeys, $oldFieldKeys);

            foreach ($addedFields as $addedField) :
                $this->findAddedFields($modelName, $addedField);
            endforeach;
        endforeach;
    }

    /**
     * Fields that have been removed.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateRemovedFields()
    {
        foreach ($this->keptModelKeys as $modelName) :

            $oldFieldKeys = $this->oldFieldKeys[$modelName];
            $newFieldKeys = $this->newFieldKeys[$modelName];

            $remFields = array_diff($oldFieldKeys, $newFieldKeys);

            foreach ($remFields as $remField) :
                $this->findRemovedFields($modelName, $remField);
            endforeach;
        endforeach;
    }

    /**
     * Fields that have been altered.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function generateAlteredFields()
    {
        /* @var $oldField Field */
        /* @var $newField Field */
        foreach ($this->keptModelKeys as $modelName) :

            $oldFieldKeys = $this->oldFieldKeys[$modelName];
            $newFieldKeys = $this->newFieldKeys[$modelName];

            $keptFieldKeys = array_intersect($oldFieldKeys, $newFieldKeys);

            $oldModelName = $this->getOldModelName($modelName);

            foreach ($keptFieldKeys as $keptField) :
                $oldFieldName = $this->getOldFieldName($modelName, $keptField);
                $oldField = $this->oldRegistry->getModel(
                    $oldModelName
                )->getMeta()->getField($oldFieldName);

                $meta = $this->newRegistry->getModel(
                    $modelName
                )->getMeta();
                $newField = $meta->getField($keptField);

                $oldDec = $this->deepDeconstruct($oldField);
                $newDec = $this->deepDeconstruct($newField);

                if ($oldDec !== $newDec):
                    $bothM2M = ($newField instanceof ManyToManyField &&
                        $oldField instanceof ManyToManyField);
                    $neitherM2M = (!$newField instanceof ManyToManyField &&
                        !$oldField instanceof ManyToManyField);

                    // Either both fields are m2m or neither is
                    if ($bothM2M || $neitherM2M):
                        $preserveDefault = true;

                        if ($oldField->isNull() && !$newField->isNull() &&
                            !$newField->hasDefault() &&
                            !$newField instanceof ManyToManyField
                        ):
                            $field = $newField->deepClone();
                            $default = MigrationQuestion::askNotNullAlteration(
                                $this->asker,
                                $modelName,
                                $keptField
                            );

                            if (NOT_PROVIDED !== $default):
                                $field->default = $default;
                                $preserveDefault = false;
                            endif;
                        else:
                            $field = $newField;
                        endif;

                        $this->addOperation(
                            $meta->getAppName(),
                            AlterField::createObject(
                                [
                                    'modelName' => $modelName,
                                    'name' => $keptField,
                                    'field' => $field,
                                    'preserveDefault' => $preserveDefault,
                                ]
                            )
                        );
                    else:
                        // We cannot alter between m2m and concrete fields
                        $this->findRemovedFields($modelName, $keptField);
                        $this->findAddedFields($modelName, $keptField);
                    endif;
                endif;

            endforeach;

        endforeach;
    }

    public function generateAlteredDbTable()
    {
        $modelToCheck = array_merge(
            $this->keptModelKeys,
            $this->keptProxyKeys,
            $this->keptUnmanagedKeys
        );

        /* @var $oldModelState ModelState */
        /* @var $newModelState ModelState */
        foreach ($modelToCheck as $modelName) :
            $oldModelName = $this->getOldModelName($modelName);
            $oldModelState = $this->fromState->getModelState($oldModelName);
            $newModelState = $this->toState->getModelState($oldModelName);
            $meta = $this->toState->getRegistry()->getModel($modelName)->getMeta();
            $oldDbTableName = (!isset($oldModelState->getMeta()['dbTable'])) ? '' : $oldModelState->getMeta(
            )['dbTable'];
            $newDbTableName = (!isset($newModelState->getMeta()['dbTable'])) ? '' : $newModelState->getMeta(
            )['dbTable'];

            if ($oldDbTableName !== $newDbTableName) :
                $this->addOperation(
                    $meta->getAppName(),
                    AlterModelTable::createObject(
                        [
                            'name' => $modelName,
                            'table' => $newDbTableName,
                        ]
                    )
                );
            endif;
        endforeach;
    }

    /**
     * Does the actual add of the field.
     *
     * @param $modelName
     * @param $fieldName
     *
     * @since  1.1.0
     *
     * @throws \Eddmash\PowerOrm\Exception\FieldDoesNotExist
     * @throws \Eddmash\PowerOrm\Exception\LookupError
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function findAddedFields($modelName, $fieldName)
    {
        $meta = $this->newRegistry->getModel($modelName)->getMeta();
        /** @var $field Field */
        $field = $meta->getField(
            $fieldName
        );
        $opDep = [];

        $preserveDefault = true;

        if (null !== $field->relation && $field->relation->toModel):

            // depend on related model being created
            $opDep[] = [
                'target' => $field->relation->toModel->getMeta()->getNSModelName(),
                'type' => self::TYPE_MODEL,
                'action' => self::ACTION_CREATED,
            ];

            // if it has through also depend on through model being created
            if ($field->relation->hasProperty('through') &&
                null != $field->relation->through &&
                !$field->relation->through->getMeta()->autoCreated
            ):

                $opDep[] = [
                    'target' => $field->relation->through->getMeta()->getNSModelName(),
                    'type' => self::TYPE_MODEL,
                    'action' => self::ACTION_CREATED,
                ];

            endif;

        endif;

        if (!$field->isNull() && !$field->hasDefault() && !$field instanceof ManyToManyField):
            $def = MigrationQuestion::askNotNullAddition(
                $this->asker,
                $modelName,
                $fieldName,
                $field
            );
            $field = $field->deepClone();
            $field->default = $def;
            $preserveDefault = false;
        endif;

        $this->addOperation(
            $meta->getAppName(),
            AddField::createObject(
                [
                    'modelName' => $modelName,
                    'name' => $fieldName,
                    'field' => $field,
                    'preserveDefault' => $preserveDefault,
                ]
            ),
            $opDep
        );
    }

    private function findRemovedFields($modelName, $fieldName)
    {
        $meta = $this->newRegistry->getModel($modelName)->getMeta();
        $this->addOperation(
            $meta->getAppName(),
            RemoveField::createObject(
                [
                    'modelName' => $modelName,
                    'name' => $fieldName,
                ]
            )
        );
    }

    private function sortOperations()
    {
        // get a map of operations and what operations the depend on.
        $graphDependency = [];
        foreach ($this->generatedOperations as $index => $operation) :
            $graphDependency[$index] = [];
        
            //check if a dependency exists on the current ops
            foreach ($operation->getDependency() as $dep) :

                foreach ($this->generatedOperations as $inIndex => $operation2) :

                    if ($this->checkDependency($operation2, $dep)):
                        $graphDependency[$index][] = $inIndex;
                    endif;
                endforeach;

            endforeach;
        endforeach;

        $this->generatedOperations = $this->topologicalSort(
            $this->generatedOperations,
            $graphDependency
        );
    }

    public function sortModels()
    {
    }

    /**
     * sorts the operations in topological order using kahns algorithim.
     *
     * @param $operations
     * @param $dependency
     *
     * @return array
     *
     * @throws ValueError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function topologicalSort($operations, $dependency)
    {
        $sorted = $arranged = [];
        $deps = $dependency;

        while ($deps):

            $noDeps = [];

            foreach ($deps as $index => $dep) :
                if (!$dep):
                    $noDeps[] = $index;
                endif;
            endforeach;

            if (!$noDeps):
                throw new ValueError('Cyclic dependency on topological sort');
            endif;

            $arranged = array_merge($arranged, $noDeps);

            $newDeps = [];
            foreach ($deps as $index => $dep) :
                if (!in_array($index, $noDeps)):
                    $parents = array_diff($dep, $noDeps);
                    $newDeps[$index] = $parents;
                endif;
            endforeach;
            $deps = $newDeps;

        endwhile;

        foreach ($arranged as $index) :
            $sorted[] = $operations[$index];
        endforeach;

        return $sorted;
    }
}
