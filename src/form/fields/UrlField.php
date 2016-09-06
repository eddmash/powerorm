<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:07 PM.
 */
namespace eddmash\powerorm\form\fields;

use eddmash\powerorm\form\widgets\UrlInput;

/**
 * Creates a:
 *      Default widget: URLInput
 *      Empty value: '' (an empty string)
 *      Validates that the given value is a valid URL.
 *
 *
 * Takes the following optional arguments:
 *      - max_length
 *      - min_length
 *
 * These are the same as CharField->max_length and CharField->min_length.
 *
 * Class UrlField
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class UrlField extends CharField
{
    public $default_validators = ['valid_url'];

    public function get_widget()
    {
        return UrlInput::instance();
    }
}
