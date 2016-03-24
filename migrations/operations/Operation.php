<?php
namespace powerorm\migrations\operations;

/**
 * Class Operation
 * @package powerorm\migrations\operations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Operation{

    public abstract function up();
    public abstract function down();
    public abstract function message();
    public abstract function state();

    public function db_table(){
        if(isset($this->options['table_name'])):
            $name = $this->options['table_name'];
            return $name;
        endif;
    }
}
