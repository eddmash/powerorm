<?php
/**
 * Created by PhpStorm.
 * User: eddmash
 * Date: 3/19/16
 * Time: 4:38 AM
 */

namespace powerorm\model\field;

use powerorm\exceptions\OrmExceptions;
use powerorm\migrations\ProjectState;

class RelatedField extends Field{

    public $related_model;
    public $model;
    public $inverse = TRUE;

    protected $on_update;
    protected $on_delete;

    public $M2M=FALSE;
    public $M2O=FALSE;
    public $O2O=FALSE;

    public $empty_label  = '---------';
    public $form_display_field;
    public $form_value_field;

    public function __construct($field_options = []){

        if(!array_key_exists('model', $field_options)):
            throw new OrmExceptions(sprintf('%1$s requires a related model', get_class($this)));
        endif;

        parent::__construct($field_options);

        // we are using proxy object for related model because we don't want to run into problems
        // a problem will arise in circular dependacy where model A has foreign key to B, and B has a foreign key to A
        $this->related_model = new RelationObject($this->model);


    }

    public function related_pk(){
        return $this->related_model->meta->primary_key;
    }

    public function form_field(){

        $opts = $this->_prepare_form_field();

        // fetch all the records in the related model
        $opts['type'] = 'multiselect';
        $opts['form_display_field'] = $this->form_display_field;
        $opts['form_value_field'] = $this->form_value_field;
        $opts['choices'] = $this->related_model->all();

        return $opts;
    }
}

/**
 * Class ForeignKey
 */
class ForeignKey extends RelatedField{
    public $constraint_name;

    public function __construct($field_options = []){
        parent::__construct($field_options);
        $this->M2O = TRUE;
    }

    public function options(){
        $this->type = $this->related_pk()->type;
        $this->signed = $this->related_pk()->signed;
        $this->db_column = $this->db_column_name();

        $opts = parent::options();
        $opts['constraint_name'] = $this->constraint_name;
        return $opts;
    }

    public function db_column_name(){
        $related_model_pk = strtolower($this->related_model->meta->primary_key->db_column_name());
        return sprintf('%1$s_%2$s', strtolower($this->name), $related_model_pk);
    }

    public function constraint_name($prefix){
        $prefix = "fk";
        return parent::constraint_name($prefix);
    }

    public function form_field(){

        $opts = $this->_prepare_form_field();

        // fetch all the records in the related model
        $opts['type'] = 'dropdown';
        $opts['form_display_field'] = $this->form_display_field;
        $opts['form_value_field'] = $this->form_value_field;
        $opts['choices'] = $this->related_model->all();

        return $opts;
    }
    
    public function check(){
        $checks = [];
        $checks[] = $this->_unique_check();
        $checks[] = $this->_delete_check();
        return $checks;
    }
    
    public function _unique_check(){
        if($this->unique):
            return \powerorm\checks\check_error($this,
                'Setting unique=True on a ForeignKey has the same effect as using a OneToOne. use OneToOne field');
        endif;
    }
    
    public function _delete_check(){
    
    }

}

/**
 * Class OneToOne
 */
class OneToOne extends ForeignKey{

    public function __construct($field_options = []){
        parent::__construct($field_options);
        $this->M2O = FALSE;
        $this->O2O = TRUE;
        $this->unique = TRUE;

    }
    
    public function _unique_check(){
    }
    

}

/**
 * Class ManyToMany
 */
class ManyToMany extends RelatedField{

    public $through;

    public function __construct($field_options = []){
        parent::__construct($field_options);
        $this->M2M = TRUE;
        if(array_key_exists('through', $field_options)):
            $this->through = $field_options['through'];
        endif;
    }

    public function check(){
        $checks = [];
        $checks[] = $this->_unique_check();
        $checks[] = $this->_ignored_options();
        $checks[] = $this->_check_relation_model();
        return $checks;
    }

    public function _unique_check(){
        if($this->unique):
            return \powerorm\checks\check_error($this, sprintf('%s field cannot be unique', get_class($this)));
        endif;

    }

    public function _ignored_options(){
        if($this->null):
            return \powerorm\checks\check_warning($this, sprintf('`null` has no effect on %s', get_class($this)));
        endif;
    }

    public function _check_relation_model(){
        $model_names = array_keys(ProjectState::app_model_objects());

        if(!in_array(strtolower($this->model), $model_names)):
            return \powerorm\checks\check_error($this,
                sprintf('%2$s field creates relationship to model `%1$s` that does not exist',
                    $this->model, get_class($this)));
        endif;
    }

    public function options(){
        $opts = parent::options();
        $opts['through'] = $this->through;
        return $opts;
    }

}


abstract class InverseRelation extends RelatedField{
    public $inverse = TRUE;
}

class HasMany extends InverseRelation{}

class HasOne extends InverseRelation{}