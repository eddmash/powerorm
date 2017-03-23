<?php
/**
 * Created by PhpStorm.
 * User: eddmash
 * Date: 3/20/17
 * Time: 7:38 PM.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */

namespace Eddmash\PowerOrm\Model\Manager;

use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Query\Queryset;

/**
 * Class M2OQueryset.
 *
 * @return Queryset
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class M2OManager extends BaseM2OManager
{
    public $reverse;
    public $instance;
    public $filters;

    /**
     * @param array $kwargs
     *
     * @return static
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public static function createObject($kwargs = [])
    {
        return new static($kwargs);
    }

    /**
     * {@inheritdoc}
     */
    public function __construct($kwargs = [])
    {
        $this->instance = ArrayHelper::getValue($kwargs, 'instance');
        $this->reverse = ArrayHelper::getValue($kwargs, 'reverse', false);
        /** @var ForeignObjectRel $rel */
        $rel = ArrayHelper::getValue($kwargs, 'rel');

        if ($this->reverse === false) :
            $model = $rel->toModel;
            $fromField = $rel->fromField;
            $toField = $rel->getRelatedField();
            $value = $this->instance->{$fromField->getAttrName()};
            $filter[$toField->getAttrName()] = $value;
        else:
            $model = $rel->getFromModel();
            //todo
            $fromField = $rel->getRelatedField();
            $toField = $rel->fromField;
            $value = $this->instance->{$fromField->getAttrName()};
            $filter[$toField->getName()] = $value;
            var_dump($filter);
            echo '<br>';
        endif;
        echo 'Theee model ==> '.$model.'<br>';
        $this->filters = $filter;

        parent::__construct($model);
    }

    public function getQueryset()
    {
        /** @var $qs Queryset */
        $qs = parent::getQueryset()->filter($this->filters);
        echo '<br>'.$qs->getSql().'<br>';
        if ($this->reverse == false) :

            $qs = $qs->get();
        endif;

        return $qs;
    }

}
