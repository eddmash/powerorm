<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 9/14/16
 * Time: 3:04 PM.
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
