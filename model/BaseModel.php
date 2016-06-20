<?php

namespace powerorm\model;

/**
 *
 */
defined('BASEPATH') OR exit('No direct script access allowed');

use powerorm\BaseOrm;
use powerorm\exceptions\OrmExceptions;
use powerorm\exceptions\TypeError;
use powerorm\form;
use powerorm\model\field\Field;
use powerorm\queries\Queryset;
use powerorm\traits\BaseObject;

/**
 * The ORM Model that adds power to CI Model.
 *
 * @package powerorm\model
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class BaseModel extends \CI_Model{

    use BaseObject;

    /**
     * Holds the name that this model represents
     * @var string
     * @ignore
     */
    protected $table_name;

    /**
     * @ignore
     * @var array
     */
    public $meta;

    /**
     * Indicates if the orm should managed the table being represented by this model
     * @var
     */
    public $managed = TRUE;

    /**
     * @ignore
     * @var
     */
    public $proxy;

    public $is_new;

    /**
     * @ignore
     */
    public function __construct()
    {
        $this->dispatch_signal('powerorm.model.pre_init', $this);

        // invoke parent
        parent::__construct();

        // initialize
        $this->init();

        $this->dispatch_signal('powerorm.model.post_init', $this);
    }

    public function init($registry='', $fields=[]){

        // create meta for this model
        $this->meta = new Meta();
        $this->meta->model_name  = $this->lower_case($this->get_class_name());
        $this->meta->db_table  = $this->get_table_name();
        $this->meta->managed  = $this->is_managed();

        // load the fields
        $this->fields();

        // set them up
        $this->setup_fields($fields);

        $this->meta->prepare($this);

        if(empty($registry)):

            // add model to the registry
            $this->get_registry()->register_model($this);
        else:
            $registry->register_model($this);
        endif;

    }



    // ========================================================================================================

    // ============================================= QUERY METHODS ============================================

    // ========================================================================================================



    /**
     * @ignore
     */
    public function get($conditions, $opts=[]){
        return $this->queryset($opts)->one($conditions);
    }

    /**
     * @ignore
     */
    public function filter($conditions, $opts=[])
    {
        return $this->queryset($opts)->filter($conditions);
    }

    /**
     * @ignore
     */
    public function all($opts=[]){
        return $this->queryset($opts)->all();
    }

    /**
     * @ignore
     */
    public function exclude($conditions, $opts=[]){
        return $this->queryset($opts)->exclude($conditions);
    }

    /**
     * Creates or Updates an object in the database,
     *
     * <h4>How it differentiates between update and create</h4>
     *
     * This methods does an update if the model contains a value on its primary key value else it creates.
     *
     * On create or update this method will return the created object.
     *
     * <h4>Signal Emitted</h4>
     * If the {@link https://github.com/eddmash/powerdispatch} is enabled, this method emits two signals
     *
     * - <strong>powerorm.model.pre_save</strong>
     *
     *      allowing any receivers listening for that signal to take some customized action.
     *
     * - <strong>powerorm.model.post_save</strong>
     *
     *      allowing any receivers listening for that signal to take some customized action.
     *
     * <h4>USAGE</h4>
     *
     * To create an new object
     *
     * <pre><code>$role = new role();
     * $role->name = "ceo";
     * $role = $role->save();</code></pre>
     *
     * To update existing an new object
     *
     * e.g. assuming the above object got a primary key of `3` we can update its name as follows
     *
     * <pre><code> $role = $this->role->get(3);
     * $role->name = "chief executive officer";
     * $role = $cat->save();</code></pre>
     * @param array $opts
     * @return mixed
     */
    public function save($opts=[]){
        // run this in transaction
        $this->db->trans_start();

        // alert everyone else of intended save
        $this->dispatch_signal('powerorm.model.pre_save', $this);

        $save_model=$this->queryset($opts)->_save();

        // alert everyone of the save
        $this->dispatch_signal('powerorm.model.post_save', $this);


        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            show_error("sorry the operation was not successful");
        }

        return $save_model;
    }

    /**
     * Eager loading, this will solve the N+1 ISSUE
     *
     * <h4>USAGE:</h4>
     *
     * To Eager load related items of a single item
     *
     * This method takes all the fields that need to be eagerly loaded.
     *
     * <pre><code>$category = $this->category_model->with(["products"])->get(array("id"=>1));
     *
     * $category->products; // the products are now accessible like so
     *
     * foreach($rr->products as $product){
     *          echo $product->name;
     * }</code></pre>
     *
     * To Eager Many To Many Relationship
     *
     * <pre><code>$roles = $this->role->with(['permissions'])->filter(array('user_model::id'=>1));
     * foreach ($roles as $role) {
     *         echo $role ;
     *
     *      foreach ($role->permissions as $perm) {
     *          echo $perm->name;
     *      }
     * }</code></pre>
     *
     *
     * To Eager Load more than one relationship , this eager loads a user roles and products.
     *
     * <pre><code>$usr= $this->user_model->with(["products", "roles"])->get(["id"=>1]);
     *
     *
     * </code></pre>
     *
     * @param array $conditions the name/names of model fields.
     * @param array $opts
     * @return Queryset
     */
    public function with($conditions, $opts=[]){

        return $this->queryset($opts)->_eager_load($conditions);
    }

    /**
     * Gets the first record in the table represented by this model.
     *
     * <h4>USAGE:</h4>
     *
     * $users = $this->user_model->first();
     *
     * echo $users->username;
     *
     * @param array $opts
     * @return $this
     */
    public function first($opts =[]){
        return $this->queryset($opts)->first();
    }

    /**
     * Gets the last record in the table represented by this model.
     *
     * <h4>USAGE:</h4>
     *
     * $users = $this->user_model->last();
     *
     * echo $users->username;
     *
     * @param array $opts
     * @return $this
     */
    public function last($opts =[]){
        return $this->queryset($opts)->last();
    }

    /**
     * This method clears all the records in the database table represented by the model.
     *
     * <h4>USAGE:</h4>
     *
     * Deleting all the records.
     *
     * <pre><code>$this->role->delete()</code></pre>
     *
     * <strong>NB</strong> also consider using the Queryset delete().
     * {@link }
     *
     * @param array $opts
     */
    public function delete($opts=[]){
        $this->queryset($opts)->clear();
    }

    /**
     * Stores Many To Many relationship
     *
     * This methods expects and array, the array should contain object of related model or
     * a Queryset object of the related model
     *
     * <h4><strong>! Important </strong>Each call of this method can only handle one type of related model and expect
     * the related models to have already been saved in the database</h4>
     *
     * e.g. the following will raise an OrmExceptions,
     *
     * <pre><code>$user = $this->user_model->get(1);
     * $role = $this->role->get(1);
     * $perm = $this->permission_model->get(1);
     * $user->add([$role, $perm]);
     * </code></pre>
     *
     * use two calls
     *
     * <pre><code>$user = $this->user_model->get(1);
     * $role = $this->role->get(1);
     * $perm = $this->permission_model->get(1);
     * $user->add([$role]);
     * $user->add([$perm]);
     *
     * // passing a Queryset
     * $role = $this->role->all());
     * $user->add([$role]);
     * </code></pre>
     *
     * @param array $related the objects of related models to associate with the current model.
     * @param string $using_db_group
     * @throws OrmExceptions
     * @throws TypeError
     */
    public function add($related=[], $using_db_group=''){
        if(!is_array($related)){
            throw new OrmExceptions(sprintf("add() expects an array of models"));
        }

        // if the array is empty exit
        if(empty($related)):
            return;
        endif;

        $related_model = '';

        // Some possibilities to consider
        foreach ($related as $item) :

            // if a Queryset was passed in
            if(!$item instanceof PModel):
                throw new OrmExceptions(
                    sprintf('add() expects an array of objects that extend the %1$s but got a %2$s',
                        'PModel', get_class($item)));
            endif;

            // get the related model name to save
            if(!empty($related_model) && $related_model!==get_class($item)):
                throw new TypeError(
                    sprintf("Multiple types provided, add() expects only one type per call, see documentation"));
            endif;

            $related_model = get_class($item);
        endforeach;

        // save related models many to many

        $this->queryset($using_db_group)->_m2m_save($related);

    }



    // ========================================================================================================

    // ================================================= OBJECT METHODS =======================================

    // ========================================================================================================



    /**
     * The database table that this model represents
     * @return string
     */
    public function get_table_name(){
        $table_name = $this->table_name;

        if(!isset($table_name)):
            $table_name = $this->lower_case($this->get_class_name());
        endif;

        return $table_name;
    }

    /**
     * The database table that this model represents with the prefix appended if one
     * was set on the database configuration
     * @param null $name
     * @return string
     */
    public function full_table_name($name = NULL)
    {
        $table_name = sprintf('%1$s%2$s', $this->db->dbprefix, $this->table_name);
        if (NULL != $name) {
            $table_name = sprintf('%1$s%2$s', $this->db->dbprefix, $name);
        }
        return $table_name;
    }

    /**
     * Returns the database prefix to used on the table represent by current model.
     * @return mixed
     */
    public function table_prefix(){
        return $this->db->dbprefix;
    }

    /**
     * The Permissions to set for this model.
     *
     * The permission include the following
     *  - can_add ------------------ User with this permission can a new item of the model
     *  - can_update ------------------ User with this permission can a update item of the model
     *  - can_view ------------------ User with this permission can a view details about a single item of the model
     *  - can_delete ------------------ User with this permission can delete item of the model
     *  - can_list ------------------ User with this permission can a see a list of items belonging to the model
     *
     * To Add more permissions in your model overrride this method as below, remember to call the parent method
     * e.g in the roles model
     * <pre><code>public static function permissions(){
     *      $perms = parent::permissions();
     *      $perms["can_assign_user_roles"]= "Can Assign User Roles";
     *      return $perms;
     * }</code></pre>
     * @return array
     */
    public static function permissions()
    {
        return array(
            "can_add" => "Can Add",
            "can_delete" => "Can Delete",
            "can_update" => "Can Update",
            "can_view" => "Can View",
            "can_list" => "Can List",
        );
    }

    /**
     * Creates a Queryset that is used to interaract with the database
     * @param string $opts
     * @return Queryset
     */
    public function queryset($opts)
    {
        return $this->_get_queryset($opts);
    }

    /**
     * Sets the relative uri to an instance of the model e.g. user/1 .
     * <pre><code>public function get_uri($slug=FALSE){
     *   $route = $this->id;
     *
     *   if($slug==TRUE){
     *      $route = $this->slug;
     *   }
     *   return 'user/'.$route;
     * }</code></pre>
     * @param bool $slug if set to true returns the url as a slug e.g. user/admin
     * @return string
     */
    public function get_uri($slug = FALSE)
    {


        return '';
    }

    /**
     * All the model fields are set on this model.
     * <pre><code>public function fields(){
     *      $this->username = ORM::CharField(['max_length'=>30]);
     *      $this->first_name = ORM::CharField(['max_length'=>30]);
     *      $this->last_name = ORM::CharField(['max_length'=>30]);
     *      $this->password = ORM::CharField(['max_length'=>255]);
     *      $this->phone_number = ORM::CharField(['max_length'=>30]);
     * }</code></pre>
     */
    public abstract function fields();

    /**
     *
     * @ignore
     */
    public function clean()
    {
        $this->clean_fields();
    }

    /**
     *
     * @ignore
     */
    public function full_clean(){

    }

    /**
     * @ignore
     * Used by migrations to provide any thing that needs to be run before the migrtion process starts
     */
    public function check(){
        $checks = [];

        $f_checks = $this->_check_fields();

        if(!empty($f_checks)):
            $checks = array_merge($checks, $f_checks);

        endif;

//        var_dump($checks);
        return $checks;
    }

    /**
     * Creates a form builder used to customize the way the form that represents current model is created.
     * @return form\ModelForm
     */
    public function form_builder()
    {
        // ToDo run checks when form is being created, please.
//        if(!empty($this->check())):
//            throw new ValidationError(checks_html_output($this->check()));
//
//        endif;

        return new form\ModelForm($this, $this->_field_values());
    }

    /**
     * @return array
     * @ignore
     */
    public function _field_values(){
        $values = [];
        foreach ($this->meta->fields as $name=>$field) :
            $values[$name] = $this->{$name};
        endforeach;
        return $values;
    }

    /**
     * Returns a Form that represents the current model.
     * @return $this
     * @throws OrmExceptions
     * @throws \powerorm\exceptions\DuplicateField
     * @throws \powerorm\exceptions\FormException
     */
    public function toForm(){
        return $this->form_builder()->form();
    }



    // ========================================================================================================

    // ============================================  UTILITY METHODS ==========================================

    // ========================================================================================================





    public static function from_db($connection, $fields_with_value){
        return new static($fields_with_value);
    }

    /**
     * Create a queryset
     * @internal
     * @return Queryset
     */
    public function _get_queryset($opts)
    {
        $opts = (empty($opts)) ? '':$opts;


        return \powerorm\helpers\create_queryset($this, NULL, $opts);
    }

    /**
     * @param $opts
     * @return mixed
     * @internal
     */
    public function _get_db($opts){
        $use_db_group = '';
        if(is_array($opts) && array_key_exists('use_db_group', $opts)):
            $use_db_group = $opts['use_db_group'];
        endif;

        if(!empty($use_db_group)):
            $database = $this->load->database($use_db_group, TRUE);
        else:
            $database = $this->load->database('', TRUE);;
        endif;

        return $database;
    }

    /**
     * Adds fields to the model and add them to meta object of the model
     * @internal
     * @param array $fields
     */
    public function setup_fields($fields)
    {

        if(!empty($fields)):
            foreach ($fields as $field_name=>$field_obj) :

                $this->_add_fields($field_name, $field_obj);
            endforeach;

            return;
        endif;

        $fields = get_class_vars(get_class($this));
        foreach ($fields as $field_name => $value) :

            if (!isset($this->$field_name)):
                continue;
            endif;

            $field_obj = $this->$field_name;

            $this->_add_fields($field_name, $field_obj);

        endforeach;
    }


    protected function _add_fields($field_name, $field_obj){
        if($field_obj instanceof Field):

            $this->add_to_class($field_name, $field_obj);
        endif;
    }

    public function add_to_class($name, $obj){
        if($obj->has_method('contribute_to_class')):

            $obj->contribute_to_class($name, $this);

        endif;

    }

    /**
     *
     * @ignore
     */
    public function clean_fields()
    {
        foreach ($this->meta->fields as $field) :
            $field_value = $this->{$field->name};
            $field->clean($this, $field_value);
        endforeach;

    }

    /**
     * @return array
     * @ignore
     */
    public function _check_fields()
    {
        $checks = [];

        foreach ($this->meta->fields as $field_name=>$field_obj) :
            $f_check = $field_obj->check();
            if(!empty($f_check)):
                $checks = array_merge($checks, $f_check);
            endif;
        endforeach;

        return $checks;
    }
    
    public function is_managed(){
        return $this->managed;
    }


    // ========================================================================================================

    // ========================================== PHP MAGIC METHODS ===========================================

    // ========================================================================================================




    /**
     * @param $method
     * @param $args
     * @return mixed
     *
     * @ignore
     */
    public function __call($method, $args){
        // some helper e.g. get_gender_display
        if(preg_match('/get_(.*)_display/',$method)):
            $name = implode('',preg_split('/^(get_)||(_display)$/', $method));


            if(isset($this->meta->fields[$name]) && !empty($this->meta->fields[$name]->choices)):
                $value = $this->{$name};
                if(empty($value)):
                    return $value;
                endif;
                return $this->meta->fields[$name]->choices[$value];
            endif;
        endif;
    }

    /**
     * @ignore
     * Sets the fields and loads the meta
     * @param $field_name
     * @param $field_obj
     */
    public function __set($field_name, $field_obj)
    {

        $this->setup_fields([$field_name=> $field_obj]);

    }

    /**
     * @ignore
     * @return string
     */
    public function __toString()
    {
        $pk = $this->meta->primary_key->name;
        return sprintf('< %1$s >', $this->get_class_name());
    }

}