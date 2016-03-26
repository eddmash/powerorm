<?php
/**
 * ORM QuerySet implementation.
 */


/**
 *
 */
namespace powerorm\queries;

use powerorm\exceptions\MultipleObjectsReturned;
use powerorm\exceptions\ObjectDoesNotExist;
use powerorm\exceptions\OrmExceptions;
use powerorm\exceptions\ValueError;
use powerorm\model\field\ForeignKey;
use powerorm\model\field\HasMany;
use powerorm\model\field\HasOne;
use powerorm\model\field\InverseRelation;
use powerorm\model\field\ManyToMany;
use powerorm\model\ProxyModel;
use powerorm\model\field\RelatedField;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class for doing database lookups, The look up is done Lazily i.e. Lazy Loading.
 *
 * This class provides several methods for interacting with the database with one
 * important thing to note is that some.
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
    protected $_qbuilder;

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
     * Holds the where clauses
     * @internal
     * @var array
     */
    protected $_where_cache = [];

    /**
     * Holds the database connection resource.this only happens if profiler is turned on.
     * @var array
     * @internal
     */
    protected $conn_id;

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
     * @ignore
     * @param \PModel $model the model the Queryset works on.
     * @param object $database the database to use.
     */
    public function __construct($database, $model)
    {
        $this->_active_model_object = $model;

        $this->_get_query_builder();
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
        if((!property_exists($result, $property))):
            return property_exists($result, $property);
        endif;

        return (empty($this->_results_cache->{$property}))? FALSE:TRUE;

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
                    return call_user_func(array($this->_results_cache, $method));
                else:
                    if(is_array($args)):
                        return call_user_func_array(array($this->_results_cache, $method), $args);
                    else:
                        return call_user_func(array($this->_results_cache, $method), $args);
                    endif;
                endif;
            endif;
        endif;
    }

    /**
     *
     * @ignore
     */
    public function __clone()
    {
        // make a copy of the database
        $this->_qbuilder = $this->_shallow_copy($this->_qbuilder);
    }

    /**
     * Evaluates the Queryset when Queryset Result is used in a count() or sizeof().
     * @ignore
     * @return mixed
     */
    public function count()
    {
        if($this->_evaluated):
            return count($this->_results_cache);
        endif;

        $this->_prepare_builder();

        $qb = $this->_shallow_copy($this->_qbuilder);

        return $qb->get()->num_rows();
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
     * Fetches exactly one record from the database table by matching the given lookup parameters.
     * This method Returns a Queryset for further refinement
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
     * To fetch a single row in the database with the primary key 1, where primary key column is `id`.
     *
     *
     * <code>      $this->User_model->get(1)</code>
     *
     *
     * if primary key is not `id` you can pass the primary key field
     *
     *
     * <code>      $this->User_model->get(array('pk'=>1))</code>
     *
     * @param array|int $values an array of field and the value to look for, or an integer-primary key.
     * @see filter() To fetch more than one item based on some conditions.
     * @param $values
     * @return array|bool|object
     * @throws MultipleObjectsReturned
     * @throws ObjectDoesNotExist
     * @throws ValueError
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
     *  $roles = $this->user_model->filter(array("role::name"=>"admin"));</code></pre>
     *
     *
     * @param array $where the where condition e.g. array('username'=>'john', 'password'="ad+as')
     * @see get() To fetch only one item.
     * @param string $foreign_key the name of the foreign key if filtering by related, defaults to adding
     * `tablename_primarykey'
     * @return Queryset
     * @throws OrmExceptions
     */
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
     * @return $this
     */
    public function all()
    {
        $this->_select($this->_active_model_object);
        $this->_from($this->_active_model_object->get_table_name());

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
     * @param $conditions
     * @return $this
     * @throws ValueError
     */
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
     * @return $this
     */
    public function distinct(){
        $this->_qbuilder->distinct();
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

            $this->_qbuilder->order_by($field, $direction);
        endforeach;


        return $this;

    }

    /**
     * Returns the Queryset with it objects grouped by the criteria provided.
     *
     * <h4>USAGE: </h4>
     *
     * To group countries by year of independence.
     *
     * <pre><code>$this->countries->all()->group_by(['independence_year']);</code></pre>
     *
     * @param $condition
     * @return $this
     * @throws ValueError
     */
    public function group_by($condition){
        if (!is_array($condition)) {
            throw new ValueError(sprintf("Arguments should be in array form"));
        }

        // ToDo group by relationship
        $new_cond = [];
        foreach ($condition as $cond) :
            if($this->_is_relation_field($cond,$this->_active_model_object)):
                $field_obj = $this->_active_model_object->meta->relations_fields[$cond];

                $new_cond[] = $field_obj->db_column_name();

            else:

                $new_cond[] = $cond;
            endif;
        endforeach;


        $this->_qbuilder->group_by($new_cond);
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

        $this->_qbuilder->limit($size, $start);

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
     * @ignore
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
     * <strong>NB:</strong> use this method sparingly, using it defeats the purpose of the ORM, which is lazy loading.
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
     * @return mixed
     */
    public function size()
    {
        return $this->count();
    }

    /**
     *@ignore
     */
    public function is_empty(){

        return ($this->size() == 0) ? TRUE : FALSE ;
    }

    /**
     *@ignore
     */
    public function delete(){

    }

    /**
     *@ignore
     */
    public function clear(){
    }

    /**
     *@ignore
     */
    public function _reset(){

        $this->_qbuilder->empty_table($this->_active_model_object->get_table_name());
    }

    //ToDo

    /**
     *@ignore
     */
    public function raw($statement){
    
    }

    // ToDo

    /**
     *@ignore
     */
    public function only($fields=[]){
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
        foreach ($this->_active_model_object->meta->relations_fields as $field=>$field_obj) {
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

            // on MYsql, for foreignkeys that allow empty values, The fields should be set to NULL
            // otherwise the following error is raised
            // #1452 - Cannot add or update a child row: a foreign key constraint fails.

            if($field_obj instanceof ForeignKey && $field_obj->null):
                if(is_string($field_value)):
                    $field_value = trim($field_value);
                endif;
                if(empty($field_value)):
                    $this->_active_model_object->{$field} = NULL;
                endif;
            endif;
        }
        $model_rep = $this->_to_array($this->_active_model_object);


        // now open connection
        $this->_qbuilder->initialize();

        // determine if its an update or a new save
        if (isset($this->_active_model_object->{$pk}) && !empty($this->_active_model_object->{$pk})):
            $pk_value = $this->_active_model_object->{$pk};
            $this->_qbuilder->where($pk, $pk_value);
            $this->_qbuilder->update($this->_active_model_object->get_table_name(), $model_rep);
        else:
            $this->_qbuilder->insert($this->_active_model_object->get_table_name(), $model_rep);
            $pk_value = $this->_qbuilder->insert_id();
        endif;

        // get saved model
        return $this->get($pk_value);
    }

    /**
     * @ignore
     * @param $model
     * @return array
     */
    public function _to_array($model){
        $rep = [];

        foreach ($model->meta->fields as $field) :
            if($field instanceof ManyToMany || $field instanceof InverseRelation):
                continue;
            endif;
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

            $act_value = $this->_active_model_object->{$this->_model_pk_field($this->_active_model_object)};
            $rel_value = $related_model->{$this->_model_pk_field($related_model)};

            // we need to avoid having duplicate rows e.g. M2M between role and perm
            // we avoid having more than one row having role=1 and perm=1
            if(!$proxy->filter([$act_name=>$act_value, $rel_name=>$rel_value])->is_empty()):
                continue;
            endif;

            $proxy->{$act_name} = $act_value;
            $proxy->{$rel_name} = $rel_value;
            $proxy->save();
        endforeach;

        return $this->_active_model_object;
    }



    /**
     * @ignore
     * @param $conditions
     * @return $this
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

    /**
     * @ignore
     * @param $values
     * @return $this
     * @throws ValueError
     */
    protected function _get($values){
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

    /**
     * @ignore
     * @param $conditions
     * @return $this
     * @throws OrmExceptions
     * @throws ValueError
     */
    protected function _filter($conditions)
    {

        $this->_validate_condition($conditions);

        // look if we have filter conditions that span relationships
        $split_conditions = $this->_split_conditions($conditions);

        // first handle the normal filter conditions
        $table_name = $this->_active_model_object->get_table_name();

        $this->_select($this->_active_model_object);
        $this->_from($table_name);

        if (!empty($split_conditions['normal_conditions'])):

            $this->_where_clause($table_name,
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
        echo $this->_qbuilder->get_compiled_select(NULL, FALSE);
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
                ($field_object->M2M || $field_object instanceof InverseRelation)):

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

    /**
     * @param $field_name
     * @param $value
     *
     * @ignore
     */
    public function _prepare_eager_values($field_name, $value){
        // set this values here to avoid having to loop over the results again get them when eager loading
        if ($this->_eager_load && in_array($field_name, $this->_fields_to_eager_load)):
            $this->_eager_fields_values[$field_name][] = $value;
        endif;
    }

    /**
     * @param $model_object
     * @return string
     * @ignore
     */
    protected function _model_name($model_object)
    {
        return $this->_stable_name($model_object->meta->model_name);
    }

    /**
     * @param $name
     * @return string
     * @ignore
     */
    protected function _stable_name($name)
    {
        return strtolower($name);
    }

    /**
     * @param $model_obj
     * @return mixed
     * @ignore
     */
    protected function _model_pk_field($model_obj)
    {
        return $this->_model_pk($model_obj)->name;
    }

    /**
     * @param $model_obj
     * @return mixed
     * @ignore
     */
    protected function _model_pk($model_obj)
    {
        return $model_obj->meta->primary_key;
    }

    /**
     * @param $field_object
     * @param $db_value
     * @return mixed
     * @ignore
     */
    protected function _map_relations($field_object, $db_value)
    {
        $value = $db_value;

        if ($field_object instanceof RelatedField):

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
     * @ignore
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
            if(!isset($this->_eager_fields_values[$field_name])):
                continue;
            endif;

            // since one eager model can be used by the current results multiple times
            // we just make sure we dont have repetitions of the the same eager model key
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

    /**
     * @throws OrmExceptions
     * @ignore
     */
    protected function _related_check()
    {
        $relation_fields = array_keys($this->_active_model_object->meta->relations_fields);
        $not_relation_fields = array_diff($this->_fields_to_eager_load, $relation_fields);


        if (count($not_relation_fields) > 0):
            throw new OrmExceptions(sprintf(' %1$s` model has no relation to %2$s choices are :  %3$s ',
                $this->_model_name($this->_active_model_object),
                stringify($not_relation_fields),
                stringify($relation_fields)));
        endif;

    }

    /**
     * @param $field_name
     * @param $main_results
     * @return mixed
     * @ignore
     */
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
        if (!$this->_evaluated):

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
        $this->_prepare_builder();

        return $this->_db_results($this->_qbuilder->get());
    }

    /**
     *
     * @ignore
     */
    public function _prepare_builder(){

        $this->_qbuilder->initialize();

        if(!empty($this->_where_cache)):

            // create the where conditions because they need a connection
            foreach ($this->_where_cache as $table_name=>$conditions) :
                $where = new Where($this->_qbuilder, $table_name);
                $where->clause($conditions);
            endforeach;

        endif;
    }

    /**
     * @param $query
     * @return mixed
     * @ignore
     */
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

    /**
     * @param $field_value
     * @return mixed
     * @ignore
     */
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

    /**
     * @param $conditions
     * @throws ValueError
     * @ignore
     */
    protected function _validate_condition($conditions)
    {

        if (!is_array($conditions)) {
            throw new ValueError(sprintf("Arguments should be in array form"));
        }

        foreach ($conditions as $key=>$value) :
            if(empty($value)):
                throw new ValueError(sprintf("Lookup condition on model `%2\$s`  has an empty value: %1\$s ",
                    stringify($conditions), $this->_active_model_object->meta->model_name));
            endif;
        endforeach;


        $this->_check_field_exist($conditions);

    }

    /**
     * @param $model_obj
     * @param $field
     * @throws OrmExceptions
     * @ignore
     */
    protected function _field_exists($model_obj, $field){
        $where_concat_pattern = "/^~[.]*/";

        // determine how to combine where statements
        $has_or = preg_match($where_concat_pattern, $field);

        // get the actual key
        if($has_or):
            $field = preg_split($where_concat_pattern, $field)[1];
        endif;

        if (!property_exists($this->_active_model_object, $field)):
            throw new OrmExceptions(
                sprintf('The field `%1$s does not exist on model  %2$s, the choices are : %3$s`',
                    $field, $this->_model_name($model_obj), stringify(array_keys($model_obj->meta->fields))));
        endif;
    }

    /**
     * @param $conditions
     * @throws OrmExceptions
     * @ignore
     */
    protected function _check_field_exist($conditions)
    {
        $fields = array_keys($this->_active_model_object->meta->fields);

        $split = $this->_split_conditions($conditions);

        foreach ($split['normal_conditions'] as $key => $value) :
            $key = $this->_field_from_condition($key);
            $this->_field_exists($this->_active_model_object, $key);
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

    /**
     * @param $conditions
     * @return array
     * @throws OrmExceptions
     * @ignore
     */
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

                // look in the normal conditions and find it any of the fields is a relationship field
                $key_test = $this->_field_from_condition($key);

                // check field exists first
                $this->_field_exists($this->_active_model_object, $key_test);

                if($this->_is_relation_field($key_test,$this->_active_model_object)):
                    $field_obj = $this->_active_model_object->meta->relations_fields[$key_test];

                    $pk = $this->_model_pk_field($field_obj->related_model);
                    $lookup = $this->_lookup_from_condition($key);

                    if(!$this->_self_referncing($field_obj)):
                        $new_lookup = $pk.'__'.$lookup;
                        $_relation_conditions[$field_obj->model][$new_lookup] = $value;
                    endif;

                    $_normal_conditions[$key] = $value;
                else:

                    $_normal_conditions[$key] = $value;
                endif;
            endif;
        endforeach;


        return ['normal_conditions' => $_normal_conditions, 'relation_conditions' => $_relation_conditions];
    }

    /**
     * @param $field_obj
     * @return bool
     * @ignore
     */
    public function _self_referncing($field_obj){
        if($this->_stable_name($field_obj->model) == $this->_model_name($this->_active_model_object)):
            return TRUE;
        endif;
    }

    /**
     * @param $model_object
     * @ignore
     */
    protected function _select($model_object)
    {
        $t_name = $model_object->get_table_name();
        $fields = [];


        foreach ($model_object->meta->fields as $field) :
            if (property_exists($field, 'M2M') && $field->M2M || $field instanceof InverseRelation):
                continue;
            endif;
            $fields[] = $t_name.".".$field->db_column_name();
        endforeach;


        $fields = implode(',', $fields);

        $this->_qbuilder->select($fields);
    }

    /**
     * @param $table_name
     * @ignore
     */
    protected function _from($table_name)
    {
        if (!$this->_is_chained()):
            $this->_table_set = TRUE;
            $this->_qbuilder->from($table_name);
        endif;
    }

    /**
     * @return bool
     * @ignore
     */
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
    protected function _where_clause($table_name, $conditions)
    {
        $this->_where_cache[$table_name]=$conditions;
    }

    /**
     * @param $conditions
     * @return array
     * @ignore
     */
    protected function _prepare_where_conditions($conditions)
    {
        $ready_conditions = [];
        foreach ($conditions as $key => $value) :

            $field_name = $this->_field_from_condition($key);
            $field_name = $this->_field_from_or($field_name);

            $new_key = $this->_active_model_object->meta->fields[$field_name]->db_column_name();


            if($this->_has_or($key)):
                $new_key = '~'.$new_key;
            endif;

            if(!empty($this->_lookup_from_condition($key))):
                $key = $new_key.'__'.$this->_lookup_from_condition($key);
            else:
                $key = $new_key;
            endif;

            $ready_conditions[$key] = $value;
        endforeach;

        return $ready_conditions;
    }

    /**
     * @param $model_name
     * @return mixed
     * @ignore
     */
    protected function _load_model($model_name)
    {
        $_ci =& get_instance();
        $model_name = $this->_stable_name($model_name);
        if (!isset($_ci->{$model_name})):
            $_ci->load->model($model_name);
        endif;
        return $_ci->{$model_name};
    }

    /**
     * @param $active_model
     * @param $candidate_model
     * @param $conditions
     * @return $this
     * @ignore
     */
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
            $this->_where_clause($joined_table_name, $conditions);
        endif;

        // from inverse side lookup
        if ($this->_stable_name($fk_info['model_name']) == $this->_model_name($candidate_model)):

            $main_pk_name = $this->_model_pk($active_model);
            $main_table = $active_model->get_table_name();
            $joined_table_name = $candidate_model->get_table_name();
            $joined_field_name = $this->_stable_name($fk_info['field_name']);

            $this->_join($main_table, $main_pk_name, $joined_table_name, $joined_field_name);
            $this->_where_clause($joined_table_name, $conditions);
        endif;

        return $this;

    }

    /**
     * @param $active_model
     * @param $candidate_model
     * @return array
     * @ignore
     */
    protected function _find_fK_info($active_model, $candidate_model)
    {

        $active_model_name = $this->_model_name($active_model);
        $candidate_model_name = $this->_model_name($candidate_model);

        // first look for a relation field to the $candidate on the active model
        foreach ($active_model->meta->relations_fields as $field) :
            $related_model_name = $this->_model_name($field->related_model);
            if (!$field->M2M && $related_model_name == $candidate_model_name && !$field instanceof InverseRelation):
                return ['model_name' => $this->_model_name($active_model), 'field_name' => $field->db_column_name()];
            endif;
        endforeach;

        // if nothing look for a relation field to the active model on $candidate
        foreach ($candidate_model->meta->relations_fields as $field) :
            $related_model_name = $this->_model_name($field->related_model);
            if (!$field->M2M && $related_model_name == $active_model_name && !$field instanceof InverseRelation):
                return ['model_name' => $this->_model_name($candidate_model), 'field_name' => $field->db_column_name()];
            endif;
        endforeach;

    }

    /**
     * @param $main_table
     * @param $main_pk_name
     * @param $joined_table_name
     * @param $joined_field_name
     * @ignore
     */
    protected function _join($main_table, $main_pk_name, $joined_table_name, $joined_field_name)
    {
        $on = "$main_table.$main_pk_name=$joined_table_name.$joined_field_name";

        $this->_qbuilder->join($joined_table_name, $on);
    }

    /**
     * @param $active_model
     * @param $candidate_model
     * @return ProxyModel
     * @throws OrmExceptions
     * @ignore
     */
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

    /**
     * @param $active_model
     * @param $candidate_model
     * @return array
     * @ignore
     */
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
     * @param $through
     * @param $owner_obj
     * @param $inverse_obj
     * @param $where_condition
     * @return $this
     * @throws OrmExceptions
     * @ignore
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
            $this->_qbuilder->join($join_table_name,
                $current_table . "." . $current_pk . "=" . $join_table_name . "." . $owner_join_pt);

            // join the related table
            $this->_qbuilder->join($related_table,
                $related_table . "." . $related_pk . "=" . $join_table_name . "." . $inverse_join_pt);

            if (!empty($where_condition)):
                $this->_where_clause($inverse_obj->get_table_name(), $where_condition);
            endif;

        endif;


        return $this;
    }

    /**
     * @param $related_obj
     * @return bool
     * @ignore
     */
    protected function _is_owning($related_obj)
    {
        // find owning side
        foreach ($this->_active_model_object->meta->relations_fields as $field):
            if ($this->_stable_name($field->model) == $this->_model_name($related_obj) &&
                !$field instanceof InverseRelation):
                return TRUE;
            endif;
        endforeach;

        return FALSE;
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

    /**
     * @param $field
     * @return mixed
     * @ignore
     */
    protected function _field_from_condition($field){

        $lookup_pattern = "/__/";
        if(preg_match($lookup_pattern, $field)):
            $options = preg_split($lookup_pattern, $field);
            $field = $options[0];
        endif;

        return $field;
    }

    /**
     * @param $field
     * @return string
     * @ignore
     */
    protected function _lookup_from_condition($field){

        $lookup_pattern = "/__/";
        if(preg_match($lookup_pattern, $field)):
            $options = preg_split($lookup_pattern, $field);
            return strtolower($options[1]);
        endif;
    }

    /**
     * @param $key
     * @return mixed
     * @ignore
     */
    protected function _field_from_or($key){
        // search for or condition
        $where_concat_pattern = "/^~[.]*/";

        // determine how to combine where statements
        $has_or = preg_match($where_concat_pattern, $key);


        // get the actual key
        if($has_or):
            $key = preg_split($where_concat_pattern, $key)[1];
        endif;

        return $key;
    }

    /**
     * @param $key
     * @return int
     * @ignore
     */
    protected function _has_or($key){
        // search for or condition
        $where_concat_pattern = "/^~[.]*/";

        // determine how to combine where statements
        return preg_match($where_concat_pattern, $key);

    }

    /**
     * @param $field
     * @param $model_obj
     * @return bool
     * @ignore
     */
    protected function _is_relation_field($field, $model_obj){

        foreach ($model_obj->meta->fields as $mod_field) :

            if($this->_stable_name($mod_field->name) == $this->_stable_name($field) &&
                $mod_field instanceof RelatedField):

                return TRUE;
            endif;
        endforeach;

        return FALSE;
    }

    /**
     * @param $active
     * @param $related_obj
     * @return bool
     * @ignore
     */
    protected function _is_m2m($active, $related_obj){
        if(!empty($this->_find_m2m_info($active, $related_obj))):
            return TRUE;
        endif;
    }

    /**
     * @param $active
     * @param $related_obj
     * @return bool
     * @ignore
     */
    protected function _is_m2o($active, $related_obj){
        if(!empty($this->_find_fK_info($active, $related_obj))):
            return TRUE;
        endif;
    }

    /**
     * @param string $params
     * @ignore
     */
    protected function _get_query_builder($params=''){
        $ci =& get_instance();
        
        if($ci->output->enable_profiler):
            $name = $this->_model_name($this->_active_model_object);
            $this->conn_id = $name.'_'.uniqid();
            $qb = query_builder($params);
            $ci->{$this->conn_id} = $qb;
            $this->_qbuilder = $qb;
        else:
            $this->_qbuilder = query_builder($params);
        endif;


    }
}


/**
 *
/**
 *@ignore

 * Borrowed from CI &DB(), disable creating a connection immediately, reason for this is because before a queryset is
 * evaluated we don't actually need a connection we just need the query_builder class only
 * @param string $params
 * @param null $query_builder_override
 * @return mixed
 */
function query_builder($params = '', $query_builder_override = NULL)
{
    // Load the DB config file if a DSN string wasn't passed
    if (is_string($params) && strpos($params, '://') === FALSE)
    {
        // Is the config file in the environment folder?
        if ( ! file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/database.php')
            && ! file_exists($file_path = APPPATH.'config/database.php'))
        {
            show_error('The configuration file database.php does not exist.');
        }

        include($file_path);

        // Make packages contain database config files,
        // given that the controller instance already exists
        if (class_exists('CI_Controller', FALSE))
        {
            foreach (get_instance()->load->get_package_paths() as $path)
            {
                if ($path !== APPPATH)
                {
                    if (file_exists($file_path = $path.'config/'.ENVIRONMENT.'/database.php'))
                    {
                        include($file_path);
                    }
                    elseif (file_exists($file_path = $path.'config/database.php'))
                    {
                        include($file_path);
                    }
                }
            }
        }

        if ( ! isset($db) OR count($db) === 0)
        {
            show_error('No database connection settings were found in the database config file.');
        }

        if ($params !== '')
        {
            $active_group = $params;
        }

        if ( ! isset($active_group))
        {
            show_error('You have not specified a database connection group via $active_group in your config/database.php file.');
        }
        elseif ( ! isset($db[$active_group]))
        {
            show_error('You have specified an invalid database connection group ('.$active_group.') in your config/database.php file.');
        }

        $params = $db[$active_group];
    }
    elseif (is_string($params))
    {
        /**
         * Parse the URL from the DSN string
         * Database settings can be passed as discreet
         * parameters or as a data source name in the first
         * parameter. DSNs must have this prototype:
         * $dsn = 'driver://username:password@hostname/database';
         */
        if (($dsn = @parse_url($params)) === FALSE)
        {
            show_error('Invalid DB Connection String');
        }

        $params = array(
            'dbdriver'	=> $dsn['scheme'],
            'hostname'	=> isset($dsn['host']) ? rawurldecode($dsn['host']) : '',
            'port'		=> isset($dsn['port']) ? rawurldecode($dsn['port']) : '',
            'username'	=> isset($dsn['user']) ? rawurldecode($dsn['user']) : '',
            'password'	=> isset($dsn['pass']) ? rawurldecode($dsn['pass']) : '',
            'database'	=> isset($dsn['path']) ? rawurldecode(substr($dsn['path'], 1)) : ''
        );

        // Were additional config items set?
        if (isset($dsn['query']))
        {
            parse_str($dsn['query'], $extra);

            foreach ($extra as $key => $val)
            {
                if (is_string($val) && in_array(strtoupper($val), array('TRUE', 'FALSE', 'NULL')))
                {
                    $val = var_export($val, TRUE);
                }

                $params[$key] = $val;
            }
        }
    }

    // No DB specified yet? Beat them senseless...
    if (empty($params['dbdriver']))
    {
        show_error('You have not selected a database type to connect to.');
    }

    // Load the DB classes. Note: Since the query builder class is optional
    // we need to dynamically create a class that extends proper parent class
    // based on whether we're using the query builder class or not.
    if ($query_builder_override !== NULL)
    {
        $query_builder = $query_builder_override;
    }
    // Backwards compatibility work-around for keeping the
    // $active_record config variable working. Should be
    // removed in v3.1
    elseif ( ! isset($query_builder) && isset($active_record))
    {
        $query_builder = $active_record;
    }

    require_once(BASEPATH.'database/DB_driver.php');

    if ( ! isset($query_builder) OR $query_builder === TRUE)
    {
        require_once(BASEPATH.'database/DB_query_builder.php');
        if ( ! class_exists('CI_DB', FALSE))
        {
            /**
             * CI_DB
             *
             * @ignore
             * Acts as an alias for both CI_DB_driver and CI_DB_query_builder.
             *
             * @see	CI_DB_query_builder
             * @see	CI_DB_driver
             */
            class CI_DB extends CI_DB_query_builder { }
        }
    }
    elseif ( ! class_exists('CI_DB', FALSE))
    {
        /**
         * @ignore
         */
        class CI_DB extends CI_DB_driver { }
    }

    // Load the DB driver
    $driver_file = BASEPATH.'database/drivers/'.$params['dbdriver'].'/'.$params['dbdriver'].'_driver.php';

    file_exists($driver_file) OR show_error('Invalid DB driver');
    require_once($driver_file);

    // Instantiate the DB adapter
    $driver = 'CI_DB_'.$params['dbdriver'].'_driver';

    $DB = new $driver($params);

    // Check for a subdriver
    if ( ! empty($DB->subdriver))
    {
        $driver_file = BASEPATH.'database/drivers/'.$DB->dbdriver.'/subdrivers/'.$DB->dbdriver.'_'.$DB->subdriver.'_driver.php';

        if (file_exists($driver_file))
        {
            require_once($driver_file);
            $driver = 'CI_DB_'.$DB->dbdriver.'_'.$DB->subdriver.'_driver';
        }
    }


    if(!class_exists('powerorm\queries\P_QB', FALSE)):
        eval(sprintf('namespace powerorm\queries; class P_QB extends \%s{}',$driver));
    endif;

    // load QueryBuilder
    require_once('QueryBuilder.php');
    return new QueryBuilder($params);
}




