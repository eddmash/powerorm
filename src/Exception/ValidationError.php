<?php

namespace Eddmash\PowerOrm\Exception;

/**
 * Class ValidationError.
 *
 * @since 1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ValidationError extends OrmError
{
    public function __construct($message, $code = '')
    {

        if(is_array($message)):
            foreach ( as $item) :

            endforeach;
        endif;
        $this->message = $message;
        $this->validationCode = $code;
        parent::__construct($message);
    }
}
