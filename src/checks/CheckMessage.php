<?php

namespace Eddmash\PowerOrm\Checks;

use Eddmash\PowerOrm\Console\Base;

/**
 * @since 1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class CheckMessage extends Base
{
    // Levels
    const DEBUG = 10;
    const INFO = 20;
    const WARNING = 30;
    const ERROR = 40;
    const CRITICAL = 50;

    public $level;
    public $message;
    public $hint;
    public $context;
    public $id;

    public function __construct($level, $msg, $hint = null, $context = null, $id = null)
    {
        $this->level = $level;
        $this->message = $msg;
        $this->hint = $hint;
        $this->context = $context;
        $this->id = $id;
    }

    public function isSerious()
    {
        return $this->level >= static::ERROR;
    }

    public function __toString()
    {
        return sprintf('Issue %1$s : (%2$s) %3$s %4$s', $this->id, $this->context, $this->message, $this->hint);
    }

    public static function createObject($config)
    {
        $message = $hint = $context = $id = '';
        extract($config);

        return new static($message, $hint, $context, $id);
    }
}
