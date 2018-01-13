<?php

namespace Eddmash\PowerOrm\Checks;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Console\Base;
use Eddmash\PowerOrm\Helpers\ArrayHelper;

/**
 * @since  1.0.0
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
        return BaseOrm::isCheckSilenced($this->id);
    }

    public function isSerious($level = null)
    {
        return $this->level >= $this->toLevel($level);
    }

    public function __toString()
    {
        $hint = null;
        if ($this->hint):
            $hint = sprintf(
                '<fg=black>%s %s <fg=yellow;options=bold>HINT:</> %s</>',
                PHP_EOL,
                str_pad('', 1, ' '),
                $this->hint
            );
        endif;
        $msg = PHP_EOL.str_pad('', 3, ' ').$this->message;

        return sprintf(
            'Issue <fg=red;options=bold>%s</> : <fg=black>(%s)</> %s %s',
            $this->id,
            $this->context,
            $msg,
            $hint
        );
    }

    public static function createObject($config)
    {
        $message = $hint = $context = $id = '';
        extract($config);

        return new static($message, $hint, $context, $id);
    }

    public function toLevel($level)
    {
        $level = (null === $level) ? static::ERROR : $level;

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
