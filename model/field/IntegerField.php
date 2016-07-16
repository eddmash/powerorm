<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:07 PM
 */

namespace powerorm\model\field;


/**
 * Creates and integer column of Values ranging from -2147483648 to 2147483647.
 *
 * The default form widget for this field is a 'number' with a fallback on 'text' on browsers that dont support html5.
 *
 * @package powerorm\model\field
 */
class IntegerField extends Field{

    /**
     * If this options is set to TRUE, it will create a signed integer 0 to 2147483647
     * else it will create an unsigned integer 0 to -2147483647.
     *
     * @var bool
     */
    public $signed=NULL;


    /**
     * {@inheritdoc}
     */
    public function __construct($field_options=[]){
        parent::__construct($field_options);

    }

    /**
     * {@inheritdoc}
     */
    public function db_type(){
        return 'INT';
    }

    public function formfield($kwargs=[]){
        $defaults=['field_class'=> form\IntegerField::full_class_name()];
        $defaults = array_merge($defaults, $kwargs);

        return parent::formfield($defaults);
    }
}