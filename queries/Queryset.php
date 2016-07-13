<?php
namespace powerorm\queries;


use powerorm\BaseOrm;
use powerorm\exceptions\MultipleObjectsReturned;
use powerorm\exceptions\NotFound;
use powerorm\exceptions\ObjectDoesNotExist;
use powerorm\exceptions\ValueError;
use powerorm\Object;

/**
 * Class Queryset
 * @package powerorm\queries
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Queryset extends Object implements \IteratorAggregate, \Countable, Query{

    const FETCH_MULTIPLE = 'result_array';
    const FETCH_SINGLE = 'row_array';
    const FETCH_FIRST = 'first_row';
    const FETCH_LAST= 'last_row';

    const OPERATION_FETCH = 1;
    const OPERATION_INSERT = 2;
    const OPERATION_UPDATE = 3;
    const OPERATION_DELETE = 4;
    const RELATIONS_LOOK_SEP = '->';
    /**
     * The maximum number of items to display in a QuerySet->__toString()
     */
    const REPR_OUTPUT_SIZE = 20;

    public $model;
    public $model_class;
    public $type;
    public $fetch_type;
    public $_evaluated = FALSE;

    /**
     * @var object Holds the Queryset Result when Queryset evaluates.
     * @internal
     */
    protected $_results_cache;

    public $sql_cache = '';

    /**
     * Holds the where clauses, we do this because to create the where condition some db
     * drivers require a connection which we open up only when evaluating
     * @internal
     * @var array
     */
    public $_filter_cache = [];

    /**
     * Keeps track of which tables have been added to the 'from' part of the query
     * @var array
     */
    public $_from_cache=[];


    public function __construct($model, $query=NULL){
        $this->model = $model;
        $this->model_class = $model->full_class_name();
        $this->_query_builder = $query;

        // default action of queryset is fetching
        $this->type = self::OPERATION_FETCH;
    }

    public function __deconstruct(){

        // close connection if its untill now open
        if($this->_query_builder->conn_id):
            $this->_query_builder->close();
        endif;
    }

    public static function instance($model, $query){
        return new static($model, $query);
    }





    // **************************************************************************************************

    // ******************************************* FETCH DATA *******************************************

    // **************************************************************************************************




    public function one($conditions=[]){
        $query = $this->_filter($conditions, self::FETCH_SINGLE);
        $no_of_records = $this->size();

        if ($no_of_records > 1):
            throw new MultipleObjectsReturned(
                sprintf('get() returned more than one %1$s -- it found %2$s!',
                    $this->_model->meta->model_name, $no_of_records));
        endif;

        if ($no_of_records == 0):
            throw new ObjectDoesNotExist(sprintf('`%s` matching query does not exist.!',
                $this->model->meta->model_name));
        endif;
        return $query;
    }

    public function all(){
        return $this->_filter();
    }

    public function filter($conditions=[]){
        return $this->_filter($conditions);
    }

    public function _validate_conditions($method, $conditions){
        assert(is_array($conditions), sprintf(" %s() expects conditions should be in array format", $method));
        return $conditions;
    }

    /**
     * Evaluates the Queryset when Queryset Result is used in a count() or sizeof().
     * @ignore
     * @return mixed
     */
    public function size()
    {
        if($this->_evaluated):

            $size = count($this->_results_cache);
        else:
            $this->_setup_filters();

            $size = $this->_query_builder->count_all_results('', FALSE);
        endif;

        return $size;
    }

    public function sql(){
        return (empty($this->sql_cache)) ? $this->_sql() : $this->sql_cache;
    }
    
    public function exists(){
        return $this->size() > 0;
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
     * @param array $conditions
     * @return $this
     * @throws ValueError
     */
    public function exclude($conditions)
    {
        $conds = [];
        foreach ($conditions as $key => $value) :
            $key = sprintf("%s__not", $key);
            $conds[$key] = $value;
        endforeach;

        $this->_filter($conds);

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
        $this->_query_builder->distinct();
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

        foreach ($criteria as $field => $direction) :

            $direction = strtoupper($direction);

            if (!in_array($direction, ['ASC', 'DESC', 'RANDOM'])):
                throw new ValueError(
                    sprintf('order_by() expects either ASC, DESC, RANDOM as ordering direction, but got %s', $direction));
            endif;

            $this->_query_builder->order_by($field, $direction);
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
     * @param array $condition
     * @return $this
     * @throws ValueError
     * @throws NotFound
     */
    public function group_by($condition){
        $condition = $this->_validate_conditions(__METHOD__,$condition);

        // ToDo group by relationship
        $new_cond = [];
        $not_found = [];
        foreach ($condition as $cond) :
            if(!$this->model->has_property($cond)):
                $not_found[] = $cond;
                continue;
            endif;
            if($this->model->meta->get_field($cond)->is_relation):
                $field_obj = $this->model->meta->relations_fields[$cond];

                $new_cond[] = $field_obj->db_column_name();

            else:

                $new_cond[] = $cond;
            endif;
        endforeach;
        
        if(!empty($not_found)):
            throw new NotFound(sprintf('The fields [ %1$s ] not found in the model %2$s'),
                join(', ',$not_found), $this->model->meta->model_name);
        endif;


        $this->_query_builder->group_by($new_cond);
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

        $this->_query_builder->limit($size, $start);

        return $this;
    }


    /**
     * Gets the first record in the current Queryset.
     *
     * <h4>USAGE:</h4>
     *
     * $users = $this->user_model->filter(['username__contains'=>'d'])->first();
     *
     * echo $users->username;
     * @return $this
     */
    public function first(){

        if($this->is_chaining()):
            $this->fetch_type = self::FETCH_FIRST;
            return $this;
        endif;

        return $this->_filter([], self::FETCH_FIRST);
    }


    /**
     * Gets the last record in the current Queryset.
     *
     * <h4>USAGE:</h4>
     *
     * $user = $this->user_model->filter(['username__contains'=>'d'])->last();
     *
     * echo $user->username;
     *
     * @return $this
     */
    public function last(){
 
        if($this->is_chaining()):
            $this->fetch_type = self::FETCH_LAST;
            return $this;
        endif;
        return $this->_filter([], self::FETCH_LAST);
    }

    public function max($column)
    {
        // TODO: Implement max() method.
    }

    public function min($column)
    {
        // TODO: Implement min() method.
    }

    public function delete()
    {
        // TODO: Implement delete() method.
    }

    public function save()
    {
        // TODO: Implement save() method.
    }


    // ----------------------------------------------- > relations
    public function with($conditions){

        $this->_validate_conditions(__METHOD__, $conditions);

        $with = [];

        foreach ($conditions as $condition) :
            $nested_relation = explode(self::RELATIONS_LOOK_SEP, $condition);
            $total = count($nested_relation)-1;

            for ($i=$total; $i>=0; $i--) :

                $with = ['model'=>$nested_relation[$i], 'field'=>'', 'relations'=>$with];
            endfor;

        endforeach;

        $this->_with = $with;

    }
    // ----------------------------------------------- > relations



    // **************************************************************************************************

    // *************************************** INTERNAL METHODS *****************************************

    // **************************************************************************************************



    /**
     * Create a deep copy of the object passed in, that is, if the object has any references,
     * both the copy and the original will work on the same references.
     *
     * @internal
     * @param $object
     * @return mixed
     */
    protected function deep_clone()
    {
        $query = clone $this->_query_builder;
        if($this->is_chaining()):
            $query->reset_query();
        endif;
        return self::instance($this->model, $query);
    }

    protected function is_chaining()
    {
        return false === empty($this->_from_cache);
    }

    protected function _filter($conditions=[], $fetch_type=self::FETCH_MULTIPLE){
 
//        $query = $this->deep_clone();

        assert(empty($this->_evaluated), "Its not possible to filter on a queryset that has already been evaluated");

        $this->fetch_type = $fetch_type;

        if(!in_array($this->model->meta->db_table, $this->_from_cache)):
            $this->_from_cache[] =$this->model->meta->db_table;
            $this->_query_builder->from($this->model->meta->db_table);
        endif;

        $this->_filter_cache = $this->_validate_conditions('filter', $conditions);

        return $this;
    }

    protected function _create_filter(){
        return new Filter($this->_query_builder, $this->model->meta->db_table);
    }

    public function _evaluate(){

        if(empty($this->_results_cache)):
            $this->_query_builder = $this->_profiler_ready($this->_query_builder);
            $this->_query_builder->initialize();

            $this->_setup_filters(FALSE);

            $this->sql_cache = $this->sql();

            // NB:: THIS ARE NOT THE ACTUAL RESULTS FROM THE DATABASE
            // this is an instance of \CI_DB_result
            $results = $this->_query_builder->get();

            if($this->fetch_type == static::FETCH_FIRST || $this->fetch_type==static::FETCH_LAST):

                $results_data = call_user_func_array([$results, $this->fetch_type], ['array']);
            else:
                $results_data = call_user_func([$results, $this->fetch_type]);
            endif;

            $this->_evaluated = TRUE;


            $this->_results_cache = $this->_populate($results_data);

            $this->_query_builder->close();

        endif;

        return $this->_results_cache;

    }

    protected function _populate($results_data)
    {
        $results = [];

        $primary_class = $this->model_class;

        if($this->fetch_type == self::FETCH_MULTIPLE):
            foreach ($results_data as $item) :
                $results[] =  $this->_populate_model($primary_class, $item);
            endforeach;
        else:
            if(empty($results_data)):
                return $results_data;
            endif;

            $results = $this->_populate_model($primary_class, $results_data);
        endif;

        return $results;
    }

    protected function _populate_model($primary_class, $results_data){

        $results = $primary_class::from_db($this->_query_builder, $results_data);

        return $results;
    }

    protected function _populate_relation($primary_model, $results){
        
    }

    protected function _sql(){

        $this->_setup_filters();

        if($this->type==self::OPERATION_FETCH):
            return $this->_query_builder->get_compiled_select('', FALSE);
        endif;

        if($this->type==self::OPERATION_INSERT):
            return $this->_query_builder->get_compiled_insert('', FALSE);
        endif;


        if($this->type==self::OPERATION_UPDATE):
            return $this->_query_builder->get_compiled_update('', FALSE);
        endif;

        if($this->type==self::OPERATION_DELETE):
            return $this->_query_builder->get_compiled_delete('', FALSE);
        endif;
    }

    protected function _setup_filters($create_connection =TRUE){
        if(!empty($this->_filter_cache)):

            if($create_connection):
                $this->_query_builder->initialize();
            endif;

            $this->_create_filter()->clause($this->_filter_cache);

            // reset the cache filter
            $this->_filter_cache = [];

            if($create_connection):
                $this->_query_builder->close();
            endif;

        endif;

    }

    protected function _profiler_ready($query){
        if(BaseOrm::ci_instance()->output->enable_profiler):

            $conn_id = $this->model->meta->model_name.'_'.uniqid();

            BaseOrm::ci_instance()->{$conn_id} = $query;
            $query = BaseOrm::ci_instance()->{$conn_id};

        endif;

        return $query;
    }

    // **************************************************************************************************

    // ************************************** MAGIC METHODS Overrides ***********************************

    // **************************************************************************************************



    /**
     * Evaluate the Queryset when existence of a property in the Queryset Result is tested. using isset().
     * @param $property
     * @ignore
     * @return bool
     */
    public function __isset($property)
    {
        $result = $this->_evaluate();

        if(!empty($result)):

            return property_exists($result, $property);

        endif;

        return empty($this->_results_cache->{$property});
    }

    /**
     * Evaluate the Queryset when a property is accessed from the Model Instance.
     * @param $property
     * @ignore
     * @return mixed
     */
    public function __get($property)
    {
        // check if queryset is already evaluated
        if (empty($this->_evaluated)):
            $this->_evaluate();
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
        $this->_evaluate();
        $results_to_reps = array_slice($this->_results_cache, 0, self::REPR_OUTPUT_SIZE);
        $string = implode(', ', $results_to_reps);
        return sprintf('[%s]', $string);
    }

    /**
     * Evaluates the Queryset when a method is being accessed in the Queryset Result.
     * @param $method
     * @param $args
     * @ignore
     * @return mixed
     */


    /**
     * Evaluates the Queryset when Queryset Result is used in a foreach.
     * @ignore
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_evaluate());
    }

    /**
     * Evaluates the Queryset when Queryset Result is used in a count() or size().
     * @ignore
     * @return mixed
     */
    public function count()
    {
        return $this->size();
    }

    /**
     *
     * @ignore
     */
    public function __clone()
    {
        // make a copy of the database
        $this->model = $this->deep_clone($this->model);
        $this->_query_builder = $this->_query_builder($this->deep_clone($this->_query_builder));
    }

}

