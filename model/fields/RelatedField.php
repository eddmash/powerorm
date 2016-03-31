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
 * todo
 * - on_delete
 *
 *      When an object referenced by a ForeignKey is deleted,
 *      Powerorm by default emulates the behavior of the SQL constraint ON DELETE CASCADE and also deletes the object
 *      containing the ForeignKey.
 *
 *      This behavior can be overridden by specifying the on_delete argument.
 *      For example, if you have a nullable ForeignKey and you want it to be set null when the referenced object is deleted:
 *
 *      user = ORM::ForeignKey(['model_name'=>'user_model', 'blank'=>True, 'null'=>True, 'on_delete'=>ORM::SET_NULL])
 *
 *      The possible values for on_delete are found in ORM:
 *      -   CASCADE
 *
 *          Cascade deletes; the default.
 *
 *      -   PROTECT
 *
 *          Prevent deletion of the referenced object by raising ProtectedError.
 *
 *      -   SET_NULL
 *
 *          Set the ForeignKey null; this is only possible if null is True.
 *
 *      -   SET_DEFAULT
 *
 *          Set the ForeignKey to its default value; a default for the ForeignKey must be set.
 */

/**
 * A Creates many-to-one relationship.
 *
 * Has one required argument:
 *
 * - model_name
 *
 *    The model to which current model is related to.
 *
 * To create a recursive relationship
 *
 * – an object that has a many-to-one relationship with itself – use ORM::ForeignKey('model_name').
 *
 * e.g. user_model that has self-refernce to itself
 *
 * ORM::ForeignKey('user_model')
 *
 * Option arguments:
 *
 * - db_index
 *
 *      You can choose if a database index is created on this field by setting db_index:
 *
 *          - FALSE - disable
 *          - TRUE - create index.
 *
 *      You may want to avoid the overhead of an index if you are creating a foreign key for consistency
 *      rather than joins.
 *
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

/**
 * Class HasMany
 * @package powerorm\model\field
 */
class HasMany extends InverseRelation{}

/**
 * Class HasOne
 * @package powerorm\model\field
 */
class HasOne extends InverseRelation{}