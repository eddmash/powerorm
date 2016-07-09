<?php
namespace powerorm;

/**
 * Orm Loader
 */
use powerorm\db\Connection;
use powerorm\form\BaseForm;
use powerorm\form\BaseModelForm;
use powerorm\form\ModelForm;
use powerorm\registry\App;

/**
 *
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PowerORM version
 * @ignore
 */
define('POWERORM_VERSION', '1.1.0');

/**
 * class that Loads the ORM LIBRARY and all the necessary classes.
 *
 * Its actually Meant to get around CodeIgniter not using namespaces
 *
 * To start using the orm, load it like any other CODIGNTER library, preferably using autoload
 * <pre><code>$autoload['libraries'] = array(
 *          'powerdispatch/signal',
 *          'powerorm/orm', <------------------------------------ the orm
 *          'powerauth/auth'
 * );</code></pre>
 *
 * <h4><strong>PModel</strong></h4>
 * The ORM Model that adds power to CI Model.
 *
 * This class provides the CI MODEL with power by doing two important things :
 *
 * - Use model to determine how the database table it represent looks like
 *
 * - Assigns Model fields
 *
 *      Allows creating fields on the model, which represent columns in the database.
 *
 * - Provides easy interaction with the database.
 *
 *      This class provides a Queryset object for each class that extends it. The Queryset class Acts like a proxy between
 *      the database and the current model.
 *
 * USAGE:
 * <h4><strong>Extending</strong></h4>
 *
 * Extend this class on model classes e.g.
 *
 * <pre><code>User_model extends  PModel{
 *      protected $table_name= '';
 *      ...
 * }</code></pre>
 *
 * The `table_name` variable is optional, this tells the orm which database table this model represents. if not set
 * Table name is taken as model name e.g. user_model above the table would be `user_model`
 *
 * <h4><strong>Interacting with the database</strong></h4>
 *
 * Load model like any other codeigniter model
 * <pre><code>$this->load->model('user_model')</code></pre>
 *
 * Extending the  PModel, provides several methods that can be used to interact with the database, e.g. the get() method
 * more on this later.
 *
 * <pre><code>$this->user_model->get(['name'=>'john'])</code></pre>
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
 * - Queryset is evaluated to get data from the database.
 *
 * The PModel has more methods read more here
 *
 * {@link http://eddmash.github.io/powerorm/docs/classes/PModel.html}
 *
 * <h4><strong>Queryset</strong></h4>
 *
 * Class for doing database lookups, The look up is done Lazily i.e. Lazy Loading.
 *
 * This class provides several methods for interacting with the database with one
 * important thing to note is that some.
 *
 * <strong><em>Most Methods return a Queryset object</em></strong> and not the database results.
 * This allows for further refinement of the Queryset.
 *
 * <h4>Methods that don't return a Querset.</h4>
 * The following are the methods don't return a Queryset:
 * - {@see Queryset::get() }
 * - {@see Queryset::size() }
 * - {@see Queryset::delete() }
 * - {@see Queryset::value() }
 * - {@see Queryset::save() }
 * - {@see Queryset::add() }
 *
 * Read more of this methods here
 * {@link http://eddmash.github.io/powerorm/docs/classes/powerorm.queries.Queryset.html }
 *
 * <h4><strong>Creating A Queryset</strong></h4>
 * Each model that extends the `PModel` class automatically gets assigned a Queryset object,
 * using this Queryset you are able perform database lookups.
 *
 * Assuming we have a model class User_model that represents all users in the user database table.
 *
 * We can interact with it as follows:
 *
 * To get one user with the `name=john`
 * <pre><code>$this->User_model->get(array('name'=>'john'))</code></pre>
 *
 * To get All user in the database
 * <pre><code>$this->User_model->all()</code></pre>
 *
 *
 * <h4><strong>Refining the Queryset</strong> (Method Chaining)</h4>
 *
 * e.g count all users
 * <pre><code> $this->user_model->all()->size(array('name'=>'john')) </code></pre>
 *
 *  get all users with the name `name=john`
 *  <pre><code> $this->user_model->all()->filter(array('name'=>'john'))
 *
 * // which can also be handle as follows
 *
 * $this->user_model->filter(array('name'=>'john'))
 * </code></pre>
 *
 *
 * <h4><strong>Getting Results</strong> (Lazily Loading and Evaluation)</h4>
 * To get results from the database, the Queryset object has to be evaluated, the reason for this is to hold
 * off from hitting the database until its absolutely necessary, that is when the results are actually needed.
 *
 *
 * A Queryset Evaluation takes place in the following situations :.
 *
 * - When looping through the Queryset using foreach.
 *     <pre><code> $admins = $this->role->all();
 *      foreach($admins as $admin){
 *             ...
 *      } </code></pre>
 *
 * - When using a Queryset like a string e.g. in an echo statement.
 *    <pre><code>echo $this->role->get(array('name'=>'admin'));</code></pre>
 *
 * - When testing or existence of a property e.g. using isset().
 *   <pre><code>$admin_role = $this->role->get(array('name'=>'admin'));
 *   if(isset($admin_role->description)){
 *       ...
 *   }</code></pre>.
 *
 * - When the {@see Queryset::value() } method of the Queryset is invoked.
 *
 *   <pre><code>$admin_role = $this->role->all()->value(); </code></pre>.
 *
 *
 *  <h4>See methods for more explanations and usage examples</h4>
 *
 *  <h3>Some common issues when using ORMs to avoid</h3>
 *
 * - <h4><strong>N+1 Problem</strong></h4>
 *
 * This problem occurs when the code needs to load the children of a parent-child relationship
 * (the “many” in the “one-to-many”).
 *
 * Most ORMs have lazy-loading enabled by default, so queries are issued for the parent record, and then one query for
 * EACH child record.
 *
 * As you can expect, doing N+1 queries instead of a single query will flood your database with queries,
 * which is something we can and should avoid.
 *
 * Consider a simple blog application which has many articles published by different authors. i.e many to one
 *
 * We want to list articles along with their title and author’s name.
 *
 * This could be achieved using the following
 *
 * <pre><code>$articles = $this->article_model->all()
 *
 * foreach($articles as $article){
 *      $article->author->name;
 * }
 * </code></pre>
 *
 * Assuming we have 20 articles in the database, the above code will produce 20+1 queries to the database
 *
 * <pre><code> // one to fetch all the articles
 *
 * select * from articles;
 *
 * // then based on the value of foreign_key to the author on each article, an author is
 * // fetched resultin in 20 more queries hence the N+1.
 *
 * SELECT 'authors'.* FROM 'authors' WHERE 'authors'.'id' = ?
 * SELECT 'authors'.* FROM 'authors' WHERE 'authors'.'id' = ?
 * SELECT 'authors'.* FROM 'authors' WHERE 'authors'.'id' = ?
 * SELECT 'authors'.* FROM 'authors' WHERE 'authors'.'id' = ?
 * SELECT 'authors'.* FROM 'authors' WHERE 'authors'.'id' = ?</code></pre>
 *
 * To solve this problem use the {@see Queryset::with()}.
 *
 * The method tells the orm to eagerly load the article and authors in one go when the Queryset is being evaluated.
 * which will result in two sql queries as shown below:
 *
 * <pre><code>$articles = $this->with(['author'])->article_model->all()
 *
 * foreach($articles as $article){
 *      $article->author->name;
 * }
 *
 * // one to fetch all the articles
 *
 * SELECT 'articles'.* FROM 'articles'
 *
 *
 * //one to fetch all authors
 *
 * SELECT 'authors'.* FROM 'authors' WHERE 'authors'.'id' IN (1,2,3,4,5)</code></pre>
 *
 * example borrowed from {@link http://www.sitepoint.com/silver-bullet-n1-problem/ }
 *
 *  To avoid this issues using this orm, use the {@see Queryset::with()} method.
 *
 * @package powerorm\model
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class BaseOrm {

    public static $SET_NULL='set_null';
    public static $CASCADE='cascade';
    public static $PROTECT='protect';
    public static $SET_DEFAULT='set_default';

    public $migration_path;

    public $models_path;

    /**
     * @param array $config
     * @ignore
     */
    public function __construct($config=[]){
        $this->setup();
        $this->init();
    }

    public static function get_models_path(){
        return APPPATH."models/";
    }

    public static function get_migrations_path(){
        return APPPATH."migrations/";
    }

    /**
     * initializes the orm and loads app some essential modules
     * @internal
     */
    public function init(){

        //setup the registry
        $this->registry = new App();
    }

    /**
     * setup the orm modules
     * @internal
     */
    protected function setup(){

        // load the CI model class
        include_once BASEPATH."core/Model.php";
        require_once(APPPATH."libraries/powerorm/exceptions/__init__.php");

        require_once("traits/__init__.php");

        // load some utility tools aka helpers
        require_once("helpers/__init__.php");

        require_once("Object.php");

        require_once("registry/__init__.php");

        // statements
        require_once("db/__init__.php");

        // model
        require_once("model/__init__.php");

    }

    public static function load_class($qualified_name){
        require_once $qualified_name;
    }


    //********************************** ORM Form*********************************


    /**
     * Returns a form builder instance. you can use this to create forms that don't depend on models.
     * Take note this method creates a new instance of the form when invoked unlike how other classes in codeigniter
     * are singletons i.e. using the same instance during the response of a request.
     * @return Form
     */
    public function get_form($opts=[]){

        $data = (array_key_exists('data', $opts)) ? $opts['data'] : [];
        $initial = (array_key_exists('initial', $opts)) ? $opts['initial'] : [];
        $form = (array_key_exists('form', $opts)) ? $opts['form'] : [];
        $model = (array_key_exists('model', $opts)) ? $opts['model'] : [];

        $kwargs = $this->form_kwargs($opts);


        assert((empty($form) || empty($model)), "Setting both { model } and { form } on the same get_form() method");

        require_once("form/__init__.php");

        // just fetch the form
        if(!empty($form)):
            return $this->_fetch_form($form, $data, $initial, $kwargs);
        endif;

        // or create a ModelForm
        if(!empty($model)):
            return (new BaseModelForm($data, $initial, $kwargs))->model($model);
        endif;

        // else the create the default form
        return new BaseForm($data, $initial, $kwargs);

    }

    public function form_kwargs($opts){
        foreach (['data', 'initial', 'model', 'form'] as $opt) :
            if(array_key_exists($opt, $opts)):
                unset($opts[$opt]);
            endif;
        endforeach;

        return $opts;
    }

    public function _fetch_form($form, $data=[], $initial=[], $kwargs=[]){
        //todo loader match to that of codeigniter especially in naming

        require_once APPPATH.'forms/'.$form.'.php';

        $form =  new $form($data, $initial, $kwargs);

        // ensure the model is loaded
        if($form instanceof BaseModelForm):
            $form->model();
        endif;

        return $form;
    }

    //********************************** ORM Registry*********************************


    /**
     * Returns the application registry.
     * @return mixed
     */
    public function get_registry(){
        $this->init();
        return $this->registry;
    }

    /**
     * Returns the numeric version of the orm
     * @return string
     */
    public function get_version(){
        if(defined('POWERORM_VERSION')):
            return POWERORM_VERSION;
        endif;
    }

    public static function version(){
        return static::instance()->get_version();
    }

    /**
     * This is just a shortcut method.
     * get the current instance of the orm
     * @return mixed
     */
    public static function instance(){
        $ci = self::ci_instance();
        return $ci->orm;
    }

    public static function dbconnection()
    {
        static::create_connection();

        $ci = self::ci_instance();

        return new Connection($ci->db, $ci->dbforge);
    }

    public static function create_connection($db_configs = NULL){


        $CI =self::ci_instance();

        self::load_database($db_configs);

        $db = & $CI->db;

        require_once(BASEPATH.'database/DB_forge.php');
        require_once(BASEPATH.'database/drivers/'.$db->dbdriver.'/'.$db->dbdriver.'_forge.php');

        $class = $db->dbdriver;
        if ( ! empty($db->subdriver)):
            $driver_path = sprintf(BASEPATH.'database/drivers/%s/subdrivers/%s_%s_forge.php', $db->dbdriver);
            if (file_exists($driver_path)):
                require_once($driver_path);
                $class = $db->dbdriver.'_'.$db->subdriver;
            endif;
        endif;

        $class = ucfirst($class);
        $class = $class."Editor";

        require_once(APPPATH.'libraries/powerorm/db/schema/'.$class.'.php');

        $class = 'powerorm\db\schema\\'.$class;

        $CI->dbforge = new $class($db);
    }

    public static function load_database($db_configs){

        if(!empty($db_configs)):
            self::ci_instance()->load->database($db_configs, FALSE, TRUE);
        else:
            self::ci_instance()->load->database('', FALSE, TRUE);;
        endif;

    }

    public static function &ci_instance(){
        return get_instance();
    }

    //********************************** ORM Checks *********************************
    public static function register_check(){

    }
}



