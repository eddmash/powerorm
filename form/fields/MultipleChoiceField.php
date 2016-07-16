<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:09 PM
 */

namespace powerorm\form\fields;


use powerorm\form\widgets\SelectMultiple;

class MultipleChoiceField extends ChoiceField{

    public function get_widget(){
        return SelectMultiple::instance();
    }
}