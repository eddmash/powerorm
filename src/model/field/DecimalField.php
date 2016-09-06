<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:06 PM
 */

namespace eddmash\powerorm\model\field;

use eddmash\powerorm\checks\Checks;

/**
 * A fixed-precision decimal number. SQl column DECIMAL(M,D)
 *
 * Has two required arguments:
 *
 * - max_digits
 *
 *    The maximum number of digits allowed in the number. Note that this number must be greater than or equal to decimal_places.
 *
 * - decimal_places
 *
 *    The number of decimal places to store with the number.
 *
 * For example, to store numbers up to 999 with a resolution of 2 decimal places, you’d use:
 *
 * ORM::DecimalField(max_digits=5, decimal_places=2)
 *
 * And to store numbers up to approximately one billion with a resolution of 10 decimal places:
 *
 * ORM::DecimalField(max_digits=19, decimal_places=10)
 *
 * The default form widget for this field is a 'text'.
 *
 * @package eddmash\powerorm\model\field
 */
class DecimalField extends Field
{
    /**
     * The maximum number of digits allowed in the number.
     * Note that this number must be greater than or equal to decimal_places.
     *
     * @var
     */
    public $max_digits;

    /**
     * The number of decimal places to store with the number.
     * @var
     */
    public $decimal_places;


    /**
     * {@inheritdoc}
     */
    public function __construct($field_options = [])
    {
        parent::__construct($field_options);
    }


    /**
     * {@inheritdoc}
     */
    public function db_type($connection)
    {
        return 'DECIMAL';
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        $checks = parent::check();
        $checks = $this->add_check($checks, $this->_decimal_places_check());
        $checks = $this->add_check($checks, $this->_check_max_digits());

        return $checks;
    }

    /**
     * @ignore
     * @return array
     */
    public function _decimal_places_check()
    {
        if (empty($this->decimal_places)):
            return [
                Checks::error([
                    "message" => sprintf("%s expects 'decimal_place' attribute to be set.", get_class($this)),
                    'hint' => null,
                    'context' => $this,
                    'id' => 'fields.E130'
                ])
            ];
        endif;

        if (!is_numeric($this->decimal_places) || $this->decimal_places < 0):
            return [
                Checks::error([
                    "message" => sprintf("%s expects 'decimal_place' attribute to be a positive integer.", get_class($this)),
                    'hint' => null,
                    'context' => $this,
                    'id' => 'fields.E131'
                ])
            ];
        endif;

        return [];
    }

    /**
     * @ignore
     * @return array
     */
    public function _check_max_digits()
    {
        if (empty($this->max_digits)):
            return [
                Checks::error([
                    "message" => sprintf("%s expects 'max_digits' attribute to be set.", get_class($this)),
                    'hint' => null,
                    'context' => $this,
                    'id' => 'fields.E132'
                ])
            ];
        endif;

        if (!is_numeric($this->max_digits) || $this->max_digits < 0):
            return [
                Checks::error([
                    "message" => sprintf("%s expects 'max_digits' attribute to be a positive integer", get_class($this)),
                    'hint' => null,
                    'context' => $this,
                    'id' => 'fields.E133'
                ])
            ];
        endif;

        // ensure max_digits is greater than decimal_places
        if ($this->max_digits < $this->decimal_places):
            return [
                Checks::error([
                    "message" => sprintf("%s expects 'max_digits' to be greater than 'decimal_places'", get_class($this)),
                    'hint' => null,
                    'context' => $this,
                    'id' => 'fields.E134'
                ])
            ];
        endif;

        return [];
    }
}
