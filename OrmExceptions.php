<?php

/**
 * Exceptions raised by the Queryset.
 * Class OrmExceptions
 *
 * @package POWERCI
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class OrmExceptions extends \Exception{}

class ObjectDoesNotExist extends OrmExceptions{}
class MultipleObjectsReturned extends OrmExceptions{}