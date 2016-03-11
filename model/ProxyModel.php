<?php
namespace powerorm\model;

//require_once "PModel.php";

/**
 * Class ProxyModel
 * @package powerorm\model
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ProxyModel extends \PModel{
    public $owner_model;
    public $inverse_model;
    public $model_name;

    public function __construct($owner_model, $inverse_model){
        $this->inverse_model = strtolower($inverse_model->model_name);
        $this->owner_model = strtolower($owner_model->model_name);
        $this->model_name = sprintf('%1$s_%2$s', strtolower($this->owner_model), strtolower($this->inverse_model));
        $this->table_name = $this->model_name;
        parent::__construct();
    }

    public function fields(){
        $this->{$this->owner_model} = new \ForeignKey(['model'=>$this->owner_model ]);
        $this->{$this->inverse_model} = new \ForeignKey(['model'=>$this->inverse_model ]);
    }
}