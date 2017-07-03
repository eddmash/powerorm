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
use Eddmash\PowerOrm\ContributorInterface;
use Eddmash\PowerOrm\DeConstructableInterface;
use Eddmash\PowerOrm\Model\Model;

/**
 * Interface FieldInterface.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
interface FieldInterface extends DeConstructableInterface, ContributorInterface
{
    /**
     * Returns the database column data type for the Field, taking into account the connection.
     *
     * @param Connection $connection
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function dbType(Connection $connection);

    /**
     * Convert the value to a php value.
     *
     * @param $value
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function convertToPHPValue($value);

    /**
     * Method called prior to prepare_value_for_db() to prepare the value before being saved
     * (e.g. for DateField.auto_now).
     *
     * model is the instance this field belongs to and add is whether the instance is being saved to the
     * database for the first time.
     *
     * It should return the value of the appropriate attribute from model for this field.
     *
     * The attribute name is in $this->getAttrName() (this is set up by Field).
     *
     * @param Model $model
     * @param bool  $add   is whether the instance is being saved to the database for the first time
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function preSave(Model $model, $add);

    /**
     * value is the current value of the model’s attribute, and the method should return data in a format that has been
     * prepared for use as a parameter in a query.ie. in the database.
     *
     * @param $value
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepareValue($value);

    /**
     * Converts value to a backend-specific value.
     * By default it returns value if prepared=True and prepare_value() if is False.
     *
     * @param $value
     * @param $connection
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function convertToDatabaseValue($value, $connection, $prepared = false);

    /**
     * Same as the prepare_value_for_db(), but called when the field value must be saved to the database.
     *
     * By default returns prepare_value_for_db().
     *
     * @param $value
     * @param $connection
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepareValueBeforeSave($value, $connection);

    /**
     * Converts a value as returned by the database to a PHP object. It is the reverse of prepare_value().
     *
     * This method is not used for most built-in fields as the database backend already returns the correct PHP type,
     * or the backend itself does the conversion.
     *
     * If present for the field subclass, fromDbValue() will be called in all circumstances when the data is loaded
     * from the database, including in aggregates and asArray() calls.
     *
     * @param Connection $connection
     * @param $value
     * @param $expression
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function fromDbValue(Connection $connection, $value, $expression);

    /**
     * Returns the value of this field in the given model instance.
     *
     * @param $obj
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function valueFromObject($obj);
}
