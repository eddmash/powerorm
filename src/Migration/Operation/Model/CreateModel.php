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

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Migration\Operation\Field\AddField;
use Eddmash\PowerOrm\Migration\Operation\Field\FieldOperation;
use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ModelState;
use Eddmash\PowerOrm\Model\Field\ManyToManyField;
use Eddmash\PowerOrm\Model\Meta;
use Eddmash\PowerOrm\Model\Model;

class CreateModel extends ModelOperation
{
    public $fields;
    /**
     * @var Meta
     */
    public $meta;

    public $extends;

    public function getDescription()
    {
        return sprintf(
            'Create %smodel %s',
            (isset($this->meta['proxy']) && $this->meta['proxy']) ? 'proxy ' : '',
            $this->name
        );
    }

    public function getConstructorArgs()
    {
        $constructorArgs = parent::getConstructorArgs();
        if (isset($constructorArgs['meta']) && empty($constructorArgs['meta'])):
            unset($constructorArgs['meta']);
        endif;

        if (isset($constructorArgs['extends'])):

            if (StringHelper::isEmpty($constructorArgs['extends']) || Model::isModelBase($constructorArgs['extends'])):

                unset($constructorArgs['extends']);
            else:
                $constructorArgs['extends'] =
                    ClassHelper::getNameFromNs($constructorArgs['extends'], BaseOrm::getModelsNamespace());
            endif;
        endif;

        return $constructorArgs;
    }

    /**
     * {@inheritdoc}
     */
    public function updateState($state)
    {
        $state->addModelState(
            ModelState::createObject(
                $this->name,
                $this->fields,
                ['meta' => $this->meta, 'extends' => $this->extends]
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function databaseForwards($schemaEditor, $fromState, $toState)
    {
        $model = $toState->getRegistry()->getModel($this->name);
        if ($this->allowMigrateModel($schemaEditor->connection, $model)):
            $schemaEditor->createModel($model);
        endif;
    }

    public function databaseBackwards($schemaEditor, $fromState, $toState)
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
     * @param Operation   $operation
     * @param Operation[] $inBetween
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function reduce($operation, $inBetween)
    {
        if ($operation instanceof DeleteModel && $this->name === $operation->name && !$this->meta->proxy) :
            return [];
        endif;

        if ($operation instanceof FieldOperation && strtolower($operation->modelName) === strtolower($this->name)) :
            echo $this.PHP_EOL;
            echo $operation.PHP_EOL;
            if ($operation instanceof AddField) :
                // check if there is an operation in between that references the same model if so, don't merge
                if ($operation->field->relation) :

//                    if($operation->field instanceof ManyToManyField):
//                        echo "$modelName %%% ".PHP_EOL;
//                    endif;
                    foreach ($inBetween as $between) :
                        $modelName = $operation->field->relation->toModel->meta->modelName;
                        if ($between->referencesModel($modelName)) :
                            return false;
                        endif;

                        if ($operation->field->relation->hasProperty('through') &&
                            $operation->field->relation->through
                        ) :
                            $modelName = $operation->field->relation->through->meta->modelName;
                            if ($between->referencesModel($modelName)) :
                                return false;
                            endif;
                        endif;
                        echo PHP_EOL;
                    endforeach;
                endif;

                $fields = $this->fields;
                $fields[$operation->field->name] = $operation->field;
                echo '************************'.PHP_EOL;

                return [
                    static::createObject(
                        [
                            'name' => $this->name,
                            'fields' => $fields,
                            'meta' => $this->meta,
                            'extends' => $this->extends,
                        ]
                    ),
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
}
