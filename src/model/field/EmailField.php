<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:05 PM
 */

namespace eddmash\powerorm\model\field;

use eddmash\powerorm\form\widgets;

/**
 * Inherits from CharField.
 * Just like CharField but ensure the input provided is a valid email.
 *
 * - default max_length was increased from 75 to 254 in order to be compliant with RFC3696/5321.
 *
 * @package eddmash\powerorm\model\field
 */
class EmailField extends CharField
{

    /**
     * {@inheritdoc}
     */
    public function __construct($field_options = [])
    {
        //  254 in order to be compliant with RFC3696/5321
        $field_options['max_length'] = 254;
        parent::__construct($field_options);
    }

    public function formfield($kwargs = [])
    {
        $defaults = ['widget' => widgets\EmailInput::full_class_name()];
        $defaults = array_merge($defaults, $kwargs);
        return parent::formfield($defaults);
    }
}
