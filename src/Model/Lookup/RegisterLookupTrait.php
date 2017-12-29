<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Lookup;

use Eddmash\PowerOrm\Exception\FieldError;

trait RegisterLookupTrait
{
    private static $lookups = [];

    /**
     * @param string $class
     * @param null   $name
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function registerLookup($class, $name = null)
    {
        /** @var $class BaseLookup */

        if (is_null($name)):
            $name = strtolower($class::$lookupName);
        endif;
        self::$lookups[$name] = $class;
    }

    /**
     * @param $name
     *
     * @return mixed
     *
     * @throws FieldError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getLookup($name)
    {
        if (array_key_exists($name, self::$lookups)):
            return self::$lookups[$name];
        endif;

        throw new FieldError(sprintf('Lookup %s is not recognized', $name));
    }

    public static function deRegisterLookup($name)
    {
        unset(self::$lookups[$name]);
    }
}
