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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Eddmash\PowerOrm\Checks\CheckError;

class CharField extends Field
{
    public function checks()
    {
        $checks = [];
        $checks = array_merge($checks, $this->checkMaxLengthAttribtue());

        return $checks;
    }

    private function checkMaxLengthAttribtue()
    {
        $errors = [];

        if (is_null($this->maxLength)):
            $errors = [
                CheckError::createObject(
                    [
                        'message' => 'CharFields must define a "maxLength" attribute.',
                        'hint' => null,
                        'context' => $this,
                        'id' => 'fields.E120',
                    ]
                ),
            ];
        elseif ($this->maxLength <= 0 || is_string($this->maxLength)):
            $errors = [
                CheckError::createObject(
                    [
                        'message' => '"maxLength" must be a positive integer.',
                        'context' => $this,
                        'id' => 'fields.E121',
                    ]
                ),
            ];
        endif;

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function dbType(Connection $connection)
    {
        return Type::STRING;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value)
    {
        $value = parent::convertToPHPValue($value);
        if(is_string($value) || is_null($value)):
            return $value;
        endif;

        return (string) $value;
    }

}
