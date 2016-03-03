<?php
namespace powerorm\model;

/**
 * Orm Errors
 */

/**
 * Errors from POWERORM
 * Class OrmErrors
 * @package powerorm
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 *
 */
class OrmErrors extends \ErrorException{}

/**
 * Raised by Orm
 * Class TypeError
 * @package powerorm
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class TypeError extends OrmErrors{}