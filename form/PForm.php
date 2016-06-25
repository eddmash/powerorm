<?php
use powerorm\form\BaseForm;
use powerorm\form\fields\CharField;
use powerorm\form\fields\EmailField;
use powerorm\form\fields\TextField;
use powerorm\form\fields\UrlField;
use powerorm\form\widgets\EmailInput;
use powerorm\form\widgets\HiddenInput;
use powerorm\form\widgets\NumberInput;
use powerorm\form\widgets\PasswordInput;
use powerorm\form\widgets\TextInput;
use powerorm\form\widgets\UrlInput;

/**
 * Class Form
 * @package powerorm\form
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class PForm extends BaseForm
{

    public static function CharField($attrs=[])
    {
        return new CharField($attrs);
    }

    public static function EmailField($attrs=[])
    {
        return new EmailField($attrs);
    }

    public static function UrlField($attrs=[])
    {
        return new UrlField($attrs);
    }

    // ******************************************************************************

    // ******************************* Widget ***************************************

    // ******************************************************************************



    public static function TextInput($attrs=[]){
        return new TextInput($attrs);
    }


    public static function PasswordInput($attrs=[]){
        return new PasswordInput($attrs);
    }

    public static function EmailInput($attrs=[]){
        return new EmailInput($attrs);
    }

    public static function UrlInput($attrs=[]){
        return new UrlInput($attrs);
    }

    public static function HiddenInput($attrs=[]){
        return new HiddenInput($attrs);
    }

    public static function NumberInput($attrs=[]){
        return new NumberInput($attrs);
    }

    public static function TextArea($attrs=[]){
        return new TextArea($attrs);
    }
}