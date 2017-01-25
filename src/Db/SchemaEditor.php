<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Eddmash\PowerOrm\BaseObject;
use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Model\Field\AutoField;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\ForeignKey;
use Eddmash\PowerOrm\Model\Field\ManyToManyField;
use Eddmash\PowerOrm\Model\Model;

class SchemaEditor extends BaseObject
{
    /**
     * @var Connection
     */
    public $connection;

    /**
     * @var AbstractSchemaManager
     */
    private $schemaManager;

    /**
     * @param Connection $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->schemaManager = $this->connection->getSchemaManager();
    }

    /**
     * @param Connection $connection
     *
     * @return SchemaEditor
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function createObject($connection)
    {
        return new static($connection);
    }

    /**
     * Creates database table represented by the model.
     *
     * @param Model $model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function createModel($model)
    {
        $schema = $this->schemaManager->createSchema();
        $tableDef = $schema->createTable($model->meta->dbTable);

        // this assumes fields set_from_name has been invoked
        $primaryKeyFields = [];
        $unique_fields = [];
        $indexes = [];

        /** @var $field Field */
        /** @var $field ForeignKey */
        foreach ($model->meta->localFields as $fname => $field) :
            $colName = $field->getColumnName();
            $type = $field->dbType($this->connection);

            // if we don't have a type stop
            if (empty($type)):
                continue;
            endif;

            if ($field->primaryKey):
                $primaryKeyFields[] = $model->meta->primaryKey->getColumnName();
            elseif ($field->isUnique()):
                $unique_fields[] = $colName;
            elseif ($field->dbIndex):
                $indexes[] = $colName;
            endif;

            $tableDef->addColumn($colName, $type, $this->getDoctrineColumnOptions($field));

            if ($field->isRelation && $field->relation && $field->dbConstraint):
                $relField = $field->getRelatedField();
                $tableDef->addForeignKeyConstraint(
                    $relField->scopeModel->meta->dbTable,
                    [$field->getColumnName()],
                    [$relField->getColumnName()]
                );
            endif;
        endforeach;

        // create the primary key
        $tableDef->setPrimaryKey($primaryKeyFields);

        // add index constraint
        if (!empty($indexes)):
            $tableDef->addIndex($indexes);
        endif;
        // add unique constraint
        if (!empty($unique_fields)):
            $tableDef->addUniqueIndex($unique_fields);
        endif;

        $this->schemaManager->createTable($tableDef);

        // many to many
        /** @var $relationField ManyToManyField */
        foreach ($model->meta->localManyToMany as $name => $relationField) :
            if ($relationField->manyToMany && $relationField->relation->through->meta->autoCreated):
                $this->createModel($relationField->relation->through);
            endif;
        endforeach;
    }

    /**
     * Drop database represented by the model.
     *
     * @param Model $model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function deleteModel($model)
    {

        // first remove any automatically created models
        /** @var $relationField ManyToManyField */
        foreach ($model->meta->localManyToMany as $name => $relationField) :
            if ($relationField->relation->through->meta->autoCreated):
                $this->deleteModel($relationField->relation->through);
            endif;
        endforeach;

        $this->schemaManager->dropTable($model->meta->dbTable);
    }

    /**
     * Renames the table a model points to.
     *
     * @param Model  $model
     * @param string $oldDbTableName
     * @param string $newDbTableName
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function alterDbTable($model, $oldDbTableName, $newDbTableName)
    {
        if ($oldDbTableName === $newDbTableName):
            return;
        endif;

        $this->schemaManager->renameTable($oldDbTableName, $newDbTableName);
    }

    /**
     * Creates a field on a model.
     *
     * Usually involves adding a column, but may involve adding a table instead (for M2M fields).
     *
     * @param Model $model
     * @param Field $field
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addField($model, $field)
    {
        $schema = $this->schemaManager->createSchema();
        // many to many
        if ($field->manyToMany && $field->relation->through->meta->autoCreated):
            $this->createModel($field->relation->through);

            return;
        endif;

        $type = $field->dbType($this->connection);
        $name = $field->getColumnName();
        $fieldOptions = $this->getDoctrineColumnOptions($field, true);

        // It might not actually have a column behind it
        if (empty($type)):
            return;
        endif;

        $tableDef = clone $schema->getTable($model->meta->dbTable);

        // normal column
        $tableDef->addColumn($name, $type, $fieldOptions);

        // unique constraint
        if ($field->isUnique()):
            $tableDef->addUniqueIndex([$name]);
        endif;

        // index constraint
        if ($field->dbIndex && !$field->isUnique()):
            $tableDef->addIndex([$name]);
        endif;

        /* @var $field ForeignKey */
        if ($field->isRelation && $field->relation && $field->dbConstraint):
            $relField = $field->getRelatedField();

            $tableDef->addForeignKeyConstraint(
                $relField->scopeModel->meta->dbTable,
                [$field->getColumnName()],
                [$relField->getColumnName()]
            );
        endif;

        $comparator = new Comparator();
        $diff = $comparator->diffTable($schema->getTable($model->meta->dbTable), $tableDef);

        if ($diff !== false):
            $this->schemaManager->alterTable($diff);
        endif;
    }

    /**
     * Removes a field from a model. Usually involves deleting a column, but for M2Ms may involve deleting a table.
     *
     * @param Model $model
     * @param Field $field
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function removeField($model, $field)
    {
        $schema = $this->schemaManager->createSchema();
        // Special-case implicit M2M tables
        if ($field->manyToMany && $field->relation->through->meta->autoCreated):
            $this->deleteModel($field->relation->through);

            return;
        endif;

        $type = $field->dbType($this->connection);
        $name = $field->getColumnName();
        $fieldOptions = $this->getDoctrineColumnOptions($field);
        // It might not actually have a column behind it
        if (empty($type)):
            return;
        endif;

        $table = $model->meta->dbTable;
        $tableDef = clone $schema->getTable($table);

        // Drop any FK constraints, MySQL requires explicit deletion
        if ($field->isRelation && $field->relation !== null):
            foreach ($this->constraintName($table, $name, ['foreignKey' => true]) as $fkConstraint) :
                $tableDef->removeForeignKey($fkConstraint);
            endforeach;
        endif;

        // remove column
        $tableDef->dropColumn($name);

        $comparator = new Comparator();
        $diff = $comparator->diffTable($schema->getTable($table), $tableDef);

        if ($diff !== false):
            $this->schemaManager->alterTable($diff);
        endif;
    }

    /**
     * Allows a field's type, uniqueness, nullability, default, column,  constraints etc. to be modified.
     *
     * Requires a copy of the old field as well so we can only perform changes that are required.
     * If strict is true, raises errors if the old column does not match old_field precisely.
     *
     * @param Model      $model
     * @param Field      $oldField
     * @param Field      $newField
     * @param bool|false $strict
     *
     * @throws ValueError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function alterField($model, $oldField, $newField, $strict = false)
    {
        $oldType = $oldField->dbType($this->connection);
        $newType = $newField->dbType($this->connection);

        if (($oldType == null && $oldField->relation == null) || ($newType == null && $newField->relation == null)):
            throw new ValueError(sprintf('Cannot alter field %s into %s - they do not properly define '.
                'db_type (are you using a badly-written custom field?)', $newField->name, $oldField->name));
        elseif ($oldType == null && $newType == null &&
            (
                $oldField->relation->through != null &&
                $newField->relation->through != null &&
                $oldField->relation->through->meta->autoCreated &&
                $newField->relation->through->meta->autoCreated
            )
        ):
            $this->_alterManyToMany($model, $oldField, $newField, $strict);
        elseif ($oldType == null && $newType == null &&
            (
                $oldField->relation->through != null &&
                $newField->relation->through != null &&
                !$oldField->relation->through->meta->autoCreated &&
                !$newField->relation->through->meta->autoCreated
            )
        ):
            return;
        else:
            throw new  ValueError(sprintf('Cannot alter field %s into %s - they are not compatible types '.
                '(you cannot alter to or from M2M fields, or add or remove through= on M2M fields)',
                $oldField->name, $newField->name));
        endif;

        $this->_alterField($model, $oldField, $newField, $strict);
    }

    /**
     * Alters M2Ms to repoint their to= endpoints.
     *
     * @param Model      $model
     * @param Field      $oldField
     * @param Field      $newField
     * @param bool|false $strict
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function _alterManyToMany($model, $oldField, $newField, $strict = false)
    {
        //Rename the through table
        if ($oldField->relation->through->meta->dbTable != $newField->relation->through->meta->dbTable):
            $this->alterDbTable(
                $oldField->relation->through,
                $oldField->relation->through->meta->dbTable,
                $newField->relation->through->meta->dbTable
            );
        endif;
    }

    /**
     * Actually perform a "physical" (non-ManyToMany) field update.
     *
     * @param $model
     * @param $oldField
     * @param $newField
     * @param bool|false $strict
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function _alterField($model, $oldField, $newField, $strict = false)
    {
    }

    /**
     * @param Field      $field
     * @param bool|false $includeDefault
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getDoctrineColumnOptions($field, $includeDefault = false)
    {
        $options = [];

        // set constraint
        if ($field->hasProperty('maxLength') && $field->maxLength):
            $options['length'] = $field->maxLength;
        endif;

        if ($field->hasProperty('maxDigits') && $field->maxDigits):
            $options['precision'] = $field->maxDigits;
        endif;

        if ($field->hasProperty('decimalPlaces') && $field->decimalPlaces):
            $options['scale'] = $field->decimalPlaces;
        endif;

        // for columns that can be signed
        if ($field->hasProperty('signed') && $field->signed !== null):
            $options['unsigned'] = $field->signed === false;
        endif;

        if ($field->hasDefault() && $includeDefault):

            // the default value
            $default_value = $this->effectiveDefault($field);

            // if value is provided, create the defualt
            if ($default_value != NOT_PROVIDED):
                $options['default'] = $default_value;
            endif;

        endif;

        // the null option
        if ($field->null):
            $options['notnull'] = $field->null;
        endif;

        // the comment option
        if ($field->comment):
            $options['comment'] = $field->comment;
        endif;

        // auto increament option
        if ($field instanceof AutoField):
            $options['autoincrement'] = true;
        endif;

        return $options;
    }

    public function constraintName($table, $column, $constraintType)
    {
        $schema = $this->schemaManager->createSchema();
        $unique = $primaryKey = $index = $foreignKey = null;
        extract($constraintType);

        $fieldConstraints = [];

        if ($foreignKey):
            $foreignKeys = $schema->getTable($table)->getForeignKeys();

            /** @var $fk ForeignKeyConstraint */
            foreach ($foreignKeys as $fk) :
                if (in_array($column, $fk->getLocalColumns())):
                    $fieldConstraints[] = $fk->getName();
                endif;
            endforeach;
        endif;

        return $fieldConstraints;
    }

    public function prepareDefault($field)
    {
    }

    /**
     * @param Field $field
     *
     * @return mixed|null|string
     *
     * @throws NotImplemented
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function effectiveDefault($field)
    {
        if ($field->hasDefault()):
            $default = $field->getDefault();
        elseif (($field->hasProperty('autoNow') && $field->autoNow) ||
            ($field->hasProperty('addAutoNow') && $field->addAutoNow)):
            throw new NotImplemented('Please implement the date defaults');
        else:
            $default = null;
        endif;

        if (is_callable($default)):
            $default = $default();
        endif;

        return $default;
    }
}
