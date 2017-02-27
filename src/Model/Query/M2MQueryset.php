<?php
/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Eddmash\PowerOrm\Model\Query;

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Field\RelatedObjects\ForeignObjectRel;
use Eddmash\PowerOrm\Model\Model;

/**
 * Class M2MQueryset
 * @package Eddmash\PowerOrm\Model\Query
 * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 */
class M2MQueryset extends ParentQueryset
{
    private $instance;
    private $field;

    public function __construct(Connection $connection = null, Model $model = null, Query $query = null, $kwargs=[])
    {
        $this->instance = ArrayHelper::getValue($kwargs, "instance");

        /**@var ForeignObjectRel $rel */
        $rel = ArrayHelper::getValue($kwargs, "rel");
        parent::__construct(null, $rel->getFromModel());
        $this->field = $rel->fromField;
    }

    public static function createObject(
        Connection $connection = null,
        Model $model = null,
        Query $query = null,
        $kwargs=[]
    ) {
        return new static($connection, $model, $query, $kwargs=[]);
    }

    public function add()
    {
        func_get_args();
    }

}