<?php
/**
 * The Relationship fields.
 */

/**
 *
 */
namespace powerorm\model\field;

use powerorm\checks\Checks;
use powerorm\exceptions\OrmExceptions;

/**
 * Creates a Relationship column or table depending on the type of relationship.
 *
 * {@inheritdoc}
 *
 * @package powerorm\model\field
 */
class RelatedField extends Field{

    /**
     * Controls whether or not a constraint should be created in the database for this foreign key.
     *
     * The default is True, and that’s almost certainly what you want; setting this to False can be very bad for data
     * integrity. That said, here are some scenarios where you might want to do this:
     * - You have legacy data that is not valid.
     * - You’re sharding your database.
     *
     * If this is set to False, accessing a related object that doesn’t exist will raise its DoesNotExist exception.
     * @var
     */
    public $db_constraint = FALSE;

    /**
     * This is the model to create a relationship with.
     * @var
     */
    protected $model;

    /**
     * @ignore
     * @var
     */
    protected $on_update;

    /**
     * When an object referenced by a ForeignKey is deleted, Powerorm by default emulates the behavior of the SQL
     * constraint ON DELETE CASCADE and also deletes the object containing the ForeignKey.
     * @var
     */
    protected $on_delete;

    /**
     * @inheritdoc
     */
    public $is_relation = TRUE;

    /**
     * The form field used for Foreignkey and ManyToMany field is dropdown.
     * This options allows you to set what is displayed as the first item on the dropdown list,
     * most its use if the relationship allows null, meaning its not mandatory a user selects and item from dropdwon
     * they can just leave it blank
     * @var string
     */
    public $empty_label  = '---------';

    /**
     * Used to set the field on the model to use for display e.g for the model user_model.
     * you could set the form_display_field to username, this will result in form select box shown below
     *
     * <pre><code>&lt;select &gt;
     *      &lt; option value=1 &gt; math // <----- the username.
     * &lt;/select &gt;</code></pre>
     * @var
     */
    public $form_display_field;

    /**
     * Works on dropdown, select, radio, checkbox.
     *
     * Used to set the model field to use for the value of the form option fields e.g for the model user_model.
     * you could set the form_value_field to email, this will result in form select box shown below
     *
     * By default the primary key is used.
     *
     * <pre><code &gt;
     *  &lt;select &gt;
     *      &lt;option value=linus@linux.com &gt; math // not the value of the option is set to an email.
     * &lt;/select &gt;</code></pre>
     * @var
     */
    public $form_value_field;

    /**
     * {@inheritdoc}
     */
    public function __construct($field_options = []){

        if(!array_key_exists('model', $field_options)):
            throw new OrmExceptions(sprintf('%1$s requires a related model', $this->get_class_name()));
        endif;

        parent::__construct($field_options);


    }

    /**
     * @ignore
     * @return mixed
     */
    public function relation_field(){

        return $this->relation->model()->meta->primary_key;
    }

    /**
     * {@inheritdoc}
     */
    public function form_field(){

        $opts = $this->_prepare_form_field();

        // fetch all the records in the related model
        $opts['type'] = 'multiselect';
        $opts['form_display_field'] = $this->form_display_field;
        $opts['form_value_field'] = $this->form_value_field;
        $opts['choices'] = $this->related_model->all();

        return $opts;
    }

    public function check(){
        $checks = parent::check();
        $checks = $this->add_check($checks, $this->_check_relation_model());
        return $checks;

    }

    /**
     * @ignore
     * @return mixed
     */
    public function _check_relation_model(){

        $models = $this->get_registry()->get_models();
        $model_names = array_keys($models);

        if(!in_array($this->lower_case($this->relation->model), $model_names)):
            return [
                Checks::error([
                    "message"=>sprintf('%1$s field creates relationship to model `%2$s` that does not exist',
                        $this->get_class_name(), ucfirst($this->relation->model)),
                    "hint"=>NULL,
                    "context"=>$this,
                    "id"=>"fields.E300"
                ])
            ];
        endif;

        return [];
    }

}

/**
 * todo
 * - on_delete
 *
 *      When an object referenced by a ForeignKey is deleted,
 *
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

    /**
     * {@inheritdoc}
     */
    public $db_constraint = TRUE;

    /**
     * {@inheritdoc}
     */
    public function __construct($field_options = []){
        parent::__construct($field_options);
        $this->M2O = TRUE;

        $this->relation = new ManyToOneObject([
            'model'=>$field_options['model'],
            'field'=>$this
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function db_column_name(){
        return sprintf('%s_id', $this->lower_case($this->name));
    }


    /**
     * {@inheritdoc}
     */
    public function constraint_name($prefix){
        $prefix = "fk";
        return parent::constraint_name($prefix);
    }


    /**
     * {@inheritdoc}
     */
    public function form_field(){

        $opts = $this->_prepare_form_field();

        // fetch all the records in the related model
        $opts['type'] = 'dropdown';
        $opts['form_display_field'] = $this->form_display_field;
        $opts['form_value_field'] = $this->form_value_field;
        $opts['choices'] = $this->related_model->all();

        return $opts;
    }


    /**
     * {@inheritdoc}
     */
    public function check(){
        $checks = parent::check();
        $checks = $this->add_check($checks, $this->_unique_check());
        $checks = $this->add_check($checks, $this->_delete_check());
        return $checks;
    }

    /**
     * @ignore
     * @return mixed
     */
    public function _unique_check(){
        if($this->unique):
            return [
                Checks::warning([
                    "message"=>"Setting unique=True on a ForeignKey has the same effect as using a OneToOne.",
                    "hint"=>"use OneToOne field",
                    "context"=>$this,
                    "id"=>"fields.W300"
                ])
            ];
        endif;

        return [];
    }

    /**
     * @ignore
     */
    public function _delete_check(){
        return [];
    }

    public function db_type()
    {
        return $this->relation_field()->db_type();
    }

}

/**
 * Class OneToOne
 */
class OneToOne extends ForeignKey{


    /**
     * {@inheritdoc}
     */
    public function __construct($field_options = []){
        parent::__construct($field_options);
        $this->M2O = FALSE;
        $this->O2O = TRUE;
        $this->unique = TRUE;

    }

    /**
     * @ignore
     */
    public function _unique_check(){
    }

}

/**
 * Class ManyToMany
 */
class ManyToMany extends RelatedField{
    public $M2M = TRUE;

    /**
     * {@inheritdoc}
     */
    public function __construct($field_options = []){
        parent::__construct($field_options);


        $this->relation = new ManyToManyObject([
            'model'=>$field_options['model'],
            'through'=> array_key_exists('through', $field_options) ? $field_options['through'] : NULL,
            'field'=>$this
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function check(){
        $checks = [];
        $checks = parent::check();
        $checks = $this->add_check($checks, $this->_unique_check());
        $checks = $this->add_check($checks, $this->_ignored_options());
        return $checks;
    }

    /**
     * @ignore
     * @return mixed
     */
    public function _unique_check(){
        if($this->unique):
            return [
                Checks::error([
                    "message"=>sprintf('%s field cannot be unique', $this->get_class_name()),
                    "hint"=>NULL,
                    "context"=>$this,
                    "id"=>"fields.E330"
                ])
            ];
        endif;

        return [];
    }

    public function __through_model_exists_check()
    {

        $models = $this->get_registry()->get_models();
        $model_names = array_keys($models);

        if($this->through!==NULL && !in_array($this->lower_case($this->through), $model_names)):
            return [
                Checks::error([
                    "message"=>sprintf('Field specifies a many-to-many relation through model %s, 
                    which does not exist.', ucfirst($this->through)),
                    "hint"=>NULL,
                    "context"=>$this,
                    "id"=>"fields.E331"
                ])
            ];
        endif;

        return [];
    }

    /**
     * @ignore
     * @return mixed
     */
    public function _ignored_options(){
        if($this->null):
            return [
                Checks::warning([
                    "message"=>sprintf('`null` has no effect on %s', $this->get_class_name()),
                    "hint"=>NULL,
                    "context"=>$this,
                    "id"=>"fields.W340"
                ])
            ];
        endif;
        return [];
    }

    public function db_type()
    {
        return NULL;
    }

    public function contribute_to_class($field_name, $model_obj)
    {
        parent::contribute_to_class($field_name, $model_obj);

        if(empty($this->relation->through)):
            $this->relation->through = $this->create_intermidiate_model($this, $model_obj);
        endif;

        if(is_string($this->relation->through)):
            $this->relation->through = $this->set_through_model($this->relation->through);
        endif;

    }

    public function set_through_model($model_name)
    {
      return $this->get_registry()->get_model($model_name);
    }

    public function create_intermidiate_model($field, $owner_model)
    {
        $owner_model_name = $owner_model->meta->model_name;
        $inverse_model_name = $field->relation->model;

        $owner_model_name = $this->lower_case($owner_model_name);
        $inverse_model_name = $this->lower_case($inverse_model_name);

        $class_name = sprintf('%1$s_%2$s', ucfirst($owner_model_name), ucfirst($field->name));

        $intermediary_class = 'use powerorm\model\BaseModel;
        class %1$s extends BaseModel{
            public function fields(){}
        }';
        $intermediary_class = sprintf($intermediary_class, $class_name);

        if(!class_exists($class_name, FALSE)):
            eval($intermediary_class);
        endif;

        $class_name = "\\".$class_name;
        $intermediary_obj = new $class_name();

        $intermediary_obj->init(NULL, [
            $owner_model_name => new ForeignKey(['model'=>$owner_model_name]),
            $inverse_model_name => new ForeignKey(['model'=>$inverse_model_name])
        ]);

        $intermediary_obj->meta->auto_created = TRUE;
        return $intermediary_obj;
    }

}

