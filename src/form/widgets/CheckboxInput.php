<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:13 PM
 */

namespace eddmash\powerorm\form\widgets;

/**
 * This creates a single checkbox on the fields, to create more than one use {@see MultipleCheckboxes}
 *
 * Checkbox: <input type='checkbox' ...>
 *
 * Class CheckboxInput
 * @package eddmash\powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class CheckboxInput extends Widget
{
    public function render($name, $value, $attrs = [], $kwargs = [])
    {
        $final_attrs = $this->build_attrs($attrs, ['type' => 'checkbox', 'name' => $name]);

        // if we have value , add it
        // but since we are dealing with checkbox, this will be checked
        if ($this->is_checked($value)):
            $final_attrs['checked'] = 'checked';
        endif;

        if (!empty($value)):
            return (string)$value;
        endif;

        return sprintf('<input %s>', $this->flat_attrs($final_attrs));
    }

    public function is_checked($value)
    {
        return !empty($value);
    }

    public function value_from_data_collection($data, $name)
    {
        // checkboxes are either checked or not checked they dont take values like other input fields
        if (!array_key_exists($name, $data)):
            return false;
        endif;

        $value = $data[$name];

        if (is_bool($value)):
            return $value;
        endif;


        // type cast otherwise
        return (bool)$value;
    }
}
