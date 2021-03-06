<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Exception;

use Exception;

/**
 * Class NotImplemented.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class NotImplemented extends \ErrorException
{
    public function __construct($message = 'method not implemeted', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
