<?php

namespace Eddmash\PowerOrm\Checks;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Console\Base;
use Eddmash\PowerOrm\Helpers\ArrayHelper;

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

    public $levelsMap = [
        'debug' => 10,
        'info' => 20,
        'warning' => 30,
        'error' => 40,
        'critical' => 50,
    ];

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

    public function isSilenced()
    {
        return in_array($this->id, BaseOrm::getInstance()->silencedChecks);
    }

    public function isSerious($level = null)
    {
        return $this->level >= $this->toLevel($level);
    }

    public function __toString()
    {
        $hint = sprintf("%s %s HINT: %s", PHP_EOL, str_pad('', 10, ' '), $this->hint);
        return sprintf('Issue %s : (%s) %s %s',
            $this->id, $this->context, $this->message, $hint);
    }

    public static function createObject($config)
    {
        $message = $hint = $context = $id = '';
        extract($config);

        return new static($message, $hint, $context, $id);
    }

    public function toLevel($level)
    {
        $level = ($level === null) ? static::ERROR : $level;

        if (is_string($level)):
            $level = ArrayHelper::getValue($this->levelsMap, strtolower($level));
        endif;

        return $level;
    }

    public function __debugInfo()
    {
        $model = [];
        $model['level'] = $this->level;
        $model['message'] = $this->message;
        $model['hint'] = $this->hint;
        $model['id'] = $this->id;
        $model['context'] = '***';

        return $model;
    }
}
