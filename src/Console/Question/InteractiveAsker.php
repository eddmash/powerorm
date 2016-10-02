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
 * This Asker class interacts with the user getting reponses from them.
 *
 * {@inheritdoc}
 *
 *
 * Class InteractiveAsker
 *
 * @since 1.0.1
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class InteractiveAsker extends Asker
{
    /**
     * {@inheritdoc}
     */
    public function ask($question)
    {
        if (!$question->getValidator()) :
            return $this->doAsk($question);
        endif;

        $interviewer = function () use ($question) {
            return $this->doAsk($question);
        };

        return $this->validateAttempts($interviewer, $question);
    }

    /**
     * @param Question $question
     *
     * @return bool|string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function doAsk($question)
    {
        $this->normal($question->getQuestion());

        $answer = false;

        if (false === $answer) :
            $answer = $this->input(' ');
            if (false === $answer) :
                throw new \RuntimeException('Aborted');
            endif;
            $answer = trim($answer);
        endif;

        $answer = strlen($answer) > 0 ? $answer : $question->getDefault();

        if ($normalizer = $question->getNormalizer()) :
            return $normalizer($answer);
        endif;

        return $answer;
    }

    /**
     * @param callable $interviewer
     * @param Question $question
     *
     * @return mixed
     *
     * @throws null
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function validateAttempts($interviewer, $question)
    {
        $error = null;
        $attempts = $question->getMaxAttempts();
        while (null === $attempts || $attempts--) {
            if (null !== $error) :
                $this->error($error->getMessage(), true);
            endif;

            try {
                return call_user_func($question->getValidator(), $interviewer());
            } catch (\Exception $error) {
            }
        }
        throw $error;
    }
}
