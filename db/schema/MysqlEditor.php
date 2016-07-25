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
class MysqlEditor extends BaseEditor implements SchemaEditorInterface
{

    public function tpl_drop_fk(){
        return 'ALTER TABLE %1$s DROP FOREIGN KEY %2$s';
    }


    public function create_table($table, $if_not_exists = FALSE, array $attributes = array()){

        parent::create_table($table, $if_not_exists , ['ENGINE'=>'InnoDB']);
    }
}