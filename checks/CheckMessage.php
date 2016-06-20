<?php
namespace powerorm\checks;

use powerorm\console\Base;


/**
 * @package powerorm\checks
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class CheckMessage extends Base{

    # Levels
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

    public function __construct($level, $msg, $hint=Null, $context=Null, $id=Null){
        $this->level = $level;
        $this->message = $msg;
        $this->hint = $hint;
        $this->context = $context;
        $this->id = $id;
    }

    public function is_serious(){
        return $this->level >= static::ERROR;
    }

    public function __toString(){
        return sprintf('Issue %1$s : (%2$s) %3$s %4$s', $this->id, $this->context, $this->message, $this->hint);
    }

}

/**
 * Class Error
 * @package powerorm\checks
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Error extends CheckMessage{
    public function __construct($msg, $hint=Null, $context=Null, $id=Null){
        parent::__construct(CheckMessage::ERROR, $msg, $hint,$context, $id);
    }
}

/**
 * Class Warning
 * @package powerorm\checks
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Warning extends CheckMessage{
    public function __construct($msg, $hint=Null, $context=Null, $id=Null){
        parent::__construct(CheckMessage::WARNING, $msg, $hint,$context, $id);
    }
}

/**
 * Class Info
 * @package powerorm\checks
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Info extends CheckMessage{
    public function __construct($msg, $hint=Null, $context=Null, $id=Null){
        parent::__construct(CheckMessage::INFO, $msg, $hint,$context, $id);
    }
}

/**
 * Class Debug
 * @package powerorm\checks
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Debug extends CheckMessage{
    public function __construct($msg, $hint=Null, $context=Null, $id=Null){
        parent::__construct(CheckMessage::DEBUG, $msg, $hint,$context, $id);
    }
}

/**
 * Class Critical
 * @package powerorm\checks
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Critical extends CheckMessage{
    public function __construct($msg, $hint=Null, $context=Null, $id=Null){
        parent::__construct(CheckMessage::CRITICAL, $msg, $hint,$context, $id);
    }
}
