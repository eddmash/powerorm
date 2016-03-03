<?php
namespace powerorm\model;
/**
 * Orm Exceptions
 */

/**
 * Exceptions raised by the Queryset.
 * Class OrmExceptions
 *
 * @package powerorm
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class OrmExceptions extends \Exception{}

/**
 * Raised if an object does not exist in the database
 * Class ObjectDoesNotExist
 */
class ObjectDoesNotExist extends OrmExceptions{}

/**
 * Raised if more than the required items are found in the database
 * Class MultipleObjectsReturned
 */
class MultipleObjectsReturned extends OrmExceptions{}