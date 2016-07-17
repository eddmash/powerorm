<?php
namespace powerorm\traits;

use powerorm\BaseOrm;

/**
 * Class BaseObject
 * @package powerorm\traits
 * @since 1.1.0
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

    /**
     * Returns the immediate parent of this object.
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function get_parent()
    {
        return get_parent_class($this);
    }

    /**
     * Retirns all he parents for this object static with the younest to the oldest
     * The resolution order to follow when going up a inheritance hierarchy.
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function get_parents()
    {
        return class_parents($this);
    }

    /**
     * Does not return anything, usually good for method that return void.
     *
     * This method invokes the specified method, going up,
     * that is for each parent of the current class invoke the specified method if it exists.
     * 
     * @param $method
     * @param $args
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function call_method_upwards($method, $args=NULL)
    {
        // start from oldest parent to the newest
        $parents = array_reverse($this->get_parents());
        foreach ($parents as $parent) :
            $reflectionParent = new \ReflectionClass($parent);

            if(!$reflectionParent->hasMethod($method)):
                continue;
            endif;

            $reflectionMethod = $reflectionParent->getMethod($method);
            if($reflectionMethod->isAbstract()):
                continue;
            endif;

            $parent_method_call = sprintf('%1$s::%2$s',$parent, $method);
            if(is_array($args)):
                call_user_func_array([$this, $parent_method_call], $args);
            else:

                call_user_func([$this, $parent_method_call], $args);
            endif;

        endforeach;

        // call the current now
        if(is_array($args)):
            call_user_func_array([$this, $method], $args);
        else:

            call_user_func([$this, $method], $args);
        endif;
    }
}