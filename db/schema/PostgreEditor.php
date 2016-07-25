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
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class PostgreEditor extends BaseEditor implements SchemaEditorInterface
{

    public $sql_alter_column_type = 'ALTER COLUMN %1$s TYPE %2$s USING %1$s::%2$s';

}