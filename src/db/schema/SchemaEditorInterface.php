<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/17/16
 * Time: 9:58 AM.
 */
namespace eddmash\powerorm\db\schema;

use eddmash\powerorm\model\BaseModel;
use eddmash\powerorm\model\field\Field;

interface SchemaEditorInterface
{
    public function column_sql(Field $field);

    // Column Manipulation
    public function add_model_field(BaseModel $model, Field $field);

    public function drop_model_field(BaseModel $model, Field $field);

    public function alter_model_field(BaseModel $model, Field $previous_field, Field $present_field);

    // Table Manipulation
    public function create_model(BaseModel $model);

    public function drop_model(BaseModel $model);

    // Constraints Manupilation
//    public function add_primary_key(BaseModel $model, Field $field);
//    public function drop_primary_key(BaseModel $model, Field $field);
//    public function add_unique_key(BaseModel $model, Field $field);
//    public function drop_unique_key(BaseModel $model, Field $field);
//    public function add_index_key(BaseModel $model, Field $field);
//    public function drop_index_key(BaseModel $model, Field $field);
}
