<?php
namespace powerorm\model;

//require_once "PModel.php";

/**
 * Created by http://eddmash.com.
 * User: eddmash
 * Date: 2/8/16
 * Time: 6:07 PM
 */
class ProxyModel extends \PModel{
    private $owner_model;
    private $inverse_model;
    public $model_name;

    public function __construct($owner_model, $inverse_model){
        $this->inverse_model = $inverse_model->meta->model_name;
        $this->owner_model = $owner_model->meta->model_name;
        $this->table_name = $this->model_name = sprintf('%1$s_%2$s', strtolower($this->owner_model), strtolower($this->inverse_model));
        parent::__construct();
    }

    public function fields(){
        $this->{$this->owner_model} = new \ForeignKeyField(['model'=>$this->owner_model ]);
        $this->{$this->inverse_model} = new \ForeignKeyField(['model'=>$this->inverse_model ]);
    }
}