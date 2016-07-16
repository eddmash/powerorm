<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:07 PM
 */

namespace powerorm\model\field;


/**
 * A true/false field.
 *
 * The default form widget for this field is a 'radio'.
 *
 * @package powerorm\model\field
 */
class BooleanField extends Field{

    /**
     * {@inheritdoc}
     */
    public $default = FALSE;


    /**
     * {@inheritdoc}
     */
    public function db_type(){
        return "BOOLEAN";
    }

    public function formfield($kwargs=[]){
        $include_blank = TRUE;

        if(!empty($this->choices)):
            if(empty($this->has_default()) || !in_array('initial', $kwargs)):
                $include_blank = FALSE;
            endif;

            $defaults=['choices'=>$this->get_choices(['include_blank'=>$include_blank])];
        else:
            // create just one checkbox
            $defaults = ['form_class'=> form\BooleanField::full_class_name()];
        endif;

        $defaults = array_merge($defaults, $kwargs);
        return parent::formfield($defaults);
    }

}