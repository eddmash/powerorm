<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:07 PM
 */

namespace eddmash\powerorm\form\fields;

use eddmash\powerorm\form\widgets\EmailInput;

/**
 * Creates an :
 *      Default widget: EmailInput
 *      Empty value: '' (an empty string)
 *      Validates that the given value is a valid email address
 *
 * Has two optional arguments for validation, max_length and min_length.
 * If provided, these arguments ensure that the string is at most or at least the given length.
 *
 * Class EmailField
 * @package eddmash\powerorm\form\fields
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class EmailField extends CharField
{
    public $default_validators = ['valid_email'];

    public function get_widget()
    {
        return EmailInput::instance();
    }
}
