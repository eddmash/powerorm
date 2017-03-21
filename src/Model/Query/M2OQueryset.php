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
namespace Eddmash\PowerOrm\Model\Query;

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Model;

/**
 * Class M2OQueryset.
 *
 * @return Queryset
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class M2OQueryset extends ParentQueryset
{
    /**
     * {@inheritdoc}
     */
    public function __construct(Connection $connection = null, Model $model = null, Query $query = null, $kwargs = []) {

        $this->instance = ArrayHelper::getValue($kwargs, 'instance');

        /** @var ForeignObjectRel $rel */
        $rel = ArrayHelper::getValue($kwargs, 'rel');

        $model = $rel->getFromModel();
        $this->field = $rel->fromField;

        parent::__construct(null, $model, null, $kwargs);
    }

}
