<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:04 PM
 */

namespace powerorm\model\field;
use powerorm\checks\Checks;


/**
 * A string field, for small- to large-sized strings. i.e. it creates SQL varchar column .
 *
 * For large amounts of text, use TextField.
 *
 * The default form input type is 'text'.
 *
 * CharField has one required argument:
 *   - max_length The maximum length (in characters) of the field.
 *          The max_length is enforced at the database level and in form validation.
 */
class CharField extends Field{

    /**
     * {@inheritdoc}
     */
    public function __construct($field_options=[]){

        parent::__construct($field_options);
    }

    /**
     * {@inheritdoc}
     */
    public function db_type($connection){
        return 'VARCHAR';
    }

    /**
     * {@inheritdoc}
     */
    public function check(){
        $checks = parent::check();
        $checks = $this->add_check($checks, $this->_max_length_check());
        return $checks;
    }

    /**
     * @ignore
     * @return array
     */
    public function _max_length_check(){
        if(empty($this->max_length)):
            return [
                Checks::error([
                    "message"=>"Charfield requires `max_length` to be set",
                    "hint"=>NULL,
                    "context"=>$this,
                    "id"=>"fields.E120"
                ])
            ];
        endif;

        if(!empty($this->max_length) && $this->max_length < 0):
            return [
                Checks::error([
                    "message"=>'Charfield requires `max_length` to be a positive integer',
                    "hint"=>NULL,
                    "context"=>$this,
                    "id"=>"fields.E121"
                ])
            ];
        endif;
        return [];
    }

    public function formfield($kwargs=[]){
        $defaults=['max_length'=>$this->max_length];
        $defaults = array_merge($defaults, $kwargs);
        return parent::formfield($defaults);
    }

}