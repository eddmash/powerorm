<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Field;

use Doctrine\DBAL\Types\Type;
use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\Checks\CheckError;

/**
 * A fixed-precision decimal number. SQl column DECIMAL(M,D).
 *
 * Has two required arguments:
 *
 * - maxDigits
 *
 *    The maximum number of digits allowed in the number. Note that this number must be greater than or equal to decimal_places.
 *
 * - decimalPlaces
 *
 *    The number of decimal places to store with the number.
 *
 * For example, to store numbers up to 999 with a resolution of 2 decimal places, you’d use:
 *
 * Models::DecimalField(['maxDigits'=>5, 'decimalPlaces'=>2])
 *
 * And to store numbers up to approximately one billion with a resolution of 10 decimal places:
 *
 * Models::DecimalField(['maxDigits'=>19, 'decimalPlaces'=>10])
 *
 * The default form widget for this field is a 'text'.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class DecimalField extends Field
{
    public $maxDigits;

    public $decimalPlaces;

    /**
     * {@inheritdoc}
     */
    public function dbType(ConnectionInterface $connection)
    {
        return Type::DECIMAL;
    }

    /**
     * {@inheritdoc}
     */
    public function formField($kwargs = [])
    {
        $kwargs['maxDigits'] = $this->maxDigits;
        $kwargs['decimalPlaces'] = $this->decimalPlaces;
        $kwargs['fieldClass'] = \Eddmash\PowerOrm\Form\Fields\DecimalField::class;

        return parent::formField($kwargs);
    }

    /**
     * {@inheritdoc}
     */
    public function checks()
    {
        $checks = parent::checks();
        $checks = array_merge($checks, $this->decimalPlacesCheck());
        $checks = array_merge($checks, $this->checkMaxDigits());

        return $checks;
    }

    /**
     * @ignore
     *
     * @return array
     */
    private function decimalPlacesCheck()
    {
        if (empty($this->decimalPlaces)) {
            return [
                CheckError::createObject(
                    [
                        'message' => sprintf("%s expects 'decimalPlaces' attribute to be set.", get_class($this)),
                        'hint' => null,
                        'context' => $this,
                        'id' => 'fields.E130',
                    ]
                ),
            ];
        }
        if (!is_numeric($this->decimalPlaces) || $this->decimalPlaces < 0) {
            return [
                CheckError::createObject(
                    [
                        'message' => sprintf(
                            "%s expects 'decimalPlaces' attribute to be a positive integer.",
                            static::class
                        ),
                        'hint' => null,
                        'context' => $this,
                        'id' => 'fields.E131',
                    ]
                ),
            ];
        }

        return [];
    }

    /**
     * @ignore
     *
     * @return array
     */
    private function checkMaxDigits()
    {
        if (empty($this->maxDigits)) {
            return [
                CheckError::createObject(
                    [
                        'message' => sprintf("%s expects 'maxDigits' attribute to be set.", static::class),
                        'hint' => null,
                        'context' => $this,
                        'id' => 'fields.E132',
                    ]
                ),
            ];
        }

        if (!is_numeric($this->maxDigits) || $this->maxDigits < 0) {
            return [
                CheckError::createObject(
                    [
                        'message' => sprintf(
                            "%s expects 'maxDigits' attribute to be a positive integer",
                            static::class
                        ),
                        'hint' => null,
                        'context' => $this,
                        'id' => 'fields.E133',
                    ]
                ),
            ];
        }

        // ensure max_digits is greater than decimal_places
        if ($this->maxDigits < $this->decimalPlaces) {
            return [
                CheckError::createObject(
                    [
                        'message' => sprintf(
                            "%s expects 'maxDigits' to be greater than 'decimalPlaces'",
                            static::class
                        ),
                        'hint' => null,
                        'context' => $this,
                        'id' => 'fields.E134',
                    ]
                ),
            ];
        }

        return [];
    }
}
