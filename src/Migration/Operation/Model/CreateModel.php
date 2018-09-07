<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration\Operation\Model;

use Eddmash\PowerOrm\Db\SchemaEditor;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Migration\Operation\Field\AddField;
use Eddmash\PowerOrm\Migration\Operation\Field\FieldOperation;
use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ModelState;
use Eddmash\PowerOrm\Migration\State\ProjectState;
use Eddmash\PowerOrm\Model\Meta;
use Eddmash\PowerOrm\Model\Model;

class CreateModel extends ModelOperation
{
    public $fields;

    /**
     * @var Meta
     */
    protected $meta = [];

    public $extends;

    public function getDescription()
    {
        return sprintf(
            'Create %smodel %s',
            (isset($this->getMeta()['proxy']) && $this->getMeta()['proxy']) ? 'proxy ' : '',
            $this->name
        );
    }

    public function getConstructorArgs()
    {
        $constructorArgs = parent::getConstructorArgs();
        if (isset($constructorArgs['meta']) &&
            isset($constructorArgs['meta']['appName'])):
            unset($constructorArgs['meta']['appName']);
        endif;
        if (isset($constructorArgs['meta']) && empty($constructorArgs['meta'])):
            unset($constructorArgs['meta']);
        endif;

        if (isset($constructorArgs['extends'])):

            if (StringHelper::isEmpty($constructorArgs['extends']) ||
                Model::isModelBase($constructorArgs['extends'])):
                unset($constructorArgs['extends']);

            endif;
        endif;

        return $constructorArgs;
    }

    /**
     * {@inheritdoc}
     */
    public function updateState(ProjectState $state)
    {
        $this->getMeta()['appName'] = $this->getAppLabel();
        $state->addModelState(
            ModelState::createObject(
                $this->name,
                $this->fields,
                ['meta' => $this->getMeta(), 'extends' => $this->extends]
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function databaseForwards(SchemaEditor $schemaEditor, ProjectState $fromState, ProjectState $toState)
    {
        $model = $toState->getRegistry()->getModel($this->name);
        if ($this->allowMigrateModel($schemaEditor->connection, $model)):
            $schemaEditor->createModel($model);
        endif;
    }

    /**
     * {@inheritdoc}
     */
    public function databaseBackwards(SchemaEditor $schemaEditor, ProjectState $fromState, ProjectState $toState)
    {
        $model = $fromState->getRegistry()->getModel($this->name);
        if ($this->allowMigrateModel($schemaEditor->connection, $model)):
            $schemaEditor->deleteModel($model);
        endif;
    }

    /**
     * Return either a list of operations the actual operation should be
     * replaced with or a boolean that indicates whether or not the specified
     * operation can be optimized across.
     *
     * @param Operation $operation
     * @param Operation[] $inBetween
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function reduce(Operation $operation, $inBetween)
    {
        if ($operation instanceof DeleteModel &&
            $this->name === $operation->name && !$this->getMeta()->proxy) :
            return [];
        endif;

        if ($operation instanceof FieldOperation &&
            strtolower($operation->modelName) === strtolower($this->name)) :

            if ($operation instanceof AddField) :
                // check if there is an operation in between that references
                // the same model if so, don't merge
                if ($operation->field->relation) :
                    foreach ($inBetween as $between) :
                        $modelName = $operation->field->relation
                            ->toModel->getMeta()->getNSModelName();
                        if ($between->referencesModel($modelName)) :
                            return false;
                        endif;

                        if ($operation->field->relation
                                ->hasProperty('through') &&
                            $operation->field->relation->through
                        ) :
                            $modelName = $operation->field->relation
                                ->through->getMeta()->getNSModelName();
                            if ($between->referencesModel($modelName)) :
                                return false;
                            endif;
                        endif;
                    endforeach;
                endif;

                $fields = $this->fields;
                $fields[$operation->field->getName()] = $operation->field;

                $op = static::createObject(
                    [
                        'name' => $this->name,
                        'fields' => $fields,
                        'meta' => $this->getMeta(),
                        'extends' => $this->extends,
                    ]
                );
                $op->setAppLabel($this->getAppLabel());

                return [
                    $op,
                ];

            endif;
        endif;

        return parent::reduce($operation, $inBetween);
    }

    public function __toString()
    {
        return sprintf('%s <%s>', get_class($this), $this->name);
    }

    public function __debugInfo()
    {
        $arr = parent::__debugInfo();
        $arr['fields'] = array_keys($this->fields);

        return $arr;
    }

    public function getMeta()
    {
        $this->meta['appName'] = $this->getAppLabel();

        return $this->meta;
    }

    /**
     * @param Meta $meta
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;
    }
}
