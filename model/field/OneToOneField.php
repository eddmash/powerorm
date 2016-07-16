<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:09 PM
 */

namespace powerorm\model\field;


/**
 * Class OneToOne
 */
class OneToOneField extends ManyToOneField{


    /**
     * {@inheritdoc}
     */
    public function __construct($field_options = []){
        parent::__construct($field_options);
        $this->M2O = FALSE;
        $this->O2O = TRUE;
        $this->unique = TRUE;

    }

    /**
     * @ignore
     */
    public function _unique_check(){
    }

}