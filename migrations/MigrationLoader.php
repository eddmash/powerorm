<?php
namespace powerorm\migrations;

/**
 * Class MigrationLoader
 * @package powerorm\migrations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class MigrationLoader{

    public $_migrations_path = APPPATH.'migrations/';

    public function __construct(){
        $this->load_migrations();
    }

    public function find_model_state($model_name){
        foreach ($this->migration_states() as $state) :
            if($state['model_name'] == $model_name):
                return $state;
            endif;
        endforeach;
    }

    public function to_field($field_details){
        $field_class = $field_details['class'];
        return new $field_class($field_details['field_options']);
    }

    public function to_models(){
        $models = [];
        foreach ($this->migration_states() as $state) :

            $class_name = ucwords(strtolower($state['model_name']));

            $new_model = $this->_define_load_class($class_name);
            // add fields
            foreach ($state['fields'] as $field_name=>$field_state) :
                $new_model->{$field_name} = $this->to_field($field_state);
            endforeach;

            $models[$state['model_name']] = $new_model;

        endforeach;
        return $models;
    }

    public function _define_load_class($class_name){
        // we create a new namespace and define new classes because,
        // we might be dealing with a model that has been dropped
        // Meaning if we try to load the model using the normal codeigniter way,
        // we will get and error of model does not exist
        $class = 'namespace _fake_;

            class %1$s extends \PModel{
                 public function fields(){}
            }';

        if(!class_exists('_fake_\\'.$class_name, FALSE)):
            eval(sprintf($class, $class_name));
        endif;

        $class_name = '_fake_\\'.$class_name;
        return new $class_name();
    }

    /**
     * Imports php file passed in
     * @param $file the file to include in full, i.e with its absoute path
     */
    public function _import_file($file){
        require_once $file;
    }

    /**
     * Get all the migration files, Absolute paths to each file.
     * @return array
     */
    public function get_migrations_files(){
        $migration_files = [];

        foreach (glob($this->_migrations_path."*.php") as $file) :
            $migration_files[]=$file;
        endforeach;

        return $migration_files;
    }

    public function migration_class_name($file){
        $file_name = preg_split("/_/", trim(basename($file, '.php')), 2);

        $name = str_replace("_", " ", $file_name[1]);
        $name = ucwords(strtolower($name));
        $name = str_replace(" ", "_", $name);

        return sprintf('Migration_%1$s', $name);
    }

    /**
     * @return array
     */
    public function get_migrations_classes(){
        $classes = [];
        foreach ($this->get_migrations_files() as $file) :
            $classes[]=$this->migration_class_name($file);
        endforeach;

        return $classes;
    }

    /**
     * Import a migration classes into scope
     */
    public function load_migrations(){
        foreach ($this->get_migrations_files() as $file) :
            $this->_import_file($file);
        endforeach;
    }

    /**
     * Returns models that have been migrated with there state,
     * this returns the latest migration of a model only.
     *
     * @return array
     */
    public function _models_migrated_state(){
        $migrated_models = [];
        $this->load_migrations();
        // get all models that have migrations
        foreach ($this->get_migrations_classes() as $migration_name) :
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

    public function _harmonize_migrations($migration_states){

        foreach ($migration_states as $index=>&$m_state) :

            // look forward see if there is any other migration related to this one
            foreach ($migration_states as $in_index=>$inner_state) :

                if($m_state['model_name'] == $inner_state['model_name'] && $index != $in_index):

                    $state = $this->_state_harmony($m_state, $inner_state);

                    if(!empty($state)):
                        unset($migration_states[$in_index]);
                    endif;

                    // drop model
                    if($m_state['operation']=='add_model' && $inner_state['operation']=='drop_model'):
                        unset($migration_states[$index]);
                        unset($migration_states[$in_index]);
                    endif;
                endif;

            endforeach;

        endforeach;
        return $migration_states;
    }

    public function _state_harmony(&$migration_state, $candidate_state){

        if($migration_state['operation']=='add_model' && $candidate_state['operation']=='add_field'):
            $add_fields = $candidate_state['fields'];

            foreach ($add_fields as $name=>$field) :
                $migration_state['fields'][$name] = $field;
            endforeach;

        endif;

        if($migration_state['operation']=='add_model' && $candidate_state['operation']=='add_m2m_field'):
            $add_fields = $candidate_state['fields'];

            foreach ($add_fields as $name=>$field) :
                $migration_state['fields'][$name] = $field;
            endforeach;

        endif;

        if($migration_state['operation']=='add_model' && $candidate_state['operation']=='drop_field'):
            $add_fields = $candidate_state['fields'];

            foreach ($add_fields as $name=>$field) :
                 unset($migration_state['fields'][$name]);
            endforeach;

        endif;

        if($migration_state['operation']=='add_model' && $candidate_state['operation']=='drop_m2m_field'):
            $add_fields = $candidate_state['fields'];

            foreach ($add_fields as $name=>$field) :
                 unset($migration_state['fields'][$name]);
            endforeach;

        endif;

        if($migration_state['operation']=='add_model' && $candidate_state['operation']=='modify_field'):
            $modified_fields = $candidate_state['fields'];

            foreach ($modified_fields as $name=>$field) :
                $migration_state['fields'][$name] = $field;
            endforeach;

        endif;



        return $migration_state;
    }

    public function _migration_objects(){
        $migrated_models = [];

        foreach ($this->get_migrations_classes() as $migration_name) :
            $migrated_models[] = new $migration_name();
        endforeach;

        return $migrated_models;
    }

    public function migration_states(){
        $state = [];
        foreach ($this->_migration_objects() as $obj) :
            $state[] = $obj->state();
        endforeach;

        return $this->_harmonize_migrations($state);
    }

    public function migrated_models(){
        $models = [];
        foreach ($this->_models_migrated_state() as $key=>$model) :
            $models[] = strtolower($model['model_name']);
        endforeach;
        return array_unique($models);
    }
}