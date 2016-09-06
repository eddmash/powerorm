<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:14 PM.
 */
namespace eddmash\powerorm\form\widgets;

/**
 * Similar to Select, but rendered as a list of radio buttons within.
 *
 * Class RadioSelect
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RadioSelect extends ChoiceInputFields
{
    public $input_type = 'radio';

    public function _prepare_checked($selected)
    {
        return (is_array($selected) && empty($selected)) ? $selected : [$selected];
    }
}
