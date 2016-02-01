<?php
/**
 * ORM QuerySet implementation.
 */


/**
 *
 */
defined('BASEPATH') OR exit('No direct script access allowed');

require_once('OrmExceptions.php');

/**
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
 * - {@see Queryset::related_one() }
 * - {@see Queryset::size() }
 * - {@see Queryset::delete() }
 * - {@see Queryset::value() }
 * - {@see Queryset::save() }
 * - {@see Queryset::add() }
 *
 * <h4><strong>Creating A Queryset</strong></h4>
 * Each model that extends the `Base_Model` class automatically gets assigned a Queryset object,
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
 * To get all the permissions user john has
 * <pre><code>$this->user_model->get(array('name'=>'john'))->related('permission_model')</code></pre>
 *
 * To count all the permissions user john has
 * <pre><code>$this->user_model->get(array('name'=>'john'))->related('permission_model')->size()</code></pre>
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
 * - When the {@see Queryset::value()} method of the Queryset is invoked.
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
 *      $article->related_one('author')->name;
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
 * <pre><code>$articles = $this->with('author')->article_model->all()
 *
 * foreach($articles as $article){
 *      $article->related_one('author')->name;
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
 * @package POWERCI
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 *
 *
 */
class Queryset implements \IteratorAggregate, \Countable{

    /**
     * @var string Holds the method to be invoked when Queryset is being evaluated
     * @internal
     */
    protected $invoke_method;

    /**
     * @var object This variable holds CodeIgniter instance.
     * @internal
     */
    protected $_context;

    /**
     *@internal
     * @var object Holds the model instance the Queryset acts on.
     */
    protected $_model_instance;

    /**
     * @var object Holds a copy of the database
     * @internal
     */
    protected $_database;

    /**
     * @var bool Indacates if a Queryset has been evaluated.
     * @internal
     */
    protected $_evaluated = FALSE;

    /**
     * @var object Holds the Queryset Result when Queryset evaluates.
     * @internal
     */
    protected $_query_result;


    /**
     * @var bool This is usually true if the methods are being chained in the current instance of the Queryset.
     * @internal
     */
    protected $_table_set=FALSE;

    /**
     * Sets if the queryset requires to do an eager load
     * @internal
     * @var bool
     */
    protected $_eager_load = FALSE;

    /**
     * Holds the models to eager load
     * @internal
     * @var array
     */
    protected $_models_to_eager_load = array();



    /**
     *  ============================================OVERRIDEN MAGIC METHODS =========================
     *  -----------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     *  ---------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     * -----------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     *
     */


    /**
     * Creates the Queryset object.
     * @param object $context The Model on which the Queryset acts on. The model also provides access to he CI_Object
     * @ignore
     */
    public function __construct($context){
        $this->_context =& $context;


        $this->_context->output->enable_profiler(FALSE);
        // clone the database to ensure its unique for each queryset
        $this->_database = clone $context->db;
        $this->_database->reset_query();

    }

    /**
     * Evaluate the Queryset when existence of a property in the Queryset Result is tested. using isset().
     * @param $property
     * @ignore
     * @return bool
     */
    public function __isset($property){
        $result = $this->_eval_queryset();
        return property_exists($result, $property);

    }

    /**
     * Evaluate the Queryset when a property is accessed from the Model Instance.
     * @param $property
     * @ignore
     * @return mixed
     */
    public function __get($property){

        $value = NULL;
        // check if queryset is already evaluated
        if(!$this->_evaluated):
            $this->_eval_queryset();
        endif;
        
        return $this->_query_result->{$property};
    }

    /**
     * Evaluates the Queryset when a method is being accessed in the Queryset Result.
     * @param $method
     * @param $args
     * @ignore
     * @return mixed
     */
    public function __call($method, $args){
        if(!method_exists($this,$method)):

            if(!$this->_evaluated):
                // evaluate the queryset
                $this->_eval_queryset();
            endif;

            // if a method is being accessed that does not exist in the queryset
            // look for it in the resulting model if the query has been evaluated
            if($this->_evaluated):

                if(empty($args)):
                    return call_user_func(array($this->_query_result, $method));
                else:
                    if(is_array($args)):
                        return call_user_func_array(array($this->_query_result, $method), $args);
                    else:
                        return call_user_func(array($this->_query_result, $method), $args);
                    endif;
                endif;
            endif;
        endif;


    }

    /**
     * Evaluates Queryset when the Queryset Result is used like a string e.g. using Queryset Result in echo statement.
     * @ignore
     * @return string
     */
    public function __toString(){

        $this->_eval_queryset();
        return sprintf('%s', $this->_query_result);
    }

    /**
     * Evaluates the Queryset when Queryset Result is used in a foreach.
     * @ignore
     * @return ArrayIterator
     */
    public function getIterator(){
        $result = new \ArrayIterator($this->_eval_queryset());
        return $result;
    }


    /**
     * Evaluates the Queryset when Queryset Result is used in a count() or sizeof().
     * @ignore
     * @return mixed
     */
    public function count(){
        $this->invoke_method = 'num_rows';
        return $this->_eval_queryset();
    }

        // ToDo serialization , to json too
//    public function __sleep(){
//        $this->_eval_queryset();
//        return array('_query_result');
//    }
    /**
     * ============================================ QUERY OPERATION METHODS =========================
     *  -----------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     *  ---------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     * -----------------------------------------------------------------------------------------------
     *  -----------------------------------------------------------------------------------------------
     *
     */


    /**
     *
     * Fetches exactly one record from the database table by matching the given lookup parameters.
     * This method Returns a Queryset for further refinement
     * USAGE:.
     *
     *
     * To fetch a single row in the database with the name john.
     *
     *
     * <code>  $this->User_model->get_querset(array('name'=>'john')) </code>
     *
     *
     * To fetch a single row in the database with the `name=john` and `age=20`.
     *
     *
     * <code>     $this->User_model->get_querset(array('name'=>'john', 'age'=20))</code>
     *
     * To fetch a single row in the database with the primary key 1, where primary key column is `id`.
     *
     *
     * <code>      $this->User_model->get_querset(1)</code>
     *
     *
     * if primary key is not `id` you can pass the primary key field
     *
     *
     * <code>      $this->User_model->get_querset(array('pk'=>1))</code>
     *
     * @param array|int $values an array of field and the value to look for, or an integer-primary key.
     * @see filter() To fetch more than one item based on some conditions.
     * @internal
     * @return  Queryset
     * @throws OrmExceptions
     * @throws MultipleObjectsReturned
     * @throws ObjectDoesNotExist
     */
    private function get_querset($values){
        $this->_chainable = FALSE;
        $this->invoke_method = 'row';

        if($this->_table_set===TRUE || $this->_evaluated===TRUE){
            throw new OrmExceptions(
                sprintf("{ %s } only allowed as the first method in the chain", __METHOD__));
        }

        //ensure a value was passed in
        if(!isset($values)){
            throw new OrmExceptions(
                sprintf("Missing argument expected 'int or array' to be passed in to `get` method"));
        }
        $current_table = $this->_context->table_name();
        // get id if value is not array
        if(!is_array($values)):
            $primary_key = $current_table.'.'.$this->_current_model_primary_key();
            $this->_database->where($primary_key, $values);
        else:
            $args = array();
            foreach ($values as $key=>$value ) {
                $args[$current_table.".".$key]=$value;
            }


            // form a where statement
            $this->_database->where($args);
        endif;

        $this->_database->from($this->_context->table_name());

        // this lets other method know that the table has been set
        $this->_table_set = TRUE;

        // ensure only one row contains the passed in id
        $database = clone $this->_database;
        $count = $database->get()->num_rows();
        if($count>1){
            throw new MultipleObjectsReturned(
                sprintf('get() returned more than one %1$s -- it returned %2$s!', get_class($this->_context),$count));
        }

        if($count==0){
            throw new ObjectDoesNotExist(
                sprintf('%s matching query does not exist.!', get_class($this->_context)));
        }

        return $this;
    }
    /**
     *
     * Fetches exactly one record from the database table by matching the given lookup parameters.
     *     *
     * Works like {@see Queryset::filter()} but returns an object of the model and not a Queryset,
     * it Also raise `MultipleObjectsReturned` exception if more than one object is returned and
     * `ObjectDoesNotExist` if no object is found.
     *
     * USAGE:.
     *
     *
     * To fetch a single row in the database with the name john.
     *
     *
     * <code>  $this->User_model->get(array('name'=>'john')) </code>
     *
     *
     * To fetch a single row in the database with the `name=john` and `age=20`.
     *
     *
     * <code>     $this->User_model->get(array('name'=>'john', 'age'=20))</code>
     *
     * To fetch a single row in the database with the primary key = 1.
     *
     *
     * <code>      $this->User_model->get(1)</code>
     *
     * @param array|int $values an array of field and the value to look for, or an integer-primary key.
     * @see filter() To fetch more than one item based on some conditions.
     * @return object
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     * @throws MultipleObjectsReturned
     * @throws ObjectDoesNotExist
     */
    public function get($values){
        $this->get_querset($values);
        $this->_eval_queryset();
        return $this->_query_result;
    }

    /**
     * Fetches all the records in the database table represented by the Model Instance.
     * USAGE:
     *
     * To get All user in the database
     * <pre><code>$this->User_model->all()</code></pre>
     *
     * @return Queryset
     * @throws BadMethodCallException
     */
    public function all(){
        $this->invoke_method = 'result';

        if($this->_table_set===TRUE || $this->_evaluated===TRUE){
            throw new BadMethodCallException(
                sprintf("{ %s } only allowed as the first method in the chain", __METHOD__));
        }

        $this->_database->from($this->_context->table_name());

        // this lets other method know that the table has been set
        $this->_table_set = TRUE;

        return $this;
    }

    /**
     * Returns an integer representing the number of objects in the database matching the QuerySet.
     *  USAGE:.
     *
     *
     * To count all user in the database.
     *
     *
     * <pre><code> $this->User_model->all()->size() </code></pre>
     *
     *
     * To count all users in the database with the `name=john.`
     *
     *
     * <pre><code> $this->User_model->all()->size(array('name'=>'john')) </code></pre>
     *
     *
     * @param array $where (optional) values to limit the Queryset.
     * @return int
     */
    public function size($where=array()){
        if(!empty($where)){
            $this->_database->where($where);
            $this->__where_clause(get_class($this->_context), $where);
        }

        return $this->count();
    }

    /**
     * Works like the get method but difference is it can return more than one item.and return a Queryset
     *
     * Implements the where part of a query and returns rows limited by the where clause.
     *
     * <h4>USAGE:</h4>
     *
     * <h4>Normal Filtering</h4>
     * To fetch all rows in the database with the name `john`.
     *      <pre><code>$this->User_model->filter(array('name'=>'john'))</code></pre>
     *
     * To fetch all rows in the database with the `name=john` and `age=20`.
     *      <pre><code>$this->User_model->filter(array('name'=>'john', 'age'=20))</code></pre>
     *
     * <h4>Related Filter</h4>
     *
     * To fetch all rows based on there related data e.g fetch user model where role is `admin`.
     *  <pre><code>
     *  $roles = $this->user_model->filter(array("role::name"=>"admin"), "power");</code></pre>
     *
     * <strong>Note</strong>
     *
     * The foreign_key is an optional field, which you only need to set if the foreign key in you database tables
     * is not a combination of the name of the database table represented by the model and id .
     *
     * e.g in our case the database table name is `role_tb`, the orm would look for a field named `role_tb_id`
     * but our tables foreign_key to role is `power` so since the foreign_key column is set as `power`
     * we have to inform the orm of this by passing `power` as the second argument in the filter method
     *
     * <h4><strong>Important</strong> Related filter works in both directions</h4>
     * Assuming a one to many relationship between category and product, where the category is the one side and
     * the product is the many side
     *
     * You can filter the One side in a one to many relationship based on a particular item on the many side.
     *
     * e.g To get the category a product belongs to:
     *
     * <pre><code>$this->category_model->filter(array('product_model::1d'=>5)));</code></pre>
     *
     * You can filter the Many side based on a particular item on the One side.
     * e.g. To get all products that fall under a certain category
     *
     * <pre><code>$this->product_model->filter(array('category_model::id'=>5)));</code></pre>
     *
     *
     *
     * @param array $where the where condition e.g. array('username'=>'john', 'password'="ad+as')
     * @see get() To fetch only one item.
     * @param string $foreign_key the name of the foreign key if filtering by related, defaults to adding
     * `tablename_primarykey'
     * @return Queryset
     * @throws OrmExceptions
     */
    public function filter($where, $foreign_key=NULL){

        if(!is_array($where)){
            throw new OrmExceptions(sprintf("filter() expected and array"));
        }

        $this->invoke_method = 'result';
        $where_condition = array();

        $related_model_name = NULL;
        $related_where = array();

        if(is_array($where)):
            foreach ($where as $key=>$value) :
                // look for relationship
                if(preg_match("/::/", $key)):
                    $related_model_name = preg_split("/::/", $key)[0];
                    $related_model_search_key = preg_split("/::/", $key)[1];

                    $related_where[$related_model_search_key] = $value;

                else:
                    $where_condition[$key] = $value;
                endif;
            endforeach;

        endif;

        // the sql for the current model
        $this->_database->select($this->_context->table_name().'.*');
        if(!$this->_table_set){
            $this->_database->from($this->_context->table_name());
        }

        // create where condition
        $this->__where_clause(get_class($this->_context), $where_condition);

        // a relationship is passed in
        if(!empty($related_model_name)):

            $this->_load_related_model($related_model_name);

            $foreign_key = (!empty($foreign_key))? $foreign_key : NULL;

            // ToDo self referencing
            $one2one = (strtolower(get_class($this->_context))===strtolower($related_model_name))? TRUE : FALSE;
            // ************************* Try Recursive  **************************
            if($one2one):

            endif;

            // foreign key table
            $foreign_key_table = $this->_search_foreignkey($related_model_name, $foreign_key);


            // ************************* Try Many To One and One To Many **************************

            // if the foreign_key_table is not the current table
            if($foreign_key_table):
                // try one to many and Many to One
                $this->_m2o_join($related_model_name, $related_where, $foreign_key);
            endif;


            // ************************* Try Many To Many **************************

            if($foreign_key_table==FALSE):
                $this->_m2m_join($related_model_name, $related_where, $foreign_key);
            endif;

        endif;

        if(!$this->_table_set):
            $this->_table_set = TRUE;
        endif;

        return $this;
    }

    /**
     * Returns a Queryset that does not include those provided in the criteria.
     *
     *
     * <h4>USAGE: </h4>
     *
     * To exclude from a resulting Queryset
     * <pre><code>$this->role->all()->exclude(['name'=>'ceo'])</code></pre>
     *
     *
     * To exclude from the model itself
     *
     * <pre><code>$this->role->exclude(['name'=>'ceo'])</code></pre>
     *
     * @param array $criteria the criteria to use to exclude objects
     * @return $this
     * @throws OrmExceptions
     */
    public function exclude($criteria = []){
        $this->invoke_method = 'result';
        if(!is_array($criteria)):
            throw new OrmExceptions("exclude() expected an array");
        endif;

        $refined_criteria = [];
        foreach ($criteria as $key=>$value) {
            $refined_criteria[$key."__not"]=$value;
        }


        $this->__where_clause(get_class($this->_context), $refined_criteria);

        if(!$this->_table_set):
            $this->_database->from($this->_context->table_name());
        endif;
;
        return $this;
    }

    /**
     * Returns a new QuerySet that uses SELECT DISTINCT in its SQL query.
     *
     * This eliminates duplicate rows from the query results.
     *
     * <h4>USAGE: </h4>
     *
     * <pre><code>$this->user_model->distinct()->filter(["name"=>"john"]);</code></pre>
     * @return Querset
     */
    public function distinct(){
        $this->_database->distinct();
        return $this;
    }

    /**
     * Returns the related data, this can return one object or an array of records.
     *
     * <h4>USAGE: </h4>
     *
     * This method locates the table with the foreign_key by appending the id `_id` on either tables as it searches.
     * If you tables use different foreign key other than this combination provide it when providing the model name as shown below
     *
     * <pre><code>$this->user_model->get(1)->related("role"); //-- this is if foreign key is `role_id`</code></pre>
     *
     * <pre><code>$this->user_model->get(1)->related("role::powers");
     * // -- if foreign key is different pass it along in this case its `powers`</code></pre>
     *
     * This method can fetch in all directions i.e if a one to many relation exist between user and products,
     * where user has many products and a product is owned by only one person
     *
     * <h4>One to Many</h4>
     * - in one to many it can fetch from the many side  i.e fetches all products owned by a user.
     *
     *
     * <h4>Many to One</h4>
     *
     * - in many to one it can fetch from the one side i.e fetches the owner ofa specific product,
     *
     *  <strong>NB </strong>this returns a Queryset representing the related model which when evaluated returns an array
     * of the related model object.
     *
     *  To get an object of the related model {@see Queryset::related_one()}
     *
     *
     * <h4>Many to Many</h4>
     * - in many to many it can fetch from the both sides
     *
     *
     * <h4>One to One</h4>
     * - in one to one it can fetch from the both sides.
     *
     *
     * @param string $model_name the name of the related model together with the foreign_key
     *
     * @return Queryset
     */
    public function related($model_name){
        $this->invoke_method = "result";
        return $this->_related($model_name);
    }

    /**
     * Fetches relations from many to one, e.g getting the owner of a business,
     * where owner can have many businesses but a business can only be owned by one person.
     *
     * Works like {@see Queryset::related()} but return an object of the related model and not a Queryset,
     * it Also raise `MultipleObjectsReturned` exception if more than one object is returned and
     * `ObjectDoesNotExist` if no object is found.
     *
     * <pre><code>$this->business_model->get(1)->related_one('category_model')</code></pre>
     *
     * @param string $model_name name of related model
     * @return object
     * @throws MultipleObjectsReturned
     * @throws ObjectDoesNotExist
     */
    public function related_one($model_name){
        $current = $this->_related($model_name);
        $this->invoke_method = "row";

        // ensure only one row contains the passed in id
        $database = clone $this->_database;
        $count = $database->get()->num_rows();
        if($count>1){
            throw new MultipleObjectsReturned(
                sprintf('related_one() returned more than one %1$s -- it returned %2$s!', get_class($this->_context),$count));
        }

        if($count==0){
            throw new ObjectDoesNotExist(
                sprintf('%s matching query does not exist.!', get_class($this->_context)));
        }
        // evaluate
        $this->_eval_queryset();
        return $this->_query_result;
    }

    /**
     * Eager loading, this will solve the N+1 ISSUE
     *
     * <h4>USAGE:</h4>
     *
     * To Eager load related items of a single item
     *
     * <pre><code>$category = $this->category_model->with("product_model")->get(array("id"=>1));
     *
     * $category->product_models; // the products are now accessible like so
     *
     * foreach($rr->product_models as $product){
     *          echo $product->name;
     * }</code></pre>
     *
     * To Eager Many To Many Relationship
     *
     * <pre><code>$roles = $this->role->with('permission_model')->filter(array('user_model::id'=>1));
     * foreach ($roles as $role) {
     *         echo $role ;
     *
     *      foreach ($role->permission_models as $perm) {
     *          echo $perm->name;
     *      }
     * }</code></pre>
     *
     *
     * To Eager Load more than one relationship , this eager loads a user roles and products.
     *
     * <pre><code>$usr= $this->user_model->with(["products", 'role'])->get(["id"=>1]);
     *
     *
     * </code></pre>
     *
     * @param string|array $eager_model_name the name/names of models to eager load.
     * @return Queryset
     */
    public function with($eager_model_name){
        $this->_eager_load = TRUE;

        if(is_string($eager_model_name)):
            $this->_models_to_eager_load[] = $eager_model_name;
        endif;

        if(is_array($eager_model_name)):
            $this->_models_to_eager_load = $eager_model_name;
        endif;
        return $this;
    }

    /**
     * This evaluates the Queryset and returns the results of the evaluation.
     *
     * <h4>USAGE</h4>
     *
     * The following will result in an array of products as opposed to a Queryset
     *
     * <pre><code>$this->product_model->filter(array('category_model::id'=>5)))->value();</code></pre>
     *
     * @return mixed
     */
    public function value(){
        $this->_eval_queryset();
        return $this->_query_result;

    }


    /**
     * Shows the `sql` statement to be executed at certain point of the queryset chaining.
     *
     * USAGE:.
     *
     * To get all users with the role admin
     *
     * <pre><code>
     * $user = $this->user_model->all()->dump_sql()
     *      ->filter(array('related'=>array('model'=>'role', 'where'=>array('name'=>'admin')));
     * foreach()->dump_sql() as $user){
     *      ...
     * }
     * </code></pre>
     *
     * This will output the following at each point of the chain where dump_sql() is used.
     *<pre><code>
     * ******************* Running the sql statement *******************************
     * SELECT *
     * FROM `user`
     * ************************************************************************
     * **************** Running the sql statement *******************************
     * SELECT `user`.*
     * FROM `user`
     * JOIN `user_role` ON `user`.`id`=`user_role`.`user_id`
     * JOIN `role` ON `role`.`id`=`user_role`.`role_id`
     * WHERE `role`.`name` = 'admin'
     * *********************************************************************
     * </code></pre>
     * @return $this
     * @throws OrmExceptions
     */
    public function dump_sql(){
        if($this->_evaluated):
            throw new OrmExceptions("dump_sql() cannot be called on an evaluated Queryset");
        endif;

        $this->_dump_sql();
        return $this;
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
     * @return mixed
     */
    public function save(){
        // run this in transaction
        $this->_database->trans_start();

        // call the pre_save method
        $this->_context->pre_save();

        // alert everyone else of intended save
        if(class_exists('Signal', FALSE)):
            $this->_context->signal->dispatch('powerorm.model.pre_save', $this->_context);
        endif;

        $save_model=$this->_save();

        // call post_save method
        $this->_context->post_save();

        // alert everyone of the save
        if(class_exists('Signal', FALSE)):
            $this->_context->signal->dispatch('powerorm.model.post_save', $this->_context);
        endif;

        $this->_database->trans_complete();

        if ($this->_database->trans_status() === FALSE)
        {
            show_error("sorry the operation was not successful");
        }

        return $save_model;
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
     * @param array $related the objects of related models to associate with the current model
     * @throws OrmExceptions
     * @throws TypeError
     */
    public function add($related=[]){
        if(!is_array($related)){
            throw new OrmExceptions(sprintf("add() expects an array"));
        }

        // Some possibilities to consider
        $loop_position =0;
        foreach ($related as $items) :

            // if a Queryset was passed in
            if($items instanceof Queryset):
                $items = $items->value();

                if(is_array($items)):
                    $toadd = $items;
                    // remove it from array
                    array_splice($related, $loop_position, 1);

                    // merge with the values of the queryset
                    $related = array_merge($related, $toadd);
                endif;
            endif;

            // if array is passed in
            if(is_array($items)):
                $toadd = $items;
                // remove it from array
                array_splice($related, $loop_position, 1);

                // merge with the values of the queryset
                $related = array_merge($related, $toadd);
            endif;
            $loop_position++;
        endforeach;



        $related_ids = array();
        $related_model = NULL;
        foreach ($related as $item) {

            // get primary key of the model
            $pk = $this->_related_model_primary_key(get_class($item));
            if(!is_object($item)):
                throw new TypeError(sprintf("add() expects an array of objects"));
            endif;

            // get the related model name to save
            if(!empty($related_model) && $related_model!==get_class($item)):
                throw new TypeError(
                    sprintf("Multiple types provided, add() expects only one type per call, see documentation"));
            endif;

            //  set only if its empty
            if(empty($related_model)):
                $related_model = get_class($item);
            endif;

            // get id of related model
            if(is_object($item) && isset($item->{$pk})):
                $related_ids[] = $item->{$pk};
            endif;

        }

        // save related models many to many

        $this->_savem2m($related_model, $related_ids);

    }

    /**
     * Sorts the results of the Queryset in either:
     *
     * - ASC -- Ascending order
     * - DESC -- Descending order
     * - RANDOM -- Random order
     *
     * <h4>USAGE<h4>
     * <pre><code>$users = $this->user_model->all()->order_by(['id'=>'DESC', 'username'=>'ASC']);
     * foreach ($users as $u) {
     *     echo $u->id;
     * }</code></pre>
     *
     * @param array $criteria the criteria to use to order the objects;
     * @return $this
     * @throws OrmExceptions
     */
    public function order_by($criteria=[]){

        if($this->_evaluated):
            throw new OrmExceptions('order_by() cannot work on a Queryset that has been evaluated');
        endif;

        if(!is_array($criteria)):
            throw new OrmExceptions('order_by() expects and array');
        endif;

        foreach ($criteria as $field=>$direction) {

            $direction = strtoupper($direction);

            if(!in_array($direction, ['ASC', 'DESC', 'RANDOM'])):
                throw new OrmExceptions(
                    sprintf('order_by() expects either ASC, DESC, RANDOM as ordering direction, but got %s', $direction));
            endif;

            $this->_database->order_by($field, $direction);
        }



        return $this;

    }

    /**
     * Limits the Queryset
     *
     * <h4>USAGE</h4>
     *
     * To get first 10 permissions
     * <pre><code>$this->permission_model->all()->limit(10)</code></pre>
     *
     * To get 10 permissions that come after the 5th permission
     * <pre><code>$this->permission_model->all()->limit(10, 5)</code></pre>
     *
     * @param int $size the number of objects to return
     * @param int $start where to begin reading from by default it starts at 0
     * @return $this
     * @throws OrmExceptions
     */
    public function limit($size, $start=0){
        if(!is_numeric($size)):
            throw new OrmExceptions('limit() Expects size to be a numeric value');
        endif;

        if(!is_numeric($start)):
            throw new OrmExceptions('limit() Expects start to be a numeric value');
        endif;

        $this->_database->limit($size, $start);

        return $this;
    }


    /**
     * Deletes records in the database.
     *
     * If no arguments are passed the method deletes all the records in the database table
     *
     * if arguments are passed it deletes using the criteria chosen.
     *
     * <h4><strong>! Important</strong></h4> This method cannot be chained.
     *
     * The following will through an exception
     *
     * <pre><code>$this->role->all()->delete()</code></pre>
     *
     * <h4>USAGE</h4>
     *
     * Deleting everything
     *
     * <pre><code>$this->role->delete()</code></pre>
     *
     * Deleting all the records with the first name john
     *
     * <pre><code>$this->user_model->delete(['username__startswith'=>'john'])</code></pre>
     *
     * @param array $criteria
     * @return $this
     * @throws OrmExceptions
     */
    public function delete($criteria = []){

        if($this->_table_set===TRUE || $this->_evaluated===TRUE){
            throw new OrmExceptions(
                sprintf("delete() can not be used in a Queryset chain"));
        }

        if(!empty($criteria) && !is_array($criteria)):
            throw new OrmExceptions('delete() Expects an array of conditions to limit delete');
        endif;

        // delete records
        if(!empty($criteria)):
            $this->__where_clause(get_class($this->_context), $criteria);
            $this->_database->delete($this->_context->table_name());
        endif;

        if(empty($criteria)):
            $this->_database->empty_table($this->_context->table_name());
        endif;

        return $this;
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
     * Does the actual hit to the database either to fetch, edit, Add, delete
     * @internal
     * @return mixed
     */
    protected function _evaluate(){

        $query = $this->_database->get();

        return $query->{$this->invoke_method}();
    }

    /**
     * Returns the results from the database already cast into apropriate model
     * @internal
     * @return array|bool|object
     */
    protected function _eval_queryset(){

        if(empty($this->_evaluated)):

            $result = $this->_evaluate();
            $this->_evaluated = TRUE;

            $this->_query_result = $this->_cast_to_model($result);
        endif;

        // load eager models if any
        $this->_eager_loader();
        return $this->_query_result;
    }

    /**
     * @internal
     * @param $model_name
     * @return mixed
     */
    public function _clone_model($model_name){

        if(is_object($model_name)){
           $clone = clone $model_name;
        }

        if(is_string($model_name) && !isset($this->_context->{$model_name})){
            $model_name = strtolower($model_name);

            $this->_context->load->model($model_name);
            $clone = clone $this->_context->{$model_name};
        }
//        echo "<pre>";print_r($this->_context);echo "</pre>";
        return $clone;
    }


    /**
     * Coverts the database result into the Class representing a database table
     * @param $fetch_result results from a database fetch
     * @param null $model_name Class to convert the database result into
     * @internal
     * @return array
     */
    protected function _cast_to_model($fetch_result, $model_name=NULL){
        // get related data
        if($model_name==NULL):
            $model_name = $this->_context;
        else:
            $this->_load_related_model($model_name);
            $model_name = $this->_context->{$model_name};
        endif;

        $result = $fetch_result;

        // if result is array
        if(is_array($fetch_result)):

            $result = array();
            // get data from the table rows
            foreach ($fetch_result as $row_obj) {
                $class_instance = $this->_clone_model($model_name);
                // ToDo its doing multiple sql calls because we are getting the meta data of the table with each initialization of an model object
                foreach ($row_obj as $column=>$value) {

                    $class_instance->{$column} = $value;

                }
                array_push($result, $class_instance);
            }


        endif;

        // in-cases only an object was returned e.g using $this->db->row()
        if(is_object($fetch_result)):
            // create A new model instance for the row
            $class_instance =$this->_clone_model($model_name);
            foreach ($fetch_result as $column=>$value):
                $class_instance->{$column} = $value;
            endforeach;

            $result = $class_instance;
        endif;


        return $result;
    }


    /**
     * Called only after evaluation of a first Queryset, it performs a second Query for the requsted eager load model
     * <pre><code>$this->role->with('permission_model');
     * SELECT `betacom_role`.*
     * FROM `betacom_user`
     * JOIN `betacom_role` ON `betacom_role`.`id`=`betacom_user_role`.`role_id`
     * WHERE `betacom_user`.`id` in (2,3)</code></pre>
     * @internal
     */
    public function _eager_loader(){

        // check if eager loading is enabled
        if(!$this->_eager_load):
            return $this;
        endif;


        // eager load values, based on current results
        // remember eager loading takes two sql queries, the primary one
        // and the eager loading one.
        $primary_result_ids = array();

        // if is array
        if(is_array($this->_query_result)){
            foreach ($this->_query_result as $row) {
                $pk = $this->_related_model_primary_key(get_class($row));
                $primary_result_ids[] = $row->{$pk};
            }

        }

        // if primary result was an object
        if(is_object($this->_query_result)){
            // get primary key
            $pk = $this->_related_model_primary_key(get_class($this->_query_result));
            $primary_result_ids[] = $this->_query_result->{$pk};
        }

        // if not foreign key ids exist just exist
        if(count($primary_result_ids)<=0){
            return $this;
        }

        // name of the model in the primary result
        $primary_result_table_name = $this->_current_table_short_name();


        $primary_results = $this->_query_result;
        if(is_object($primary_results)){
            $primary_results = array($primary_results);
        }

        // loop through all the models we area eager loading
        foreach ($this->_models_to_eager_load as $eager_load_model_name) {

            // load the model
            $this->_load_related_model($eager_load_model_name);


            // get the table with the foreign key
            if($this->_search_foreignkey($eager_load_model_name)):

                // create a new Quuerset, based on the model to eager load
                $q = new Queryset($this->_context->{$eager_load_model_name});

                // perform a where in on the eager model using the primary result ids
                $current_pk = $this->_current_model_primary_key();

                $args = array(
                    get_class($this->_context)."::".$current_pk."__in" => $primary_result_ids
                );

                $eager_models = $q->distinct()->filter($args);

                // if primary result was an array
                // mount the eagerly loaded data on the primary results
                if(is_array($primary_results)):

                    foreach ($primary_results as $primary_result):
                        // creates  the eagerly loaded data class variable
                        if(!property_exists($primary_result, $eager_load_model_name."s")):
                            $primary_result->{$eager_load_model_name."s"} = array();
                        endif;

                        // works for one to many
                        foreach ($eager_models as $model_eager) :

                            if($model_eager->{$primary_result_table_name.'_'.$current_pk}===$primary_result->{$current_pk}):
                                $primary_result->{$eager_load_model_name."s"}[]=$model_eager;
                            endif;

                        endforeach;

                    endforeach;

                endif;
            else:
                // Many to Many Eager Loading
                $join_table = $this->_get_join_table($eager_load_model_name);
                $current_short_name = $this->_current_table_short_name();
                $current_pk = $this->_current_model_primary_key();

                $select = $this->_context->{$eager_load_model_name}->table_name().".* ,$join_table.$current_short_name"."_".$current_pk;
                $this->_database->select($select);
                $this->_database->from($this->_context->table_name());
                $this->_m2m_join($eager_load_model_name);
                $this->_database->where_in($this->_context->table_name().'.'.$current_pk, $primary_result_ids);

                $query = $this->_database->get();

                $result_cast = $this->_cast_to_model($query->result(), $eager_load_model_name);


                // if primary result was an array
                // mount the eagerly loaded data on the primary results
                if(is_array($primary_results)):

                    foreach ($primary_results as $primary_result):
                        // creates  the eagerly loaded data class variable
                        if(!property_exists($primary_result, $eager_load_model_name."s")):
                            $primary_result->{$eager_load_model_name."s"} = array();
                        endif;

                        // works for one to many
                        foreach ($result_cast as $model_eager) :

                            if($model_eager->{$primary_result_table_name.'_'.$current_pk}===$primary_result->{$current_pk}):
                                $primary_result->{$eager_load_model_name."s"}[]=$model_eager;
                            endif;

                        endforeach;

                    endforeach;

                endif;


            endif;


        }

        return $this;
    }

    /**
     * Creates the different types of where clause based on looksup provided in the condition e.g ['name__exact'=>"john"]
     * @internal
     * @param string $model_name
     * @param $conditions
     * @throws OrmExceptions
     */
    public function __where_clause($model_name=NULL, $conditions){

        // create where clause from the args
        foreach ($conditions as $key=>$value) {
            $lookup = NULL;
            // check which where clause to use
            if(preg_match("/__/", $key)):
                $options = preg_split("/__/", $key);
                $key = $options[0];
                $lookup = strtolower($options[1]);
            endif;

            $model_name = strtolower($model_name);

            // append table name to key
            if(!empty($model_name)):
                $key = $this->_context->{$model_name}->table_name().".$key";
            endif;

            switch($lookup):
                case NULL:
                    $this->_database->where($key, $value);
                    break;
                case 'in':
                    $this->_database->where_in($key, $value);
                    break;
                case 'gt':
                    $this->_database->where("$key >", $value);
                    break;
                case 'lt':
                    $this->_database->where("$key <", $value);
                    break;
                case 'gte':
                    $this->_database->where("$key >=", $value);
                    break;
                case 'lte':
                    $this->_database->where("$key <=", $value);
                    break;
                case 'contains':
                    $this->_database->like($key, $value, 'both');
                    break;
                case 'startswith':
                    $this->_database->like($key, $value, 'after');
                    break;
                case 'endswith':
                    $this->_database->like($key, $value, 'before');
                    break;
                case 'between':
                    if(!is_array($value) || (is_array($value) && count($value)!=2)){
                        throw new OrmExceptions(
                            sprintf("filter() usin between expected value to be an array, with two values only"));
                    }
                    $this->_database->where("$key BETWEEN $value[0] AND $value[1] ");
                    break;
                case 'isnull':
                    $this->_database->where($key, $value);
                    break;
                case 'not':
                    $this->_database->where("$key !=", $value);
                    break;
                case 'notin':
                    $this->_database->where_not_in($key, $value);
                    break;
            endswitch;



        }

    }

    /**
     * Create and Many To Many Join and adds the where clause
     * @internal
     * @param $related_model_name
     * @param $where_condition
     * @return $this
     */
    public function _m2m_join($related_model_name, $where_condition=NULL, $foreign_key=NULL){
        // Todo work on foreign key
        $current_table_short_name = $this->_current_table_short_name();

        // current primary key
        $current_pk = $this->_current_model_primary_key();

        //related primary key
        $related_pk = $this->_related_model_primary_key($related_model_name);

        // load related model if its not loaded
        if(!isset($this->_context->{$related_model_name})){
            $this->_context->load->model($related_model_name);
        }

        $related_table_short_name = $this->_related_table_short_name($related_model_name);


        // many to many join table
        $join_table_name = $this->_get_join_table($related_model_name);


        // many to many
        if(!empty($join_table_name)):

            // join with the join table
            $this->_database->join($join_table_name,
                $this->_context->table_name().".".$current_pk."=".$join_table_name.".".$current_table_short_name."_".$current_pk);

            // join the related table
            $this->_database->join($this->_context->{$related_model_name}->table_name(),
                $this->_context->{$related_model_name}->table_name().".".$related_pk."=".$join_table_name.".".$related_table_short_name."_".$related_pk);

            if(!empty($where_condition)):
                $this->__where_clause(get_class($this->_context->{$related_model_name}), $where_condition);
            endif;

        endif;


        return $this;
    }


    /**
     * Create a Many To One / One To Many join and create add the where clause
     * @internal
     * @param $related_model_name
     * @param $where_condition
     * @param null $foreign_key
     */
    protected function _m2o_join($related_model_name, $where_condition=NULL, $foreign_key=NULL){
        $current_pk = $this->_current_model_primary_key();
        $related_pk = $this->_related_model_primary_key($related_model_name);

        $related_table_name = $this->_context->{$related_model_name}->table_name();

        $related_table_short_name = $this->_related_table_short_name($related_model_name);
        $current_table_short_name = $this->_current_table_short_name();

        // foreign key table
        $foreign_key = (isset($foreign_key))?$foreign_key: NULL;

        $foreign_key_table = $this->_search_foreignkey($related_model_name, $foreign_key);


        // ================================= ONE TO MANY ===================================


        if($foreign_key_table===$this->_context->table_name()):
            // if someone is trying to filter the Many side
            // e.g if some is trying to get all products that fall under a certain category
            // in a relationship where the category is the one side and the product is the many side
            // $this->product_model->filter(array('category_model::id'=>5)));
            $on = $foreign_key_table.".".$related_table_short_name."_".$related_pk."=".$related_table_name.".".$related_pk;
            $this->_database->join($related_table_name, $on);
        else:

            // ========================================== MANY TO ONE =============================


            // if someone is trying to filter the One side in a one to many relationship
            // e.g if some is trying to get the category a product belongs to
            // in a relationship where the category is the one side and the product is the many side
            // $this->category_model->filter(array('product_model::1d'=>5)));
            $on = $this->_context->table_name().".".$current_pk."=".$related_table_name.".".$current_table_short_name."_".$current_pk;
            $this->_database->join($this->_context->{$related_model_name}->table_name(), $on);
        endif;

        if(!empty($where_condition)):
            $this->__where_clause($related_model_name, $where_condition);
        endif;


    }

    /**
     * Create a self referencing fetch and create add the where clause
     * todo
     * @internal
     * @param $related_model_name
     * @param null $where_condition
     * @param null $foreign_key
     * @return $this
     */
    public function _self_reference($related_model_name, $where_condition=NULL, $foreign_key=NULL){
        $related_model_short_name = $this->_related_table_short_name($related_model_name);
        return $this;
    }

    /**
     * Returns the related model.
     * @internal
     * @param $model_name
     * @return $this
     * @throws OrmExceptions
     */
    public function _related($model_name){
        $current_pk = $this->_current_model_primary_key();
        $related_pk = $this->_related_model_primary_key($model_name);
        // check that we have the parent model to get its related data.
        if(!isset($this->_context->{$current_pk})):
            throw new OrmExceptions(sprintf("Trying to get related data of nothing."));
        endif;

        $related_model_name = NULL;
        $foreign_key = NULL;

        if(preg_match("/::/", $model_name)):
            $related_model_name = preg_split("/::/", $model_name)[0];
            $foreign_key = preg_split("/::/", $model_name)[1];
        else:
            $related_model_name = $model_name;
        endif;

        // related model instance
        $this->_load_related_model($related_model_name);

        $related_model_short_name = $this->_related_table_short_name($related_model_name);

        // if foreign key has not been set, try using the default one by combine the model name and the id column
        if(empty($foreign_key)){
            $foreign_key = $related_model_short_name."_".$related_pk;
        }

        $class_instance = $this->_context->{$related_model_name};

        $this->_database->select($class_instance->table_name().".*");

        if(isset($this->_context->{$current_pk}) && !$this->_table_set){
            $this->invoke_method = 'result';
            $this->_database->from($this->_context->table_name());
            $this->_database->where(array($this->_context->table_name().'.'.$current_pk=>$this->_context->{$current_pk}));
        }


        $one2one = (strtolower(get_class($this->_context))===strtolower($related_model_name))? TRUE : FALSE;

        // ************************* Try Recursive  **************************
        if($one2one):
            $this->_self_reference($related_model_name, NULL, $foreign_key);
        endif;

        // ************************* Try Many To One and One To Many **************************

        // foreign key table
        $foreign_key_table = $this->_search_foreignkey($related_model_name, $foreign_key);
        if($foreign_key_table && !$one2one):
            // try one to many and Many to One
            $this->_m2o_join($related_model_name, NULL, $foreign_key);
        endif;


        // ************************* Try Many To Many **************************

        if($foreign_key_table==FALSE && !$one2one):
            $this->_m2m_join($related_model_name, NULL, $foreign_key);
        endif;

        // reset context to related model
        $this->_context = $class_instance;

        return $this;
    }

    /**
     * Returns the total rows in the database
     * @internal
     * @return mixed
     */
    protected function _count($query){
        return $query->num_rows();
    }

    /**
     * Gets the join table if its many to many relationship between model tables
     * @param $model
     * @param $related_model_name
     * @internal
     * @return string
     */
    protected function _get_join_table($related_model_name){

        $current_table_name=str_replace($this->_context->config->item('db_table_prefix'), '',$this->_context->table_name());

        // get related table
        $join_table = $this->_context->{$related_model_name}->table_name().'_'.$current_table_name;

        if(!$this->_database->table_exists($join_table)):

            $join_table = $this->_context->table_name().'_'.$this->_related_table_short_name($related_model_name);
        endif;

        return $join_table;
    }

    /**
     * Gets the table with foreign key i.e the owning side of a one to many relationship.
     * @param $related_model_name
     * @internal
     * @return bool|string
     */
    protected function _search_foreignkey($related_model_name, $foreign_key=NULL){

        $present = FALSE;

        // short name without the prefix e.g. user instead of betacom_user
        $related_table_name_short= $this->_related_table_short_name($related_model_name);

        $current_table_id=$this->_current_table_short_name().'_'.$this->_current_model_primary_key();

        // search foreign key of current table on the related table
        if($this->_database->field_exists($current_table_id, $this->_context->{$related_model_name}->table_name())){
            $present = $this->_context->{$related_model_name}->table_name();
        }

        $related_model_name_id = $related_table_name_short.'_'.$this->_related_model_primary_key($related_model_name);


        if($present==FALSE && $foreign_key){

            // search foreign key of current table on the related table
            if($this->_database->field_exists($foreign_key, $this->_context->{$related_model_name}->table_name())){
                $present = $this->_context->{$related_model_name}->table_name();
            }

            // search foreign key of the related table on current table
            if($this->_database->field_exists($foreign_key, $this->_context->table_name())){
                $present = $this->_context->table_name();
            }
        }

        // ToDo search in related table
        // search foreign key of the related table on current model
        if($present==FALSE && in_array($related_model_name_id, $this->_context->fields_names())){
            $present = $this->_context->table_name();
        }


        return $present;
    }

    /**
     * Returns the database table of a model without the database prefix
     * @param $model_name
     * @internal
     * @return mixed
     */
    protected function _related_table_short_name($model_name){
        return str_replace($this->_context->config->item('db_table_prefix'), '',$this->_context->{$model_name}->table_name());
    }

    /**
     * @internal
     * @return mixed
     */
    protected function _current_table_short_name(){

        return str_replace($this->_context->config->item('db_table_prefix'), '',$this->_context->table_name());
    }

    /**
     * Display the sql statement to be executed
     * @internal
     */
    protected function _dump_sql(){
        echo '******************* Running the sql statement *******************************<br>';
        echo $this->_database->get_compiled_select(NULL, FALSE);
        echo '<br>************************************************************************<br>';
    }

    /**
     * @internal
     * @param $related_model_name
     */
    public function _load_related_model($related_model_name){
        if(!isset($this->_context->{$related_model_name})):
            $this->_context->load->model($related_model_name);
        endif;
    }


    /**
     * Does the actual saving
     * @internal
     * @return mixed
     */
    public function _save(){
        // set the slug if it is not set
        if((property_exists($this->_context, 'slug') &&
                property_exists($this->_context, 'name') &&
                isset($this->_context->name)) &&
            (!isset($this->_context->slug) || strlen($this->_context->slug)==0) ){

            $this->_context->slug = url_title($this->_context->name, 'dash', TRUE);

        }
        // actual saving
        $pk = $this->_current_model_primary_key();
        if(isset($this->_context->{$pk}) && !empty($this->_context->{$pk})):
            $this->_database->where($pk, $this->_context->{$pk});
            $this->_database->update($this->_context->table_name(), $this->_context);
        else:
            $this->_database->insert($this->_context->table_name(), $this->_context);
        endif;

        // get saved model
        return $this->get($this->_database->insert_id());
    }



    /**
     * Save Many to Many relations
     * @param $model
     * @param $values -- the values to save can be id or models of related model(s)
     * @internal
     * @return $this
     */
    protected function _savem2m($related_model_name, $values){
        log_message('INFO', '----------------------------M2M');

        $related_model_name = strtolower($related_model_name);
        // LOAD THE related model
        if(!class_exists($related_model_name, FALSE)){
            $this->_context->load->model($related_model_name);
        }

        $related_table_short_name = $this->_related_table_short_name($related_model_name);

        $join_table = $this->_get_join_table($related_model_name);

        $current_table_short_name = $this->_current_table_short_name();

        $current_pk = $this->_current_model_primary_key();
        $related_pk = $this->_related_model_primary_key($related_model_name);
        if(!empty($values)):
            $relation_save = array();
            foreach ($values as $id):
                $args = array(
                    $current_table_short_name.'_'.$current_pk=> $this->_context->{$current_pk},
                    $related_table_short_name.'_'.$related_pk=> $id
                );
                $relation_save[]= $args;
            endforeach;
            // before we do any saves remove all related data from earlier, this is incase we are doing an update
            $this->_database->delete($join_table, [$current_table_short_name.'_'.$current_pk=> $this->_context->{$current_pk}]);

            // save the relation
            $this->_database->insert_batch($join_table, $relation_save);
        endif;

        return $this->_context;
    }

    /**
     * Get primary key of related model
     * @ignore
     * @param $model_name
     * @return null
     */
    public function _related_model_primary_key($model_name){
        $this->_load_related_model($model_name);
        $table_columns = $this->_context->{$model_name}->meta();

        $primary_key_column = NULL;
        foreach ($table_columns as $col) :
            if($col->primary_key):
                $primary_key_column = $col->name;
            endif;
        endforeach;

        return $primary_key_column;
    }

    /**
     * Get primary key of the current model
     * @ignore
     * @return null
     */
    public function _current_model_primary_key(){
        $table_columns = $this->_context->meta();

        $primary_key_column = NULL;
        foreach ($table_columns as $col) :
            if($col->primary_key):
                $primary_key_column = $col->name;
            endif;
        endforeach;

        return $primary_key_column;
    }

    /**
     * @ignore
     */
    public function __destruct(){
        $name = $this->_context->table_name();
        unset($this->_context);
        unset($this->_database);
        unset($this->_query_result);
        unset($this->invoke_method);
        unset($this);
        log_message('INFO', sprintf('**************** Queryset for model %s Destroyed ****************', $name));

    }



}
