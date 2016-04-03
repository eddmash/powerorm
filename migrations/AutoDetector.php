<?php
namespace powerorm\migrations;

use powerorm\migrations\operations\DropTriggers;
use powerorm\model\field\DateTimeField;
use powerorm\exceptions\OrmExceptions;
use powerorm\migrations\operations\AddM2MField;
use powerorm\migrations\operations\AddModel;
use powerorm\migrations\operations\AddTriggers;
use powerorm\migrations\operations\AlterField;
use powerorm\migrations\operations\DropField;
use powerorm\migrations\operations\DropM2MField;
use powerorm\migrations\operations\DropModel;
use powerorm\migrations\operations\AddField;
use powerorm\model\field\InverseRelation;
use powerorm\model\field\RelatedField;
use powerorm\model\ProxyModel;

/**
 * Class AutoDetector
 * @package powerorm\migrations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AutoDetector{
    public $operations = [];
    public $current_state;
    public $proxies = [];

    // use proxy objects to create migration this is to allow storing model state in file
    // add reference after creating table
    public function __construct($current_state, $history_state){
        $this->history_state = $history_state;
        $this->current_state = $this->_prepare($current_state);
    }

    /**
     * Prepares the state for use in migration.
     * @internal
     * @param $current_state
     * @return mixed
     */
    public function _prepare($current_state){
        $current_state->models = $this->_model_resolution_order($current_state->models);
        return $current_state;
    }

    /**
     * Resolves how the models will be migrated based on how they depend on each other.
     * @param $models
     * @return array
     */
    public function _model_resolution_order($models){
        $order_models = [];

        // loop as many times as the size of models passed in.
        $i = 0;
        while($i< count($models)):

            foreach ($models as $name=>$model) :

                if(empty($model->relations_fields)):
                    $order_models[$name]= $model;
                endif;

                $existing_models = array_merge(array_keys($order_models), $this->migrated_models());

                $dependencies = [];
                foreach ($model->relations_fields as $field) :

                    $dependencies[] = $this->stable_name($field->related_model->meta->model_name);

                    $missing =array_diff($dependencies, $existing_models);

                    // if there is nothing missing or this is just an inverse relation just add.
                    if(count($missing)==0 || $field instanceof InverseRelation):
                        $order_models[$name]= $model;
                    endif;

                    // also self add those that have self refernces
                    $dependencies[] = $name;
                    if($this->_self_referencing($name, $dependencies)):
                        $order_models[$name]= $model;
                    endif;
                endforeach;

            endforeach;
            $i++;
        endwhile;
        return $order_models;
    }

    /**
     * Returns all the operation that need to be migrated.
     * @return array
     */
    public function get_operations(){
        return $this->find_operations();
    }

    /**
     * Gets all the models that have alredy been migrated
     * @return array
     */
    public function migrated_models(){
        return array_keys($this->history_state->models);
    }

    /**
     * Creates a list operations that need to be done based on the current state of the project.
     * @internal
     * @param $model_name
     * @param $operation
     * @param $dependency
     */
    public function operations_todo($model_name, $operation, $dependency){
        $this->operations[] = [
            'model_name'=>$this->stable_name($model_name),
            'operation'=> $operation,
            'dependency'=>$dependency
        ];
    }

    /**
     * Gets all the operations that needs to be carried out.
     * @internal
     * @return array
     * @throws OrmExceptions
     */
    public function find_operations(){
        # Generate non-rename model operations
        $this->find_deleted_models();
        $this->find_created_models();
        $this->find_added_fields();
        $this->find_dropped_fields();
        $this->find_altered_fields();
        $this->setup_triggers();


        // first resolve dependencies, to ensure that we don't add an operation that expects a model to
        // already exist only to find it does not
        $this->_operation_resolution_order();

        // try to merge some of the operations, e.g AddModel and AddField can be merged if the act on same model
        // and depend on model that already exists

        return $this->_optimize();
    }

    public function setup_triggers(){

        foreach ($this->current_state->models as $model_meta) :

            if(isset($this->history_state->models[$this->stable_name($model_meta->model_name)])):
                $past = $this->history_state->models[$this->stable_name($model_meta->model_name)];
            else:
                $past = [];
            endif;

            //find trigger fields
            $this->_create_triggers($model_meta, $past);
        endforeach;


    }

    /**
     * Detects any new models to be acted on.
     * @intenal
     */
    public function find_created_models(){

        if(!empty($this->history_state->models)):
            $past_model_names = array_keys($this->history_state->models);
        else:
            $past_model_names = [];
        endif;


        $current_models = $this->_model_resolution_order($this->current_state->models);


        $current_model_names = array_keys($current_models);

        $added_models = array_values(array_diff($current_model_names, $past_model_names));

        // go through the created models and create necessary operations
        foreach ($added_models as $added_model) :

            $model_state = $current_models[$added_model];


            // create model operation
            $this->operations_todo(
                $added_model,
                new AddModel($added_model, $model_state->local_fields, ['table_name'=>$model_state->db_table]),
                []
            );

            // add relation operations
            // we do this separately because need the model to be created before the relationships are created
            // maybe optimize later
            foreach ($model_state->relations_fields as $field) :
                $field_depends_on = [ucwords($this->stable_name($field->related_model->meta->model_name)),
                    ucwords($this->stable_name($added_model))];
                if($field instanceof InverseRelation):
                    continue;
                endif;
                if($field->M2M):
                   $this->_add_m2m_field($model_state, $field, $field_depends_on);
                else:
                    $this->operations_todo($added_model,
                        new AddField($added_model, $field, ['table_name'=>$model_state->db_table]),
                        $field_depends_on
                    );
                endif;

            endforeach;

        endforeach;
    }

    public function _create_triggers($new_state, $past_state){
        $now_trigger_fields=[];
        $past_trigger_fields=[];

        $now_fields = $new_state->fields;


        foreach ($now_fields as $field) :
            if($this->is_trigger_field($field)):
                $now_trigger_fields[$field->name] = $field;
            endif;
        endforeach;

        if(!empty($past_state)):
            $past_fields = $past_state->fields;
            foreach ($past_fields as $field) :
                if($this->is_trigger_field($field)):
                    $past_trigger_fields[$field->name] = $field;
                endif;
            endforeach;
        endif;

        $add_fields =  array_diff(array_keys($now_trigger_fields), array_keys($past_trigger_fields));
        $drop_fields = array_diff(array_keys($past_trigger_fields), array_keys($now_trigger_fields));

        if(!empty($add_fields)):
            $this->operations_todo(
                $new_state->model_name,
                new AddTriggers($new_state->model_name, $now_trigger_fields, ['table_name'=>$new_state->db_table]),
                [$new_state->model_name]
            );
            return;
        endif;

        if(!empty($drop_fields)):
            if(!empty($now_trigger_fields)):
                $this->operations_todo(
                    $new_state->model_name,
                    new AddTriggers($new_state->model_name, $now_trigger_fields, ['table_name'=>$new_state->db_table]),
                    [$new_state->model_name]
                );
            else:
                // gets here only if this was the last set of trigger fields on the model
                $this->operations_todo(
                    $this->_past_names($new_state->model_name),
                    new DropTriggers($this->_past_names($past_state->model_name),
                        $past_trigger_fields,
                        ['table_name'=>$this->_past_names($past_state->db_table)]),
                    [$this->_past_names($past_state->model_name)]
                );
            endif;
            return;
        endif;

        if(!empty($now_trigger_fields) && !empty($past_trigger_fields)):
            foreach ($now_trigger_fields as $field) :
                if(isset($past_trigger_fields[$field->name])):
                    $past = $past_trigger_fields[$field->name];
                    if($this->_is_modified($field, $past)):
                        $this->operations_todo(
                            $new_state->model_name,
                            new AddTriggers($new_state->model_name, $now_trigger_fields,
                                ['table_name'=>$new_state->db_table]),
                            [$new_state->model_name]
                        );
                    endif;
                endif;

            endforeach;
            return;
        endif;

        if(!empty($now_trigger_fields) && empty($past_trigger_fields)):

            $this->operations_todo(
                $new_state->model_name,
                new AddTriggers($new_state->model_name, $now_trigger_fields, ['table_name'=>$new_state->db_table]),
                [$new_state->model_name]
            );
            return;
        endif;

    }

    public function _past_names($name){

        if(preg_match("/_fake_/", $name)):
            return $this->stable_name(str_replace('_fake_\\', '', $name));
        endif;
        return $name;
    }

    /**
     * detect Models that have been deleted.
     * @internal
     */
    public function find_deleted_models(){

        if(empty($this->history_state->models)):
            return;
        endif;

        $current_models = $this->current_state->models;

        $current_model_names = array_keys($current_models);
        $past_model_names = array_keys($this->history_state->models);

        $deleted_models = array_values(array_diff($past_model_names, $current_model_names));

        foreach ($deleted_models as $deleted_model) :
            $model_state = $this->history_state->models[$deleted_model];
            $name = $model_state->db_table;
            if(preg_match("/_fake_/", $name)):
                $name = str_replace('_fake_\\', '', $name);
            endif;
            $this->operations_todo(
                $deleted_model,
                new DropModel($deleted_model, $model_state->local_fields, ['table_name'=>$name]),
                []
            );
        endforeach;


    }

    /**
     * Detect new fields
     * @internal
     */
    public function find_added_fields(){
        if(empty($this->history_state->models)):
          return;
        endif;

        // search for each model in the migrations, if present get its fields
        // note those that we added
        foreach ($this->current_state->models as $model_name => $model_meta) :
            if(!isset($this->history_state->models[$this->stable_name($model_name)])):
                continue;
            endif;

            $model_past_state = $this->history_state->models[$this->stable_name($model_name)];

            $current_fields = array_keys($model_meta->fields);
            $past_fields = array_keys($model_past_state->fields);

            $new_fields_names = array_values(array_diff($current_fields, $past_fields));

            if(empty($new_fields_names)):
                continue;
            endif;

            foreach ($new_fields_names as $field_name) :
                $field = $model_meta->fields[$this->stable_name($field_name)];


                if($field instanceof InverseRelation):
                    continue;
                endif;

                $this->_create_target_field($field, $model_name, $model_meta);
            endforeach;
        endforeach;


    }

    public function _create_target_field($field, $model_name, $model_obj){
        $field_depends_on = [];

        if(isset($field->related_model)):
            $field_depends_on = [ucwords($this->stable_name($field->related_model->meta->model_name)),
                ucwords($this->stable_name($model_name))];
        endif;

        if(property_exists($field, 'M2M') && $field->M2M):
            $this->_add_m2m_field($model_obj, $field, $field_depends_on);
        else:
            $this->operations_todo($model_name,
                new AddField($model_name, $field, ['table_name'=>$model_obj->db_table]),
                $field_depends_on
            );
        endif;
    }

    /**
     * Detect dropped fields.
     * @internal
     */
    public function find_dropped_fields(){

        if(empty($this->history_state->models)):
          return;
        endif;

        // search for each model in the migrations, if present get its fields
        // note those that we added
        foreach ($this->current_state->models as $model_name => $model_obj) :
            if(!isset($this->history_state->models[$model_name])):
                continue;
            endif;
            $model_past_state = $this->history_state->models[$model_name];

            $current_fields = array_keys($model_obj->fields);
            $past_fields = array_keys($model_past_state->fields);

            $dropped_fields_names = array_values(array_diff($past_fields, $current_fields));


            if(empty($dropped_fields_names)):
                continue;
            endif;

            foreach ($dropped_fields_names as $field_name) :

                $field = $model_past_state->fields[$field_name];

                $this->_drop_target_field($field, $model_name, $model_obj);
            endforeach;
        endforeach;


    }

    /**
     * @ignore
     * @param $field
     * @param $model_name
     * @param $model_obj
     */
    public function _drop_target_field($field, $model_name, $model_obj){

        $field_depends_on = [];
        if(isset($field->related_model)):
            $field_depends_on = [ucwords($this->stable_name($field->related_model->meta->model_name)),
                ucwords($this->stable_name($model_name))];
        endif;

        if(property_exists($field, 'M2M') && $field->M2M):
            $this->_drop_m2m_field($model_obj, $field, $field_depends_on);
        else:
            $this->operations_todo($model_name,
                new DropField($model_name, $field, ['table_name'=>$model_obj->db_table]),
                $field_depends_on
            );
        endif;
    }

    /**
     * Detects any field alterations.
     * @internal
     */
    public function find_altered_fields(){
        if(empty($this->history_state->models)):
            return;
        endif;

        foreach ($this->current_state->models as $model_name => $model_obj) :
            if(!isset($this->history_state->models[$model_name])):
                continue;
            endif;

            $model_past_state = $this->history_state->models[$model_name];

            $past_fields = $model_past_state->fields;

            foreach ($model_obj->fields as $name=>$field) :
                if(!isset($past_fields[$name])):
                    continue;
                endif;

                $modified_field_names = $this->_is_modified($field, $past_fields[$name]);
                // if there are not modifications found, no need to go on.
                if(empty($modified_field_names)):
                    continue;
                endif;


                // if field is moving to / from a relationship field, we need to drop the old version
                // and create the new version
                if($field instanceof RelatedField || $past_fields[$name] instanceof RelatedField):
                    $past_field_state = $past_fields[$name];
                    $this->_drop_target_field($past_field_state, $model_name, $model_obj);
                    $this->_create_target_field($field, $model_name, $model_obj);
                    continue;
                endif;

                if(!empty($modified_field_names)):
                    $this->operations_todo(
                        $model_name,
                        new AlterField($model_name,
                            [$name=>['present'=>$field, 'past'=>$past_fields[$name]]],
                            ['table_name'=>$model_obj->db_table,]
                        ),
                        []
                    );
                endif;
            endforeach;

        endforeach;

    }

    public function _is_modified($field, $past_field){
        $modified_field_names = [];
        if(!$field instanceof InverseRelation):

            $current_options = $field->options();
            $past_options = $past_field->options();


            $modified_field_names = array_diff_assoc($current_options, $past_options);


            if(!empty($modified_field_names)):
                foreach (['constraint_name'] as $f_name) :
                    if(array_key_exists($f_name, $modified_field_names)):
                        unset($modified_field_names[$f_name]);
                    endif;
                endforeach;
            endif;
        endif;

        return $modified_field_names;
    }

    public function is_trigger_field($field_obj){
        if($field_obj instanceof DateTimeField && ($field_obj->on_creation || $field_obj->on_update)):
            return TRUE;
        endif;
        return FALSE;
    }
    
    /**
     * Find dependecies of an operation based on where its in the operations list.
     * @internal
     * @param $operation
     * @param $history
     * @return array
     */
    public function _dependency_check($operation, $history){
        // get already existing models
        $existing_models = $this->migrated_models();

        if(!empty($history)):

            // get names of models they act on, this means they create or act on a modes thats already created
            foreach ($history as $e_op) :
                $existing_models[] = $this->stable_name($e_op['operation']->model_name);
            endforeach;
        endif;

        $dependencies = [];
        foreach ($operation['dependency'] as $dep) :
            $dependencies[] = $this->stable_name($dep);
        endforeach;

        // do the models that the operation depends on exist
        return array_diff($dependencies, $existing_models);
    }

    /**
     * Find out if model depends on itself i.e. self refencing.
     * @internal
     * @param $model
     * @param $dependency
     * @return bool
     */
    public function _self_referencing($model_name, $dependency){
        $depends = [];
        foreach ($dependency as $dep) :
            $depends[] = $this->stable_name($dep);
        endforeach;

        return [$this->stable_name($model_name), $this->stable_name($model_name)] == $depends;
    }

    /**
     * Reduce the number of operations, by merging operations that are mergable.
     * @internal
     * @param $operations
     * @return array
     */
    public function _optimize(){

        foreach ($this->operations as  $index=>&$main_operation) :

            // get operations between start and position of operation including the operation
            $history = array_slice($this->operations, 0, $index+1);
            // look forward through all the other operations and see if the can be merged
            // if merged remove them from the operations
            // if none add it to the new array
            foreach ($this->operations  as  $candidate_index=>$candidate_operation) :

                if($candidate_index == $index):
                    continue;
                endif;

                // check if the candidate depends on models that don't exist
                $pending = $this->_dependency_check($candidate_operation, $history);

                // if some dependencies are still pending just pass
                if(!empty($pending)):
                    continue;
                endif;

                // IF A MERGE HAS HAPPENED REMOVE THE CURRENT CANDINDATE FROM THE LIST OF OPERATIONS
                $act =  $this->_merge($main_operation, $candidate_operation);

                if($act):
                    unset($this->operations[$candidate_index]);
                endif;
            endforeach;
        endforeach;
        return array_values($this->operations);
    }

    public function myecho($item){
        echo "<pre>";
        print_r($item);
        echo "</pre>";
    }

    /**
     * Orders the operations so that operations don't depend on models that dont exist.
     * @internal
     * @throws OrmExceptions
     */
    public function _operation_resolution_order(){
        $ordered_ops = [];
        $dependent_ops = [];
        $proxy_ops = [];

        // holds names of models that already exist/ are to be created
        $created_models = $this->migrated_models();

        // first those that depend on nothing
        // those that depend on one model and is not self referencing
        foreach ($this->operations as $op) :
            $mod_name = $op['model_name'];
            // no dependency mostly AddModel operation
            if(empty($op['dependency'])):
                $ordered_ops[] = $op;
                $created_models[] = $op['model_name'];
                continue;
            endif;

            // if this is not a proxy model.
            $is_proxy_model = isset($op['operation']->options['proxy_model']) && $op['operation']->options['proxy_model'];

            // with dependency come later
            if($is_proxy_model):
                $proxy_ops[] = $op;
                continue;
            endif;

            // with dependency come later
            if(!empty($op['dependency'])):
                $dependent_ops[] = $op;
            endif;
        endforeach;

        // those with dependency come next
        foreach ($dependent_ops as $dep_op) :

            if(in_array($dep_op['model_name'], $created_models)):
                $ordered_ops[] = $dep_op;
            else:
                throw new OrmExceptions(
                    sprintf('Trying `%1$s` that depends on model `%2$s` that does not seem to exist',
                        get_class($dep_op['operation']), $dep_op['model_name']));
            endif;
        endforeach;

        foreach ($proxy_ops as $dep_op) :

            $deps =[];
            foreach ($dep_op['dependency'] as $dep) :
                $deps[] = $this->stable_name($dep);
            endforeach;
            $mission_dep = array_diff($deps, $created_models);

            if(count($mission_dep)==0):
                $ordered_ops[] = $dep_op;
            else:
                throw new OrmExceptions(
                    sprintf('Trying `%1$s` that depends on model `%2$s` that does not seem to exist',
                        get_class($dep_op['operation']), json_encode($mission_dep)));
            endif;
        endforeach;
        $this->operations = $ordered_ops;

    }

    /**
     * Creates an add operation of M2M fields.
     * @internal
     * @param $owner_meta
     * @param $field
     * @param $field_depends_on
     */
    public function _add_m2m_field($owner_meta, $field, $field_depends_on){

        if(empty($field->through)):
            $inverse_meta = $field->related_model->meta;
            $proxy = new ProxyModel($owner_meta,$inverse_meta);
            $name = $this->stable_name($owner_meta->model_name);

            $this->operations_todo(
                $name,
                new AddM2MField($name, [$field->name=>$field], $proxy,
                    ['table_name'=>$proxy->meta->db_table, 'proxy_model'=>$proxy->meta->proxy_model]),
                $field_depends_on);
        endif;
    }

    /**
     * creates a drop operation for M2M fields
     * @internal
     * @param $owner_meta
     * @param $field
     * @param $field_depends_on
     */
    public function _drop_m2m_field($owner_meta, $field, $field_depends_on){

        if(empty($field->through)):
            $inverse_meta = $field->related_model->meta;
            $proxy = new ProxyModel($owner_meta,$inverse_meta);
            $name = $this->stable_name($owner_meta->model_name);

            $this->operations_todo(
                $name,
                new DropM2MField($name, [$field->name=>$field], $proxy,
                    ['table_name'=>$proxy->meta->db_table, 'proxy_model'=>$proxy->meta->proxy_model]),
                $field_depends_on);
        endif;

    }

    /**
     * Does the actual optimization.
     * @internal
     * @param $operation
     * @param $candidate_operation
     * @return bool|mixed
     */
    public function _merge(&$operation, $candidate_operation){
        // if they act on same model they can merge
        if($this->stable_name($operation['model_name']) == $this->stable_name($candidate_operation['model_name'])):

            if($operation['operation'] instanceof AddModel &&
                $candidate_operation['operation'] instanceof AddField):
                return $this->_merge_model_add_and_field_add($operation, $candidate_operation);
            endif;

            if($operation['operation'] instanceof AddField &&
                $candidate_operation['operation'] instanceof AddField):
                return $this->_merge_field_add($operation, $candidate_operation);
            endif;

            if($operation['operation'] instanceof DropField &&
                $candidate_operation['operation'] instanceof DropField):
                return $this->_merge_field_drop($operation, $candidate_operation);
            endif;

            if($operation['operation'] instanceof AlterField &&
                $candidate_operation['operation'] instanceof AlterField):
            return $this->_merge_field_alter($operation, $candidate_operation);
        endif;

        endif;

        return FALSE;
    }

    /**
     * Tries to merge operations, returns null on fail, or $candidate_operation merged with the $operation
     * @internal
     * @param $operation
     * @param $candidate_operation
     * @param $before_candidate_operations
     * @return mixed
     */
    public function _merge_model_add_and_field_add(&$operation, $candidate_operation){
        $model_name = $operation['model_name'];

        // if self referencing just pass
        if($this->_self_referencing($model_name, $candidate_operation['dependency'])):
            return FALSE;
        endif;

        $fields = $candidate_operation['operation']->fields;

        foreach ($fields as $field) :
            $operation['operation']->fields[$field->name]= $field;
        endforeach;

        return TRUE;

    }

    /**
     * @internal
     * @param $operation
     * @param $candidate_operation
     * @return bool
     */
    public function _merge_field_add(&$operation, $candidate_operation){
        $fields = $candidate_operation['operation']->fields;

        foreach ($fields as $name=>$field) :
            $operation['operation']->fields[$name]= $field;
        endforeach;

        return TRUE;
    }

    /**
     * @internal
     * @param $operation
     * @param $candidate_operation
     * @return bool
     */
    public function _merge_field_drop($operation, $candidate_operation){
        $fields = $candidate_operation['operation']->fields;
        foreach ($fields as $field) :
            $operation['operation']->fields[$field->name]= $field;
        endforeach;

        return TRUE;
    }

    /**
     * @internal
     * @param $operation
     * @param $candidate_operation
     * @return bool
     */
    public function _merge_field_alter(&$operation, $candidate_operation){
        $next = $candidate_operation['operation']->fields;
        $present = $operation['operation']->fields;
        $operation['operation']->fields = array_merge($present, $next);
        return TRUE;
    }

    /**
     * makes for consistent names to use across the class.
     * @internal
     * @param $name
     * @return string
     */
    public function stable_name($name){
        return strtolower($name);
    }
}

// ToDo very serious validation on foreignkey constraint, based on cascade passed in.
// tOdO set default if add foreignkey on a table with values
// tOdO validate if fk is null and it is not set as accepting null
//ToDo on migration check if any records exist, if any exist ask for a value for those