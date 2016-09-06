<?php

namespace eddmash\powerorm\exceptions;

/**
 * Class ValidationError.
 *
 * @since 1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ValidationError extends OrmErrors
{
    public function __construct($message, $code = '')
    {
        parent::__construct($message);

        //todo handle if message is array
        $this->message = $message;
        $this->validation_code = $code;
    }

    public function get_message()
    {
        return $this->message;
    }
}
