<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:15 PM.
 */
namespace powerorm\form\widgets;

/**
 * Similar to SelectMultiple, but rendered as a list of check buttons:.
 *
 * Class CheckboxSelectMultiple
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class MultipleCheckboxes extends ChoiceInputFields
{
    public $input_type = 'checkbox';

    public function _prepare_checked($selected_choices)
    {
        $selected = [];
        foreach ($selected_choices as $choice) :
            $selected[] = (string) $choice;
        endforeach;

        return $selected;
    }
}
