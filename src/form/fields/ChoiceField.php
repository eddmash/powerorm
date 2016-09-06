<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:08 PM
 */

namespace eddmash\powerorm\form\fields;

use eddmash\powerorm\form\widgets\MultipleCheckboxes;
use eddmash\powerorm\form\widgets\Select;
use eddmash\powerorm\form\widgets\SelectMultiple;

/**
 * Creates a :
 *      Default widget: Select
 *      Empty value: '' (an empty string)
 * Takes one extra required argument:
 *      choices
 *          - Takes an associative array of value=>label e.g. ['f'=>'female'] or with grouping
 *              $MEDIA_CHOICES = [
 *                  'Audio'=>[
 *                      'vinyl'=>'Vinyl',
 *                      'cd'=> 'CD',
 *                  ],
 *                  'Video'=> [
 *                      'vhs'=> 'VHS Tape',
 *                      'dvd'=> 'DVD',
 *                  ],
 *                  'unknown'=> 'Unknown',
 *              ];
 *
 * Class ChoiceField
 * @package eddmash\powerorm\form\fields
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ChoiceField extends Field
{
    public $choices = [];

    public function __construct($opts = [])
    {
        parent::__construct($opts);
        $this->widget->choices = $this->choices;
    }

    public function get_html_name()
    {
        if ($this->widget instanceof SelectMultiple || $this->widget instanceof MultipleCheckboxes):
            return sprintf('%s[]', $this->name);

        endif;
        return parent::get_html_name();
    }

    public function get_widget()
    {
        return Select::instance();
    }
}
