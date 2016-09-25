<?php
/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Console\Question;

use Eddmash\PowerOrm\Console\Base;

/**
 * The Asker class provides functions to ask the user for more information on the command line.
 *
 * Asking a smiple question like how old are you.
 * Usage:
 *
 * <pre>$q = new Question('how old are you ?');
 * $asker = InteractiveAsker::createObject();
 * $asker->ask($q);</pre>
 *
 * @since 1.0.1
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Asker extends Base
{
    protected $question;

    public static function createObject()
    {
        return new static();
    }

     /**
      * @param Question $question
      *
      * @return string
      *
      * @since 1.1.0
      *
      * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
      */
     public function ask($question) {}
}
