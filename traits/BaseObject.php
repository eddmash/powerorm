<?php
namespace powerorm\traits;

use powerorm\BaseOrm;

/**
 * Class BaseObject
 * @package powerorm\traits
 * @since 1.0.2
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
trait BaseObject
{



    /**
     * @ignore
     * @var bool
     */
    private $_signal = FALSE;

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized ;
     */
    public function init()
    {

    }

    /**
     * Returns the fully qualified name of this class.
     * @return string the fully qualified name of this class.
     */
    public static function full_class_name()
    {
        return get_called_class();
    }

    public function get_class_name(){
        $name = get_class($this);
        if(strpos($name, '\\')):
            $name = explode('\\', $name);
            if(is_array($name)):
                $name=array_pop($name);
            endif;
        endif;
        return $name;
    }

    /**
     * Returns a value indicating whether a method is defined.
     *
     * The default implementation is a call to php function `method_exists()`.
     * You may override this method when you implemented the php magic method `__call()`.
     * @param string $name the method name
     * @return boolean whether the method is defined
     */
    public function has_method($name)
    {
        return method_exists($this, $name);
    }

    public function has_property($name)
    {
        return property_exists($this, $name);
    }

    public function lower_case($name){
        return strtolower($name);
    }

    public function get_registry(){
        return BaseOrm::instance()->get_registry();
    }

    public function orm_instance()
    {
        return BaseOrm::instance();
    }

    public function &ci_instance(){
        return BaseOrm::ci_instance();
    }

    public function dispatch_signal($signal_name, $object){
        if ($this->_signal):
            $this->signal->dispatch($signal_name, $object);
        endif;
    }
}