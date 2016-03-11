<?php
/**
 * ORM QuerySet implementation.
 */


/**
 *
 */
namespace powerorm\queries;


use HasMany;
use HasOne;
use powerorm\exceptions\MultipleObjectsReturned;
use powerorm\exceptions\ObjectDoesNotExist;
use powerorm\exceptions\OrmExceptions;
use powerorm\exceptions\ValueError;
use powerorm\model\ProxyModel;

defined('BASEPATH') OR exit('No direct script access allowed');

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
 * <pre><code>$articles = $this->with(['author' =>'author'])->article_model->all()
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
 * @package powerorm\queries
 * @since   1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Queryset implements \IteratorAggregate, \Countable
{

    /**
     * @var string Identifies if the Queryset returns more than one row.
     * @internal
     */
    protected $_output_type = 'result';

    /**
     * @internal
     * @var object Holds the model instance the Queryset acts on.
     */
    protected $_active_model_object;

    /**
     * @var object Holds a copy of the database connection the current queryset will use
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
    protected $_results_cache;


    /**
     * @var bool This is usually true if the methods are being chained in the current instance of the Queryset.
     * @internal
     */
    protected $_table_set = FALSE;

    /**
     * Sets if the queryset requires to do an eager load
     * @internal
     * @var bool
     */
    protected $_eager_load = FALSE;

    /**
     * Values for fields to eager load
     * @var array
     * @internal
     */
    protected $_eager_fields_values = [];

    /**
     * The Queryset to use to get the eager fetched results
     * @var Queryset
     * @internal
     */
    protected $_eager_fetched_results;

    /**
     * Holds the fields to eager load
     * @internal
     * @var array
     */
    protected $_fields_to_eager_load = array();

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
     * @param $model the model the Queryset works on.
     */
    public function __construct($model)
    {

        $this->_active_model_object = $model;

        // setup database
        $this->_database();

    }

    /**
     * Evaluate the Queryset when existence of a property in the Queryset Result is tested. using isset().
     * @param $property
     * @ignore
     * @return bool
     */
    public function __isset($property)
    {
        $result = $this->_eval_queryset();
        return property_exists($result, $property);

    }

    /**
     * Evaluate the Queryset when a property is accessed from the Model Instance.
     * @param $property
     * @ignore
     * @return mixed
     */
    public function __get($property)
    {

        $value = NULL;
        // check if queryset is already evaluated
        if (!$this->_evaluated):
            $this->_eval_queryset();
        endif;

        return $this->_results_cache->{$property};
    }

    /**
     * Evaluates Queryset when the Queryset Result is used like a string e.g. using Queryset Result in echo statement.
     * @ignore
     * @return string
     */
    public function __toString()
    {

        $this->_eval_queryset();
        return sprintf('%s', $this->_results_cache);
    }

    public function __clone()
    {
        // make a copy of the database
        $this->_database = $this->_shallow_copy($this->_database);
    }

    /**
     * Evaluates the Queryset when Queryset Result is used in a count() or sizeof().
     * @ignore
     * @return mixed
     */
    public function count()
    {
        $this->_output_type = 'num_rows';
        return $this->_eval_queryset();
    }

    /**
     * Evaluates the Queryset when Queryset Result is used in a foreach.
     * @ignore
     * @return ArrayIterator
     */
    public function getIterator()
    {
        $result = new \ArrayIterator($this->_eval_queryset());
        return $result;
    }

    // ToDo serialization , to json too
//    protected function __sleep(){
//        $this->_eval_queryset();
//        return array('_results_cache');
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
     * @return  Queryset
     * @throws MultipleObjectsReturned
     * @throws ObjectDoesNotExist
     */
    public function get($values)
    {
        $this->_get($values);

        // we are cloning the queryset because, we want the main queryset to
        // return what is actually expected instead of the number of rows
        // ensure only one row contains the passed in id
        $temp_queryset = $this->_shallow_copy($this);
        $no_of_records = $temp_queryset->size();

        if ($no_of_records > 1):
            throw new MultipleObjectsReturned(
                sprintf('get() returned more than one %1$s -- it found %2$s!',
                    $this->_model_name($this->_active_model_object), $no_of_records));
        endif;

        if ($no_of_records == 0):
            throw new ObjectDoesNotExist(sprintf('`%s` matching query does not exist.!',
                $this->_model_name($this->_active_model_object)));
        endif;


        return $this->_eval_queryset();
    }

    public function filter($conditions){
        $this->_filter($conditions);
        return $this;
    }

    /**
     * Fetches all the records in the database table represented by the Model Instance.
     * USAGE:
     *
     * To get All user in the database
     * <pre><code>$this->User_model->all()</code></pre>
     *
     * @return Queryset
     */
    public function all()
    {
        $this->_select($this->_active_model_object);
        $this->_from($this->_active_model_object->get_table_name());

        return $this;
    }

    public function exclude($conditions)
    {
        $this->_validate_condition($conditions);

        $conds = [];
        foreach ($conditions as $key => $value) :
            $key = sprintf("%s__not", $key);
            $conds[$key] = $value;
        endforeach;

        $this->filter($conds);
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
     * @throws ValueError
     */
    public function order_by($criteria = [])
    {

        $this->_validate_condition($criteria);

        foreach ($criteria as $field => $direction) :

            $direction = strtoupper($direction);

            if (!in_array($direction, ['ASC', 'DESC', 'RANDOM'])):
                throw new ValueError(
                    sprintf('order_by() expects either ASC, DESC, RANDOM as ordering direction, but got %s', $direction));
            endif;

            $this->_database->order_by($field, $direction);
        endforeach;


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
     * @throws ValueError
     */
    public function limit($size, $start = 0)
    {
        if (!is_numeric($size)):
            throw new ValueError('limit() Expects size to be a numeric value');
        endif;

        if (!is_numeric($start)):
            throw new ValueError('limit() Expects start to be a numeric value');
        endif;

        $this->_database->limit($size, $start);

        return $this;
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
     */
    public function dump_sql()
    {
        $this->_dump_sql();
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
    public function value()
    {
        $this->_eval_queryset();
        return $this->_results_cache;

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
    public function size($conditions = array())
    {
        $this->filter($conditions);
        $this->_output_type = 'num_rows';
        return $this->count();
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
     * Does the actual saving
     * @ignore
     * @return object
     * @throws MultipleObjectsReturned
     * @throws ObjectDoesNotExist
     * @throws OrmExceptions
     */
    public function _save()
    {

        $pk = $this->_model_pk_field($this->_active_model_object);

        // save related models
        // got through the fields trying to find field with objects as value
        foreach ($this->_active_model_object->meta->relations_fields as $field) {
            $field_value = $this->_active_model_object->{$field};
            // assumes current model is the owning side being saved that is it has the foreign key
            if (is_object($field_value) && $field_value instanceof Queryset):
                // evaluate the Queryset
                $field_value = $field_value->value();
            endif;

            if (is_object($field_value)):
                $related_pk = $this->_model_pk_field($field_value);

                // Ensure that a model instance without a PK hasn't been assigned to
                // a ForeignKey or OneToOneField on this model. If the field is
                // accepts null, allowing the save() would result in silent data loss of the unsaved model..
                if (empty($field_value->{$related_pk})):
                    throw new OrmExceptions(
                        sprintf("Saving model failed, The value for `%s` field is an unsaved model", $field));
                endif;

                $this->_active_model_object->{$field} = $field_value->{$related_pk};
            endif;
        }
        $model_rep = $this->_to_array($this->_active_model_object);

        // determine if its an update or a new save
        if (isset($this->_active_model_object->{$pk}) && !empty($this->_active_model_object->{$pk})):
            $pk_value = $this->_active_model_object->{$pk};
            $this->_database->where($pk, $pk_value);
            $this->_database->update($this->_active_model_object->get_table_name(), $model_rep);
        else:
            $this->_database->insert($this->_active_model_object->get_table_name(), $model_rep);
            $pk_value = $this->_database->insert_id();
        endif;

        // get saved model
        return $this->get($pk_value);
    }

    public function _to_array($model){
        $rep = [];

        foreach ($model->meta->fields as $field) :
            $rep[$field->db_column_name()] = $this->_active_model_object->{$field->name};
        endforeach;

        return $rep;

    }

    /**
     * Save Many to Many relations
     * @param $values -- the values to save can be id or models of related model(s)
     * @ignore
     * @return $this
     */
    public function _m2m_save($related_model_objects)
    {
        foreach ($related_model_objects as $related_model) :
            $act_name = $this->_model_name($this->_active_model_object);
            $rel_name = $this->_model_name($related_model);
            $proxy = $this->_m2m_through_model($this->_active_model_object, $related_model);
            $proxy->{$act_name} = $this->_active_model_object->{$this->_model_pk_field($this->_active_model_object)};
            $proxy->{$rel_name} = $related_model->{$this->_model_pk_field($related_model)};
            $proxy->save();
        endforeach;

        return $this->_active_model_object;
    }

    /**
     * Eager loading, this will solve the N+1 ISSUE
     *
     * <h4>USAGE:</h4>
     *
     * To Eager load related items of a single item
     *
     * Provide the name on which the eagerly loaded results will be accessed from in the example below all products of
     * category with the `id = 1` will be accessed from the variable `products` .
     *
     * <pre><code>$category = $this->category_model->with(["products"=>"product_model"])->get(array("id"=>1));
     *
     * $category->products; // the products are now accessible like so
     *
     * foreach($rr->products as $product){
     *          echo $product->name;
     * }</code></pre>
     *
     * To Eager Many To Many Relationship
     *
     * <pre><code>$roles = $this->role->with(['permissions'=>'permission_model'])->filter(array('user_model::id'=>1));
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
     * <pre><code>$usr= $this->user_model->with(["products"=>"product_model", 'roles'=>"role_model"])->get(["id"=>1]);
     *
     *
     * </code></pre>
     *
     * @param string|array $eager_model_name the name/names of models to eager load.
     * @return Queryset
     */
    public function _eager_load($conditions)
    {
        $this->_eager_load = TRUE;

//        $this->_validate_condition($conditions);

        if (is_array($conditions)):
            $eager_models = [];
            foreach ($conditions as $name) :
                $eager_models [] = $this->_stable_name($name);
            endforeach;

            $this->_fields_to_eager_load = $eager_models;
        endif;
        return $this;
    }

    public function first(){
        $this->_output_type = 'first_row';
        return $this;
    }

    public function last(){
        $this->_output_type = 'last_row';
        return $this;
    }

    public function _get($values){
        $conditions = [];
        $this->_output_type = 'row';

        //ensure a value was passed in
        if (!isset($values)) {
            throw new ValueError(
                sprintf("Missing argument expected 'int or array' to be passed in to `get` method"));
        }

        // get id if value is not array
        if (is_numeric($values)):
            $pk = $this->_model_pk_field($this->_active_model_object);
            $conditions[$pk] = $values;
        endif;

        // get id if value is not array
        if (is_array($values)):
            $conditions = $values;
        endif;

        $this->_filter($conditions);

        return $this;
    }

    public function _filter($conditions)
    {
        $this->_validate_condition($conditions);

        // look if we have filter conditions that span relationships
        $split_conditions = $this->_split_conditions($conditions);

        // first handle the normal filter conditions
        $table_name = $this->_active_model_object->get_table_name();

        $this->_select($this->_active_model_object);
        $this->_from($table_name);

        if (!empty($split_conditions['normal_conditions'])):
            $this->_where_clause($this->_database, $table_name,
                $this->_prepare_where_conditions($split_conditions['normal_conditions']));
        endif;

        // lets handle there relationship filter
        if (!empty($split_conditions['relation_conditions'])):

            foreach ($split_conditions['relation_conditions'] as $model_name => $conditions) :
                $model_object = $this->_load_model($model_name);

                if($this->_is_m2o($this->_active_model_object, $model_object)):
                    $this->_m2o_join($this->_active_model_object, $model_object, $conditions);
                endif;

                if($this->_is_m2m($this->_active_model_object, $model_object)):
                    $proxy = $this->_m2m_through_model($this->_active_model_object, $model_object);
                    $this->_m2m_join($proxy, $this->_active_model_object, $model_object, $conditions);
                endif;
            endforeach;


        endif;

        return $this;
    }

    /**
     * Coverts the database result into the Class representing a database table
     * @param $fetch_result results from a database fetch
     * @param null $model_name Class to convert the database result into
     * @internal
     * @return array
     */
    protected function _cast_to_model($fetch_result, $target_model_obj = NULL)
    {

        if ($target_model_obj == NULL):
            $target_model_obj = $this->_active_model_object;
        endif;

        $eagerly_results = NULL;

        // in-case we don't go through the following condition, i.e casting of ther results to $targe model
        // this normally
        $result = $fetch_result;

        // if result is array
        if ($this->_output_type == 'result'):

            $result = [];
            // get data from the table rows
            foreach ($fetch_result as $row_obj) :
                $new_row = $this->_result_mapping($row_obj, $target_model_obj);
                $result[] = $new_row;
            endforeach;
            // check if we need to eagerly load any models
            $result = $this->_eager_load_relations($result);
        endif;

        // in-cases only an object was returned e.g using $this->db->row()
        if (in_array($this->_output_type, ['row', 'first_row', 'last_row'])):
            $this->_eager_load = FALSE;
            $result = $this->_result_mapping($fetch_result, $target_model_obj);
        endif;


        return $result;
    }

    /**
     * Display the sql statement to be executed
     * @internal
     */
    protected function _dump_sql()
    {
        echo '******************* Running the sql statement *******************************<br>';
        echo $this->_database->get_compiled_select(NULL, FALSE);
        echo '<br>************************************************************************<br>';
    }

    /**
     * Converts a result object in the required class object
     * @internal
     * @param $result_object
     * @param $target_class
     * @return mixed
     */
    protected function _result_mapping($db_result_object, $target_model_obj)
    {

        $target_object = $this->_shallow_copy($target_model_obj);

        foreach ($target_object->meta->fields as $field_name => $field_object):


            if (property_exists($field_object, 'related_model') &&
                ($field_object->M2M || is_subclass_of($field_object, 'InverseRelation'))):

                $act_pk = $this->_model_pk_field($this->_active_model_object);
                $value = $db_result_object->{$act_pk};

            else:
                $column_name = $field_object->db_column_name();
                // column value in the database
                $value = $db_result_object->$column_name;
            endif;

            $this->_prepare_eager_values($field_name, $value);

            $value = $this->_map_relations($field_object, $value);
            // map it
            $target_object->{$field_name} = $value;

        endforeach;

        return $target_object;
    }

    public function _prepare_eager_values($field_name, $value){
        // set this values here to avoid having to loop over the results again get them when eager loading
        if ($this->_eager_load && in_array($field_name, $this->_fields_to_eager_load)):
            $this->_eager_fields_values[$field_name][] = $value;
        endif;
    }
    protected function _model_name($model_object)
    {
        return $this->_stable_name($model_object->meta->model_name);
    }

    protected function _stable_name($name)
    {
        return strtolower($name);
    }

    protected function _model_pk_field($model_obj)
    {
        return $this->_model_pk($model_obj)->name;
    }

    protected function _model_pk($model_obj)
    {
        return $model_obj->meta->primary_key;
    }

    protected function _map_relations($field_object, $db_value)
    {
        $value = $db_value;

        if (is_subclass_of($field_object, 'RelatedField')):

            // owning side
            // if this is a relationship and its is not eagerly loaded, return a Queryset for the relation
            if (!$this->_eager_load && !empty($db_value)):
                $rel_pk = $this->_model_pk_field($field_object->related_model);

                $act_name = $this->_model_name($this->_active_model_object);
                $act_pk = $this->_model_pk_field($this->_active_model_object);

                if($field_object->M2M):
                    return $field_object->related_model->filter(["$act_name::$act_pk"=>$db_value]);
                endif;

                if($field_object instanceof HasMany):
                    return $field_object->related_model->filter(["$act_name::$act_pk"=>$db_value]);
                endif;

                if($field_object instanceof HasOne):
                    return $field_object->related_model->filter(["$act_name::$act_pk"=>$db_value])->first();
                endif;

                if(!$field_object->M2M && $this->_is_owning($field_object->related_model)):
                    return $field_object->related_model->filter([$rel_pk => $db_value])->first();
                endif;
            endif;
        endif;

        return $value;

    }

    /**
     * @param array $main_result
     * @return array|null
     */
    protected function _eager_load_relations($main_result)
    {
        $result = $main_result;

        if (empty($this->_fields_to_eager_load)):
            return $result;
        endif;

        // ensure model requested for eager loading are actually related to the current model
        $this->_related_check();
        foreach ($this->_fields_to_eager_load as $field_name) :

            // since one eager model can be used by the current results multiple times
            // we just make sure we dont have repeatations of the the same eager model key
            $field_values = array_unique($this->_eager_fields_values[$field_name]);

            // fetch the eager models with the values above
            $eager_model_object = $this->_active_model_object->meta->relations_fields[$field_name]->related_model;

            // the pk for the eager model
            $pk_field_name = $this->_model_pk_field($eager_model_object);

            // this is queryset
            $this->_eager_fetched_results = $eager_model_object->filter([$pk_field_name . "__in" => $field_values]);


            $result = $this->_m2o_eager_loader($field_name, $main_result);
        endforeach;


        return $result;
    }

    protected function _related_check()
    {
        $relation_fields = array_keys($this->_active_model_object->meta->relations_fields);
        $not_relation_fields = array_diff($this->_fields_to_eager_load, $relation_fields);


        if (count($not_relation_fields) > 0):
            throw new OrmExceptions(sprintf('``%1$s` model has not relation to `%2$s` choices are [ %3$s ]',
                $this->_model_name($this->_active_model_object),
                implode(', ', $not_relation_fields),
                implode(', ', $relation_fields)));
        endif;

    }

    protected function _m2o_eager_loader($field_name, $main_results)
    {
        if (empty($this->_eager_fields_values)):
            return $main_results;
        endif;

        if (is_array($main_results)):
            // update values in the main result
            foreach ($main_results as $result) :
                $field_value = $result->{$field_name};
                $result->{$field_name} = $this->_locate_loaded_model($field_value);
            endforeach;
        endif;

        if (is_object($main_results)):
            $field_value = $main_results->{$field_name};
            $main_results->{$field_name} = $this->_locate_loaded_model($field_value);
        endif;

        return $main_results;
    }

    /**
     * Returns the results from the database already cast into apropriate model
     * @internal
     * @return array|bool|object
     */
    protected function _eval_queryset()
    {
        if (empty($this->_evaluated)):

            // evaluate main object
            $main_result = $this->_evaluate();
            $this->_evaluated = TRUE;

            $this->_results_cache = $this->_cast_to_model($main_result);
        endif;

        return $this->_results_cache;
    }

    /**
     * Does the actual hit to the database either to fetch, edit, Add, delete
     * @internal
     * @return mixed
     */
    protected function _evaluate()
    {
        return $this->_db_results($this->_database->get());
    }

    protected function _db_results($query)
    {

        if ($this->_output_type == 'row'):
            return $query->row();
        endif;

        if ($this->_output_type == 'result'):
            return $query->result();
        endif;

        if ($this->_output_type == 'num_rows'):
            return $query->num_rows();
        endif;

        if ($this->_output_type == 'first_row'):
            return $query->first_row();
        endif;

        if ($this->_output_type == 'last_row'):
            return $query->last_row();
        endif;
    }

    protected function _locate_loaded_model($field_value)
    {

        foreach ($this->_eager_fetched_results as $result) :
            $pk_name = $this->_model_pk_field($result);
            if ($result->{$pk_name} == $field_value):
                return $result;
            endif;
        endforeach;

        return $field_value;
    }

    protected function _validate_condition($conditions)
    {

        if (!is_array($conditions)) {
            throw new ValueError(sprintf("Arguments should be in array form"));
        }

        $this->_check_field_exist($conditions);

    }

    protected function _check_field_exist($conditions)
    {
        $fields = array_keys($this->_active_model_object->meta->fields);

        $split = $this->_split_conditions($conditions);

        foreach ($split['normal_conditions'] as $key => $value) :
            $key = $this->_field_from_condition($key);
            if (!property_exists($this->_active_model_object, $key)):
                throw new OrmExceptions(
                    sprintf('The field `%1$s does not exist on model  %2$s, the choices are %3$s`',
                        $key, $this->_model_name($this->_active_model_object), implode(',', $fields)));
            endif;
        endforeach;

        // ensure that there is an actual relationship between this model and the current one
//        foreach ($split['relation_conditions'] as $key => $value) :
//            foreach ($this->_active_model_object->meta->relations_fields as $r_field) :
//                if ($this->_model_name($this->$r_field->model_name) != $this->_stable_name($key)):
//                    throw new OrmExceptions(
//                        sprintf('The Model `%1$s does not have a relationship to the Model  %2$s', $key,
//                            $this->_model_name($this->_active_model_object), implode(',', $fields)));
//                endif;
//            endforeach;
//
//        endforeach;


    }

    protected function _split_conditions($conditions)
    {
        $_relation_conditions = [];
        $_normal_conditions = [];
        foreach ($conditions as $key => $value) :
            // look for relationship
            if (preg_match("/::/", $key)):
                $related_model_name = preg_split("/::/", $key)[0];
                $related_model_search_key = preg_split("/::/", $key)[1];

                $_relation_conditions[$related_model_name][$related_model_search_key] = $value;

            else:
                $_normal_conditions[$key] = $value;
            endif;
        endforeach;

        return ['normal_conditions' => $_normal_conditions, 'relation_conditions' => $_relation_conditions];
    }

    protected function _select($model_object)
    {
        $t_name = $model_object->get_table_name();
        $fields = [];

        foreach ($model_object->meta->fields as $field) :
            if (property_exists($field, 'M2M') && $field->M2M || $field instanceof \InverseRelation):
                continue;
            endif;
            $fields[] = $t_name.".".$field->db_column_name();
        endforeach;

        $fields = implode(',', $fields);

        $this->_database->select($fields);
    }

    protected function _from($table_name)
    {
        if (!$this->_is_chained()):
            $this->_table_set = TRUE;
            $this->_database->from($table_name);
        endif;
    }

    protected function _is_chained()
    {
        if ($this->_table_set):
            return TRUE;
        endif;
        return FALSE;
    }

    /**
     * Creates the different types of where clause based on looksup provided in the condition e.g ['name__exact'=>"john"]
     * @internal
     * @param string $model_name
     * @param $conditions
     */
    protected function _where_clause($database, $table_name, $conditions)
    {

        $where = new Where($database, $table_name);
        $where->clause($conditions);
    }

    protected function _prepare_where_conditions($conditions)
    {
        $ready_conditions = [];
        foreach ($conditions as $key => $value) :
            $field_name = $this->_field_from_condition($key);
            $new_key = $this->_active_model_object->meta->fields[$field_name]->db_column_name();

            if(!empty($this->_lookup_from_condition($key))):
                $key = $new_key.'__'.$this->_lookup_from_condition($key);
            else:
                $key = $new_key;
            endif;

            $ready_conditions[$key] = $value;
        endforeach;

        return $ready_conditions;
    }

    protected function _load_model($model_name)
    {
        $_ci =& get_instance();
        $model_name = strtolower($model_name);
        if (!isset($_ci->{$model_name})):
            $_ci->load->model($model_name);
        endif;
        return $_ci->{$model_name};
    }

    protected function _m2o_join($active_model, $candidate_model, $conditions)
    {
        $fk_info = $this->_find_fK_info($active_model, $candidate_model);


        // from Owning side lookup
        if ($this->_stable_name($fk_info['model_name']) == $this->_model_name($active_model)):

            $joined_field_name = $this->_model_pk($active_model);
            $joined_table_name = $candidate_model->get_table_name();
            $main_table = $active_model->get_table_name();
            $main_pk_name = $this->_stable_name($fk_info['field_name']);

            $this->_join($main_table, $main_pk_name, $joined_table_name, $joined_field_name);
            $this->_where_clause($this->_database, $joined_table_name, $conditions);
        endif;

        // from inverse side lookup
        if ($this->_stable_name($fk_info['model_name']) == $this->_model_name($candidate_model)):

            $main_pk_name = $this->_model_pk($active_model);
            $main_table = $active_model->get_table_name();
            $joined_table_name = $candidate_model->get_table_name();
            $joined_field_name = $this->_stable_name($fk_info['field_name']);

            $this->_join($main_table, $main_pk_name, $joined_table_name, $joined_field_name);
            $this->_where_clause($this->_database, $joined_table_name, $conditions);
        endif;

        return $this;

    }

    protected function _find_fK_info($active_model, $candidate_model)
    {

        $active_model_name = $this->_model_name($active_model);
        $candidate_model_name = $this->_model_name($candidate_model);

        // first look for a relation field to the $candidate on the active model
        foreach ($active_model->meta->relations_fields as $field) :
            $related_model_name = $this->_model_name($field->related_model);
            if (!$field->M2M && $related_model_name == $candidate_model_name && !$field instanceof \InverseRelation):
                return ['model_name' => $this->_model_name($active_model), 'field_name' => $field->db_column_name()];
            endif;
        endforeach;

        // if nothing look for a relation field to the active model on $candidate
        foreach ($candidate_model->meta->relations_fields as $field) :
            $related_model_name = $this->_model_name($field->related_model);
            if (!$field->M2M && $related_model_name == $active_model_name && !$field instanceof \InverseRelation):
                return ['model_name' => $this->_model_name($candidate_model), 'field_name' => $field->db_column_name()];
            endif;
        endforeach;

    }

    protected function _join($main_table, $main_pk_name, $joined_table_name, $joined_field_name)
    {
        $on = "$main_table.$main_pk_name=$joined_table_name.$joined_field_name";

        $this->_database->join($joined_table_name, $on);
    }

    protected function _m2m_through_model($active_model, $candidate_model)
    {
        $m2m_field_info = $this->_find_m2m_info($active_model, $candidate_model);

        $m2m_field = $m2m_field_info['m2m_field'];
        if (!empty($m2m_field->through)):
            throw new OrmExceptions(
                sprintf("Seems you have an intermidiary table `%s`, add() will not work in this case",
                    $m2m_field->through)
            );
        endif;

        if ($m2m_field_info['model_name'] == $this->_model_name($active_model)):
            $owner_model = $active_model;
            $inverse_model = $candidate_model;
        else:
            $owner_model = $candidate_model;
            $inverse_model = $active_model;
        endif;

        return new ProxyModel($owner_model->meta, $inverse_model->meta);;

    }

    protected function _find_m2m_info($active_model, $candidate_model)
    {

        $active_model_name = $this->_model_name($active_model);
        $candidate_model_name = $this->_model_name($candidate_model);

        // first look for a relation field to the $candidate on the active model
        foreach ($active_model->meta->relations_fields as $field) :
            $related_model_name = $this->_model_name($field->related_model);
            if ($field->M2M && $related_model_name == $candidate_model_name):
                return ['model_name' => $this->_model_name($active_model), 'm2m_field' => $field];
            endif;
        endforeach;

        // if nothing look for a relation field to the active model on $candidate
        foreach ($candidate_model->meta->relations_fields as $field) :
            $related_model_name = $this->_model_name($field->related_model);
            if ($field->M2M && $related_model_name == $active_model_name):
                return ['model_name' => $this->_model_name($candidate_model), 'm2m_field' => $field];
            endif;
        endforeach;

    }

    /**
     * Create and Many To Many Join and adds the where clause
     * @internal
     * @param $related_model_name
     * @param $where_condition
     * @return $this
     */
    protected function _m2m_join($through, $owner_obj, $inverse_obj, $where_condition)
    {
        $join_table_name = $through->get_table_name();

        $current_table = $owner_obj->get_table_name();
        $current_pk = $this->_model_pk_field($owner_obj);
        $related_table = $inverse_obj->get_table_name();
        $related_pk = $this->_model_pk_field($inverse_obj);

        $owner_join_pt = '';
        $inverse_join_pt = '';
        foreach ($through->meta->relations_fields as $field) :

            if ($this->_stable_name($field->model) == $this->_model_name($owner_obj)):
                $owner_join_pt = $field->db_column_name();
                continue;
            endif;
            if ($this->_stable_name($field->model) == $this->_model_name($inverse_obj)):
                $inverse_join_pt = $field->db_column_name();
                continue;
            endif;
        endforeach;

        if (empty($owner_join_pt) || empty($inverse_join_pt)):
            throw new OrmExceptions(
                sprintf('The Model %1$s does not have a field that completes the M2M to %2$s',
                    $this->_model_name($through)), $this->_model_name($inverse_obj));
        endif;

        // many to many
        if (!empty($join_table_name)):

            // join with the join table
            $this->_database->join($join_table_name,
                $current_table . "." . $current_pk . "=" . $join_table_name . "." . $owner_join_pt);

            // join the related table
            $this->_database->join($related_table,
                $related_table . "." . $related_pk . "=" . $join_table_name . "." . $inverse_join_pt);

            if (!empty($where_condition)):
                $this->_where_clause($this->_database, $this->_model_name($inverse_obj), $where_condition);
            endif;

        endif;


        return $this;
    }

    protected function _is_owning($related_obj)
    {
        // find owning side
        foreach ($this->_active_model_object->meta->relations_fields as $field):
            if ($this->_stable_name($field->model) == $this->_model_name($related_obj) &&
                !$field instanceof \InverseRelation):
                return TRUE;
            endif;
        endforeach;

        return FALSE;
    }

    protected function _database()
    {
        $_ci =& get_instance();
        // load database
        $_ci->load->database();
        if (ENVIRONMENT == 'development'):
            // for development only
            $_ci->output->enable_profiler(TRUE);
            $this->db_id = uniqid();
            $_ci->{$this->db_id} = $this->_shallow_copy($_ci->db);
            // create a copy of the database to ensure its unique for each queryset
            $this->_database = $_ci->{$this->db_id};

        else:
            // create a copy the database to ensure its unique for each queryset
            $this->_database = $this->_shallow_copy($_ci->db);
        endif;

        $this->_database->reset_query();
    }

    /**
     * Create a shallow copy of the object passed in, that is, if the object has any references,
     * both the copy and the original will work on the same references.
     *
     * @internal
     * @param $object
     * @return mixed
     */
    protected function _shallow_copy($object)
    {
        return clone $object;
    }

    public function _field_from_condition($field){

        $lookup_pattern = "/__/";
        if(preg_match($lookup_pattern, $field)):
            $options = preg_split($lookup_pattern, $field);
            $field = $options[0];
        endif;
        return $field;
    }

    public function _lookup_from_condition($field){

        $lookup_pattern = "/__/";
        if(preg_match($lookup_pattern, $field)):
            $options = preg_split($lookup_pattern, $field);
            return strtolower($options[1]);
        endif;
    }

    public function _is_m2m($active, $related_obj){
        if(!empty($this->_find_m2m_info($active, $related_obj))):
            return TRUE;
        endif;
    }

    public function _is_m2o($active, $related_obj){
        if(!empty($this->_find_fK_info($active, $related_obj))):
            return TRUE;
        endif;
    }

}