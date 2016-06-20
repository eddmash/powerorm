<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/13/16
 * Time: 10:21 PM
 */

namespace powerorm\db\schema;

/**
 * Class PostgreEditor
 * @package powerorm\db\schema
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class PostgreEditor extends \CI_DB_postgre_forge
{

    use BaseEditor;

    public function tpl_alter_column_type($column, $type){
        return sprintf('ALTER COLUMN %1$s TYPE %2$s USING %1$s::%2$s', $column, $type);
    }

}