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

use Eddmash\PowerOrm\Object;
use InvalidArgumentException;

class Question extends Object
{
    protected $question;
    protected $default;
    protected $hidden;
    protected $validator;
    protected $normalizer;
    protected $attempts;

    public function __construct($question, $default = null)
    {
        $this->question = $question;
        $this->default = $default;
    }

    /**
     * @return mixed
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @param mixed $question
     */
    public function setQuestion($question)
    {
        $this->question = $question;
    }

    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param null $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * @return mixed
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * @param mixed $hidden
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * Sets a validator for the question.
     *
     * @param null|callable $validator
     *
     * @return Question The current instance
     */
    public function setValidator(callable $validator = null)
    {
        $this->validator = $validator;

        return $this;
    }
    /**
     * Gets the validator for the question.
     *
     * @return null|callable
     */
    public function getValidator()
    {
        return $this->validator;
    }
    /**
     * Sets the maximum number of attempts.
     *
     * Null means an unlimited number of attempts.
     *
     * @param null|int $attempts
     *
     * @return Question The current instance
     *
     * @throws \InvalidArgumentException In case the number of attempts is invalid
     */
    public function setMaxAttempts($attempts)
    {
        if (null !== $attempts && $attempts < 1) {
            throw new InvalidArgumentException('Maximum number of attempts must be a positive value.');
        }
        $this->attempts = $attempts;

        return $this;
    }
    /**
     * Gets the maximum number of attempts.
     *
     * Null means an unlimited number of attempts.
     *
     * @return null|int
     */
    public function getMaxAttempts()
    {
        return $this->attempts;
    }

    /**
     * Gets the normalizer for the response.
     *
     * The normalizer can ba a callable (a string), a closure or a class implementing __invoke.
     *
     * @return callable
     */
    public function getNormalizer()
    {
        return $this->normalizer;
    }

    /**
     * Sets a normalizer for the response.
     *
     * The normalizer can be a callable (a string), a closure or a class implementing __invoke.
     *
     * @param callable $normalizer
     *
     * @return Question The current instance
     */
    public function setNormalizer(callable $normalizer)
    {
        $this->normalizer = $normalizer;

        return $this;
    }

    protected function isAssoc($array)
    {
        return (bool) count(array_filter(array_keys($array), 'is_string'));
    }
}
