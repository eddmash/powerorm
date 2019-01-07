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

/**
 * Class ValidationError.
 *
 * @since  1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ValidationError extends \Exception implements \IteratorAggregate
{
    private $validationCode;

    /**
     * @var ValidationError[]
     */
    private $errorList = [];

    public function __construct($message, $code = '')
    {
        if ($message instanceof self) {
            $message = $message->getMessage();
            $code = $message->validationCode;
        }

        if (is_array($message)) {
            foreach ($message as $item) {
                if (!$item instanceof self) {
                    $item = new self($item);
                }
                $this->errorList = array_merge($this->errorList, $item->errorList);
            }
        } else {
            $this->message = $message;
            $this->validationCode = $code;
            $this->errorList = [$this];
        }
        parent::__construct($this->message);
    }

    /**
     * @return array
     */
    public function getErrorList()
    {
        return $this->errorList;
    }

    /**
     * @return string
     */
    public function getValidationCode()
    {
        return $this->validationCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getMessages());
    }

    /**
     * Returns all the messages.
     *
     * @return array|string
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getMessages()
    {
        $message = [];
        foreach ($this->errorList as $item) {
            $message[] = $item->getMessage();
        }

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return implode(', ', $this->getMessages());
    }
}
