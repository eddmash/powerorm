<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:09 PM
 */

namespace eddmash\powerorm\form\fields;

use eddmash\powerorm\form\widgets\SelectMultiple;

class MultipleChoiceField extends ChoiceField
{
    public function get_widget()
    {
        return SelectMultiple::instance();
    }
}
