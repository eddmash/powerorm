<?php
use powerorm\form\BaseForm;
use powerorm\form\BaseModelForm;
use powerorm\form\widgets as FormWidgets;
use powerorm\form\fields as FormFields;

trait form{


    public static function CharField($attrs=[])
    {
        return new FormFields\CharField($attrs);
    }

    public static function EmailField($attrs=[])
    {
        return new FormFields\EmailField($attrs);
    }

    public static function UrlField($attrs=[])
    {
        return new FormFields\UrlField($attrs);
    }

    public static function BooleanField($attrs=[])
    {
        return new FormFields\BooleanField($attrs);
    }

    public static function ChoiceField($attrs=[]){
        return new FormFields\ChoiceField($attrs);
    }
    public static function MultipleChoiceField($attrs=[]){
        return new FormFields\MultipleChoiceField($attrs);
    }




    // ******************************************************************************

    // ******************************* Widget ***************************************

    // ******************************************************************************



    public static function TextInput($attrs=[]){
        return new FormWidgets\TextInput($attrs);
    }

    public static function PasswordInput($attrs=[]){
        return new FormWidgets\PasswordInput($attrs);
    }

    public static function EmailInput($attrs=[]){
        return new FormWidgets\EmailInput($attrs);
    }

    public static function UrlInput($attrs=[]){
        return new FormWidgets\UrlInput($attrs);
    }

    public static function HiddenInput($attrs=[]){
        return new FormWidgets\HiddenInput($attrs);
    }

    public static function NumberInput($attrs=[]){
        return new FormWidgets\NumberInput($attrs);
    }

    public static function TextArea($attrs=[]){
        return new FormWidgets\TextArea($attrs);
    }

    public static function CheckboxInput($attrs=[]){
        return new FormWidgets\CheckboxInput($attrs);
    }

    public static function Select($attrs=[]){
        return new FormWidgets\Select($attrs);
    }

    public static function SelectMultiple($attrs=[]){
        return new FormWidgets\SelectMultiple($attrs);
    }

    public static function RadioSelect($attrs=[]){
        return new FormWidgets\RadioSelect($attrs);
    }

    public static function MultipleCheckboxes($attrs=[]){
        return new FormWidgets\MultipleCheckboxes($attrs);
    }
}

/**
 * Class Form
 * @package powerorm\form
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class PForm extends BaseForm
{
    use form;
}

class PModelForm extends BaseModelForm{
    use form;
}