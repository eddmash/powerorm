<?php
/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Backends;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
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
     * @var ConnectionInterface
     */
    public $connection;

    /**
     * @var AbstractSchemaManager
     */
    private $schemaManager;

    /**
     * @var bool
     */
    private $getSqlStatements;

    protected $sqls;

    /**
     * @param ConnectionInterface $connection
     * @param bool                $getSql
     */
    public function __construct(ConnectionInterface $connection, $getSql = false)
    {
        $this->connection = $connection;
        $this->schemaManager = $this->connection->getSchemaManager();
        $this->getSqlStatements = $getSql;
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return SchemaEditor
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function createObject(
        ConnectionInterface $connection,
        $getSql = false
    ) {
        return new static($connection, $getSql);
    }

    /**
     * Creates database table represented by the model.
     *
     * @param Model $model
     *
     * @throws ValueError
     * @throws \Doctrine\DBAL\DBALException
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function createModel($model)
    {
        $schema = new Schema();

        $tableDef = $schema->createTable($model->getMeta()->getDbTable());
        // this assumes fields set_from_name has been invoked
        $primaryKeyFields = [];
        $unique_fields = [];
        $indexes = [];
        /** @var $field Field */
        /** @var $field ForeignKey */
        foreach ($model->getMeta()->localFields as $fname => $field) {
            $colName = $field->getColumnName();
            $type = $field->dbType($this->connection);
            // if we don't have a type skip
            if (empty($type)) {
                continue;
            }
            if ($field->primaryKey) {
                $primaryKeyFields[] = $model->getMeta()->primaryKey->getColumnName();
            } elseif ($field->isUnique()) {
                $unique_fields[] = $colName;
            } elseif ($field->dbIndex) {
                $indexes[] = $colName;
            }
            $tableDef->addColumn($colName, $type, $this->getDoctrineColumnOptions($field));
            if ($field->isRelation && $field->relation && $field->dbConstraint) {
                $relField = $field->getRelatedField();
                $tableDef->addForeignKeyConstraint(
                    $relField->scopeModel->getMeta()->getDbTable(),
                    [$field->getColumnName()],
                    [$relField->getColumnName()]
                );
            }
        }
        // create the primary key
        $tableDef->setPrimaryKey($primaryKeyFields);
        // add index constraint
        if (!empty($indexes)) {
            $tableDef->addIndex($indexes);
        }
        // add unique constraint
        if (!empty($unique_fields)) {
            $tableDef->addUniqueIndex($unique_fields);
        }
        $this->createTable($tableDef);
        // many to many
        /** @var $relationField ManyToManyField */
        foreach ($model->getMeta()->localManyToMany as $name => $relationField) {
            if ($relationField->manyToMany && $relationField->relation->through->getMeta()->autoCreated) {
                $this->createModel($relationField->relation->through);
            }
        }
    }

    /**
     * Drop database represented by the model.
     *
     * @param Model $model
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function deleteModel($model)
    {
        // first remove any automatically created models
        /** @var $relationField ManyToManyField */
        foreach ($model->getMeta()->localManyToMany as $name => $relationField) {
            if ($relationField->relation->through->getMeta()->autoCreated) {
                $this->deleteModel($relationField->relation->through);
            }
        }
        $this->dropTable($model->getMeta()->getDbTable());
    }

    /**
     * Renames the table a model points to.
     *
     * @param Model  $model
     * @param string $oldDbTableName
     * @param string $newDbTableName
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function alterDbTable($model, $oldDbTableName, $newDbTableName)
    {
        if ($oldDbTableName === $newDbTableName) {
            return;
        }
        $this->renameTable($oldDbTableName, $newDbTableName);
    }

    /**
     * Creates a field on a model.
     *
     * Usually involves adding a column, but may involve adding a table instead (for M2M fields).
     *
     * @param Model $model
     * @param Field $field
     *
     * @throws NotImplemented
     * @throws ValueError
     * @throws \Doctrine\DBAL\Schema\SchemaException
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function addField($model, $field)
    {
        // many to many
        if ($field->manyToMany && $field->relation->through->getMeta()->autoCreated) {
            $this->createModel($field->relation->through);

            return;
        }
        $type = $field->dbType($this->connection);
        $name = $field->getColumnName();
        $fieldOptions = $this->getDoctrineColumnOptions($field, true);
        // It might not actually have a column behind it
        if (empty($type)) {
            return;
        }

        $tableDef = new TableDiff($model->getMeta()->getDbTable());
        // normal column
        $tableDef->addedColumns[] = new Column(
            $name,
            Type::getType($type),
            $fieldOptions
        );
        // unique constraint
        if ($field->primaryKey) {
            $tableDef->addedIndexes[] = new Index(
                'primary',
                [$name],
                true,
                true
            );
        }
        // unique constraint
        if ($field->isUnique() && !$field->primaryKey) {
            $tableDef->addedIndexes[] = new Index(
                sprintf('uniq_%s_%s', mt_rand(1, 1000), $name),
                [$name],
                true
            );
        }
        // index constraint
        if ($field->dbIndex && !$field->isUnique() && !$field->primaryKey) {
            $tableDef->addedIndexes[] = new Index(
                sprintf('idx_%s_%s', mt_rand(1, 1000), $name),
                [$name]
            );
        }

        // we need to drop in-database defaults
        if ($this->effectiveDefault($field)) {
            //todo
        }

        /* @var $field ForeignKey */
        if ($field->isRelation && $field->relation && $field->dbConstraint) {
            $relField = $field->getRelatedField();

            $tableDef->addedForeignKeys[] = new ForeignKeyConstraint(
                [$field->getColumnName()],
                $relField->scopeModel->getMeta()->getDbTable(),
                [$relField->getColumnName()]
            );
        }

        $this->alterTable($tableDef);
    }

    /**
     * Removes a field from a model. Usually involves deleting a column, but for M2Ms may involve deleting a table.
     *
     * @param Model $model
     * @param Field $field
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Eddmash\PowerOrm\Exception\LookupError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function removeField($model, $field)
    {
        // Special-case implicit M2M tables
        if ($field->manyToMany && $field->relation->through->getMeta()->autoCreated) {
            $this->deleteModel($field->relation->through);

            return;
        }
        $type = $field->dbType($this->connection);
        $name = $field->getColumnName();

        // It might not actually have a column behind it
        if (empty($type)) {
            return;
        }
        $table = $model->getMeta()->getDbTable();
        $tableDef = new TableDiff($table);
        // Drop any FK constraints, MySQL requires explicit deletion
        if ($field->isRelation && null !== $field->relation) {
            foreach ($this->constraintName($table, $name, ['foreignKey' => true]) as $fkConstraint) {
                $tableDef->removedForeignKeys[] = $fkConstraint;
            }
        }

        // if its pk we need to drop  fk constraints that point to us
        if ($field->primaryKey) {
            $newRels = $field->scopeModel->getMeta()->getReverseRelatedObjects();
            /** @var $newRel RelatedField */
            foreach ($newRels as $newRel) {
                $fkConstraints = $this->constraintName(
                    $newRel->scopeModel->getMeta()->getDbTable(),
                    $newRel->getColumnName(),
                    ['foreignKey' => true]
                );
                $relDiff = new TableDiff($newRel->scopeModel->getMeta()->getDbTable());
                $relDiff->removedForeignKeys = $fkConstraints;
                $this->alterTable($relDiff);
            }
        }

        // remove column
        $tableDef->removedColumns[] = new Column($name, Type::getType($type));

        $this->alterTable($tableDef);
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
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function alterField(Model $model, Field $oldField, Field $newField, $strict = false)
    {
        $oldType = $oldField->dbType($this->connection);
        $newType = $newField->dbType($this->connection);
        if ((is_null($oldType) && is_null($oldField->relation)) || (is_null($newType) && is_null($newField->relation))) {
            throw new ValueError(
                sprintf(
                    'Cannot alter field %s into %s - they do not properly define '.
                    'db_type (are you using a badly-written custom field?)',
                    $newField->getName(),
                    $oldField->getName()
                )
            );
        } elseif (is_null($oldType) && is_null($newType) &&
            (
                null !== $oldField->relation->through &&
                null !== $newField->relation->through &&
                $oldField->relation->through->getMeta()->autoCreated &&
                $newField->relation->through->getMeta()->autoCreated
            )
        ) {
            $this->alterManyToMany($model, $oldField, $newField, $strict);
        } elseif (is_null($oldType) && is_null($newType) &&
            (
                null !== $oldField->relation->through &&
                null !== $newField->relation->through &&
                !$oldField->relation->through->getMeta()->autoCreated &&
                !$newField->relation->through->getMeta()->autoCreated
            )
        ) {
            return;
        } elseif (is_null($oldType) && is_null($newType)) {
            throw new  ValueError(
                sprintf(
                    'Cannot alter field %s into %s - they are not compatible types '.
                    '(you cannot alter to or from M2M fields, or add or remove through= on M2M fields)',
                    $oldField->getName(),
                    $newField->getName()
                )
            );
        }
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
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function alterManyToMany(Model $model, Field $oldField, Field $newField, $strict = false)
    {
        //Rename the through table
        if ($oldField->relation->through->getMeta()->getDbTable() != $newField->relation->through->getMeta()
                ->getDbTable()) {
            $this->alterDbTable(
                $oldField->relation->through,
                $oldField->relation->through->getMeta()->getDbTable(),
                $newField->relation->through->getMeta()->getDbTable()
            );
        }
    }

    /**
     * @param Model $model
     * @param Field $oldField
     * @param Field $newField
     * @param bool  $strict
     *
     * @throws ValueError
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     * @throws \Eddmash\PowerOrm\Exception\FieldDoesNotExist
     * @throws \Eddmash\PowerOrm\Exception\LookupError
     */
    private function doFieldAlter(Model $model, Field $oldField, Field $newField, $strict = false)
    {
        $oldType = $oldField->dbType($this->connection);
        $newType = $newField->dbType($this->connection);
        $table = $model->getMeta()->getDbTable();
        $droppedFks = [];
        // *****************  get foreign keys and drop them, we will recreate them later *****************
        if ($oldField->relation && $oldField->dbConstraint) {
            $diff = new TableDiff($table);
            foreach ($this->constraintName(
                $table,
                $oldField->getColumnName(),
                ['foreignKey' => true]
            ) as $fkConstraint) {
                $diff->removedForeignKeys[] = $fkConstraint;
                $droppedFks[] = $oldField->getName();
            }
            if (false !== $diff) {
                $this->alterTable($diff);
            }
        }
        // *****************  get uniques and drop them  *****************
        if ($oldField->isUnique() &&
            (!$newField->isUnique() ||
                (!$oldField->primaryKey && $newField->primaryKey))) {
            $diff = new TableDiff($table);
            foreach ($this->constraintName(
                $table,
                $oldField->getColumnName(),
                ['unique' => true]
            ) as $constraint) {
                $diff->removedIndexes[] = $constraint;
            }
            if (false !== $diff) {
                $this->alterTable($diff);
            }
        }
        // *************  if Primary key is changing drop FK constraints that point to it first. *********
        if ($oldField->primaryKey && $newField->primaryKey && $newType !== $oldType) {
            $newRels = $newField->scopeModel->getMeta()->getReverseRelatedObjects();
            /** @var $newRel RelatedField */
            foreach ($newRels as $newRel) {
                $fkConstraints = $this->constraintName(
                    $newRel->scopeModel->getMeta()->getDbTable(),
                    $newRel->getColumnName(),
                    ['foreignKey' => true]
                );
                $relDiff = new TableDiff($newRel->scopeModel->getMeta()->getDbTable());
                $relDiff->removedForeignKeys = $fkConstraints;
                if (false !== $relDiff) {
                    $this->alterTable($relDiff);
                }
            }
        }
        // *************  get index and drop them. *********
        if ($oldField->dbIndex && !$newField->dbIndex &&
            (!$oldField->isUnique() &&
                (!$newField->isUnique() && $oldField->isUnique()))
        ) {
            $diff = new TableDiff($table);
            foreach ($this->constraintName(
                $table,
                $oldField->getColumnName(),
                ['index' => true]
            ) as $indexConstraint) {
                $diff->removedIndexes[] = $indexConstraint;
            }
            if (false !== $diff) {
                $this->alterTable($diff);
            }
        }
        // ******** todo Change check constraints? ***********

        // **********************************************************************************************************
        // ************************ We change the other column properties like null, default etc ********************
        // **********************************************************************************************************

        if ($oldField->getColumnName() !== $newField->getColumnName()) {
            $diff = new TableDiff($table);

            $diff->renamedColumns[$oldField->getColumnName()] = new Column(
                $newField->getColumnName(),
                Type::getType($oldField->dbType($this->connection))
            );
        } else {
            $oldOpts = $this->getDoctrineColumnOptions(
                $oldField,
                true
            );
            $newOpts = $this->getDoctrineColumnOptions(
                $newField,
                true
            );
            $newColumn = new Column(
                $oldField->getColumnName(),
                Type::getType($oldField->dbType($this->connection)),
                $newOpts
            );

            $changeProperties = array_diff(
                array_keys($oldOpts),
                array_keys($newOpts)
            );
            foreach ($oldOpts as $name => $oldOpt) {
                if (isset($newOpts[$name]) && $newOpts[$name] != $oldOpts[$name]) {
                    $changeProperties[] = $name;
                }
            }

            $diff = new TableDiff($table);
            $diff->changedColumns[] = new ColumnDiff(
                $oldField->getColumnName(),
                $newColumn,
                array_unique($changeProperties)
            );
        }

        if ($diff) {
            $this->alterTable($diff);
        }

        // **********************************************************************************************************
        // ************************ End of properties change *********************************** ********************
        // **********************************************************************************************************

        // **************** Added a unique? *****************
        if ((!$oldField->isUnique() && $newField->isUnique()) ||
            ($oldField->primaryKey && !$newField->primaryKey && $newField->isUnique())
        ) {
            $diff = new TableDiff($table);
            $diff->addedIndexes[] = new Index(
                sprintf('uniq_%s', mt_rand(1, 1000), $newField->getColumnName()),
                [$newField->getColumnName()],
                true
            );
            if (false !== $diff) {
                $this->alterTable($diff);
            }
        }
        // **************** Added a index? *****************
        if (!$oldField->dbIndex && $newField->dbIndex && !$newField->isUnique() &&
            !($oldField->isUnique() && $newField->isUnique())
        ) {
            $diff = new TableDiff($table);
            $diff->addedIndexes[] = new Index(
                sprintf('idx_%s', mt_rand(1, 1000), $newField->getColumnName()),
                [$newField->getColumnName()],
                true
            );
            if (false !== $diff) {
                $this->alterTable($diff);
            }
        }
        // **************** Type alteration on primary key? Then we need to alter the column referring to us. *********
        // **************** Does it have a foreign key? *************
        /** @var $newField RelatedField */
        /* @var $fromField RelatedField */
        /* @var $toField RelatedField */
        if ($newField->relation &&
            ($droppedFks || !$oldField->relation || !$oldField->dbConstraint) &&
            $newField->dbConstraint
        ) {
            $diff = new TableDiff($table);
            list($fromField, $toField) = $newField->getRelatedFields();
            $diff->addedForeignKeys[] = new ForeignKeyConstraint(
                [$fromField->getColumnName()],
                $newField->getRelatedModel()->getMeta()->getDbTable(),
                [$toField->getColumnName()]
            );
            if (false !== $diff) {
                $this->alterTable($diff);
            }
        }
        // ****************** Rebuild FKs that pointed to us if we previously had to drop them ****************
        if ($oldField->primaryKey && $newField->primaryKey && $newType !== $oldType) {
            $newRels = $newField->scopeModel->getMeta()->getReverseRelatedObjects();
            $fkConstraints = [];
            /** @var $newRel RelatedField */
            foreach ($newRels as $newRel) {
                if ($newRel->manyToMany) {
                    continue;
                }
                $fkConstraints[$newRel->scopeModel->getMeta()->getDbTable()][] = $newRel;
            }
            foreach ($fkConstraints as $relTableName => $rels) {
                $relDiff = new TableDiff($relTableName);
                foreach ($rels as $rel) {
                    list($fromField, $toField) = $rel->getRelatedFields();
                    $relDiff->addedForeignKeys[] = new ForeignKeyConstraint(
                        [$fromField->getColumnName()],
                        $toField->scopeModel->getMeta()->getDbTable(),
                        [$toField->getColumnName()]
                    );
                }
                if (false !== $relDiff) {
                    $this->alterTable($relDiff);
                }
            }
        }
    }

    /**
     * @param Field      $field
     * @param bool|false $includeDefault
     *
     * @return array
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getDoctrineColumnOptions($field, $includeDefault = false)
    {
        $options = [];
        // set constraint
        if ($field->hasProperty('maxLength') && $field->maxLength) {
            $options['length'] = $field->maxLength;
        }
        if ($field->hasProperty('maxDigits') && $field->maxDigits) {
            $options['precision'] = $field->maxDigits;
        }
        if ($field->hasProperty('decimalPlaces') && $field->decimalPlaces) {
            $options['scale'] = $field->decimalPlaces;
        }
        // for columns that can be signed
        if ($field->hasProperty('signed') && null !== $field->signed) {
            $options['unsigned'] = false === $field->signed;
        }
        if ($field->hasDefault() && $includeDefault) {
            // the default value
            $default_value = $this->effectiveDefault($field);
            // if value is provided, create the defualt
            if (NOT_PROVIDED != $default_value) {
                $options['default'] = $default_value;
            }
        }
        // the null option
        if ($field->isNull()) {
            $options['notnull'] = false;
        }
        // the comment option
        if ($field->comment) {
            $options['comment'] = $field->comment;
        }
        // auto increament option
        if ($field instanceof AutoField) {
            $options['autoincrement'] = true;
        }

        return $options;
    }

    public function constraintName($table, $column, $constraintType = [])
    {
        $foreignKey = ArrayHelper::pop($constraintType, 'foreignKey', false);
        $fieldConstraints = [];
        if ($foreignKey) {
            $foreignKeys = $this->schemaManager->listTableForeignKeys($table);
            /** @var $fk ForeignKeyConstraint */
            foreach ($foreignKeys as $fk) {
                if (in_array($column, $fk->getLocalColumns())) {
                    $fieldConstraints[] = $fk->getName();
                }
            }
        } else {
            $indexes = $this->getIndexes($table, $constraintType);
            foreach ($indexes as $index) {
                if (in_array($column, $index->getColumns())) {
                    $fieldConstraints[] = $index->getName();
                }
            }
        }

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
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function effectiveDefault($field)
    {
        if ($field->hasDefault()) {
            $default = $field->getDefault();
        } elseif (($field->hasProperty('autoNow') && $field->autoNow) ||
            ($field->hasProperty('addAutoNow') && $field->addAutoNow)
        ) {
            $default = new \DateTime('now', BaseOrm::getInstance()->getTimezone());
        } else {
            $default = null;
        }
        if (is_callable($default)) {
            $default = call_user_func($default);
        }

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
        foreach ($indexes as $indexObj) {
            if ($unique && $indexObj->isUnique()) {
                $keys[] = $indexObj;
            }
            if ($primaryKey && $indexObj->isPrimary()) {
                $keys[] = $indexObj;
            }
            if ($index && $indexObj->isSimpleIndex()) {
                $keys[] = $indexObj;
            }
        }

        return $keys;
    }

    private function getPlatform()
    {
        return $this->connection->getDatabasePlatform();
    }

    public function addSql($sql)
    {
        foreach ((array) $sql as $query) {
            $this->sqls[] = $query;
        }
    }

    /**
     * @return mixed
     */
    public function getSqls()
    {
        return $this->sqls;
    }

    /**
     * Creates a new table.
     *
     * @param \Doctrine\DBAL\Schema\Table $table
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createTable(Table $table)
    {
        if ($this->getSqlStatements) {
            $createFlags = AbstractPlatform::CREATE_INDEXES |
                AbstractPlatform::CREATE_FOREIGNKEYS;
            $this->addSql(
                $this->getPlatform()
                    ->getCreateTableSQL($table, $createFlags)
            );
        } else {
            $this->schemaManager->createTable($table);
        }
    }

    private function dropTable($dbTable)
    {
        if ($this->getSqlStatements) {
            $this->addSql($this->getPlatform()->getDropTableSQL($dbTable));
        } else {
            $this->schemaManager->dropTable($dbTable);
        }
    }

    /**
     * Renames a given table to another name.
     *
     * @param string $name    the current name of the table
     * @param string $newName the new name of the table
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function renameTable($name, $newName)
    {
        $tableDiff = new TableDiff($name);
        $tableDiff->newName = $newName;
        $this->alterTable($tableDiff);
    }

    /**
     * @param $tableDiff
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function alterTable($tableDiff)
    {
        if ($this->getSqlStatements) {
            $this->addSql($this->getPlatform()->getAlterTableSQL($tableDiff));
        } else {
            $this->schemaManager->alterTable($tableDiff);
        }
    }
}
