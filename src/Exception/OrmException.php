<?php

namespace Eddmash\PowerOrm\Exception;

use Throwable;

/**
 * Class OrmException.
 *
 * @since  1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class OrmException extends \Exception
{
    /**
     * @param Throwable $e
     *
     * @return static
     */
    public static function fromThrowable(\Exception $e)
    {
        return new static($e->getMessage(), $e->getCode(), $e);
    }
}
