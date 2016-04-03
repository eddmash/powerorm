<?php
/**
 * The class that creates forms from models.
 */
/**
 *
 */
namespace powerorm\form;

use PModel;
use powerorm\exceptions\DuplicateField;
use powerorm\exceptions\FormException;
use powerorm\exceptions\OrmExceptions;
use powerorm\exceptions\ValueError;


/**
 * Creates a Form using fields on a model that extend the PModel class.
 * {@inheritdoc}
 * @package powerorm\form
 */
class ModelForm extends Form{

    /**
     * @internal
     * @var
     */
    public $model_object;

    /**
     * @internal
     * @var
     */
    public $fields;

    /**
     * @internal
     * @var
     */
    public $none_model_fields;

    /**
     * @internal
     * @var
     */
    protected $field_customize;

    /**
     * @internal
     * @var
     */
    private $only;

    /**
     * @internal
     * @var
     */
    private $ignored;

    /**
     * @internal
     * @var
     */
    private $extra_fields;

    /**
     * Assuming that we have a user model that has already been loaded and it only has two fields name and age;
     * <pre><code> $initial =[
     *  'name'=>'mat',
     *  'age'=>10
     * ];
     *
     * new ModelForm($this->user, $initial);
     * </code></pre>
     *
     * @param PModel $context the model object to create a form from.
     * @param array $initial the data displayed on form fields when the form is first loaded.
     */
    public function __construct(PModel $context, $initial=[]){
        parent::__construct($initial);
        $this->model_object = $context;
    }

    /**
     * This sets which fields present in the model not required on the form.
     *
     * This means the form will be created using all the other model fields apart from those specified on this method
     *
     * <h4>usage<h4/>
     * in an update form you expect to be able to update all you're profile detail
     * expects for the password whose mechanism for update is usually different.
     *
     * <pre><code> $form_builder->ignore(['password']);</code></pre>
     *
     * @param array $fields_names an array of fields on the model to ignore when creating the form.
     * @throws OrmExceptions
     */
    public function ignore($fields_names){
        if(!is_array($fields_names)):
            throw new OrmExceptions(
                sprintf('setting ignore() expects an array of arguments but got a %s', gettype($fields_names)));
        endif;

        $this->ignored = $fields_names;
    }

    /**
     * This sets only the required model fields.
     *
     * This means the form will be created using just those fields specified on this method
     *
     * <h4>usage<h4/>
     * in a log-in form you only require username and password in most cases.
     *
     * <pre><code> $form_builder->only(['username', 'password']);</code></pre>
     *
     * @param $fields_names
     * @throws ValueError
     */
    public function only($fields_names){
        if(!is_array($fields_names)):
            throw new ValueError("only() expects an array of model fields to show");
        endif;
        $this->only = $fields_names;
    }

    /**
     * While the form does a great work of mapping a model field to form field,
     * sometimes you might need to change the mapping to another form field or you may also wish to add more items to
     * the form field. this method is responsible for that kind of customization.
     *
     * <h4>usage<h4/>
     *
     * below we are telling the form builder to make the password field a form field of type password and also contain
     * some addition attributes
     *
     * <pre><code> $form_builder->customize([
     *   'username'=>['attrs'=>['placeholder'=>'username.....', 'class'=>'form-control']],
     *   'password'=>['type'=>'password',  'attrs'=>['placeholder'=>'password...']]
     * ]);</code></pre>
     *
     * This method accepts all the options the Form field accepts in its constructor.
     * {@see Field::__construct()}
     *
     * @param $fields
     * @return bool
     */
    public function customize($fields){
        if(empty($fields)):
            return FALSE;
        endif;
        // ensure we dont get a value passed i
        foreach ($fields as $key=>$opts) :
            if(array_key_exists('value', $opts)):
                throw new \InvalidArgumentException(sprintf("Field `%s` Trying to set value on { form->customize() },
                use { form->initial() } method", $key));
            endif;
        endforeach;

        $this->field_customize = $fields;
    }

    /**
     * Adds extra fields to form build based on a model.
     *
     * Some times some fields displayed on the form are not necessary stored in the database, i.e.
     * The model being used to create the form does not have the fields you wish in its fields list.
     *
     * <h4>Example</h4>
     *
     * Some forms have a checkbox that forces user to accept that they have read the terms and conditions.
     * or in a sign-up form you might require users to repeat there password
     *
     * <pre><code>
     * // terms and condition
     * $form_builder->extra_fields([
     *    'term_conditions' => ['type' => 'checkbox', 'choices' => ['y'=>'yes']],
     * ]);
     *
     * // repeat password
     * $form_builder->extra_fields([
     *    'repeat_password' => ['type' => 'repeat', 'repeat_field' => 'password'],
     * ]);</code></pre>
     *
     *
     *
     * @param $fields
     * @return bool
     */
    public function extra_fields($fields){
        if(empty($fields)):
            return FALSE;
        endif;

        $this->extra_fields = $fields;
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     * @throws DuplicateField
     * @throws FormException
     * @throws OrmExceptions
     * @throws ValueError
     */
    public function form(){

        if(!empty($this->ignore) && !empty($this->only)):
            throw new OrmExceptions('setting only() and ignore() is not allowed on the same form');
        endif;
        $form_fields = [];

        // if only is set
        if(!empty($this->only)):
            foreach ($this->only as $field_name) :
                if(isset($this->model_object->meta->fields[$field_name])):
                    $form_fields[$field_name] = $this->model_object->meta->fields[$field_name]->form_field();
                else:
                    throw new FormException(
                        sprintf('The field `%1$s` is not defined in the model `%2$s`, choices are : %3$s', $field_name,
                            $this->model_object->meta->model_name, stringify(array_keys($this->model_object->meta->fields))));
                endif;
            endforeach;
        endif;

        // if ignored set
        if(!empty($this->ignored)):
            $model_fields = $this->model_object->meta->fields;
            foreach ($model_fields as $field_name=>$field_obj) :

                if(in_array($field_name, $this->ignored)):
                    continue;
                endif;

                $form_fields[$field_name] = $model_fields[$field_name]->form_field();
            endforeach;
        endif;

        // if at this point fields is still empty just load all the fields in the model
        if(empty($form_fields)):

            foreach($this->model_object->meta->fields as $field_name=>$field_obj):
                $form_fields[$field_name] = $field_obj->form_field();
            endforeach;

        endif;

        if(!empty($this->field_customize)):
            // ensure we are not customizing fields that are not in the form fields
            $form_fields_names = array_keys(array_change_key_case($form_fields, CASE_LOWER));
            foreach (array_keys($this->field_customize) as $field_name) :
                if(!in_array($this->_stable_name($field_name), $form_fields_names)):
                    throw new FormException(sprintf('Trying to customize { %s } that does not exist on the form', $field_name));
                endif;
            endforeach;
        endif;


        // create the fields now
        foreach ($form_fields as $field_name=>$field_opts) :
            $opts = $this->_merge_options($field_name,$field_opts);

            $this->fields[$this->_stable_name($field_name)] = new Field($opts);
        endforeach;

        // then load extra fields
        if(!empty($this->extra_fields)):
            foreach ($this->extra_fields as $field_name=>$field_value) :
                $field_value['name'] = $field_name;
                
                // ensure required arguments are present
                $this->_validate_field($field_value);

                // if field with similar name is already load complain like hell
                if(in_array($this->_stable_name($field_value['name']), array_keys($this->fields))):
                    throw new DuplicateField(sprintf('The field `%s` seems to already exist on the form', $field_name));
                endif;

                // look for repeated fields
                if($field_value['type']=='repeat'):

                    if(!isset($field_value['repeat_field'])):
                        throw new FormException(
                            sprintf('The field %1$s is set as type { repeat } but no { repeat_field } has been provided',
                                $field_name));
                    endif;


                    if(array_key_exists($field_value['repeat_field'], $this->fields)):
                        $rep_name = $field_value['repeat_field'];
                        $repeated_field = $this->fields[$rep_name];
                        $repeated_field->validations = $repeated_field->validations + ['required'];
                        // unset it so that we can add them next to each other
                        unset($this->fields[$field_value['repeat_field']]);

                        $this->fields[$rep_name] = $repeated_field;
                        $field_value['validations']=["matches[$rep_name]"];
                        $field_value['type']= $repeated_field->type;


                        $opts = array_merge($this->_combine_opts($field_value, $repeated_field->get_skeleton()));

                        $this->fields[$this->_stable_name($field_name)] = new Field($opts); ;
                    endif;

                    continue;
                endif;

                $this->fields[$this->_stable_name($field_name)] = new Field($field_value);
            endforeach;

        endif;


        return parent::form();
    }

    /**
     * @ignore
     * @param $field_name
     * @param $from_model
     * @return mixed
     */
    public function _merge_options($field_name, $from_model){

        if(!empty($this->field_customize) && array_key_exists($field_name, $this->field_customize)):

            return $this->_combine_opts($this->field_customize[$field_name], $from_model);
        endif;

        return $from_model;
    }

    /**
     * @ignore
     * @param $new
     * @param $old
     * @return mixed
     */
    public function _combine_opts($new, $old){

        foreach ($new as $key=>$value) :


            if($key == 'validations' && array_key_exists($key, $old) && !empty($old[$key])):

                $old[$key] = array_merge($old[$key], $value);

                continue;
            endif;

            $old[$key] = $new[$key];
        endforeach;

        return $old;
    }

    /**
     * Saves the forms model_instance into the database
     * @param null $model_name
     * @param null $values
     * @return mixed
     */
    public function save(){
        //  update model instance fields with the form data
        foreach ($this->fields as $field):
            if(array_key_exists($field->name, get_object_vars($this->model_object))):
                $this->model_object->{$field->name} = $field->value;
            endif;
        endforeach;
        return $this->model_object->save();
    }

    /**
     * @ignore
     * @return string
     */
    public function __toString(){
        return sprintf('< %s Form >', ucwords(strtolower($this->model_object->meta->model_name)));
    }
}