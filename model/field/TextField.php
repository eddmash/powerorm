<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:06 PM
 */

namespace powerorm\model\field;

use powerorm\form\widgets\TextArea;


/**
 * A large text field. The default form widget for this field is a 'Textarea'.
 */
class TextField extends Field{


    /**
     * {@inheritdoc}
     */
    public function __construct($field_options = []){
        parent::__construct($field_options);

        $this->unique = FALSE;
        $this->db_index = FALSE;
    }


    /**
     * {@inheritdoc}
     */
    public function db_type($connection){
        return 'TEXT';
    }

    public function formfield($kwargs=[]){

        $kwargs['field_class'] = TextArea::full_class_name();
        return parent::formfield($kwargs);
    }

}
