<?php
/**
 * Created by http://eddmash.com.
 * User: eddmash
 * Date: 5/28/16
 * Time: 10:41 AM
 */

namespace powerorm\db\schema;


use powerorm\exceptions\ValueError;

/**
 * Class BaseEditor
 * @package powerorm\db\schema
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
trait BaseEditor
{

    //   ************************** TEMPLATES to override ********************************

    public function tpl_fk_constraint($constraint_name, $column_name, $relation_table, $relation_table_column){
        return sprintf(
            'CONSTRAINT %1$s
                    FOREIGN KEY (%2$s)
                    REFERENCES %3$s(%4$s)',
            $constraint_name,
            $column_name,
            $relation_table,
            $relation_table_column
        );

    }

    public function tpl_drop_fk(){
        return 'ALTER TABLE %1$s DROP CONSTRAINT %2$s';
    }

    public function tpl_unique_constraint($columns){
        return sprintf("UNIQUE (%s)", $columns);
    }

    public function tpl_index_constraint($constraint_name){

        return sprintf("INDEX (%s)", $constraint_name);
    }

    public function tpl_alter_column_type($column, $type){
        return sprintf('ALTER COLUMN %1$s TYPE %2$s', $column, $type);
    }
    
    public function tpl_alter_column($table, $change){
        return sprintf('ALTER TABLE %1$s %2$s', $table, $change);
    }

    // ************************ Prepare for database alteration **************************

    public function add_model_field($model, $field){

        // many to many
        if($field->M2M && $field->relation->through->meta->auto_created):
            $this->create_model($field->relation->through);
            return;
        endif;

        $sql_def = $this->field_as_sql($field);

        if(empty($sql_def)):
            return;
        endif;

        // normal column
        $this->add_column($model->meta->db_table, [$field->db_column_name()=>$sql_def]);

        // unique constraint
        if($field->is_unique()):
            $this->add_unique_key($model, $field);
        endif;

        // index constraint
        if($field->db_index and ! $field->is_unique()):
            $this->add_index_key($model, $field);
        endif;

        // set up constraint if we have to
        if($field->is_relation && $field->db_constraint):
            $this->add_foreign_key_constraint($model, $field);
        endif;
    }

    public function drop_model_field($model, $field){
        // if we created a table for m2m
        if($field->M2M && $field->relation->through->meta->auto_created):
            $this->drop_model($field->relation->through);
            return;
        endif;

        if($field->is_relation):
            // drop contraints
            $this->drop_foreign_key_constraint($model, $field);
        endif;

        // drop column
        $this->drop_column($model->meta->db_table, $field->db_column_name());
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
     * @param $model
     * @param $previous_field
     * @param $present_field
     * @param bool|False $strict
     * @return array|null
     * @throws ValueError
     */
    public function alter_model_field($model, $previous_field, $present_field, $strict=False){
        $previous_type = $previous_field->db_type();
        $present_type = $present_field->db_type();

        // ensure they have a type if they are not relationship fields
        if((is_null($present_type) && is_null($present_field->relation->through)) ||
            (is_null($previous_type) && is_null($previous_field->relation->through))):
            throw new ValueError(
                sprintf('Cannot alter field %1$s into %2$s they do not properly define db_type',
                    $present_field->name, $previous_field->name));
        endif;

        // alter many to many
        if(
            is_null($present_type) &&
            is_null($previous_type) &&
            (
                !is_null($present_field->relation->through) &&
                !is_null($previous_field->relation->through) &&
                $present_field->relation->through->auto_created &&
                $previous_field->relation->through->auto_created
            )
        ):
            return $this->_alter_many_to_many($model, $previous_field, $present_field, $strict);
        endif;

        // if its a many to many but the model is not auto created just pass
        if(
            is_null($present_type) &&
            is_null($previous_type) &&
            (
                !is_null($present_field->relation->through) &&
                !is_null($previous_field->relation->through) &&
                ! $present_field->relation->through->auto_created &&
                ! $previous_field->relation->through->auto_created
            )
        ):
            return NULL;
        endif;

        // If we get here and we still dont have a field type
        if(is_null($present_type) || is_null($previous_type)):
            throw new ValueError(
                sprintf('Cannot alter field %1$s into %2$s - they are not compatible types (you cannot alter to or '.
                    'from M2M fields, or add or remove through= on M2M fields', $present_field, $previous_field));
        endif;

        $this->_alter_field($model, $previous_field, $present_field, $previous_type, $present_type,$strict);
    }

    public function create_model($model){
        // this assumes fields set_from_name has been invoked
        $fields = [];
        $unique_fields = [];
        foreach ($model->meta->fields as $name=>$field) :

            if($field->is_unique()):
                $unique_fields[] = $field;
            endif;

            $sql_def = $this->field_as_sql($field);
            if(!empty($sql_def)):
                $fields[$field->db_column_name()] = $sql_def;
            endif;
        endforeach;

        $this->add_field($fields);

        // create the primary key
        $this->add_primary_key($model->meta->primary_key->db_column_name());

        $this->create_table($model->meta->db_table,TRUE);

        // add unique constraint
        foreach ($unique_fields as $field) :
            $this->add_unique_key($model, $field);
        endforeach;


        // add fk constraint
        foreach ($model->meta->relations_fields as $name=>$relation_field) :
            if($relation_field->inverse || $relation_field->M2M):
                continue;
            endif;
            $this->add_foreign_key_constraint($model, $relation_field);
        endforeach;

        // many to many
        foreach ($model->meta->relations_fields as $name=>$relation_field) :
            if( $relation_field->M2M && $relation_field->relation->through->meta->auto_created):
                $this->create_model($relation_field->relation->through);
            endif;
        endforeach;

    }
    
    public function drop_model($model){

        // first remove any automatically created models
        foreach ($model->meta->relations_fields as $name=>$field) :
            //todo
        endforeach;

        $this->drop_table($model->meta->db_table);

    }


    public function _alter_field($model, $previous_field, $present_field, $previous_type, $present_type,$strict){

        //  ***********************  CONSTRAINT DROPS IF WE HAVE TO ***********************

        // drop fks if they exist to allow as to work we will recreate them later
        $dropped_fks = [];
        if($previous_field->relation && $previous_field->db_constraint):
            // take note of it
            $dropped_fks[] = $this->_constrain_name($model->meta->model_name, $previous_field->name, 'fk');
            // drop it
            $this->drop_foreign_key_constraint($model, $previous_field);
        endif;

        //  *********************** Drop uniqueness ***********************

        if($previous_field->is_unique() && !$present_field->is_unique()):
            $this->drop_unique_key($model, $previous_field);
        endif;
        // todo drop index
        // was the column name renamed, this can happen when moving to/from a relational field. e.g ForeignKey
        // here we just rename the column only
        if($present_field->db_column_name() !== $previous_field->db_column_name()):
            $altered_attrs = [];

            $altered_attrs [$previous_field->db_column_name()] = [
                'name'=> $present_field->db_column_name(),
                'type'=> $previous_type,
                'constraint'=>  $previous_field->max_length,
            ];
            $this->modify_column($model->meta->db_table, $altered_attrs);
            // empty it
        endif;

        // **** OPEN SEASON FOR EVERYTHING ELSE
        //  *********************** Type ***********************

        $column_name = $present_field->db_column_name();

        if($present_type !== $previous_type || $previous_field->max_length !== $present_field->max_length):
//            $altered_attrs = [];
//
//            $altered_attrs[$column_name] = [
//                'type'=> $present_type,
//                'constraint'=> $present_field->max_length,
//            ];
//            $this->modify_column($model->meta->db_table, $altered_attrs);
            $this->alter_column_type($model, $present_field, $previous_field);

        endif;

        // *********************** Default ***********************

        $present_default = $present_field->get_default();
        $previous_default = $previous_field->get_default();

        if(
            $present_default !== $previous_default &&
            !is_null($present_default) &&
            $this->skip_default($present_field)
        ):
            $alter_default = TRUE;

            $altered_attrs = [];

            $altered_attrs [$previous_field->db_column_name()] = [
                'default'=> $present_field->default,
                'type'=> $previous_type,
                'constraint'=>  $previous_field->max_length,
            ];

            $this->modify_column($model->meta->db_table, $altered_attrs);
        endif;

        //  *********************** Null ***********************

        if($present_field->null !== $previous_field->null):
            $altered_attrs = [];

            $altered_attrs[$column_name] = [
                'null'=> $present_field->null,
                'type'=> $previous_type,
                'constraint'=>  $previous_field->max_length,
            ];
            $this->modify_column($model->meta->db_table, $altered_attrs);
        endif;

        // ******* CREATE CONSTRAINTS IF WE HAVE TO ***********************
        //  *********************** add Unique ***********************
        if(!$previous_field->is_unique() && $present_field->is_unique()):
            $this->add_unique_key($model, $present_field);
        endif;
        // todo add index
        // todo alter pk
        // Add FK
        //  - add foreign key, if current field is a relation constraint
        //  - and no fk constraints we dropped or previous field is not a relation field that is not a constraint
        if(
            $present_field->relation &&
            $present_field->db_constraint &&
            (
                !empty($dropped_fks) ||
                is_null($previous_field->relation) ||
                !$previous_field->db_constraint
            )
        ):

            $this->add_foreign_key_constraint($model, $present_field);
        endif;

    }

    public function _alter_many_to_many($model, $previous_field, $present_field, $strict){
        return [];
    }

    // ****************************** Alter actual database **********************************


    public function field_as_sql($field){

        $type = $field->db_type();
        if(empty($type)):
            return [];
        endif;

        $sql = [];

        $sql['type'] = $type;

        // set constraint
        if($field->has_property('max_length')):
            $sql['constraint'] = $field->max_length;
        endif;

        if($field->has_property('max_digits') && $field->has_property('decimal_places')):
            $sql['constraint'] = [$field->max_digits, $field->decimal_places];
        endif;

        // for columns that can be signed
        if($field->has_property('signed') && $field->signed !== NULL):
            $sql['unsigned'] = $field->signed ===FALSE;
        endif;

        if($field->has_default()):
            // the default value
            $sql['default'] = $this->default_value($field);

        endif;

        // the null option
        $sql['null'] = $field->null;

        // auto increament option
        if($field->has_property('auto')):
            $sql['AUTO_INCREMENT'] = $field->auto;
        endif;

        return $sql;
    }

    public function default_value($field){

        switch (gettype($field->default)) {
            case 'integer':
                $value = (string) $field->default;
                break;
            case 'double':
                $value = str_replace(',', '.', (string) $field->default);
                break;
            case 'boolean':
                $value = $this->$field ? 'TRUE' : 'FALSE';
                break;
            case 'object':
                $value = (string) $field->default;
                break;
            default:
                $value = $field->default;
        }


        return $value;
    }

    public function add_primary_key($name){
        $this->add_key($name, TRUE);
    }

    public function add_unique_key($model, $field){

        $constraint_name = $this->_constrain_name($model->meta->model_name, $field->name, 'uni');
        $table = $this->db->protect_identifiers($model->meta->db_table, TRUE);
        $this->db->query($this->add_unique_constraint($table, $constraint_name, $field->db_column_name()));
    }

    public function add_index_key($model, $field){

        $constraint_name = $this->_constrain_name($model->meta->model_name, $field->name, 'idx');
        $table = $this->db->protect_identifiers($model->meta->db_table, TRUE);
        $this->db->query($this->add_index_constraint($table, $constraint_name, $field->db_column_name()));
    }

    public function drop_unique_key($model, $field){
        $table = $this->db->protect_identifiers($model->meta->db_table, TRUE);
        $constraint_name = $this->_constrain_name($model->meta->model_name, $field->name, 'uni');

        $this->db->query($this->drop_unique_constraint($table, $constraint_name));
    }

    public function _constrain_name($model_name, $field_name, $type){
        return sprintf('%1$s_%2$s_%3$s', $type, $model_name, $field_name);
    }
    
    public function alter_column_type($model, $present_field, $previous_field){

        $present_type = $present_field->db_type();
        $constraint=  $present_field->max_length;

        $type = (empty($constraint)) ? $present_type : sprintf('%1$s(%2$s)', $present_type, $constraint);

        $table_name = $this->db->protect_identifiers($model->meta->db_table, TRUE);
        $change = $this->tpl_alter_column_type($present_field->db_column_name(), $type);

        $this->db->query($this->tpl_alter_column($table_name, $change));
    }

    //    ************************** TEMPLATES END ********************************

    public function add_unique_constraint($table, $constraint_name, $column_name){
        return sprintf('ALTER TABLE %1$s ADD CONSTRAINT %2$s %3$s',
            $table,
            $constraint_name,
            $this->tpl_unique_constraint($column_name));
    }

    public function add_index_constraint($table, $constraint_name, $column_name){
        return sprintf('CREATE %1$s ON %2$s(%3$s)',
            $this->tpl_index_constraint($constraint_name),
            $table,
            $column_name
        );
    }

    public function drop_unique_constraint($table, $constraint_name){
        return sprintf('ALTER TABLE %1$s DROP INDEX %2$s', $table, $constraint_name);
    }

    // interface implementations
    public function add_foreign_key_constraint($model, $field){
        $this->add_column($model->meta->db_table, [$this->_create_fk_contraint($model, $field)]);
    }

    public function drop_foreign_key_constraint($model, $field){

        $this->db->query($this->_drop_fk_constraint($model, $field));
    }

    public function _create_fk_contraint($model, $field){
        $column_name = $field->db_column_name();
        $relation_table = $this->db->protect_identifiers($field->relation->model()->meta->db_table, TRUE);

        $relation_table_column = $field->relation_field()->db_column_name();
        $constraint_name = $this->_constrain_name($model->meta->model_name, $field->name, 'fk');

        $constraint = $this->tpl_fk_constraint($constraint_name, $column_name, $relation_table, $relation_table_column);
        return $constraint;
    }

    public function _drop_fk_constraint($model, $field){
        $constraint_name = $this->_constrain_name($model->meta->model_name, $field->name, 'fk');


        $table = $this->db->protect_identifiers($model->meta->db_table, TRUE);

        return sprintf($this->tpl_drop_fk(), $table, $this->db->escape_identifiers($constraint_name));
    }

    /**
     * Some backends don't accept default values for certain columns types (i.e. MySQL longtext and longblob).
     * MySQL doesn't accept default values for TEXT and BLOB types, and implicitly treats these columns as nullable.
     * @return mixed     *
     */
    public function skip_default($field){

        $db_type = $field->db_type();
        $cols = [
            'tinyblob', 'blob', 'mediumblob', 'longblob',
            'tinytext', 'text', 'mediumtext', 'longtext'
        ];
        return !is_null($db_type) && in_array(strtolower($db_type), $cols);
    }

}