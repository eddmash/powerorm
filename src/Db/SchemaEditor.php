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
use Doctrine\DBAL\Schema\Schema;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\ForeignKey;
use Eddmash\PowerOrm\Model\Field\ManyToManyField;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Object;

class SchemaEditor extends Object
{
    /**
     * @var Connection
     */
    public $connection;
    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var AbstractSchemaManager
     */
    private $schemaManager;

    /**
     * @param Connection $connection
     */
    public function __construct($connection) {
        $this->connection = $connection;
        $this->schemaManager = $this->connection->getSchemaManager();
        $this->schema = $this->schemaManager->createSchema();
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
    public static function createObject($connection) {
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
    public function createModel($model) {

        $tableDef = $this->schema->createTable($model->meta->dbTable);

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
            if(empty($type)):
                continue;
            endif;

            if($field->primaryKey):
                $primaryKeyFields[] = $model->meta->primaryKey->getColumnName();
            elseif ($field->isUnique()):
                $unique_fields[] = $colName;
            elseif ($field->dbIndex):
                $indexes[] = $colName;
            endif;

            $tableDef->addColumn($colName, $type, $this->getDoctrineColumnOptions($field));

            if($field->isRelation && $field->relation && $field->dbConstraint):
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
        if(!empty($indexes)):
            $tableDef->addIndex($indexes);
        endif;
        // add unique constraint
        if(!empty($unique_fields)):
            $tableDef->addUniqueIndex($unique_fields);
        endif;

        $this->schemaManager->createTable($tableDef);

        // many to many
        /** @var $relationField ManyToManyField */
        foreach ($model->meta->localManyToMany as $name => $relationField) :
            if($relationField->manyToMany && $relationField->relation->through->meta->autoCreated):
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
    public function deleteModel($model) {

        // first remove any automatically created models
        /** @var $relationField ManyToManyField */
        foreach ($model->meta->localManyToMany as $name => $relationField) :
            if($relationField->relation->through->meta->autoCreated):
                $this->deleteModel($relationField->relation->through);
            endif;
        endforeach;

        $this->schemaManager->dropTable($model->meta->dbTable);

    }

    public function alterDbTable($model, $oldDbTable, $newDbTable) {

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
    public function addField($model, $field) {

        // many to many
        if($field->manyToMany && $field->relation->through->meta->autoCreated):
            $this->createModel($field->relation->through);

            return;
        endif;

        $type = $field->dbType($this->connection);
        $name = $field->getColumnName();
        $fieldOptions = $this->getDoctrineColumnOptions($field);

        // It might not actually have a column behind it
        if(empty($type)):
            return;
        endif;

        $tableDef = clone $this->schema->getTable($model->meta->dbTable);

        // normal column
        $tableDef->addColumn($name, $type, $fieldOptions);

        // unique constraint
        if($field->isUnique()):
            $tableDef->addUniqueIndex([$name]);
        endif;

        // index constraint
        if($field->dbIndex and !$field->isUnique()):
            $tableDef->addIndex([$name]);
        endif;

        /* @var $field ForeignKey */
        if($field->isRelation && $field->relation && $field->dbConstraint):
            $relField = $field->getRelatedField();

            $tableDef->addForeignKeyConstraint(
                $relField->scopeModel->meta->dbTable,
                [$field->getColumnName()],
                [$relField->getColumnName()]
            );
        endif;

        $comparator = new Comparator();
        $diff = $comparator->diffTable($this->schema->getTable($model->meta->dbTable), $tableDef);

        if($diff !== false):
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
    public function removeField($model, $field) {
        // Special-case implicit M2M tables
        if($field->manyToMany && $field->relation->through->meta->autoCreated):
            $this->deleteModel($field->relation->through);

            return;
        endif;

        $type = $field->dbType($this->connection);
        $name = $field->getColumnName();
        $fieldOptions = $this->getDoctrineColumnOptions($field);
        // It might not actually have a column behind it
        if(empty($type)):
            return;
        endif;

        $table = $model->meta->dbTable;
        $tableDef = clone $this->schema->getTable($table);

        // Drop any FK constraints, MySQL requires explicit deletion
        if($field->isRelation && $field->relation != null):
            foreach ($this->constraintName($table, $name, ['foreignKey' => true]) as $fkConstraint) :
                $tableDef->removeForeignKey($fkConstraint);
            endforeach;
        endif;

        // remove column
        $tableDef->dropColumn($name);

        $comparator = new Comparator();
        $diff = $comparator->diffTable($this->schema->getTable($table), $tableDef);

        if($diff !== false):
            $this->schemaManager->alterTable($diff);
        endif;

    }

    public function alterField($model, $oldField, $newField, $strict = false) {

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

        if($field->hasProperty('decimalPlaces') && $field->decimalPlaces):
            $options['scale'] = $field->decimalPlaces;
        endif;

        // for columns that can be signed
        if ($field->hasProperty('signed') && $field->signed !== null):
            $options['unsigned'] = $field->signed === false;
        endif;

        if ($field->hasDefault() && ($includeDefault && !$this->skipDefault($field))):

            // the default value
            $default_value = $this->effectiveDefault($field);

            // if value is provided, create the defualt
            if ($default_value == NOT_PROVIDED):

                $options['default'] = $this->prepareDefault($field);

            endif;

        endif;

        // the null option
        if($field->null):
            $options['notnull'] = $field->null;
        endif;

        // the comment option
        if($field->comment):
            $options['comment'] = $field->comment;
        endif;

        // auto increament option
        if ($field->hasProperty('auto') && $field->auto):
            $options['autoincrement'] = $field->auto;
        endif;

        return $options;
    }

    public function constraintName($table, $column, $constraintType) {
        $unique = $primaryKey = $index = $foreignKey = null;
        extract($constraintType);

        $fieldConstraints = [];

        if($foreignKey):
            $foreignKeys = $this->schema->getTable($table)->getForeignKeys();

            /** @var $fk ForeignKeyConstraint */
            foreach ($foreignKeys as $fk) :
                if(in_array($column, $fk->getLocalColumns())):
                    $fieldConstraints[] = $fk->getName();
                endif;
            endforeach;
        endif;

        return $fieldConstraints;
    }
    /**Some backends don't accept default values for certain columns types (i.e. MySQL longtext and longblob).
     * @param $field
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function skipDefault($field)
    {

    }

    public function prepareDefault($field)
    {

    }

    public function effectiveDefault($field)
    {

    }

}
