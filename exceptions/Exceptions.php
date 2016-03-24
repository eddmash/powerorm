<?php
namespace powerorm\exceptions;

/**
 * Class OrmErrors
 * @package powerorm\exceptions
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class OrmErrors extends \ErrorException{}


/**
 * Class OrmExceptions
 * @package powerorm\exceptions
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class OrmExceptions extends \Exception{}

/**
 * @package powerorm\exceptions
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class TypeError extends OrmErrors{}

/**
 *
 * @package powerorm\exceptions
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class AmbiguityError extends \Exception{}


/**
 * Class ValueError
 * @package powerorm\exceptions
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ValueError extends OrmExceptions{}

/**
 * Class FormException
 * @package powerorm\exceptions
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class FormException extends \Exception{}

/**
 * Class DuplicateField
 * @package powerorm\exceptions
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class DuplicateField extends FormException{}
/**
 * Class ObjectDoesNotExist
 * @package powerorm\exceptions
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ObjectDoesNotExist extends OrmExceptions{}

/**
 * Class MultipleObjectsReturned
 * @package powerorm\exceptions
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class MultipleObjectsReturned extends OrmExceptions{}

class ValidationError extends OrmErrors{}