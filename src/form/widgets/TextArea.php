<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:12 PM
 */

namespace eddmash\powerorm\form\widgets;

/**
 * Text area: <textarea>...</textarea>
 *
 * Class TextArea
 * @package eddmash\powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class TextArea extends Widget
{
    public function __construct($attrs = [], $kwargs = [])
    {
        $default_attrs = ['cols' => '40', 'rows' => '10'];
        if (!empty($attrs)):
            $attrs = array_merge($default_attrs, $attrs);
        endif;
        parent::__construct($attrs);
    }

    public function render($name, $value, $attrs = [], $kwargs = [])
    {
        $final_attrs = $this->build_attrs($attrs, ['type' => $this->input_type, 'name' => $name]);

        return sprintf('<textarea %1$s>%2$s</textarea>', $final_attrs, $this->prepare_value($value));
    }
}
