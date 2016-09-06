<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/23/16
 * Time: 7:37 AM.
 */
namespace eddmash\powerorm\db\schema;

use eddmash\powerorm\exceptions\ValueError;
use eddmash\powerorm\model\BaseModel;
use eddmash\powerorm\model\field\Field;
use eddmash\powerorm\NOT_PROVIDED;

abstract class BaseEditor extends ForgeClass
{
    public $sql_alter_column_type = 'ALTER COLUMN %(column)s TYPE %(type)s';

    public $sql_create_unique = 'ALTER TABLE %1$s ADD CONSTRAINT %2$s UNIQUE (%3$s)';
    public $sql_delete_unique = 'ALTER TABLE %1$s DROP CONSTRAINT %2$s';

    public $sql_create_fk = 'ALTER TABLE %1$s ADD CONSTRAINT %2$s  FOREIGN KEY (%3$s) REFERENCES %4$s(%5$s)';
    public $sql_delete_fk = 'ALTER TABLE %1$s DROP CONSTRAINT %2$s';

    public $sql_create_index = 'CREATE INDEX %1$s ON %2$s (%3$s)';
    public $sql_delete_index = 'DROP INDEX %s';

    public $sql_update_with_default = 'UPDATE %1$s SET %2$s = %3$s WHERE %2$s IS NULL';

    public function get_connection()
    {
        return $this->db;
    }

    /**
     * Represent the model in the database.
     *
     * @param BaseModel $model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function create_model(BaseModel $model)
    {
        // this assumes fields set_from_name has been invoked
        $fields = [];
        $unique_fields = [];

        // non-m2m fields
        foreach ($model->meta->fields as $name => $field) :

            if ($field->is_unique()):
                $unique_fields[] = $field;
            endif;

            $sql_def = $this->column_sql($field);

            if (!empty($sql_def)):
                $fields[$field->db_column_name()] = $sql_def;
            endif;
        endforeach;

        $this->_add_field_create($fields);

        // create the primary key
        $this->_add_primary_key_create($model);

        $this->_table_create($model);

        // add unique constraint
        // consider unique_together todo
        foreach ($unique_fields as $field) :
            $this->add_unique_constraint($model, $field);
        endforeach;

        // add fk constraint
        foreach ($model->meta->relations_fields as $name => $relation_field) :
            if ($relation_field->inverse || $relation_field->M2M || !$relation_field->db_constraint):
                continue;
            endif;

            $this->add_fk_constraint($model, $relation_field);
        endforeach;

        // many to many
        foreach ($model->meta->relations_fields as $name => $relation_field) :
            if ($relation_field->M2M && $relation_field->relation->through->meta->auto_created):
                $this->create_model($relation_field->relation->through);
            endif;
        endforeach;
    }

    /**
     * Drop the model representation in the database.
     *
     * @param BaseModel $model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function drop_model(BaseModel $model)
    {

        // first remove any automatically created models
        foreach ($model->meta->relations_fields as $name => $field) :
            if ($field->M2M && $field->relation->through->auto_created):
                $this->_table_drop($field->relation->through);
            endif;
        endforeach;

        $this->_table_drop($model);
    }

    /**
     * Represent a model field in the database.
     *
     * @param BaseModel $model
     * @param Field     $field
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function add_model_field(BaseModel $model, Field $field)
    {

        // many to many
        if ($field->M2M && $field->relation->through->meta->auto_created):
            $this->create_model($field->relation->through);

            return;
        endif;

        $sql_def = $this->column_sql($field);

        // It might not actually have a column behind it
        if (empty($sql_def)):
            return;
        endif;

        // normal column
        $this->_add_field_alter($model, $field);

        // unique constraint
        if ($field->is_unique()):
            $this->add_unique_constraint($model, $field);
        endif;

        // index constraint
        if ($field->db_index and !$field->is_unique()):
            $this->add_index_constraint($model, $field);
        endif;

        // set up constraint if we have to
        if ($field->is_relation && $field->db_constraint):
            $this->add_fk_constraint($model, $field);
        endif;
    }

    /**
     * Remove the model field representation from the database.
     *
     * @param BaseModel $model
     * @param Field     $field
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function drop_model_field(BaseModel $model, Field $field)
    {
        // if we created a table for m2m
        if ($field->M2M && $field->relation->through->meta->auto_created):
            $this->drop_model($field->relation->through);

            return;
        endif;

        if ($field->is_relation):
            // drop constraints
            $this->drop_constraint($this->sql_delete_fk, $model, $field, 'fk');
        endif;

        // drop column
        $this->_drop_field($model, $field);
    }

    /**
     * Allows a field's -:
     *      - type,
     *      - uniqueness,
     *      - nullability,
     *      - default,
     *      - db_column,
     *      - constraints
     *      - etc.
     * to be modified.
     *
     * @param Field $previous_field
     * @param Field $present_field
     *
     * @throws ValueError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function alter_model_field(BaseModel $model, Field $previous_field, Field $present_field)
    {
        $previous_type = $previous_field->db_type($this->get_connection());
        $present_type = $present_field->db_type($this->get_connection());

        // ensure they have a type if they are not relationship fields
        if ((is_null($present_type) && is_null($present_field->relation->through)) ||
            (is_null($previous_type) && is_null($previous_field->relation->through))
        ):
            throw new ValueError(
                sprintf('Cannot alter field %1$s into %2$s they do not properly define db_type',
                    $present_field->name, $previous_field->name));
        endif;

        // alter many to many
        if (
            is_null($present_type) && is_null($previous_type) &&
            (
                !is_null($present_field->relation->through) &&
                !is_null($previous_field->relation->through) &&
                $present_field->relation->through->auto_created &&
                $previous_field->relation->through->auto_created
            )
        ):
            return $this->_alter_many_to_many($model, $previous_field, $present_field);
        endif;

        // if its a many to many but the model is not auto created just pass
        if (
            is_null($present_type) &&
            is_null($previous_type) &&
            (
                !is_null($present_field->relation->through) &&
                !is_null($previous_field->relation->through) &&
                !$present_field->relation->through->auto_created &&
                !$previous_field->relation->through->auto_created
            )
        ):
            return null;
        endif;

        // If we get here and we still don't have a field type and we cant do anything
        if (is_null($present_type) || is_null($previous_type)):
            throw new ValueError(
                sprintf('Cannot alter field %1$s into %2$s - they are not compatible types (you cannot alter to or '.
                    'from M2M fields, or add or remove through= on M2M fields', $present_field, $previous_field));
        endif;

        $this->_alter_field($model, $previous_field, $present_field, $previous_type, $present_type);
    }

    public function _alter_field(BaseModel $model, Field $previous_field, Field $present_field, $previous_type, $present_type)
    {

        // before we alter we need to drop any foreign keys that are created by this field, this way we can alter it.
        $dropped_fks = [];
        if ($previous_field->is_relation && $previous_field->db_constraint):
            // take note of it
            $dropped_fks[] = $this->create_constraint_name($model, $previous_field, 'fk');
            // drop it
            $this->drop_constraint($this->sql_delete_fk, $model, $previous_field, 'fk');
        endif;

        // are we altering to add primary key, unique  ?
        $adding_pk = (!$previous_field->primary_key && $present_field->primary_key);
        $adding_unique = (!$previous_field->unique && $present_field->unique);
        $drop_unique = ($previous_field->unique && !$present_field->unique);

        //  *********************** Drop uniqueness ***********************

        // if old state of field was unique but the new state of field is not unique and
        // we are not adding a primary key
        if ($previous_field->is_unique() && (!$present_field->is_unique() || $adding_pk)):
            $this->drop_constraint($this->sql_delete_unique, $model, $previous_field, 'uni');
        endif;

        //  *********************** Drop Reverse Relations if PK type was altered ***********************
        // that is relationship that depend on us
        if ($previous_field->primary_key && $present_field->primary_key && $present_type !== $previous_type):
            $pointing_to_us = $model->meta->get_reverse_fields();

            if (!empty($pointing_to_us)):
                foreach ($pointing_to_us as $rels) :

                    if ($rels->M2M):
                        continue;
                    endif;

                    // drop them for now.
                    $this->drop_constraint($this->sql_delete_fk, $rels->container_model, $rels, 'fk');
                endforeach;
            endif;
        endif;

        //  *********************** Drop index ***********************

        //if we are not adding unique, and previous was not unique but in the past was indexed but presently we are not
        if ($previous_field->db_index &&
            !$present_field->db_index &&
            !$previous_field->is_unique() &&
            !$drop_unique
        ):

            $this->drop_constraint($this->sql_delete_index, $model, $previous_field, 'idx');

        endif;

        //  *********************** Column name change ***********************

        // was the column name renamed
        // here we just rename the column only
        if ($present_field->db_column_name() !== $previous_field->db_column_name()):
            $altered_attrs = [];

            $altered_attrs [$previous_field->db_column_name()] = [
                'name' => $present_field->db_column_name(),
                'type' => $previous_type,
                'constraint' => $previous_field->max_length,
                'null' => $previous_field->null, // be explicit because of how forge modify_column works
            ];

            $this->_modify_field($model, $altered_attrs);

        endif;

        // **** OPEN SEASON FOR EVERYTHING ELSE ****

        $alter_actions = [];

        // did type change/constraint ?
        if ($present_type !== $previous_type || $present_field->max_length !== $previous_field->max_length):

            //todo take care of postgres serial here
            $alter_actions[] = [
                $previous_field->db_column_name() => [
                    'type' => $present_type,
                    'constraint' => $present_field->max_length,
                    'null' => $previous_field->null, // be explicit because of how forge modify_column works
                ],
            ];

        endif;

        //did default change ?
        $old_default = $this->effective_default($previous_field);
        $new_default = $this->effective_default($present_field);

        $adds_default = ($old_default !== $new_default &&
            !$new_default instanceof NOT_PROVIDED &&
            !$this->skip_default($present_field)
        );

        if ($adds_default):
            $alter_actions[] = [
                $previous_field->db_column_name() => [
                    'default' => $this->prepare_default($present_field),
                ],
            ];
        endif;

        $null_actions = [];

        //did null change ?
        if ($present_field->null !== $previous_field->null):

            $null_actions[] = [
                $previous_field->db_column_name() => [
                    'type' => $present_type,
                    'constraint' => $present_field->max_length,
                    'null' => $present_field->null,
                ],
            ];

        endif;

        // if we have a default and we are moving from null to not null

        // we need to do the following :

        // 1. Update existing rows with default value

        // 2. Change to not null while

        // 3. ensuring it has the default value

        // 4. drop the defaults again

        // we call this { four_way_default_alteration }

        $four_way_default_alteration = ($present_field->has_default() &&
            ($previous_field->null && !$present_field->null)
        );

        if (!empty($alter_actions) || !empty($null_actions)):
            if (!$four_way_default_alteration):
                $alter_actions = array_merge($alter_actions, $null_actions);
            endif;

            foreach ($alter_actions as $alter_action) :
                $this->_modify_field($model, $alter_action);
            endforeach;

            if ($four_way_default_alteration):

                // update other fields with the default
                $statement = sprintf($this->sql_update_with_default,
                    $this->_escape_identifiers($this->get_model_table($model)),
                    $this->_escape_identifiers($present_field->db_column_name()),
                    $new_default
                );
                $this->_query($statement);

                // run the null actions
                foreach ($null_actions as $null_action) :
                    $this->_modify_field($model, $null_action);
                endforeach;
            endif;
        endif;

        //did we add unique ?
        if ($adding_unique || ($previous_field->primary_key && !$present_field->primary_key && $present_field->unique)):
            $this->add_unique_constraint($model, $present_field);
        endif;

        // did we add index ?
        if (!$adding_unique && !$present_field->unique && !$previous_field->db_index && $present_field->db_index):
            $this->add_index_constraint($model, $present_field);
        endif;

        $rels_to_update = [];

        if ($present_type !== $previous_type && $present_field->primary_key && $previous_field->primary_key):
            $rels_to_update = $model->meta->get_reverse_fields();
        endif;

        // did the field become a primary key ?
        if (!$previous_field->primary_key && $present_field->primary_key):
            //todo
        endif;

        // update the fields that point to us.
        foreach ($rels_to_update as $rel_to_update) :

            $rel_field = $rel_to_update->relation_field();

            $alter_statement = [
                $rel_to_update->db_column_name() => [
                    'type' => $rel_field->db_type($this->get_connection()),
                    'constraint' => $rel_field->max_length,
                    'null' => $rel_field->null, // be explicit because of how forge modify_column works
                ],
            ];

            $this->_modify_field($rel_to_update->container_model, $alter_statement);
        endforeach;

        // did we become a relationship field or do we need to make it a db_constraint ?
        if ($present_field->is_relation &&
            $present_field->db_constraint &&
            (!$previous_field->is_relation || !$previous_field->db_constraint || $dropped_fks)
        ):
            $this->add_fk_constraint($model, $present_field);
        endif;

        // rebuild the fks we dropped in the beginning
        if ($present_field->primary_key && $previous_field->primary_key && $present_type !== $previous_type):
            $pointing_to_us = $model->meta->get_reverse_fields();
            if (!empty($pointing_to_us)):
                foreach ($pointing_to_us as $rels) :
                    if ($rels->M2M):
                        continue;
                    endif;

                    // add them back.
                    $this->add_fk_constraint($rels->container_model, $rels);
                endforeach;

            endif;
        endif;
    }

    // **************************************** ******* *************************************************
    // **************************************** HELPERS *************************************************
    // **************************************** ******* *************************************************

    public function column_sql(Field $field, $include_default = false)
    {
        $type = $field->db_type($this->get_connection());

        if (empty($type)):
            return [];
        endif;

        $sql = [];

        $sql['type'] = $type;

        // set constraint
        if ($field->has_property('max_length')):
            $sql['constraint'] = $field->max_length;
        endif;

        if ($field->has_property('max_digits') && $field->has_property('decimal_places')):
            $sql['constraint'] = [$field->max_digits, $field->decimal_places];
        endif;

        // for columns that can be signed
        if ($field->has_property('signed') && $field->signed !== null):
            $sql['unsigned'] = $field->signed === false;
        endif;

        if ($field->has_default() && ($include_default && !$this->skip_default($field))):
            // the default value
            $default_value = $this->effective_default($field);

            // if value is provided, create the defualt
            if ($default_value instanceof NOT_PROVIDED):

                $sql['default'] = $this->prepare_default($field);

            endif;

        endif;

        // the null option
        $sql['null'] = $field->null;

        // auto increament option
        if ($field->has_property('auto')):
            $sql['AUTO_INCREMENT'] = $field->auto;
        endif;

        return $sql;
    }

    /**
     * Returns the table name represented by the model , but prefixed with whatever the user set.
     * Note we dont escape here.
     *
     * @param BaseModel $model
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function get_model_table(BaseModel $model)
    {
        return sprintf('%1$s%2$s', $this->db->dbprefix, $model->meta->db_table);
    }

    /**
     * Some backends don't accept default values for certain columns types (i.e. MySQL longtext and longblob).
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function skip_default(Field $field)
    {
        return false;
    }

    /**
     * Returns a field's effective database default value.
     *
     * @param Field $field
     *
     * @return mixed|void
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function effective_default(Field $field)
    {
        if ($field->has_default()):
            $default = $field->get_default();
        else:
            $default = NOT_PROVIDED::instance();
        endif;

        if (is_callable($default)):
            $default = call_user_func($default);
        endif;

        return $field->prepare_value_before_save($default, $this->get_connection());
    }

    public function prepare_default($value)
    {
        return $value;
    }

    // **************************************** *************** *************************************************
    // **************************************** Standardization *************************************************
    // **************************************** *************** *************************************************

    /**
     * Add a field to table using alter.
     *
     * @param BaseModel $model
     * @param Field     $field
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _add_field_alter(BaseModel $model, Field $field)
    {
        $sql_def = $this->column_sql($field);
        $this->add_column($model->meta->db_table, [$field->db_column_name() => $sql_def]);
    }

    /**
     * Add table field on the table create statement.
     *
     * @param $fields
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _add_field_create($fields)
    {
        $this->add_field($fields);
    }

    /**
     * Sets up primary key for a table on create statement.
     *
     * @param BaseModel $model
     * @param Field     $field
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _add_primary_key_create(BaseModel $model)
    {
        $this->add_key($model->meta->primary_key->db_column_name(), true);
    }

    protected function _drop_field(BaseModel $model, Field $field)
    {
        $this->drop_column($model->meta->db_table, $field->db_column_name());
    }

    /**
     * Modifies the table represent by the model.
     *
     * @param BaseModel $model
     * @param $sql
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _modify_field(BaseModel $model, $sql)
    {
        $this->modify_column($model->meta->db_table, $sql);
    }

    /**
     * Create the create table statement for a model table.
     *
     * @param BaseModel $model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _table_create(BaseModel $model)
    {
        $this->create_table($model->meta->db_table, true);
    }

    protected function _table_drop(BaseModel $model)
    {
        $this->drop_table($model->meta->db_table);
    }

    /**
     * Escapes columns and table names based on the current database.
     *
     * @param $item
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _escape_identifiers($item)
    {
        return $this->db->escape_identifiers($item);
    }

    /**
     * Runs all raw sql statements. instead of passing through the db_forge.
     *
     * @param $sql
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _query($sql)
    {
        return $this->db->query($sql);
    }

    public function start_transaction($db)
    {
        $this->db->trans_start();
    }

    public function complete_transaction()
    {
        $this->db->trans_complete();
    }

    // ******************************************* *********** *************************************************
    // ******************************************* CONSTRAINTS *************************************************
    // ******************************************* *********** *************************************************

    /**
     * Creates Unique constraint to a table.
     *
     * @param BaseModel $model
     * @param Field     $field
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function add_unique_constraint(BaseModel $model, Field $field)
    {
        $this->_query($this->_unique_constraint_sql($model, $field));
    }

    /**
     * Creates Index constraint to a table.
     *
     * @param BaseModel $model
     * @param Field     $field
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function add_index_constraint(BaseModel $model, Field $field)
    {
        $this->_query($this->_index_constraint_sql($model, $field));
    }

    /**
     * Create Foreign key to table.
     *
     * @param BaseModel $model
     * @param Field     $field
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function add_fk_constraint(BaseModel $model, Field $field)
    {
        $this->_query($this->_fk_constraint_sql($model, $field));
    }

    /**
     * Drops any constraint based on the template.
     *
     * @param $template
     * @param BaseModel $model
     * @param Field $
     * @param $field
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function drop_constraint($template, BaseModel $model, Field $field, $type)
    {
        $table = $this->_escape_identifiers($this->get_model_table($model));

        $constraint_name = $this->_escape_identifiers($this->create_constraint_name($model, $field, $type));

        $this->_query(sprintf($template, $table, $constraint_name));
    }

    /**
     * @param BaseModel $model
     * @param Field     $field
     * @param string    $type
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function create_constraint_name(BaseModel $model, Field $field, $type)
    {
        return sprintf('%1$s_%2$s_%3$s', $type, $model->meta->model_name, $field->name);
    }

    /**
     * Create sql statement for adding unique constraint.
     *
     * @param BaseModel $model
     * @param Field     $field
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _unique_constraint_sql(BaseModel $model, Field $field)
    {
        $constraint_name = $this->_escape_identifiers($this->create_constraint_name($model, $field, 'uni'));

        $table = $this->_escape_identifiers($this->get_model_table($model));
        $column = $this->_escape_identifiers($field->db_column_name());

        return sprintf($this->sql_create_unique, $table, $constraint_name, $column);
    }

    /**
     * Create sql statement for adding index constraint.
     *
     * @param BaseModel $model
     * @param Field     $field
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _index_constraint_sql(BaseModel $model, Field $field)
    {
        $constraint_name = $this->_escape_identifiers($this->create_constraint_name($model, $field, 'idx'));

        $table = $this->_escape_identifiers($this->get_model_table($model));
        $column = $this->_escape_identifiers($field->db_column_name());

        return sprintf($this->sql_create_index, $constraint_name, $table, $column);
    }

    protected function _fk_constraint_sql(BaseModel $model, Field $field)
    {
        $constraint_name = $this->_escape_identifiers($this->create_constraint_name($model, $field, 'fk'));

        $table = $this->_escape_identifiers($this->get_model_table($model));

        $column = $this->_escape_identifiers($field->db_column_name());

        $target_table = $this->_escape_identifiers($this->get_model_table($field->relation->get_model()));

        $target_column = $this->_escape_identifiers($field->relation_field()->db_column_name());

        return sprintf($this->sql_create_fk, $table, $constraint_name, $column, $target_table, $target_column);
    }

    // **************************************** ***** *************************************************
    // **************************************** Magic *************************************************
    // **************************************** ***** *************************************************
}
