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
use powerorm\exceptions\ValueError;


require_once POWERORM_BASEPATH.'model/field/Accessor.php';
require_once POWERORM_BASEPATH.'model/field/RelationObject.php';

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
     * - You’re sharing your database.
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
