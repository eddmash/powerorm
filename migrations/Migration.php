<?php
/**
 * Created by http://eddmash.com.
 * User: edd
 * Date: 4/21/16
 * Time: 1:09 PM
 */

namespace powerorm\migrations;
use powerorm\DeConstruct;

use powerorm\helpers\Tools;
use powerorm\migrations\operations\Operation;
use powerorm\model\field\Field;
use powerorm\Object;


/**
 * Base class of all migrations.
 *
 * Represents a list of operations to run at any particular time.
 *
 * A migration represents a set of operations that need to be done on the database to
 * match up what the models in the application represent.
 *
 * Operations are the actions that need to be taken on the database to make it match what the
 * application models represent.
 *
 * @package powerorm\migrations
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Migration extends Object
{
    public $operations;
    public $name;
    public $requires;

    public function __construct($name){
        $this->name = $name;

        $this->operations = $this->operations();
        $this->requires = $this->get_dependency();

    }

    public function _operations(){
        $imports = [];

        $operations = [];
        $ops = $this->operations();

        foreach ($ops as $operation) :
            $op_string = StringifyOperation::formatted($operation);

            array_push($operations, $op_string[0]);

            $imports = array_merge($imports, $op_string[1]);
        endforeach;

        $imports = array_unique($imports);

        $import_paths = '';

        foreach ($imports as $import) :
            $import = sprintf("use %s;", $import);
            $import_paths .= $import.PHP_EOL;
        endforeach;


        $content = '['.PHP_EOL;
        foreach ($operations as $op) :
            $content .= sprintf("\t\t\t%s,".PHP_EOL.PHP_EOL, $op);
        endforeach;
        $content .= "\t\t]";

        return [$content, $import_paths];
    }

    public function as_string(){

        list($ops, $imports) = $this->_operations();

        $template = template();

        $class_name = $this->class_name();
        $depends_on = Tools::stringify($this->get_dependency());

        return sprintf($template, $imports, $class_name, $depends_on, $ops);
    }

    public function class_name(){
        // capitalize all the first letters in word
        $name = str_replace("_", " ", $this->name);
        $name = ucwords($name);
        $name = str_replace(" ", "_", $name);
        return sprintf("Migration_%s",$name);
    }

    public function file_name(){
        return $this->lower_case($this->name);
    }

    public function apply($project_state, $connection){
        $ops = $this->operations();

        foreach ($ops as $op) :

            // get a copy of the state before it is updated by the operation
            $state_before_op = $project_state->deep_clone();

            $op->update_state($project_state);
            $op->update_database($connection, $state_before_op, $project_state);
        endforeach;

        return $project_state;
    }

    public function unapply($project_state, $connection){
        $ops = $this->operations();

        $items_to_run = [];

        $state_after_op = $project_state->deep_clone();
        // we need to reverse the operations so that foreignkeys are removed before model is destroyed
        foreach ($ops as $op) :

            $state_before_op = $state_after_op->deep_clone();
            $state_after_op = $state_after_op->deep_clone();

            // update state
            $op->update_state($state_after_op);

            /**
             * we insert them in the reverse order so the last operation is run first
             */
            array_unshift($items_to_run,
                ['operation'=>$op, 'state_before_op'=>$state_before_op, 'state_after_op'=>$state_after_op]);
        endforeach;


        foreach ($items_to_run as $item) :

            $operation = $item['operation'];
            $state_before_op = $item['state_before_op'];
            $state_after_op = $item['state_after_op'];

            /**
             * Since we are un applying the past state is where we want to revert to
             * and the updated state is the state we are moving from i.e
             * we are moving from $state_after_op to $state_before_op
             */
            $operation->rollback_database($connection, $state_after_op, $state_before_op);
        endforeach;


        return $project_state;

    }

    /**
     * Migration use this method to contribute to the current state of the project.
     * @param $state
     * @return mixed
     */
    public function update_state($state){

        $operations = $this->operations();

        foreach ($operations as $operation) :

            $operation->update_state($state);

        endforeach;

        return $state;
    }

    /**
     * Returns all the operations the migration runs.
     */
    public function operations(){
        return $this->operations;
    }

    /**
     * Returns all the dependencies for this migration.
     * @return mixed
     */
    public function get_dependency(){
        return $this->requires;
    }

    public static function stringify($value){

        if(is_string($value)):
            return [sprintf("'%s'", $value), []];
        endif;

        if(is_array($value)):
            $import = [];
            $assoc = [];

            foreach ($value as $key=>$val) :
                if(!is_int($key)):
                    $key_arr =Migration::stringify($key);
                    $val_arr = Migration::stringify($val);

                    array_push($assoc, sprintf('%1$s=> %2$s', $key_arr[0], $val_arr[0]));

                    if(!empty($key_arr[1])):
                        $import = array_merge($import, $key_arr[1]);
                    endif;

                    if(!empty($val_arr[1])):
                        $import = array_merge($import, $val_arr[1]);
                    endif;
                else:

                    $val_arr = Migration::stringify($val);
                    array_push($assoc, $val_arr[0]);

                    if(!empty($val_arr[1])):
                        $import = array_merge($import, $val_arr[1]);
                    endif;

                endif;

            endforeach;

            return [sprintf("[%s]", join(", ", $assoc)), $import];
        endif;

        if(is_object($value) && $value instanceof DeConstruct):
            $skel = $value->skeleton();

            $import = [$skel['path']];

            $class = array_pop(explode("\\", $skel['path']));


            $constructor_args = $skel['constructor_args'];

            $cons_args = [];
            foreach ($constructor_args as $arg) :

                $val_array = Migration::stringify($arg);

                array_push($cons_args, $val_array[0]);

                if(!empty($val_array[1])):
                    $import = array_merge($import, $val_array[1]);
                endif;
            endforeach;

            return [sprintf('new %1$s(%2$s)', $class, join(",", $cons_args)), $import];
        endif;

        if($value === TRUE):
            return ['TRUE', []];
        endif;

        if($value === FALSE):
            return ['FALSE', []];
        endif;


        return [$value, []];
    }

    public function __toString()
    {
        return (string)$this->name;
    }


}

class StringifyOperation{
    private $operation;
    private $buffer;
    private $indentation;


    public function __construct($operation, $indentation=4){
        $this->operation = $operation;
        $this->indentation = $indentation;
        $this->buffer = [];
    }

    public static function formatted($operation, $indentation=4){
        $op_string = new StringifyOperation($operation, $indentation);
        return $op_string->stringify();
    }

    public function stringify(){
        $skel = $this->operation->skeleton();

        $path = "";
        $constructor_args = [];
        //unpack the array to set the above variables with actual values.
        extract($skel);

        $import = [$path];

        $class = array_pop(explode("\\", $path));

        foreach ($constructor_args as $arg) :

            if(is_array($arg)):

                if(empty($arg)):
                    $this->add_item("[],");
                else:
                    $this->add_item('[');

                    foreach ($arg as $key=>$val) :
                        if(!is_int($key)):
                            $key_arr =Migration::stringify($key);

                            if(is_array($val)):

                                $this->add_indent();
                                    $this->add_item(sprintf('%1$s=>[ ', $key_arr[0]));

                                        foreach ($val as $val_key=>$in_val) :

                                            $val_arr = Migration::stringify($in_val);
                                            $import = array_merge($import, $val_arr[1]);

                                            $this->add_indent();
                                            $this->add_item(sprintf("'%1\$s'=> %2\$s,", $val_key, $val_arr[0]));
                                            $this->reduce_indent();

                                        endforeach;

                                    $this->add_item("]");
                                $this->reduce_indent();

                            else:
                                $val_arr = Migration::stringify($val);
                                $this->add_indent();
                                $this->add_item(sprintf('%1$s=> %2$s,', $key_arr[0], $val_arr[0]));
                                $this->reduce_indent();
                            endif;

                            // imports
                            if(!empty($key_arr[1])):
                                $import = array_merge($import, $key_arr[1]);
                            endif;

                            if(!empty($val_arr[1])):
                                $import = array_merge($import, $val_arr[1]);
                            endif;
                        else:

                            $val_arr = Migration::stringify($val);

                            $this->add_indent();
                            $this->add_item(sprintf("%s",$val_arr[0]));
                            $this->reduce_indent();


                            if(!empty($val_arr[1])):
                                $import = array_merge($import, $val_arr[1]);
                            endif;

                        endif;
                    endforeach;

                    $this->add_item('],');

                endif;
            else:

                $val_array = Migration::stringify($arg);
                $this->add_item(sprintf(" %s,",$val_array[0]));


            endif;

            if(!empty($val_array[1])):
                $import = array_merge($import, $val_array[1]);
            endif;
        endforeach;

        $string = join(PHP_EOL, $this->buffer);

        $string = trim($string, ',');


        return [sprintf("new %1\$s(%2\$s\t\t\t)", $class, PHP_EOL.$string.PHP_EOL), $import];

    }

    public function op_string(){
        return stringify($this->buffer);
    }

    public function add_item($item){
        $indentation = $this->indent($this->indentation);

        $this->buffer[] = $indentation.$item;
    }

    public function add_indent(){
        $this->indentation++;
    }

    public function reduce_indent(){
        $this->indentation--;
    }

    public function indent($by=1){
        $tab = "\t";
        return str_repeat($tab, $by);
    }
}

function template(){

    $template = "<?php
namespace app\\migrations;

use powerorm\\migrations\\Migration;
%1\$s

class %2\$s extends Migration{

    public function get_dependency(){
        return %3\$s ;
    }

    public function operations(){
        return %4\$s ;
    }
}";

    return $template;
}
