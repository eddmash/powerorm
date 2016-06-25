<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 6/23/16
 * Time: 3:55 PM
 */

namespace powerorm\form\widgets;


use powerorm\exceptions\NotImplemented;
use powerorm\helpers\Tools;
use powerorm\model\field\TextField;
use powerorm\Object;

class Widget extends Object
{

    public $attrs;
    public $needs_multipart_form = FALSE;
    public $is_required = FALSE;

    public function __construct($attrs=[], $kwargs=[])
    {
        $this->attrs = $attrs;
    }

    public function build_attrs($attrs=[], $kwargs=[])
    {

        $final_attrs = array_merge($this->attrs, $kwargs);

        if(!empty($attrs)):
            $final_attrs = array_keys($final_attrs, $attrs);
        endif;

        return $final_attrs;
    }

    public function render($name, $value, $attrs=[])
    {
        throw new NotImplemented('subclasses of Widget must provide a render() method');

    }

    public function prepare_value($value){
        return $value;
    }

    public function value_from_data_collection($data, $name)
    {
        return (isset($data[$name])) ? $data[$name]: NULL;
    }
    public function is_hidden(){
        return ($this->has_property('input_type')) ? $this->input_type==='hidden' : FALSE;
    }

    public function flat_attrs($attrs){
        $str_attrs = '';
        foreach ($attrs as $key=>$attr) :
            if($attrs===TRUE || $attrs===FALSE):
                $str_attrs .= ' '.$key;
            else:
                $str_attrs .= sprintf(' %1$s = %2$s',$key, $attr);
            endif;
        endforeach;

        return $str_attrs;
    }
}

class Input extends Widget{
    public $input_type = NULL;

    public function render($name, $value, $attrs=[])
    {
        $final_attrs = $this->build_attrs($attrs, ['type'=>$this->input_type, 'name'=>$name]);

        // if we have value , add it
        if(!empty($value)):
            $final_attrs['value'] = $this->prepare_value($value);
        endif;

        return sprintf('<input %s>', $this->flat_attrs($final_attrs));
    }
}

class TextInput extends Input{
    public $input_type = 'text';
}

class EmailInput extends TextInput{
    public $input_type = 'email';
}

class UrlInput extends TextInput{
    public $input_type = 'url';
}

class PasswordInput extends TextInput{
    public $input_type = 'password';
}

class NumberInput extends TextInput{
    public $input_type = 'number';
}

class HiddenInput extends TextInput{
    public $input_type = 'hidden';
}

class TextArea extends Widget{

    public function __construct($attrs=[], $kwargs=[]){
        $default_attrs = ['cols'=>'40', 'rows'=> '10'];
        if(!empty($attrs)):
            $attrs = array_merge($default_attrs, $attrs);
        endif;
        parent::__construct($attrs);
    }

    public function render($name, $value, $attrs=[]){
        $final_attrs = $this->build_attrs($attrs, ['type'=>$this->input_type, 'name'=>$name]);

        return sprintf('<textarea %1$s>%2$s</textarea>', $final_attrs, $this->prepare_value($value));
    }
}