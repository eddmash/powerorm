<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Field;

use Eddmash\PowerOrm\Checks\CheckWarning;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Model\Delete;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ManyToManyRel;
use Eddmash\PowerOrm\Model\Meta;
use Eddmash\PowerOrm\Model\Model;

/**
 * Provide a many-to-many relation by using an intermediary model that holds two ForeignKey fields pointed at the two
 * sides of the relation.
 *
 * Unless a ``through`` model was provided, ManyToManyField will use the createManyToManyIntermediaryModel factory
 * to automatically generate the intermediary model.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ManyToManyField extends RelatedField
{
    /**
     * {@inheritdoc}
     */
    public $manyToMany = true;

    public $dbTable;

    /**
     * {@inheritdoc}
     *
     * @var ManyToManyRel
     */
    public $relation;

    public function __construct($kwargs)
    {
        if (!isset($kwargs['rel']) || (isset($kwargs['rel']) && $kwargs['rel'] === null)):
            $kwargs['rel'] = ManyToManyRel::createObject([
                'fromField' => $this,
                'to' => ArrayHelper::getValue($kwargs, 'to'),
                'through' => ArrayHelper::getValue($kwargs, 'through'),
                'throughFields' => ArrayHelper::getValue($kwargs, 'throughFields'),
                'dbConstraint' => ArrayHelper::getValue($kwargs, 'dbConstraint', true),
            ]);
        endif;

        $this->hasNullKwarg = ArrayHelper::hasKey($kwargs, 'null');

        parent::__construct($kwargs);
    }

    /**
     * {@inheritdoc}
     */
    public function contributeToClass($fieldName, $modelObject)
    {
        parent::contributeToClass($fieldName, $modelObject);

        // if through model is set
        if ($this->relation->through !== null):
            $callback = function ($kwargs) {
                /* @var $field RelatedField */
                /** @var $related Model */
                $related = $kwargs['relatedModel'];
                $field = $kwargs['fromField'];

                $field->relation->through = $related;
                $field->doRelatedClass($related, $kwargs['scopeModel']);
            };

            Tools::lazyRelatedOperation($callback, $this->scopeModel, $this->relation->through, ['fromField' => $this]);
        else:
            $this->relation->through = $this->createManyToManyIntermediaryModel($this, $this->scopeModel);
        endif;
    }

    public function contributeToRelatedClass($relatedModel, $scopeModel)
    {
    }

    /**
     * Creates an intermediary model.
     *
     * @param ManyToManyField $field
     * @param Model           $model
     *
     * @return Model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function createManyToManyIntermediaryModel($field, $model)
    {

        $modelName = $model->meta->modelName;

        if (is_string($field->relation->toModel)):
            $toModelName = Tools::resolveRelation($model, $field->relation->toModel);
        else:
            $toModelName = $field->relation->toModel->meta->modelName;
        endif;

        $className = sprintf('%1$s_%2$s', $modelName, $field->name);
        $intermediaryClass = 'class %1$s extends %2$s{
            public function fields(){}
        }';
        $intermediaryClass = sprintf($intermediaryClass, $className, Model::getFullClassName());
        if (!class_exists($className, false)):
            eval($intermediaryClass);
        endif;

        $from = strtolower($modelName);
        $to = strtolower($toModelName);
        if ($from == $to):
            $to = sprintf('to_%s', $to);
            $from = sprintf('from_%s', $from);
        endif;
        $fields = [
            $from => ForeignKey::createObject([
                'to' => $modelName,
                'dbConstraint' => $field->relation->dbConstraint,
                'onDelete' => Delete::CASCADE,
            ]),
            $to => ForeignKey::createObject([
                'to' => $toModelName,
                'dbConstraint' => $field->relation->dbConstraint,
                'onDelete' => Delete::CASCADE,
            ]),
        ];

        $meta = [
            'dbTable' => $field->_getM2MDbTable($model->meta),
            'verboseName' => sprintf('%s-%s relationship', $from, $to),
            'uniqueTogether' => [$from, $to],
            'autoCreated' => true,
        ];

        $className = '\\'.$className;
        /** @var $intermediaryObj Model */
        $intermediaryObj = new $className();

        $intermediaryObj->init($fields, ['meta' => $meta, 'registry' => $field->scopeModel->meta->registry]);

        return $intermediaryObj;
    }

    /**
     * provides the m2m table name for this relation.
     *
     * @param Meta $meta
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function _getM2MDbTable($meta)
    {
        if ($this->relation->through !== null):
            return $this->relation->through->meta->dbTable;
        elseif ($this->dbTable):
            return $this->dbTable;
        else:
            // oracle allows identifier of 30 chars max
            return StringHelper::truncate(sprintf('%s_%s', $meta->dbTable, $this->name), 30);
        endif;
    }

    public function checks()
    {
        $checks = parent::checks();
        $checks = array_merge($checks, $this->_checkIgnoredKwargOptions());

        return $checks;
    }

    public function _checkIgnoredKwargOptions()
    {
        $warnings = [];
        if ($this->hasNullKwarg):
            $warnings = [
                CheckWarning::createObject([
                    'message' => sprintf('null has no effect on ManyToManyField.'),
                    'hint' => null,
                    'context' => $this,
                    'id' => 'fields.W340',
                ]),
            ];
        endif;

        return $warnings;
    }
}
