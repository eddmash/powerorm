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
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\TableDiff;
use Eddmash\PowerOrm\BaseObject;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\AutoField;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Field\ForeignKey;
use Eddmash\PowerOrm\Model\Field\ManyToManyField;
use Eddmash\PowerOrm\Model\Field\RelatedField;
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
        if ($field->primaryKey):
            $tableDef->setPrimaryKey([$name]);
        endif;
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

            // we need to drop in-database defaults
            if ($this->effectiveDefault($field)):

            endif;
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

        // if its pk we need to drop  fk constraints that point to us
        if ($field->primaryKey) :
            $newRels = $field->scopeModel->meta->getReverseRelatedObjects();
            /** @var $newRel RelatedField */
            foreach ($newRels as $newRel) :
                $fkConstraints = $this->constraintName(
                    $newRel->scopeModel->meta->dbTable,
                    $newRel->getColumnName(),
                    ['foreignKey' => true]
                );
                $relDiff = new TableDiff($newRel->scopeModel->meta->dbTable);
                $relDiff->removedForeignKeys = $fkConstraints;
                if ($relDiff !== false):
                    $this->schemaManager->alterTable($relDiff);
                endif;
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
    public function alterField(Model $model, Field $oldField, Field $newField, $strict = false)
    {
        $oldType = $oldField->dbType($this->connection);
        $newType = $newField->dbType($this->connection);
        if ((is_null($oldType) && is_null($oldField->relation)) || (is_null($newType) && is_null($newField->relation))):
            throw new ValueError(
                sprintf(
                    'Cannot alter field %s into %s - they do not properly define '.
                    'db_type (are you using a badly-written custom field?)',
                    $newField->getName(),
                    $oldField->getName()
                )
            );
        elseif (is_null($oldType) && is_null($newType) &&
            (
                $oldField->relation->through !== null &&
                $newField->relation->through !== null &&
                $oldField->relation->through->meta->autoCreated &&
                $newField->relation->through->meta->autoCreated
            )
        ):
            $this->alterManyToMany($model, $oldField, $newField, $strict);
        elseif (is_null($oldType) && is_null($newType) &&
            (
                $oldField->relation->through !== null &&
                $newField->relation->through !== null &&
                !$oldField->relation->through->meta->autoCreated &&
                !$newField->relation->through->meta->autoCreated
            )
        ):
            return;
        elseif (is_null($oldType) && is_null($newType)):
            throw new  ValueError(
                sprintf(
                    'Cannot alter field %s into %s - they are not compatible types '.
                    '(you cannot alter to or from M2M fields, or add or remove through= on M2M fields)',
                    $oldField->getName(),
                    $newField->getName()
                )
            );
        endif;
        $this->doFieldAlter($model, $oldField, $newField, $strict);
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
    private function alterManyToMany(Model $model, Field $oldField, Field $newField, $strict = false)
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

    private function doFieldAlter(Model $model, Field $oldField, Field $newField, $strict = false)
    {
        $schema = $this->schemaManager->createSchema();

        $oldType = $oldField->dbType($this->connection);
        $newType = $newField->dbType($this->connection);
        $table = $model->meta->dbTable;
        $droppedFks = [];
        // *****************  get foreign keys and drop them, we will recreate them later *****************
        if ($oldField->relation && $oldField->dbConstraint) :
            $diff = new TableDiff($table);
            foreach ($this->constraintName(
                $table,
                $oldField->getColumnName(),
                ['foreignKey' => true]
            ) as $fkConstraint) :
                $diff->removedForeignKeys[] = $fkConstraint;
                $droppedFks[] = $oldField->getName();
            endforeach;
            if ($diff !== false):
                $this->schemaManager->alterTable($diff);
            endif;
        endif;
        // *****************  get uniques and drop them  *****************
        if ($oldField->isUnique() && (!$newField->isUnique() || (!$oldField->primaryKey && $newField->primaryKey))) :
            $diff = new TableDiff($table);
            foreach ($this->constraintName($table, $oldField->getColumnName(), ['unique' => true]) as $constraint) :
                $diff->removedIndexes[] = $constraint;
            endforeach;
            if ($diff !== false):
                $this->schemaManager->alterTable($diff);
            endif;
        endif;
        // *************  if Primary key is changing drop FK constraints that point to it first. *********
        if ($oldField->primaryKey && $newField->primaryKey && $newType !== $oldType) :
            $newRels = $newField->scopeModel->meta->getReverseRelatedObjects();
            /** @var $newRel RelatedField */
            foreach ($newRels as $newRel) :
                $fkConstraints = $this->constraintName(
                    $newRel->scopeModel->meta->dbTable,
                    $newRel->getColumnName(),
                    ['foreignKey' => true]
                );
                $relDiff = new TableDiff($newRel->scopeModel->meta->dbTable);
                $relDiff->removedForeignKeys = $fkConstraints;
                if ($relDiff !== false):
                    $this->schemaManager->alterTable($relDiff);
                endif;
            endforeach;
        endif;
        // *************  get index and drop them. *********
        if ($oldField->dbIndex && !$newField->dbIndex &&
            (!$oldField->isUnique() && (!$newField->isUnique() && $oldField->isUnique()))
        ) :
            $diff = new TableDiff($table);
            foreach ($this->constraintName($table, $oldField->getColumnName(), ['index' => true]) as $indexConstraint) :
                $diff->removedIndexes[] = $indexConstraint;
            endforeach;
            if ($diff !== false):
                $this->schemaManager->alterTable($diff);
            endif;
        endif;
        // ******** todo Change check constraints? ***********

        // **********************************************************************************************************
        // ************************ We change the other column properties like null, default etc ********************
        // **********************************************************************************************************

        $tableClone = clone  $schema->getTable($table);
        if ($oldField->getColumnName() !== $newField->getColumnName()) :
            $tableClone->addColumn(
                $newField->getColumnName(),
                $newType,
                $this->getDoctrineColumnOptions($newField, true)
            );
            $tableClone->dropColumn($oldField->getColumnName());
        else:
            $tableClone->changeColumn(
                $oldField->getColumnName(),
                $this->getDoctrineColumnOptions($newField, true)
            );
        endif;

        $comparator = new Comparator();
        $diff = $comparator->diffTable($schema->getTable($table), $tableClone);

        if ($diff !== false):
            $this->schemaManager->alterTable($diff);
        endif;

        // **********************************************************************************************************
        // ************************ End of properties change *********************************** ********************
        // **********************************************************************************************************

        // **************** Added a unique? *****************
        if ((!$oldField->isUnique() && $newField->isUnique()) ||
            ($oldField->primaryKey && !$newField->primaryKey && $newField->isUnique())
        ):
            $diff = new TableDiff($table);
            $diff->addedIndexes[] = new Index(
                sprintf('uniq_%s', mt_rand(1, 1000), $newField->getColumnName()),
                [$newField->getColumnName()],
                true
            );
            if ($diff !== false):
                $this->schemaManager->alterTable($diff);
            endif;
        endif;
        // **************** Added a index? *****************
        if (!$oldField->dbIndex && $newField->dbIndex && !$newField->isUnique() &&
            !($oldField->isUnique() && $newField->isUnique())
        ):
            $diff = new TableDiff($table);
            $diff->addedIndexes[] = new Index(
                sprintf('idx_%s', mt_rand(1, 1000), $newField->getColumnName()),
                [$newField->getColumnName()],
                true
            );
            if ($diff !== false):
                $this->schemaManager->alterTable($diff);
            endif;
        endif;
        // **************** Type alteration on primary key? Then we need to alter the column referring to us. *********
        // **************** Does it have a foreign key? *************
        /** @var $newField RelatedField */
        /* @var $fromField RelatedField */
        /* @var $toField RelatedField */
        if ($newField->relation &&
            ($droppedFks || !$oldField->relation || !$oldField->dbConstraint) &&
            $newField->dbConstraint
        ):
            $diff = new TableDiff($table);
            list($fromField, $toField) = $newField->getRelatedFields();
            $diff->addedForeignKeys[] = new ForeignKeyConstraint(
                [$fromField->getColumnName()],
                $newField->getRelatedModel()->meta->dbTable,
                [$toField->getColumnName()]
            );
            if ($diff !== false):
                $this->schemaManager->alterTable($diff);
            endif;
        endif;
        // ****************** Rebuild FKs that pointed to us if we previously had to drop them ****************
        if ($oldField->primaryKey && $newField->primaryKey && $newType !== $oldType) :
            $newRels = $newField->scopeModel->meta->getReverseRelatedObjects();
            $fkConstraints = [];
            /** @var $newRel RelatedField */
            foreach ($newRels as $newRel) :
                if ($newRel->manyToMany):
                    continue;
                endif;
                $fkConstraints[$newRel->scopeModel->meta->dbTable][] = $newRel;
            endforeach;
            foreach ($fkConstraints as $relTableName => $rels) :
                $relDiff = new TableDiff($relTableName);
                foreach ($rels as $rel) :
                    list($fromField, $toField) = $rel->getRelatedFields();
                    $relDiff->addedForeignKeys[] = new ForeignKeyConstraint(
                        [$fromField->getColumnName()],
                        $toField->scopeModel->meta->dbTable,
                        [$toField->getColumnName()]
                    );
                endforeach;
                if ($relDiff !== false):
                    $this->schemaManager->alterTable($relDiff);
                endif;
            endforeach;
        endif;
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
            $options['notnull'] = false;
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

    public function constraintName($table, $column, $constraintType = [])
    {
        $foreignKey = ArrayHelper::pop($constraintType, 'foreignKey', false);
        $fieldConstraints = [];
        if ($foreignKey):
            $foreignKeys = $this->schemaManager->listTableForeignKeys($table);
            /** @var $fk ForeignKeyConstraint */
            foreach ($foreignKeys as $fk) :
                if (in_array($column, $fk->getLocalColumns())):
                    $fieldConstraints[] = $fk->getName();
                endif;
            endforeach;
        else:
            $indexes = $this->getIndexes($table, $constraintType);
            foreach ($indexes as $index) :
                if (in_array($column, $index->getColumns())):
                    $fieldConstraints[] = $index->getName();
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
            ($field->hasProperty('addAutoNow') && $field->addAutoNow)
        ):
            $default = new \DateTime('now', BaseOrm::getInstance()->getTimezone());
        else:
            $default = null;
        endif;
        if (is_callable($default)):
            $default = call_user_func($default);
        endif;

        return $default;
    }

    /**
     * @param string $table
     * @param string $type  accepts (unique, primary_key, index) as values
     *
     * @return Index[]
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    private function getIndexes($table, $type = [])
    {
        $unique = $primaryKey = $index = null;
        extract($type);
        $indexes = $this->schemaManager->listTableIndexes($table);
        $keys = [];
        foreach ($indexes as $indexObj) :
            if ($unique && $indexObj->isUnique()) :
                $keys[] = $indexObj;
            endif;
            if ($primaryKey && $indexObj->isPrimary()) :
                $keys[] = $indexObj;
            endif;
            if ($index && $indexObj->isSimpleIndex()) :
                $keys[] = $indexObj;
            endif;
        endforeach;

        return $keys;
    }
}
