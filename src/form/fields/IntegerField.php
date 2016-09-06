<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:08 PM.
 */
namespace eddmash\powerorm\form\fields;

use eddmash\powerorm\form\widgets\NumberInput;

/**
 * Creates a:
 *      Default widget: NumberInput.
 *      Empty value: None.
 *
 * Validates that the given value is an integer.
 *
 *
 * Takes two optional arguments for validation:
 *  - max_value
 *  - min_value
 *
 * These control the range of values permitted in the field.
 *
 * Class IntegerField
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class IntegerField extends Field
{
    public $min_value;
    public $max_value;

    public function __construct($opts = [])
    {
        parent::__construct($opts);

        if ($this->max_value):
            $this->validators[] = sprintf('greater_than[%s]', $this->max_value);
        endif;

        if ($this->min_value):
            $this->validators[] = sprintf('less_than[%s]', $this->min_value);
        endif;
    }

    public function get_widget()
    {
        return NumberInput::instance();
    }

    public function widget_attrs($widget)
    {
        $attrs = parent::widget_attrs($widget);

        if ($this->max_value):
            $attrs['max'] = $this->max_value;
        endif;

        if ($this->min_value):
            $attrs['min'] = $this->min_value;
        endif;

        return $attrs;
    }
}
