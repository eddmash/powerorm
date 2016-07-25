<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:14 PM
 */

namespace powerorm\form\widgets;




/**
 * Base class of widgets like checkbox and radio which can be more than one.
 * Class ChoiceInputFields
 * @package powerorm\form\widgets
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class ChoiceInputFields extends Input{
    public $choices=[];
    public $input_type = '';
    public $outer_html = '<ul %1$s> %2$s </ul>';
    public $inner_html = '<li>%1$s %2$s </li>';

    public function __construct($attrs=[], $kwargs=[]){
        parent::__construct($attrs);

        if(array_key_exists('choices', $kwargs)):
            $this->choices = $kwargs['choices'];
        endif;
    }

    public function render($name, $value, $attrs=[], $kwargs=[]){

        if(empty($value)):
            // in case its null, false etc
            $value = [];
        endif;

        $output = [];

        // add select options
        $options[] = $this->render_options($name, $value, $attrs);

        if(!empty($options)):
            $output = array_merge($output, $options);
        endif;

        return join(' ', $output);
    }

    public function render_options($field_name, $checked_choices, $attrs=[]){

        /**
         * 'choices'=>[
         *      'gender'=> ['f'=>'Female', 'm'=>'Male' ],
         *      'bmw'=>'mercedes benz'
         * ]
         */
        $choices = $this->choices;

        if(is_callable($choices)):
            $choices = call_user_func($choices);
        endif;

        $output = [];

        $count = 1;
        foreach ($choices as $label=>$value) :

            $attrs_ = $this->build_attrs($attrs,[
                'name'=>$field_name,
                'type'=>$this->input_type,
            ]);

            $attrs_['id'] = $attrs_['id'].'_'.$count;

            if(is_array($value)):

                $sub_widget = new static($attrs_, ['choices'=>$value]);

                $output[] = sprintf($this->inner_html, $label, $sub_widget->render($field_name, $checked_choices));

            else:
                $sub_widget = '';

                $output[] = sprintf($this->inner_html,
                    $this->render_option($checked_choices, $value, $label, $attrs_), $sub_widget);
            endif;

            $count++;
        endforeach;

        return sprintf($this->outer_html, $this->flat_attrs($attrs),join(' ', $output));
    }

    public function render_option($checked_choices, $label, $value, $attrs=[]){

        $checked_html = '';
        $attrs['value'] = $value;

        $checked = $this->_prepare_checked($checked_choices);

        if(in_array($value, $checked)):
            $checked_html = 'checked="checked"';
        endif;

        $template = '<label for=""> <input %1$s %2$s/> %3$s </label>';

        return sprintf($template, $this->flat_attrs($attrs), $checked_html, $label);
    }

    public function _prepare_checked($checked_choices){
        return $checked_choices;
    }
}
