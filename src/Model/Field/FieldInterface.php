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

use Eddmash\PowerOrm\ContributorInterface;
use Eddmash\PowerOrm\DeConstructableInterface;

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
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function dbType($connection);

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
    public function toPhp($value);

    /**
     * Returns a powerorm.form.Field instance for this database Field.
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function formField($kwargs = []);

    /**
     * Method called prior to prepare_value_for_db() to prepare the value before being saved
     * (e.g. for DateField.auto_now).
     *
     * model_instance is the instance this field belongs to and add is whether the instance is being saved to the
     * database for the first time.
     *
     * It should return the value of the appropriate attribute from model_instance for this field.
     * The attribute name is in $this->name (this is set up by Field).
     *
     * @param $model
     * @param bool $add is whether the instance is being saved to the database for the first time
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function preSave($model, $add);

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
    public function prepareValueForDb($value, $connection, $prepared = false);

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
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function fromDbValue();

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