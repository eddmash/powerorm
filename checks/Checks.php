<?php
namespace powerorm\checks;
use powerorm\BaseOrm;

/**
 * Class Checks
 * @package powerorm\checks
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Checks{
    public $registered_checks;

    public function __construct(){
        $this->checks = [];
    }

    public function register($check){
        assert(is_array($check), "checks methods must return a list");
        $this->registered_checks = array_merge($this->registered_checks, $check);
    }
    
    public function run_checks(){
        $models = BaseOrm::instance()->get_registry()->get_models();

        foreach ($models as $name=>$model_obj) :
            $checks = $model_obj->check();

            foreach ($checks as $check) :
                $this->registered_checks[] = $check;
            endforeach;

        endforeach;


        return $this->get_checks();
    }
    
    public function get_checks(){
        return $this->registered_checks;
    }


//    some short hand
    public static function critical($opts){
        $message = $hint = $context = $id = '';
        extract($opts);
        return new Critical($message, $hint, $context, $id);
    }
    public static function error($opts){
        $message = $hint = $context = $id = '';
        extract($opts);
        return new Error($message, $hint, $context, $id);
    }
    public static function warning($opts){
        $message = $hint = $context = $id = '';
        extract($opts);
        return new Warning($message, $hint, $context, $id);
    }
    public static function debug($opts){
        $message = $hint = $context = $id = '';
        extract($opts);
        return new Debug($message, $hint, $context, $id);
    }
    public static function info($opts){
        $message = $hint = $context = $id = '';
        extract($opts);
        return new Info($message, $hint, $context, $id);
    }

}
