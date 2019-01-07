<?php
/**
 * Created by PhpStorm.
 * User: eddmash
 * Date: 3/20/17
 * Time: 7:38 PM.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */

namespace Eddmash\PowerOrm\Model\Manager;

use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Query\Queryset;

/**
 * Gets related data from the Many side of the relationship.
 *
 * user has many cars so this will query cars related to a particular user in
 * this the default attribute to be used will be ::
 *
 *  $car->user
 *
 * @return Queryset
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class M2OManager extends BaseM2OManager implements ManagerInterface
{
    public $reverse;

    public $instance;

    public $filters;

    /**
     * @var ForeignObjectRel
     */
    protected $relation;

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
        /* @var ForeignObjectRel $rel */
        $this->relation = $rel = ArrayHelper::getValue($kwargs, 'rel');

        if (false === $this->reverse) {
            $model = $rel->toModel;
            $fromField = $rel->fromField;
            $toField = $rel->getRelatedField();
            $value = $this->instance->{$fromField->getAttrName()};
            $filter[$toField->getAttrName()] = $value;
        } else {
            $model = $rel->getFromModel();
            //todo
            $fromField = $rel->getRelatedField();
            $toField = $rel->fromField;
            $value = $this->instance->{$fromField->getAttrName()};
            $filter[$toField->getName()] = $value;
        }

        $this->filters = $filter;

        parent::__construct($model);
    }

    public function getQueryset()
    {
        /** @var $qs Queryset */
        $qs = parent::getQueryset();

        return $qs->filter($this->filters);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return parent::getIterator();
    }
}
