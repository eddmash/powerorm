<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:07 PM
 */

namespace powerorm\model\field;



/**
 * An IntegerField that automatically increments according to available IDs.
 * You usually won’t need to use this directly;
 * a primary key field will automatically be added to your model if you don’t specify otherwise.
 *
 * @package powerorm\model\field
 */
class AutoField extends IntegerField{

    /**
     * @ignore
     * @var bool
     */
    public $auto=FALSE;


    /**
     * {@inheritdoc}
     */
    public function __construct($field_options=[]){
        parent::__construct($field_options);
        $this->auto = TRUE;
        $this->unique = TRUE;
        $this->primary_key = TRUE;
    }


    /**
     * {@inheritdoc}
     */
    public function contribute_to_class($property_name, $model){
        assert($model->meta->has_auto_field!==TRUE,
            sprintf("%s has more than one AutoField, this is not allowed", $model->meta->model_name));
        parent::contribute_to_class($property_name, $model);

        $model->meta->has_auto_field = TRUE;
        $model->meta->auto_field = $this;
    }
}