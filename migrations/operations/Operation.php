<?php
namespace powerorm\migrations\operations;
/**
 * Created by http://eddmash.com.
 * User: eddmash
 * Date: 2/21/16
 * Time: 12:16 AM
 */
abstract class Operation{

    public abstract function up();
    public abstract function down();
    public abstract function message();
    public abstract function state();

    public function db_table(){
        if(isset($this->options['table_name'])):
            return $this->options['table_name'];
        endif;
    }
}