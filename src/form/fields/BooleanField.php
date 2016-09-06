<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:08 PM
 */

namespace eddmash\powerorm\form\fields;

use eddmash\powerorm\form\widgets\CheckboxInput;

/**
 * Creates a :
 *       Default widget: CheckboxInput
 *       Empty value: False
 *       Validates that the value is True (e.g. the check box is checked) if the field has required=True.
 *
 * Class BooleanField
 * @package eddmash\powerorm\form\fields
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class BooleanField extends Field
{
    public function get_widget()
    {
        return CheckboxInput::instance();
    }
}
