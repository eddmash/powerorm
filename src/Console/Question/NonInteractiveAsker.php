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

/**
 * {@inheritdoc}
 *
 *
 * Usage:
 *
 * <pre>$q = new Question('how old are you ?');
 * $asker = NonInteractiveAsker::createObject();
 * $asker->ask($q);</pre>
 *
 * Class NonInteractiveAsker
 *
 * @since 1.0.1
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class NonInteractiveAsker extends Asker
{
    /**
     * {@inheritdoc}
     */
    public function ask($question)
    {
        return $question->getDefault();
    }
}
