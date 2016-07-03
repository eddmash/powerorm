<?php
/**
 * Created by http://eddmash.com.
 * User: eddmash
 * Date: 5/28/16
 * Time: 8:59 AM
 */

namespace powerorm\db\schema;


/**
 * Class MysqlEditor
 * @package powerorm\db\schema
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class MysqlEditor extends \CI_DB_mysql_forge
{
    use BaseEditor;

    public function tpl_drop_fk(){
        return 'ALTER TABLE %1$s DROP FOREIGN KEY %2$s';
    }


    public function create_table($table, $if_not_exists = FALSE, array $attributes = array()){

        parent::create_table($table, $if_not_exists , ['ENGINE'=>'InnoDB']);
    }

    public function tpl_alter_column_type($column, $type){
        return sprintf('MODIFY %1$s %2$s', $column, $type);
    }
}