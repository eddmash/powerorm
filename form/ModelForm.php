<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/3/16
 * Time: 10:13 AM
 */

namespace powerorm\form;

function fields_from_model($model, $fields, $excludes, $widgets, $labels, $help_texts, $field_classes){

    $model_fields = $model->meta->fields;

    $fields =[];
    foreach ($model_fields as $name=>$obj) :
        $fields[$name] = $obj->formfield();
    endforeach;


    return $fields;
}
class ModelForm extends BaseForm
{
    public $model;
    protected $fields=[];
    protected $excludes=[];
    protected $labels=[];
    protected $widgets=[];
    protected $help_texts=[];
    protected $field_classes=[];

    public function __construct($model, $data=[], $initial=[], $kwargs=[]){
        $this->model = $model;
        parent::__construct($data, $initial, $kwargs);
    }

    public function init(){
        $fields = fields_from_model($this->model, $this->fields, $this->excludes,
            $this->widgets, $this->labels, $this->help_texts, $this->field_classes
        );

        foreach ($fields as $name=>$value) :
            // if field is already in the fields, that takes precedence over model field name
            if(array_key_exists($name, $this->fields)):
                continue;
            endif;
            $this->_field_setup($name, $value);
        endforeach;

    }

    public function model($model=NULL){

        $this->model = ($model!==NULL)? $model : $this->model;
        return $this;
    }

    public function only($fields=[]){
        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }
    
    public function exclude($excludes=[]){
        $this->excludes = array_merge($this->excludes, $excludes);
        return $this;
    }
    
    public function labels($labels=[]){
        $this->labels = array_merge($this->labels, $labels);
        return $this;
    }

    public function widgets($widgets=[]){
        $this->widgets = array_merge($this->widgets, $widgets);
        return $this;
    }

    public function help_texts($help_texts=[]){
        $this->help_texts = array_merge($this->help_texts, $help_texts);
        return $this;
    }

    public function field_classes($field_classes=[]){
        $this->field_classes = array_merge($this->field_classes, $field_classes);
        return $this;
    }

}