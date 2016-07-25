<?php
namespace powerorm\queries;


use powerorm\BaseOrm;
use powerorm\exceptions\MultipleObjectsReturned;
use powerorm\exceptions\NotFound;
use powerorm\exceptions\NotSupported;
use powerorm\exceptions\ObjectDoesNotExist;
use powerorm\exceptions\ValueError;
use powerorm\model\field\ImageField;
use powerorm\Object;


interface QuerysetAccess extends \IteratorAggregate, \ArrayAccess, \Countable{}


/**
 * <h4>When QuerySets are evaluated</h4>
 *
 * Internally, a QuerySet can be constructed, filtered, sliced, and generally passed around without actually hitting the
 * database.
 *
 * No database activity actually occurs until you do something to evaluate the queryset.
 *
 * You can evaluate a QuerySet in the following ways:
 *
 *  - Iteration.
 *      A QuerySet is iterable, and it executes its database query the first time you iterate over it.
 *      For example, this will echo the username of each user in the database:
 *
 *          <code>$all = $this->user->all();
 *          foreach ($all as $user) :
 *              echo $item->username.'<br>';
 *          endforeach;
 *          </code>
 *
 *      Note: Don’t use this if all you want to do is determine if at least one result exists.
 *      It’s more efficient to use {@see Queryset::exists()}.
 *
 *  - Counting
 *      A QuerySet is evaluated when you call count() on it. This, as you might expect, returns the size of the results.
 *
 *      Note: If you only need to determine the number of records in the set (and don’t need the actual objects),
 *      it’s much more efficient to handle a count at the database level using SQL’s SELECT COUNT(*).
 *
 *      <code>$all = $this->user->all();
 *          echo count($all);
 *
 *          $lucies = $this->user->filter(['name'=>'lucy']);
 *          echo count($lucies);
 *      </code>
 *
 *      Powerorm provides a {@see PModel::count() } and {@see Queryset::count() } method for precisely this reason.
 *
 *  - toString
 *      A QuerySet is evaluated when you try to use it as a string.
 *      <code>$all = $this->user->all();
 *          echo $all;
 *
 *          $lucies = $this->user->filter(['name'=>'lucy']);
 *          echo $lucies;
 *      </code>
 *
 *   - Slicing
 *      A Queryset is evaluated when you try extract a slice/section of it. This is achieved through the use of the
 *      {@see Queryset::slice($start, $size) } method.
 *
 *      <code>$all = $this->user->all();
 *          echo $all->slice(3, 40); // return 40 records starting from the 3 record
 *
 *          $lucies = $this->user->filter(['name'=>'lucy']);
 *          echo $lucies->slice(10); // return all records starting from the 10 record
 *      </code>
 *
 *      Powerorm provides a {@see Queryset::limit() } method for precisely this reason.
 *      if you know in advance which section of the data you want
 *
 * Class Queryset
 * @package powerorm\queries
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Queryset extends Object implements QuerysetAccess, Query{


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
     * @var mixed Holds the Queryset Result when Queryset evaluates.
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

    /**
     * INDICATE IF WE NEED TO EAGER LOAD
     * @var bool
     */
    protected $eager_fetch = FALSE;

    /**
     * Indicates that some of the eager loading needs to be done with a second query,
     * mostly for fields that create a many side.
     *
     * @var bool
     *
     */
    protected $_defered_eager = FALSE;


    public function __construct($model, $query=NULL){
        $this->model = $model;
        $this->model_class = $model->full_class_name();
        $this->_query_builder = $query;

        // default action of queryset is fetching
        $this->type = self::OPERATION_FETCH;
    }

    public function __deconstruct(){

        // close connection if its until now open
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
        $no_of_records = $this->_size();

        if ($no_of_records > 1):
            throw new MultipleObjectsReturned(
                sprintf('get() returned more than one %1$s -- it found %2$s!',
                    $this->_model->meta->model_name, $no_of_records));
        endif;

        if ($no_of_records == 0):
            throw new ObjectDoesNotExist(sprintf('`%s` matching query does not exist.!',
                $this->model->meta->model_name));
        endif;

        $this->_evaluate();
        return $this->_results_cache;
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

    public function sql(){
        return (empty($this->sql_cache)) ? $this->_sql() : $this->sql_cache;
    }

    public function exists(){
        return $this->count() > 0;
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


    /**
     * Slice the queryset, just like you would slice and array using array_slice().
     *
     * This method evaluates the Queryset before slicing it.
     *
     * @param int $start the where to begin spliting the queryset, the first element is at index 0, just like arrays
     * @param int $count  how many items to be included in the slice, if empty it go to end of the queryset
     * @return \LimitIterator
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function slice($start, $count=Null)
    {
        if(!empty($count)):
            return new \LimitIterator($this->getIterator(), $start, $count);
        endif;

        return new \LimitIterator($this->getIterator(), $start);
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

        $this->with = $this->_prepare_with($conditions);

        return $this;
    }
    // ----------------------------------------------- > relations



    // **************************************************************************************************

    // *************************************** INTERNAL METHODS *****************************************

    // **************************************************************************************************

    /**
     * Make the fields to prefetch ready for uses.
     * @param $conditions
     * @return array
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _prepare_with($conditions){
        $with = [];


        foreach ($conditions as $condition) :
            $inner_rel = [];

            $condition_size = strlen($condition);
            $sep_size = strlen(self::RELATIONS_LOOK_SEP);

            if($occurs = strpos($condition, self::RELATIONS_LOOK_SEP)):

                $relation = substr($condition, 0, $occurs);


                $inner_rel = $this->_prepare_with([substr($condition, $occurs+$sep_size, $condition_size)]);

                $condition =  $relation;

            endif;

            $with[$condition]= $inner_rel;

        endforeach;

        if(!empty($with)):
            $this->eager_fetch = TRUE;
        endif;
        return $with;
    }

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


    /**
     * Evaluates the Queryset when Queryset Result is used in a count() or sizeof().
     * @ignore
     * @return mixed
     */
    protected function _size()
    {
        if($this->_evaluated):
            $size = count($this->_results_cache);
        else:
            $this->_setup_filters();

            // setup eager relations
            if($this->eager_fetch):
                $this->_eager_fetch();
            endif;

            $size = $this->_query_builder->count_all_results('', FALSE);
        endif;

        return $size;
    }

    protected function _evaluate(){

        if(empty($this->_results_cache)):
            $this->_query_builder = $this->_profiler_ready($this->_query_builder);
            $this->_query_builder->initialize();

            // setup select statement
            $this->_setup_select();

            // setup where conditions
            $this->_setup_filters(FALSE);

            // setup eager relations
            if($this->eager_fetch):
                $this->_eager_fetch();
            endif;

            $this->sql_cache = $this->sql();

            $results = $this->_fetch_data();

            $this->_evaluated = TRUE;

            $populated_results = $this->_populate($results);
            
            if($this->_defered_eager):
                $this->_resolve_defered_eager($populated_results, $this->_defered_eager_relation);
            endif;

            $this->_results_cache = $populated_results;
            $this->_query_builder->close();

        endif;

        return $this->_results_cache;

    }

    /**
     * Does the actual Fetching of data from the database.
     *
     * @return mixed
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _fetch_data()
    {
        // NB:: THIS ARE NOT THE ACTUAL RESULTS FROM THE DATABASE
        // this is an instance of \CI_DB_result
        $result_obj = $this->_query_builder->get();

        if($this->fetch_type == static::FETCH_FIRST || $this->fetch_type==static::FETCH_LAST):
           return call_user_func_array([$result_obj, $this->fetch_type], ['array']);
        endif;

        return call_user_func([$result_obj, $this->fetch_type]);
    }

    protected function _setup_select()
    {
        list($select, $class_info) = $this->_get_select();

        // alias the fields
        $aliased_fields= [];
        foreach ($select as $index=>$sel) :
            $aliased_fields[] = sprintf('%1$s AS %2$s', $sel, str_replace('.', '_', $sel));
        endforeach;

        $this->_query_builder->select($this->_compile($aliased_fields), false);
    }


    protected function _eager_fetch(){
        list($select, $class_info) = $this->_get_select();

        $this->_defered_eager_relation = [];
        $this->_join_cache = [];

        $this->_eager_load($class_info);


    }

    protected function _eager_load($class_info){

        foreach ($class_info['related_klass_infos'] as $related_klass_info) :
            $rel_field = $related_klass_info['field'];

            $this->_join_cache[] = $rel_field->name;

            if($this->_should_defer_eager($rel_field)):
                $this->_defered_eager_relation[] = $rel_field;
                $this->_defered_eager = TRUE;
            endif;

            if(!in_array($rel_field->name, $this->_join_cache)):
                $this->_join_sql($this->model, $rel_field);
            endif;
        endforeach;
    }

    protected function _should_defer_eager($field)
    {
        if($field->M2M || $field->M2O):
            return TRUE;
        endif;
    }

    protected function _resolve_defered_eager($populated_results, $_defered_eager_relation)
    {

    }

    /**
     * Creates a Join statement.
     *
     * @param Object $model the model that contains the relationship field, that is the owning model
     * @param Object $field the field that creates the relation from the model passed in to another
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _join_sql($model, $field){
        
        if($field->is_inverse()):
            $owner_model = $field->relation->get_model();
            $inverse_model = $model;
            $join_table = $owner_model->meta->db_table;
        else:
            $owner_model = $model;
            $inverse_model = $field->relation->get_model();
            $join_table = $inverse_model->meta->db_table;
        endif;

        $inverse_model_pk = $inverse_model->meta->primary_key;


        // todo check for who owns the relationship
        $join_on = $this->_select_field($inverse_model, $inverse_model_pk);



        $main_on = $this->_select_field($owner_model, $field);

        $on = sprintf('%1$s = %2$s', $join_on, $main_on);


        $this->_query_builder->join($join_table, $on);
    }

    /**
     * returns select field, class info
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    protected function _get_select()
    {
        $model = $this->model;

        // all the fields to select in the query
        $select = [];

        // we keep track at which positions all the fields of a model are in the select array.
        $select_fields = [];

        $default_fields = $this->_get_default_fields($model);

        foreach ($default_fields as $index=>$name) :
            $select[] = $name;
            $select_fields[] = $index;
        endforeach;

        $class_info = [
            'model'=>$model,
            'select_fields'=>$select_fields,
            'related_klass_infos'=>[]
        ];

        if($this->eager_fetch):
            $class_info['related_klass_infos'] = $this->_get_related_selection($select);
        endif;

        return [$select, $class_info];
    }

    protected function _model_alias($model)
    {
        return $model->meta->db_table;
    }

    protected function _field_alias($model, $field)
    {
        return sprintf('%1$s_%2$s', $this->_model_alias($model), $field->db_column_name());
    }

    protected function _get_default_fields($model){
        $columns = [];
        foreach ($model->meta->fields as $field) :
            if($field->is_inverse()):
                continue;
            endif;
            $columns[] = $this->_select_field($model, $field);
        endforeach;


        return $columns;
    }

    protected function _select_field($model, $field)
    {
        return sprintf('%1$s.%2$s', $model->meta->db_table, $field->db_column_name());
    }

    protected function _get_related_selection(&$select, $model=NULL, $requested_fields = NULL){
        if(empty($model)):
            $model = $this->model;
        endif;
        
        if(empty($requested_fields)):
            $requested_fields = $this->with;
        endif;

        $related_class_infos = [];

        foreach ($model->meta->fields as $name=>$field) :


            // ensure the field was eager loaded
            if(!$this->_is_requested_relation($field, $requested_fields)):
                continue;
            endif;

            $field_model = $field->relation->get_model();
            $class_info =[
                'model'=>$field_model,
                'field'=>$field
            ];

            $rel_fields = $this->_get_default_fields($field_model);

            $select_fields = [];

            //remember array start at zero
            $count_select = count($select)-1;
            foreach ($rel_fields as $index=>$name) :
                $count_select++; // increment the count with each loop
                $select[] = $name;
                $select_fields[] = $count_select;
            endforeach;

            $class_info['select_fields'] = $select_fields;

            // adjust requested fields to get any relation that go deeper that is follow user->role

            $next_relation = $requested_fields[$field->name];
            $class_info['related_klass_infos'] = $this->_get_related_selection($select, $field_model, $next_relation);



            $related_class_infos[] = $class_info;
        endforeach;

        return $related_class_infos;
    }

    protected function _is_requested_relation($field, $requested_fields){
        if(!$field->is_relation):
            return FALSE;
        endif;

        if(!array_key_exists($field->name, $requested_fields)):
            return FALSE;
        endif;

        return TRUE;
    }

    protected function _populate($results_data)
    {

        if(empty($results_data)):
            return $results_data;
        endif;

        $this->print_r($results_data);

        $results = NULL;

        $primary_class = $this->model_class;

        list($select, $class_info) = $this->_get_select();

        if($this->fetch_type == self::FETCH_MULTIPLE):

            foreach ($results_data as $item) :
                $results[] = $this->_populate_model($primary_class, $item, $class_info, $select);
            endforeach;
        else:
            $results = $this->_populate_model($primary_class, $results_data, $class_info, $select);
        endif;

        return $results;
    }

    protected function _populate_model($primary_class, $data_item, $class_info, $select){

        $model = $primary_class::from_db($this->_query_builder, $this->_get_model_values($select, $class_info, $data_item));

        if($this->eager_fetch):
            $this->_populate_relations($model, $data_item, $class_info, $select);
        endif;

        return $model;
    }

    protected function _populate_relations(&$primary_model, $results, $class_info, $select){

        foreach ($class_info['related_klass_infos'] as $related_klass_info) :
            $rel_model = $related_klass_info['model'];
            $rel_field = $related_klass_info['field'];


            $rel_obj = $this->_populate_model($rel_model->full_class_name(), $results, $related_klass_info, $select);
            $primary_model->{$rel_field->get_cache_name()} = $rel_obj;
        endforeach;

        return $primary_model;
    }

    protected function _get_model_values($select, $class_info, $data_item)
    {
        $fields_positions = $class_info['select_fields'];

        $model_values = [];

        foreach ($fields_positions as $fields_position) :
            if(array_key_exists($fields_position, $select)):

                $field_name = $select[$fields_position];

                $aliased_field_name = (strpos($field_name, "."))? str_replace('.', '_', $field_name) : $field_name;
                $field_name = ($pos = strpos($field_name, "."))? substr($field_name, $pos+1) : $field_name;

                $model_values[$field_name] = $data_item[$aliased_field_name];
            endif;
        endforeach;

        return $model_values;
    }

    protected function print_r($model_values)
    {
        echo "<pre>";
        var_dump($model_values);
        echo "</pre>";
    }

    protected function _compile($list){

        return join(', ', $list);
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

    protected function _create_iterator($list){
        return new \ArrayIterator($list);
    }

    // **************************************************************************************************

    // ************************************** MAGIC METHODS Overrides ***********************************

    // **************************************************************************************************


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

        $results_to_reps = $this->_results_cache;

        if(count($this->_results_cache) > self::REPR_OUTPUT_SIZE):
            $results_to_reps = array_slice($this->_results_cache, 0, self::REPR_OUTPUT_SIZE);
            $results_to_reps[] = "...(remaining elements truncated)...";
        endif;

        $string = implode(', ', $results_to_reps);
        return sprintf('[ %s]', $string);
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
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $this->_evaluate();

        // incase the results cache is not an instance of the result cache
        return $this->_create_iterator($this->_results_cache);
    }

    /**
     * Evaluates the Queryset when Queryset Result is used in a count() or size().
     * @ignore
     * @return mixed
     */
    public function count()
    {
        return $this->_size();
    }

    /**
     *
     * @ignore
     */
    public function __clone()
    {
        // make a copy of the database
        $this->model = $this->deep_clone();

        $this->_query_builder = clone $this->_query_builder;
    }


    /**
     * Check if the key passed in exists.
     * @param mixed $offset the offset to check on
     * @return boolean
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function offsetExists($offset)
    {
        $this->_evaluate();

        return isset($this->_results_cache[$offset]);
    }

    /**
     * retrieve the value for the given key
     * @param integer $offset the offset to retrieve element.     *
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        $exists = $this->offsetExists($offset);
        return isset($exists) ? $this->_results_cache[$offset] : null;
    }

    /**
     * Assign the value to the given key.
     * @param integer $offset the offset to set element
     * @param mixed $item the element value
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     * @throws NotSupported
     */
    public function offsetSet($offset, $item)
    {
        throw new NotSupported('set/unset operations are not supported by queryset');
    }

    /**
     * The key to unset.
     * @param mixed $offset the offset to unset element
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     * @throws NotSupported
     */
    public function offsetUnset($offset)
    {

        throw new NotSupported('set/unset operations are not supported by queryset');
    }

    
}

