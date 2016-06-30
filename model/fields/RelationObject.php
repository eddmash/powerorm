<?php
namespace powerorm\model\field;
use powerorm\BaseOrm;
use powerorm\Object;

/**
 * Act as a buffer for Relation Fields, to help avoid issues with relations and self referencing.
 *
 * @package powerorm\model
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class RelationObject extends Object{

    public $auto_created = TRUE;
    public $field = NULL;
    public $model = NULL;
    public $field_name = NULL;
    public $related_name = NULL;

    public function __construct($opts=[]){

        $this->model = $opts['model'];
        $this->field = $opts['field'];
    }

    public function model()
    {
        if (array_key_exists($this->model, $this->get_registry()->get_models())) :
            return  $this->get_registry()->get_model($this->model);

        else:
                return NULL;

        endif;
    }
}

class ManyToOneObject extends RelationObject{}

class OneToOneObject extends RelationObject{}

class ManyToManyObject extends RelationObject{
    public $through = NULL;
    public function __construct($opts=[]){
        parent::__construct($opts);
        $this->through = $opts['through'];
    } 
}
