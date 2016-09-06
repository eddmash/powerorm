<?php

namespace eddmash\powerorm\model;

/*
 *
 */
defined('BASEPATH') or exit('No direct script access allowed');

use eddmash\powerorm\app\Registry;
use eddmash\powerorm\BaseObject;
use eddmash\powerorm\db\Connection;
use eddmash\powerorm\exceptions\OrmExceptions;
use eddmash\powerorm\exceptions\TypeError;
use eddmash\powerorm\form;
use eddmash\powerorm\model\field\Field;
use eddmash\powerorm\queries\Queryset;
use Orm;

/**
 * The ORM Model that adds power to CI Model.
 *
 * <h4>Model inheritance</h4>
 *
 * Model inheritance in Powerorm works almost identically to the way normal class inheritance works in PHP,
 * That means the base class should subclass PModel.
 *
 * The only decision you have to make is whether you want the parent models to be models in their own right
 * (with their own database tables), or
 *
 * if the parents are just holders of common information that will only be visible through the child models.
 *
 * There are three styles of inheritance that are possible in Powerorm:
 *
 *  - Often, you will just want to use the parent class to hold information that you don’t want to have to type out for
 *    each child model. This class isn’t going to ever be used in isolation, so Abstract base classes are what you’re
 *    after.
 *
 *  - If you’re subclassing an existing model (perhaps something from another application entirely) and want each model
 *    to have its own database table, Multi-table inheritance is the way to go.
 *
 *  - Finally, if you only want to modify the PHP-level behavior of a model, without changing the models fields in any
 *    way, you can use Proxy models.
 *
 *
 * <h4>Abstract base classes</h4>
 *
 * <strong>Note</strong>Because codeigniter does not autoload classes you need to require the file with the abstract
 * class in your model file.
 *
 * Abstract base classes are useful when you want to put some common information into a number of other models.
 * You create an Abstract base class by simply creating a normal php abstract base class.
 *
 * This model will then not be used to create any database table.
 *
 * Instead, when it is used as a base class for other models, its fields will be added to those of the child class.
 *
 * Any fields defined in the Abstract that are again defined in the Child class will be over written by those in the
 * child class.
 *
 * <code>abstract class CommonInfo extends PModel{
 *      public function fields(){
 *          name = PModel::CharField(['max_length'=>100]);
 *          age = PModel::IntegerField();
 *      }
 * }
 *
 * class Student extends CommonInfo{
 *      public function fields(){
 *          home_group = PModel::CharField(['max_length'=>5]);
 *      }
 * }</code>
 *
 * The Student model will have three fields: name, age and home_group.
 *
 * The CommonInfo model cannot be used as a normal model, since it is an abstract base class.
 * It does not generate a database table, and cannot be instantiated or saved directly.
 *
 * For many uses, this type of model inheritance will be exactly what you want.
 *
 * It provides a way to factor out common information at the php level, while still only creating one database table per
 * child model at the database level.
 *
 *
 * <H4><strong>NB:</strong><small> Attribute inheritance</small></h4>
 * When inheriting, Some attributes will need to be overridden in child classes, since it doesn't make sense to set
 * them in the base class.
 *
 * For example, setting <em>table_name</em> would mean that all the child classes
 * (the ones that don’t specify <em>table_name</em> explictly) would use the same database table,
 *
 * which is almost certainly not what you want.
 *
 *
 * <h4>Multi-table inheritance</h4>
 *
 * The second type of model inheritance supported by Powerorm is when each model in the hierarchy is a table all by
 * itself.
 *
 *
 * Each model corresponds to its own database table and can be queried and created individually.
 *
 * The inheritance relationship introduces links between the child model and each of its parents
 * (via an automatically-created OneToOne). For example:
 *
 * <code>class Place extends PModel{
 *      public function fields(){
 *          name = PModel::CharField(['max_length'=>100]);
 *          address = PModel::CharField(['max_length'=>80]);
 *      }
 * }
 *
 * class Restaurant extends Place{
 *      public function fields(){
 *          serves_hot_dogs =PModel::BooleanField(['default'=>False]);
 *          serves_pizza =PModel::BooleanField(['default'=>False]);
 *      }
 * }</code>
 *
 *
 * <strong>Note</strong>Because codeigniter does not autoload classes you need to load the base class first before
 * the child. when using e.g. load the models defined above as show :
 *
 * <code>// load model
 * $this->load->model('place');
 * $this->load->model('restraurant');</code>
 *
 * All of the fields of Place will also be available in Restaurant, although the data will reside in a different
 * database table. So these are both possible:
 *
 * <code>$this->place->filter([name="Bob's Cafe"]);
 * $this->restaurant->filter([name="Bob's Cafe"]);</code>
 *
 * todo Check on this reverse lookup
 * If you have a Place that is also a Restaurant, you can get from the Place object to the Restaurant object by using
 * the lower-case version of the model name:
 *
 * <code>p = $this->place->get(['id'=12]);
 * // If p is a Restaurant object, this will give the child class:
 * p.restaurant</code>
 *
 * However, if p in the above example was not a Restaurant (it had been created directly as a Place object or was the
 * parent of some other class), referring to p.restaurant would raise a exception.
 *
 *
 *
 * In reality the orm creates the base model table as expected in the database i.e with all the field the model
 * specifies but for the child model it creates the a table with fields that have been specfied in the child model
 * and create a one-to-one connection to the base models' table.
 *
 * <h4>Proxy models</h4>
 *
 * When using multi-table inheritance, a new database table is created for each subclass of a model.
 *
 * This is usually the desired behavior, since the subclass needs a place to store any additional data fields that
 * are not present on the base class.
 *
 * Sometimes, however, you only want to change the php behavior of a model – perhaps to add a new method.
 *
 * This is what proxy model inheritance is for:
 *
 * creating a proxy for the original model. You can create, delete and update instances of the proxy model and all
 * the data will be saved as if you were using the original (non-proxied) model.
 *
 * The difference is that you can change things like the default model ordering or the default manager in the proxy,
 * without having to alter the original.
 *
 * Proxy models are declared like normal models. You tell Powerorm that it’s a proxy model by setting the <em>proxy</em>
 * attribute of the class to True.
 *
 *<code>class Employee extends PModel{
 *      public function fields(){
 *          name = PModel::CharField(['max_length'=>100]);
 *          age = PModel::InteferField();
 *      }
 * }
 *
 * class Auditor extends Employee{
 *
 *      public $proxy = TRUE;
 *
 *      // get how many times a specific accountant has audited a specific employee.
 *      public function get_times_has_audited($employee){}
 * }
 *</code>
 *
 * <strong>Note</strong>Because codeigniter does not autoload classes you need to load the base class first before
 * the child. when using e.g. load the models defined above as show :
 *
 * <code>// load model
 * $this->load->model('employee');
 * $this->load->model('auditor');</code>
 *
 * The Auditor class operates on the same database table as its parent Person class.
 * In particular, any new instances of Employee will also be accessible through Auditor, and vice-versa:
 * <code>p = $this->employee->create(['name='foobar']);
 * $this->auditor->get(['name'='foobar']);</code>
 *
 * <h4><strong>NB:</strong>QuerySets still return the model that was requested</h4>
 *
 * There is no way to have Powerorm to return, say, a Auditor object whenever you query for Employee objects.
 *
 * A queryset for Employee objects will return those types of objects.
 *
 * The whole point of proxy objects is that code relying on the original Em[loyee will use those and your own code can
 * use the extensions you included (that no other code is relying on anyway).
 *
 * It is not a way to replace the Employee (or any other) model everywhere with something of your own creation.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class BaseModel extends \CI_Model
{
    use BaseObject;

    const CASCADE = 'cascade';
    const PROTECT = 'protect';
    const SET_NULL = 'set_null';
    const SET_DEFAULT = 'set_default';
    const SET = 'set';

    /**
     * Holds the name of the database table that this model represents.
     *
     * To save you time, Powerorm automatically derives the name of the database table from the name of your model
     * class. string off the namespace part e.g
     *
     * for a user model with a namespaced name of \myapp\models\user, the table name will be user
     *
     * @var string
     * @ignore
     */
    protected $table_name;

    /**
     * Indicates if the orm should managed the table being represented by this model.
     *
     * Defaults to True, meaning Powerorm  will create the appropriate database tables in migrate or as part of
     * migrations and remove them as part of a flush command.
     *
     * That is, Powerorm manages the database tables’ lifecycles.
     *
     * If False, no database table creation or deletion operations will be performed for this model.
     *
     * This is useful if the model represents an existing table or a database view that has been created by some other
     * means.
     *
     * This is the only difference when managed=False.
     *
     * All other aspects of model handling are exactly the same as normal. This includes:
     *  - Adding an automatic primary key field to the model if you don’t declare it.
     *   To avoid confusion for later code readers, it’s recommended to specify all the columns from the database table
     *   you are modeling when using unmanaged models.
     *
     *  - If a model with managed=False contains a ManyToManyField that points to another unmanaged model,
     *    then the intermediate table for the many-to-many join will also not be created.
     *
     *    However, the intermediary table between one managed and one unmanaged model will be created.
     *
     *    If you need to change this default behavior, create the intermediary table as an explicit model
     *   (with managed set as needed) and use the ManyToManyField->through attribute to make the relation
     *   use your custom model.
     *
     * @var
     */
    protected $managed = true;

    /**
     * When using multi-table inheritance, a new database table is created for each subclass of a model.
     *
     * This is usually the desired behavior, since the subclass needs a place to store any additional data fields that
     * are not present on the base class.
     *
     * Sometimes, however, you only want to change the php behavior of a model – perhaps to add a new method.
     *
     * This is what proxy model inheritance is for:
     *
     * creating a proxy for the original model. You can create, delete and update instances of the proxy model and all
     * the data will be saved as if you were using the original (non-proxied) model.
     *
     * The difference is that you can change things like the default model ordering or the default manager in the proxy,
     * without having to alter the original.
     *
     * Proxy models are declared like normal models. You tell Powerorm that it’s a proxy model by setting the proxy
     * attribute of the class to True.
     *
     * @var
     */
    protected $proxy = false;

    /**
     * Indicates if this is a new model, in the sense that it has not been loaded with values from the database like
     * you would when you want to update a database record.
     *
     * @ignore
     *
     * @var bool
     */
    protected $is_new = true;

    /**
     * A list of all Fields that the model has.
     *
     * @ignore
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Meta information about the model.
     *
     * @ignore
     *
     * @var Meta
     */
    public $meta;

    /**
     * @ignore
     */
    public function __construct($data = [])
    {
        assert(is_array($data), 'Model expects and array of field/value');

        $this->dispatch_signal('powerorm.model.pre_init', $this);

        // invoke parent
        parent::__construct();

        $this->init();

        // run this after init() to ensure the values are not reset by init().
        $this->populate($data);

        $this->dispatch_signal('powerorm.model.post_init', $this);
    }

    /**
     * Loads data to the model.
     *
     * @param $data
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function populate($data)
    {
        foreach ($data as $field => $value) :
            $this->{$field} = $value;
        endforeach;
    }

    /**
     * @param Registry $registry
     * @param array    $fields
     *
     * @throws TypeError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function init($registry = null, $fields = [])
    {
        if (null === $registry):
            // add model to the registry
            $registry = Orm::get_registry();
        endif;

        // create meta for this model
        $meta = new Meta(['registry' => $registry]);
        $this->add_to_class('meta', $meta);

        // set them up
        $this->setup_fields($fields);

        // ensure the model is ready for use.
        $meta->prepare($this);

        $registry->register_model($this);

//        // have a reference to the registray associated to this model
//        $meta->registry = $registry;
    }

    public static function instance($registry = '', $fields = [])
    {
        $obj = new static();
        $obj->init($registry, $fields);

        return $obj;
    }

    // ========================================================================================================

    // ============================================= QUERY METHODS ============================================

    // ========================================================================================================

    /**
     * @ignore
     */
    public function one($conditions, $opts = [])
    {
        return $this->queryset($opts)->one($conditions);
    }

    /**
     * @ignore
     */
    public function filter($conditions, $opts = [])
    {
        return $this->queryset($opts)->filter($conditions);
    }

    /**
     * @ignore
     */
    public function all($opts = [])
    {
        return $this->queryset($opts)->all();
    }

    /**
     * @ignore
     */
    public function exclude($conditions, $opts = [])
    {
        return $this->queryset($opts)->exclude($conditions);
    }

    /**
     * @ignore
     */
    public function count($opts = [])
    {
        return $this->queryset($opts)->size();
    }

    /**
     * @ignore
     */
    public function exists($opts = [])
    {
        return $this->queryset($opts)->exists();
    }

    /**
     * Creates or Updates an object in the database,.
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
     *
     * @param array $opts
     *
     * @return mixed
     */
    public function save($opts = [])
    {
        // run this in transaction
        $this->db->trans_start();

        // alert everyone else of intended save
        $this->dispatch_signal('powerorm.model.pre_save', $this);

        $save_model = $this->queryset($opts)->_save();

        // alert everyone of the save
        $this->dispatch_signal('powerorm.model.post_save', $this);

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            show_error('sorry the operation was not successful');
        }

        return $save_model;
    }

    /**
     * Eager loading, this will solve the N+1 ISSUE.
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
     * @param array $conditions the name/names of model fields
     * @param array $opts
     *
     * @return Queryset
     */
    public function with($conditions, $opts = [])
    {
        return $this->queryset($opts)->with($conditions);
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
     *
     * @return $this
     */
    public function first($opts = [])
    {
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
     *
     * @return $this
     */
    public function last($opts = [])
    {
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
     * {@link}
     *
     * @param array $opts
     */
    public function delete($opts = [])
    {
        $this->queryset($opts)->clear();
    }

    /**
     * Stores Many To Many relationship.
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
     * @param array  $related        the objects of related models to associate with the current model
     * @param string $using_db_group
     *
     * @throws OrmExceptions
     * @throws TypeError
     */
    public function add($related = [], $using_db_group = '')
    {
        if (!is_array($related)) {
            throw new OrmExceptions(sprintf('add() expects an array of models'));
        }

        // if the array is empty exit
        if (empty($related)):
            return;
        endif;

        $related_model = '';

        // Some possibilities to consider
        foreach ($related as $item) :

            // if a Queryset was passed in
            if (!$item instanceof self):
                throw new OrmExceptions(
                    sprintf('add() expects an array of objects that extend the %1$s but got a %2$s',
                        'PModel', get_class($item)));
            endif;

            // get the related model name to save
            if (!empty($related_model) && $related_model !== get_class($item)):
                throw new TypeError(
                    sprintf('Multiple types provided, add() expects only one type per call, see documentation'));
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
     * The database table that this model represents.
     *
     * @return string
     */
    public function get_table_name()
    {
        $table_name = $this->table_name;

        if (!isset($table_name)):
            $this->table_name = $table_name = $this->standard_name($this->get_class_name());
        endif;

        return $table_name;
    }

    /**
     * The database table that this model represents with the prefix appended if one
     * was set on the database configuration.
     *
     * @param null $name
     *
     * @return string
     */
    public function full_table_name($name = null)
    {
        $table_name = sprintf('%1$s%2$s', $this->db->dbprefix, $this->table_name);
        if (null != $name) {
            $table_name = sprintf('%1$s%2$s', $this->db->dbprefix, $name);
        }

        return $table_name;
    }

    /**
     * Returns the database prefix to used on the table represent by current model.
     *
     * @return mixed
     */
    public function table_prefix()
    {
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
     *
     * @return array
     */
    public static function permissions()
    {
        return array(
            'can_add' => 'Can Add',
            'can_delete' => 'Can Delete',
            'can_update' => 'Can Update',
            'can_view' => 'Can View',
            'can_list' => 'Can List',
        );
    }

    /**
     * Creates a Queryset that is used to interaract with the database.
     *
     * @param string $opts
     *
     * @return Queryset
     */
    public function queryset($opts)
    {
        return $this->_get_queryset($opts);
    }

    /**
     * Sets the relative uri to an instance of the model e.g. user/1 .
     * <pre><code>public function get_uri($slug=FALSE){
     *   $route = $this->id;.
     *
     *   if($slug==TRUE){
     *      $route = $this->slug;
     *   }
     *   return 'user/'.$route;
     * }</code></pre>
     *
     * @param bool $slug if set to true returns the url as a slug e.g. user/admin
     *
     * @return string
     */
    public function get_uri($slug = false)
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
     * }</code></pre>.
     */
    abstract public function fields();

    /**
     * @ignore
     */
    public function clean()
    {
        $this->clean_fields();
    }

    /**
     * @ignore
     */
    public function full_clean()
    {
    }

    /**
     * @ignore
     * Used by migrations to provide any thing that needs to be run before the migrtion process starts
     */
    public function check()
    {
        $checks = [];

        $f_checks = $this->_check_fields();

        if (!empty($f_checks)):
            $checks = array_merge($checks, $f_checks);

        endif;

        return $checks;
    }

    /**
     * @return array
     * @ignore
     */
    public function _field_values()
    {
        $values = [];
        foreach ($this->meta->fields as $name => $field) :
            $values[$name] = $this->{$name};
        endforeach;

        return $values;
    }

    /**
     * Returns a Form that represents the current model.
     *
     * @return $this
     *
     * @throws OrmExceptions
     * @throws \eddmash\powerorm\exceptions\DuplicateField
     * @throws \eddmash\powerorm\exceptions\FormException
     */
    public function toForm()
    {
        return $this->form_builder()->form();
    }

    // ========================================================================================================

    // ============================================  UTILITY METHODS ==========================================

    // ========================================================================================================

    protected function _load_fields()
    {
        $reflect = new \ReflectionClass($this->get_parent());
        if ($reflect->isAbstract() && $this->proxy):
            throw new TypeError(
                sprintf('Proxy model { %s } cannot have all its base classes as abstract.', $this->get_class_name()));
        endif;

        $this->call_method_upwards('fields');
    }

    public static function from_db($connection, $fields_with_value)
    {
        return new static($fields_with_value);
    }

    /**
     * Create a queryset.
     *
     * @internal
     *
     * @return Queryset
     */
    public function _get_queryset($opts)
    {
        $opts = (empty($opts)) ? '' : $opts;

        return Connection::get_queryset($this, null, $opts);
    }

    /**
     * @param $opts
     *
     * @return mixed
     *
     * @internal
     */
    public function _get_db($opts)
    {
        $use_db_group = '';
        if (is_array($opts) && array_key_exists('use_db_group', $opts)):
            $use_db_group = $opts['use_db_group'];
        endif;

        if (!empty($use_db_group)):
            $database = $this->load->database($use_db_group, true);
        else:
            $database = $this->load->database('', true);
        endif;

        return $database;
    }

    /**
     * Adds fields to the model and add them to meta object of the model.
     *
     * @internal
     *
     * @param array $fields
     */
    public function setup_fields($fields)
    {

        // load the fields
        $this->_load_fields();

        if (!empty($fields)):
            foreach ($fields as $field_name => $field_obj) :
                $this->_add_fields($field_name, $field_obj);
            endforeach;

            return;
        endif;

        // in case the fields were not added dynamically, they already defined them as properties of the class.
        $fields = get_class_vars(get_class($this));

        foreach ($fields as $field_name => $value) :

            if (!isset($this->{$field_name})):
                continue;
            endif;

            $field_obj = $this->{$field_name};

            $this->_add_fields($field_name, $field_obj);

            // remove it as an attribute, since we store all the fields in the fields array,
            // and use magic method __get() to access them
            if ($value instanceof Field):
                unset($this->{$field_name});
            endif;

        endforeach;

        foreach ($this->fields as $name => $field_obj) :
            $this->_add_fields($name, $field_obj);
        endforeach;
    }

//    protected function _load_field()
//    {

//    }

    /**
     * Adds model fields.
     *
     * @param $field_name
     * @param $field_obj
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _add_fields($field_name, $field_obj)
    {
        if ($field_obj instanceof Field):

            $this->add_to_class($field_name, $field_obj);

        endif;
    }

    public function load_field($field)
    {
        $this->fields[$field->name] = $field;
    }

    public function add_to_class($name, $obj)
    {
        if ($obj->has_method('contribute_to_class')):
            $obj->contribute_to_class($name, $this);
        endif;
    }

    /**
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

        foreach ($this->meta->fields as $field_name => $field_obj) :
            $f_check = $field_obj->check();
            if (!empty($f_check)):
                $checks = array_merge($checks, $f_check);
            endif;
        endforeach;

        return $checks;
    }

    /**
     * @return bool true if the orm manages this model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function is_managed()
    {
        return $this->managed;
    }

    /**
     * @return bool true if the model is a proxy model
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function is_proxy()
    {
        return $this->proxy;
    }

    public function has_property($name)
    {
        return property_exists($this, $name) || array_key_exists($name, $this->fields);
    }
    // ========================================================================================================

    // ========================================== PHP MAGIC METHODS ===========================================

    // ========================================================================================================

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     *
     * @ignore
     */
    public function __call($method, $args)
    {
        // some helper e.g. get_gender_display
        if (preg_match('/get_(.*)_display/', $method)):
            $name = implode('', preg_split('/^(get_)||(_display)$/', $method));

            if (isset($this->meta->fields[$name]) && !empty($this->meta->fields[$name]->choices)):
                $value = $this->{$name};
                if (empty($value)):
                    return $value;
                endif;

                return $this->meta->fields[$name]->choices[$value];
            endif;
        endif;

        throw new \BadMethodCallException(
            sprintf('Call to undefined method %1$s:%2$s() ', $this->get_class_name(), $method));
    }

    public function __set($name, $value)
    {
        if ($value instanceof Field):
            $this->fields[$name] = $value;
        else:
            $this->{$name} = $value;
        endif;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->fields)):
            return $this->fields[$name];
        endif;

        return parent::__get($name);
    }

    /**
     * @ignore
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('< %1$s: %1$s object> ', $this->get_class_name());
    }

    public function __debugInfo()
    {
        $model = [];
        foreach (get_object_vars($this) as $name => $value) :
            if ($name === 'fields'):
                continue;
            endif;
            $model[$name] = $value;
        endforeach;

        return $model;
    }
}
