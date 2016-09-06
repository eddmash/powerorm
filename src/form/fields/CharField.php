<?php

namespace eddmash\powerorm\form\fields;

/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:05 PM.
 */

/**
 * Creates a :
 *      Default widget: TextInput
 *      Empty value: '' (an empty string)
 *      Validates max_length or min_length, if they are provided. Otherwise, all inputs are valid.
 *
 * Has two optional arguments for validation:
 *  - max_length
 *  - min_length
 *
 *  If provided, these arguments ensure that the string is at most or at least the given length.
 *
 * Class CharField
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class CharField extends Field
{
    public $max_length;
    public $min_length;

    public function __construct($opts = [])
    {
        parent::__construct($opts);

        if ($this->max_length):
            $this->validators[] = sprintf('max_length[%s]', $this->max_length);
        endif;

        if ($this->min_length):
            $this->validators[] = sprintf('min_length[%s]', $this->min_length);
        endif;
    }

    public function widget_attrs($widget)
    {
        $attrs = parent::widget_attrs($widget);
        if ($this->max_length):
            $attrs['maxlength'] = $this->max_length;
        endif;

        return $attrs;
    }
}
