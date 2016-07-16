<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:10 PM
 */

namespace powerorm\form\widgets;



/**
 * base class for all input widgets, should never initialized
 * Class Input
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Input extends Widget{
    public $input_type = NULL;

    public function render($name, $value, $attrs=[], $kwargs=[])
    {
        $final_attrs = $this->build_attrs($attrs, ['type'=>$this->input_type, 'name'=>$name]);

        // if we have value , add it
        if(!empty($value)):
            $final_attrs['value'] = $this->prepare_value($value);
        endif;

        return sprintf('<input %s>', $this->flat_attrs($final_attrs));
    }
}