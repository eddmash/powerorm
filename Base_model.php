<?php
/**
 * The Orm Model that adds power to the CI Model
 */

/**
 *
 */
defined('BASEPATH') OR exit('No direct script access allowed');


require_once('Queryset.php');
require_once('OrmErrors.php');
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
 * <pre><code>User_model extends  Base_model{
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
 * @package POWERCI
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Base_model extends \CI_Model{

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
    private $_model_meta = array();

    /**
     * @ignore
     * @var bool
     */
    private $_signal = FALSE;

    /**
     * @ignore
     */
    public function __construct(){
        log_message('info', sprintf('*********************** Model `%s` loaded*****************', get_class($this)));
        if(class_exists('Signal', FALSE)):
            $this->_signal = TRUE;
        endif;

        if($this->_signal):
            $this->signal->dispatch('model.pre_init', $this);
        endif;

        if(empty($this->table_name)):
            $this->table_name = str_replace('_model', '', get_class($this));
        endif;

        // invoke parent
        parent::__construct();

        // set model fields
        if(is_subclass_of($this, 'Base_model')){
            // load database
            $this->load->database();
            $this->_set_model_fields();
        }

        if($this->_signal):
            $this->signal->dispatch('model.post_init', $this);
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
        if (!method_exists($this, $method) &&
            is_subclass_of($this, 'Base_model') &&
            in_array($method, get_class_methods('Queryset'))):
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
     * Gets the meta data about the table being represented by the model
     * @internal
     * @return mixed
     */
    public function _meta(){
        if(!$this->db->table_exists($this->table_name())):
            throw new OrmExceptions(sprintf("The table %s does not exist", $this->table_name()));
        endif;

        if(empty($this->_model_meta )){
            $this->_model_meta = $this->db->field_data($this->table_name());
            $this->_fields_loaded = TRUE;
        }
        return $this->_model_meta ;
    }

    /**
     * Gets the meta data about the table being represented by the model.
     * Returns an object that contains all the table fields with there information like ;
     * - type - the type of the column
     * - max_length -  maximum length of the column
     * - name -  column name
     * - primary_key - 1 if the column is a primary key
     * @return object
     */
    public function meta(){
        $table_meta = new \stdClass();

        // get fields in the database table
        if(count($this->_model_meta)==0){
            $this->_meta();
        }


        foreach ($this->_model_meta as $meta) {
            $table_meta->{$meta->name} = $meta;
        }

        return $table_meta;
    }

    /**
     * Returns the names of the fields in the model
     * @return array
     */
    public function fields_names(){
        $table_meta = array();
        // get fields in the database table

        foreach ($this->_meta() as $meta) {
            array_push($table_meta, $meta->name);
        }

        return $table_meta;
    }

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
     * Creates class variables from the fields in the database table the model represents
     * @internal
     */
    public function _set_model_fields(){
        // set fields only when they have not been set for this object
        if($this->_fields_loaded===FALSE){

            foreach ($this->_meta() as $meta) {

                $this->{$meta->name} = '';
            }
        }
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
     * Returns the name of the primary key column
     * @return string
     */
    public function primary_key(){
        $table_columns = $this->meta();

        $primary_key_column = NULL;
        foreach ($table_columns as $col) :
            if($col->primary_key):
                $primary_key_column = $col->name;
            endif;
        endforeach;

        return $primary_key_column;
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
     * This method is called just before a models object is saved,
     *
     * A good example of when to use this method is when setting the `slug_field`
     *
     * USAGE:
     *
     * In the user_model assuming that the slug field is generated from the username,
     * we can do this automatically without the need for us to keep on setting this field each time a user is being saved.
     *
     * <pre><code>class User_model extends  Base_model {
     *      ... other code
     *
     *      public function pre_save(){
     *          $this->slug = url_title($this->username, 'dash', TRUE);
     *      }
     * }</code></pre>.
     *
     */
    public function pre_save(){
        $this->set_dates();
    }

    /**
     * Is called just after a models object is saved
     * e.g. setting creation time e.t.c
     */
    public function post_save(){

    }

    /**
     * Sets the date fields override this to do custom save
     */
    public function set_dates(){
        // if the id is not set, this means its anew item being created
        if(property_exists($this, 'created_on') && empty($this->id)){
            $this->created_on = date('Y-m-d H:i:s');
        }

        // if id is set, its an update
        if(property_exists($this, 'last_updated_on') && isset($this->id)){
            $this->last_updated_on = date('Y-m-d H:i:s');
        }
    }




}