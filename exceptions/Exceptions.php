<?php
namespace powerorm\exceptions;

/**
 * Class NotImplemented
 * @package powerorm\exceptions
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class NotImplemented extends \ErrorException{}

/**
 * Class NotFound
 * @package powerorm\exceptions
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class NotFound extends \ErrorException{}
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

/**
 * Class ValidationError
 * @package powerorm\exceptions
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ValidationError extends OrmErrors{}

/**
 * Class CommandError
 * @package powerorm\exceptions
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class CommandError extends OrmErrors{}

/**
 * Class NotSupported
 * @package powerorm\exceptions
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class NotSupported extends OrmExceptions{}

/**
 * Class SystemCheckError
 * @package powerorm\exceptions
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class SystemCheckError extends OrmErrors{}