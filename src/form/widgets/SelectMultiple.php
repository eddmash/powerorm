<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:13 PM.
 */
namespace eddmash\powerorm\form\widgets;

/**
 * Similar to Select, but allows multiple selection: <select multiple='multiple'>...</select>.
 *
 * Class SelectMultiple
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class SelectMultiple extends Select
{
    public $multiple_selected = true;
    public $open_select = '<select %s  multiple="multiple">';

    public function _prepare_selected($selected_choices)
    {
        $selected = [];
        foreach ($selected_choices as $choice) :
            $selected[] = (string) $choice;
        endforeach;

        return $selected;
    }
}
