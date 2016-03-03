<?php

/**
 * The Orm Model that adds power to the CI Model
 */

/**
 *
 */
defined('BASEPATH') OR exit('No direct script access allowed');

use powerorm\form;
use powerorm\model\Meta;
use powerorm\model\Queryset;
use powerorm\model\ProxyModel;

/**
 * The ORM Model that adds power to CI Model.
 *
 * This class provides the CI MODEL with power by doing two important things :
 *
 * - Assigns Model fields
 *
 * based on the table name provide, this model takes all the columns in that database table and creates model fields
 * for each one of them.
 *
 * - Provides easy interaction with the database.
 *
 * This class provides a Queryset object for each class that extends it. The Queryset class Acts like a proxy between
 * the database and the current model.
 *
 * The Queryset class provides several method for working with the database {@see Queryset } for more.
 *
 *
 * USAGE:
 * <h4><strong>Extending</strong></h4>
 * Extend this class from model classes e.g.
 *
 * <pre><code>User_model extends  PModel{
 *      protected $table_name= '';
 *      ...
 * }</code></pre>
 *
 * The `table_name` variable is optional, this tells the orm which database table this model represents. if not set
 * Table name is taken as model name without the model part e.g. user_model above the table would be `user`
 *
 * <h4><strong>Interacting with the database</strong></h4>
 *
 * Load model as usual
 * <pre><code>$this->load->model('user_model')</code></pre>
 * then
 * <pre><code>$this->user_model->get(array('name'=>'john'))</code></pre>
 * This will return object of the user_model.
 *
 * <h4><strong>How it works</strong></h4>
 * - The first Queryset method invoked on the model creates that models Queryset.
 *   Each of this methods below create a new Queryset instance.
 *  <pre><code>$this->user_model->get(array('name'=>'john'));
 * $this->user_model->all());</code></pre>
 *
 * - Several Queryset methods can be chained together to refine the query.
 *   <pre><code>$this->user_model->all()->filter(array('username'=>'admin'));
 * **************************************************
 * SELECT `user`.*
 * FROM `user`
 * *************************************************
 * $this->user_model->all()->filter(array('username'=>'admin'));
 * ----------
 * SELECT `user`.*
 * FROM `user`
 * WHERE `user`.`username` = 'admin'</code></pre>
 *
 * - Queryset is evaluated to get data from the database.{@see Queryset} for more.
 *
 *
 * @package powerorm
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class PModel extends \CI_Model{

    /**
     * Holds the name that this model represents
     * @var string
     * @ignore
     */
    protected $table_name= '';

    /**
     * @ignore
     * @var bool
     */
    private $_fields_loaded= FALSE;

    /**
     * @ignore
     * @var array
     */
    public $meta;

    /**
     * @ignore
     * @var bool
     */
    private $_signal = FALSE;

    /**
     * @ignore
     */
    public function __construct(){
        if(class_exists('Signal', FALSE)):
            $this->_signal = TRUE;
        endif;

        if($this->_signal):
            $this->signal->dispatch('powerorm.model.pre_init', $this);
        endif;

        // invoke parent
        parent::__construct();

        // set model fields
        if(is_subclass_of($this, 'PModel')){
            // set default table name
            if(empty($this->table_name)):
                $this->table_name = strtolower(get_class($this));
            endif;

            // create meta for this model
            $this->meta = new Meta();
            $this->meta->model_name = get_class($this);
            $this->meta->db_table = $this->table_name();

            //load fields
            $this->fields();

            // try to find if a primary key was set
            if(empty($this->meta->primary_key) && !isset($this->id)):

                $this->id = new AutoField(['primary_key'=>TRUE]);

            endif;

            $this->class_variables_loader();
        }

        if($this->_signal):
            $this->signal->dispatch('powerorm.model.post_init', $this);
        endif;

    }


    /*
     *  -----------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     *  ---------------------------------------------------------------------------------------------
     *  ----------------------------------------- PHP MAGIC METHODS ----------------------------------
     *  -----------------------------------------------------------------------------------------------
     * -----------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     *
     */
    /**
     * @ignore
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args){

        // create a Queryset if method is class and is present in Queryset
        if (!method_exists($this, $method) && is_subclass_of($this, 'PModel') &&
            in_array($method, get_class_methods('powerorm\model\Queryset'))):
            $q = $this->_get_queryset();

            if(empty($args)):
                // invoke from the queryset
                return call_user_func(array($q, $method));
            else:
                // invoke from the queryset
                if(is_array($args)):
                    return call_user_func_array(array($q, $method), $args);
                else:
                    return call_user_func(array($q, $method), $args);
                endif;
            endif;
        endif;
    }


    /**
     * ==================================  UTILITY METHODS =======================================
     *  -----------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     *  ---------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     * -----------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     *
     */

    /**
     * The database table that this model represents
     * @param string $name
     * @return string
     */
    public function table_name($name=NULL){
        $table_name = $this->config->item('db_table_prefix').$this->table_name;
        if(NULL!=$name){
            $table_name = $this->config->item('db_table_prefix').$name;
        }
        return $table_name;
    }

    /**
     * Create a queryset
     * @internal
     * @return Queryset
     */
    public function _get_queryset(){
        return new Queryset($this);
    }

    /**
     * Creates a Queryset that is used to interaract with the database
     * @return Queryset
     */
    public function queryset(){
        return $this->_get_queryset();
    }


    /**
     * ================================================= OBJECT METHODS ==================================
     *  -----------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     *  ---------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     * -----------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     *
     */


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
    public static function permissions(){
        return array(
            "can_add"=> "Can Add",
            "can_delete"=> "Can Delete",
            "can_update"=> "Can Update",
            "can_view"=> "Can View",
            "can_list"=> "Can List",
        );
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
    public function get_uri($slug=FALSE){


        return '';
    }

    /**
     * To load fields
     */
    public abstract function fields();

    /**
     * Sets the fields and loads the meta
     * @param $field_name
     * @param $field_obj
     */
    public function __set($field_name, $field_obj){
        $this->_meta_loader($field_name, $field_obj);
        $this->class_variables_loader();

    }

    public function _meta_loader($field_name, $field_obj){
        if(!isset($this->$field_name)):

            $this->$field_name = '';

        endif;

        if(is_subclass_of($field_obj, 'ModelField')):

            // add field to meta data to keep track
            $field_obj->name = $field_obj->db_column = $field_name;
            $this->meta->load_field($field_obj);

        endif;

        if(!isset($this->$field_name) && is_subclass_of($field_obj, 'InverseRelation')):
            $this->$field_name = '';
            // add field to meta data to keep track
            $field_obj->name = $field_name;
            $this->meta->load_inverse_field($field_obj);
        endif;
    }

    public function _model_name($model){
        $model_name = $model->meta->model_name;
        if($model instanceof ProxyModel):
            $model_name = $model->model_name;
        endif;

        return $model_name;
    }

    public function class_variables_loader(){

        foreach (get_class_vars(get_class($this)) as $field_name=>$value) :

            if(!isset($this->$field_name)):
                continue;
            endif;
            $field_obj=$this->$field_name;

            $this->_meta_loader($field_name, $field_obj);

        endforeach;
    }

    public function __toString(){
        $pk = $this->meta->primary_key->name;
        return sprintf('< %1$s %2$s >', ucwords(strtolower(get_class($this))), $this->$pk);
    }

    public function clean_fields(){
        foreach ($this->meta->fields as $field) :
            $field_value = $this->{$field->name};
            $field->clean($this, $field_value);
        endforeach;

    }

    public function model_clean(){

    }

    public function as_form(){
        return new form\ModelForm($this);
    }

}
