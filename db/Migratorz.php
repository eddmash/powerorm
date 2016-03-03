<?php

use powerorm\db\MysqlCreateOperation;
use powerorm\db\MysqlAlterAddOperation;
use powerorm\db\MysqlAlterDropOperation;
use powerorm\db\MysqlModifyOperation;
use powerorm\model\ProxyModel;
use powerorm\model\OrmExceptions;

//ToDo check db for its state before migrate is called to ensure we are not recreated items that have already been created

/**
 * Handles migration of each model individually
 * Class Migrator
 */
class Migratorz{

    public static $model_path = APPPATH.'models/';
    public $_migrations_path = APPPATH.'migrations/';
    public $m2m_fields;
    protected $operation;
    protected $operations= [];
    private $_ci;
    private $model;
    private $model_name;
    private $cyclic_relation=[];

    public function __construct($model){
        $this->_ci =& get_instance();

        if(is_string($model)):
            var_dump($model.'===========using==========');
            $this->model_name = strtolower($model);

            $this->model = $this->load_model($model);
        endif;
//
        if(is_object($model)):
            $this->model = $model;
            $this->model_name = strtolower($model->meta->model_name);
        endif;
    }

    /**
     * loads a model files into scope
     */
    public function load_model($model){
        $model = ucwords(strtolower($model));
        $this->_ci->load->model($model);
        return $this->_ci->{$model};
    }

    public function _load_file($model){
        require_once $model;
    }

    public static function makemigrations(){
        foreach (Migrator::all_models_names() as $model) :

            Migrator::model_migrations_generator($model);

        endforeach;
    }

    /**
     * Names of models in the application
     * @return array
     */
    public static function all_models_names(){
        $classes = [];
        foreach (Migrator::all_models_files() as $file) :
            $classes[]=strtolower(basename($file, '.php'));
        endforeach;

        return $classes;
    }

    /**
     * list of all model files in the application
     * @return array
     */
    public static function all_models_files(){
        $model_files = [];

        foreach (glob(Migrator::$model_path."*.php") as $file) :
            $model_files[]=$file;
        endforeach;

        return $model_files;
    }

    public static function model_migrations_generator($model){
        // check if it exists before we create
        if(is_string($model)):
            $model = ucwords(strtolower($model));
        endif;
        $mi = new Migrator($model);

        // resolve dependencies
        $mi->resolve_dependencies();

        $mi->create_migration_files();

//        $mi->resolve_m2m();

    }
    public function resolve_m2m(){

        $fields = $this->model->meta->relations_fields;

        if(empty($fields)):
            return;
        endif;

        $proxy_models = [];
        foreach ($fields as $field) :
            if(! $field instanceof ManyToManyField):
                continue;
            endif;
            $related_m = $field->related_model;
            $proxy_model = new ProxyModel($this->model, $related_m);

            Migrator::model_migrations_generator($proxy_model);
        endforeach;
    }

    public function resolve_dependencies(){
        $this->circular_dependency();


        // if this model has dependencies resolve them.
        if(empty($this->dependencies())):
            return;
        endif;
        foreach ($this->dependencies() as $dependent_model) :
            $dependent_model = strtolower($dependent_model);

            // ensure model has been created
            if(!in_array($dependent_model, $this->all_models_names())):
                throw new OrmExceptions(
                    sprintf('Model `%1$s` depends on a non-existent model `%2$s`',
                        $this->model_name, $dependent_model));
            endif;

            // if we have any cyclic relations just bypass for now
//            if(!in_array(strtolower($this->model_name), $this->cyclic_relation)):
//                continue;
//            endif;
        var_dump( $this->model_name);
        var_dump( $this->cyclic_relation);
            // ensure that the dependent model is not migrated first
            if($this->model_latest_migration($dependent_model)==NULL &&
                !in_array(strtolower($dependent_model), $this->cyclic_relation)):

                var_dump("not migrated model ------------".$dependent_model);
                Migrator::model_migrations_generator($dependent_model);
            endif;

        endforeach;

    }

    /**
     * Goes through all the dependencies on the current model getting model that depend on the current model
     */
    public function circular_dependency(){
        $circular = NULL;
        foreach ($this->dependencies() as $dep_model) :
            $dep_obj =$this->load_model($dep_model);
            $dependencies = $this->dependencies($dep_obj);
            if(in_array(ucwords(strtolower($this->model_name)), $dependencies)):
                $this->cyclic_relation[] =  strtolower($dep_model);
            endif;
        endforeach;

        return $circular;
    }

    public function dependencies($model_obj=NULL){
        $dependencies = [];
        if($model_obj == NULL):
            $fields = $this->model->meta->relations_fields;
        else:
            $fields = $model_obj->meta->relations_fields;
        endif;

        foreach ($fields as $field) :
            $dependencies[] = ucwords($field->related_model->meta->model_name);
        endforeach;
        $dependencies = array_unique($dependencies);

        return array_unique($dependencies);
    }

    public function model_latest_migration($model_name){
        $model_name = strtolower($model_name);

        $migrated_models_states = $this->migrated_models();

        if(array_key_exists($model_name, $migrated_models_states)):
            return $migrated_models_states[$model_name];
        endif;
        return NULL;
    }

    /**
     * Returns models that have been migrated with there state,
     * this returns the latest migration of a model only.
     *
     * @return array
     */
    public function migrated_models(){
        $migrated_models = [];
        $this->load_migrations();
        // get all models that have migrations
        foreach ($this->all_migrations_classes() as $migration_name) :
            $migration = new $migration_name();
            $model_name = $migration->model;

            // don't add it if its already added
            if(array_key_exists($model_name, $migrated_models)):
                continue;
            endif;

            $migrated_models[strtolower($migration->model)] = $migration->state();
        endforeach;

        return $migrated_models;
    }

    /**
     * Loads a model files into scope
     */
    public function load_migrations(){
        $this->_ci->load->library('migration');
        foreach ($this->all_migrations_files() as $file) :
            $this->_load_file($file);
        endforeach;
    }


    /**
     * @return array
     */
    public function all_migrations_classes(){
        $classes = [];
        foreach ($this->all_migrations_files() as $file) :
            $classes[]=$this->migration_class_name($file);
        endforeach;

        return $classes;
    }

    public function migration_class_name($file){
        $file_name = preg_split("/_/", trim(basename($file, '.php')), 2);

        $name = str_replace("_", " ", $file_name[1]);
        $name = ucwords(strtolower($name));
        $name = str_replace(" ", "_", $name);

        return sprintf('Migration_%1$s', $name);
    }

    public function create_migration_files(){
        $this->change_detector();

        if(!empty($this->operations)):
            $this->writer();
        else:
            echo PHP_EOL."***** No Changes were detected".PHP_EOL.PHP_EOL;
        endif;
    }

    public function change_detector(){
        // check if there are any model to drop

        $all_models = $this->all_models_names();
        $migrated_models = array_values(array_keys($this->migrated_models()));


        // ToDo handle dropping table
        // since adding a new table to the database requires we have a file that represets
        // it we will add this into the next migrartion file
//        $droped_models = array_values(array_diff($migrated_models, $all_models));
//
//        if(!empty($droped_models)):
//
//            foreach ($droped_models as $dropped_model) :
//                $table_name = $this->migrated_models()[$dropped_model]['model_meta']['db_table'];
//                var_dump($table_name);
////                $migrated_models[$dropped_model];
//                $this->operations[] = new MysqlDropOperation($table_name);
//            endforeach;
//        endif;

        // ************************************* CREATION OF NEW MODEL ****************************

        $present_state = $this->model_state();
        $previous_state = $this->previous_state();

        // if not previous state it mean this is a new model so it needs to created
        if($previous_state == NULL):
            // return create operation
            //create operation
            $table_name = $this->model->meta->db_table;
            $this->operations[] = new MysqlCreateOperation($table_name, $present_state);
            return;
        endif;


        // ************************************* ADDITION OF COLUMNS ****************************

        // compare the field since we clearly have a history.
        $present_fields = array_keys($present_state['fields']);
        $previous_fields = array_keys($previous_state['fields']);

        // if in present_fields but not in previous_fields, means we need to add fields
        $add_field_names = array_values(array_diff($present_fields, $previous_fields));

        // and vice versa
        $drop_field_names = array_values(array_diff($previous_fields, $present_fields));

        if(!empty($add_field_names) || !empty($drop_field_names)):
            $table_name = $this->model->meta->db_table;

            // return an alter operation, it should take both fields and there states
            $add_fields = [];
            foreach ($add_field_names as $field_name) :
                $field = $this->_ignore_m2m($present_state['fields'][$field_name]);
                if(!empty($field)):
                    $add_fields[]=$field;
                endif;
            endforeach;

            $drop_fields = [];
            foreach ($drop_field_names as $field_name) :
                $drop_field = $previous_state['fields'][$field_name];

                // since we dont store the related value in the migration file
                // we need to reset it here
                if(isset($drop_field['model']) && !empty($drop_field['model'])):
                    $class_name = ucwords(strtolower($drop_field['model']));
//                        $this->load_model($class_name);
                    $drop_field['related_model'] = $this->load_model($class_name);;
                endif;

                $drop_fields[]=$drop_field;
            endforeach;

            if(!empty($add_fields)):
                $this->operations[] = new MysqlAlterAddOperation($table_name, $add_fields);
            endif;

            if(!empty($drop_field)):
                $this->operations[] = new MysqlAlterDropOperation($table_name, $drop_fields);
            endif;



        // ************************************* MODIFICATION OF COLUMNS ****************************



            // if we are here it means we need to check if any fields were altered
            $modified_fields = [];

            foreach ($present_fields as $present_field_name) :

                if(!isset($previous_state['fields'][$present_field_name])):
                    continue;
                endif;
                $field_past_state = $previous_state['fields'][$present_field_name];

                $field_present_state = $present_state['fields'][$present_field_name];

                // something change so a modification needs to happened
                // the side that matters is the change on the present model
                $attr_diff = array_diff_assoc($field_present_state, $field_past_state);


                // don't look at changes in constrain_name since its dynamically generated for each model
                if(array_key_exists('constraint_name', $attr_diff)):
                    unset($attr_diff['constraint_name']);
                endif;

                if(array_key_exists('related_model', $attr_diff)):
                    unset($attr_diff['related_model']);
                endif;

                // if something still remains, then thats a legitimate change
                if(!empty($attr_diff)):
                    $modified_fields[$present_field_name]= array("present"=>$field_present_state,
                        "past"=>$field_past_state);
                endif;

            endforeach;

            if(!empty($modified_fields)):
                $table_name = $this->model->meta->db_table;
                // modification operation
                $this->operations[] = new MysqlModifyOperation($table_name, $modified_fields);
            endif;

        endif;


    }

    public function _ignore_m2m($field){

        if(isset($field['M2M']) && empty($field['M2M'])):
            return $field;
        endif;
    }

    public function model_state(){
        $state['model_meta']['model_name']= $this->model->meta->model_name;
        $state['model_meta']['db_table']= $this->model->meta->db_table;
        $state['model_meta']['primary_key']= $this->model->meta->primary_key->name;
        $state['fields'] = [];
        foreach ($this->model->meta->fields as $field_name=>$field_object) :

            $state['fields'][$field_name]= $this->model_field_state($field_object);
        endforeach;

        return $state;
    }

    /**
     * Field State.
     * @param $field_name
     * @param $field_object
     * @return array
     */
    public function model_field_state($field_object){
        $state = $field_object->skeleton()['field_options'];

        return $state;
    }

    public function previous_state(){
        $latest_migration = $this->model_latest_migration($this->model->meta->model_name);
        if($latest_migration==NULL):
            return NULL;
        endif;

        return $latest_migration;

    }

    public function _model_name($model){
        $model_name = $model->meta->model_name;
        if($model instanceof ProxyModel):
            $model_name = $model->model_name;
        endif;

        return $model_name;
    }

    public function writer(){

        // make file and class as unique as possible
        $microtime = microtime(TRUE);
        $time = floor($microtime);
        $micro = floor(10000 * ($microtime-$time));

        $timestamp = sprintf('%1$s_%2$s', $time, $micro);

        $file_name = sprintf('%1$s_%2$s_%3$s_%4$s',
            $this->file_timestamp(),
            strtolower($this->op_type()),
            strtolower($this->_model_name($this->model)),
            $timestamp);

        $template = $this->migration_template($timestamp);


        // absolute path to file
        $file = $this->_migrations_path.$file_name.".php";

        $file_handle = fopen($file,"w");
        fprintf($file_handle, $template);
        fclose($file_handle);

        chmod($file, 0777);


    }

    // ToDo check config it use timestamp or sequential
    public function file_timestamp(){
        $microtime = microtime(TRUE);
        $time = floor($microtime);
        $micro =($microtime - $time) * 100;

        $timestamp = date("YmdHis", ($time+$micro));

        // incase generated timestamp is less than the last timestamp fast forward
        if($timestamp <= $this->last_timestamp()):
            $timestamp = $this->last_timestamp()+1;
        endif;

        return $timestamp;
    }

    public function last_timestamp(){
        $model_files = [];
        foreach (glob(APPPATH.'migrations/'."*.php") as $file) :
            $model_files[]=$file;
        endforeach;
        rsort($model_files);

        foreach ($model_files as $file) :
            $file = basename($file, '.php');
            return (int)preg_split('/_/', $file, 2)[0];
        endforeach;

    }

    public function up(){
        $statement = '';
        foreach ($this->operations as $operation) :
            $statement .=$operation->up();
        endforeach;

        return $statement;
    }

    public function down(){
        $statement = '';
        foreach ($this->operations as $operation) :
            $statement .=$operation->down();
        endforeach;

        return $statement;
    }

    public function op_type(){
        $statement = '';
        foreach ($this->operations as $operation) :
            $statement =$operation->op_type();
        endforeach;

        return $statement;
    }

    public function migration_template($timestamp){
        $line_break = PHP_EOL;
        $tab = "\t";

        $class_name = $this->class_name()."_".$timestamp;

        $model_name =$this->model_name;
        $depends = $this->pretty_dump($this->dependencies(), 1, ';');

        $migration_file = "<?php $line_break";
        $migration_file .="class $class_name extends CI_Migration{ $line_break";
        $migration_file .=$tab."public \$model= '$model_name';$line_break$line_break";
        $migration_file .=$tab."public \$depends= $depends $line_break$line_break";
        $migration_file .=$tab."public function up(){ $line_break";

        $migration_file .= $this->up();
//        $migration_file .= $this->operation->up_triggers();

        $migration_file .=$tab."}$line_break$line_break";
        $migration_file .=$tab."public function down(){ $line_break";

//        $migration_file .= $this->operation->down_triggers();
        $migration_file .= $this->down();

        $migration_file .=$tab."}$line_break$line_break";
        $migration_file .=$tab."public function state(){ $line_break";

        $migration_file .= $this->state();

        $migration_file .=$tab."}$line_break$line_break";
        $migration_file .="}";

        return $migration_file;
    }

    public function class_name(){
        $name = sprintf('migration_%1$s_%2$s',
            ucwords(strtolower($this->op_type())),
            ucwords(strtolower($this->_model_name($this->model))));

        $name = str_replace("_", " ", $name);
        $name = ucwords(strtolower($name));
        $name = str_replace(" ", "_", $name);

        return $name;
    }

    public function pretty_dump($data=array(), $indent=1, $close='', $start=''){

        $indent_character = "\t";

        $outer_indent = $indent_character;

        $count = 1;

        while($count<=$indent):
            $outer_indent .= $indent_character;
            $count++;
        endwhile;

        $inner_indent = $outer_indent.$indent_character;

        $string_state = '';
        if(!empty($start)):
            $string_state .=$outer_indent."$start";
        endif;

        $string_state .= PHP_EOL.$outer_indent."[".PHP_EOL;

        if(!empty($data)):
            foreach ($data as $key=>$value) :

                if(is_array($value)):
                    if(!is_numeric($key)):
                        $string_state .= "$inner_indent'$key'=>";
                    endif;
                    $string_state .= $inner_indent.$this->pretty_dump($value, $indent+1, ',');
                endif;


                if(is_numeric($key) && !is_array($value)):

                    $string_state .= $inner_indent."'$value',".PHP_EOL;
                endif;

                if(!is_numeric($key) && !is_array($value)):
                    $string_state .= $inner_indent."'$key'=>'$value',".PHP_EOL;

                endif;
            endforeach;
        endif;

        $string_state .=  $outer_indent."]";

        if(!empty($close)):
            $string_state .=  $close;
        endif;
        $string_state .=  PHP_EOL;

        return $string_state;
    }

    /**
     * Model state
     * @return array
     */
    public function state(){

        $fields = [];
        $state = $this->model_state();
        foreach($state['fields'] as $key=>$field):

            if(array_key_exists('related_model', $field)):
                unset($field['related_model']);
            endif;
            $fields[$key] = $field;
        endforeach;
        $state['fields'] = $fields;

        return $this->pretty_dump($state, 1, ';', 'return');

    }

    public function myecho($item){
        echo "<pre>";
        print_r($item);
        echo "</pre>";
    }
}
