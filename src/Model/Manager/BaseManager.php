<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Manager;

use Eddmash\PowerOrm\BaseObject;
use Eddmash\PowerOrm\Exception\TypeError;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Queryset;

class BaseManager extends BaseObject implements ManagerInterface
{
    /**
     * @var Model
     */
    public $model;

    /**
     * {@inheritdoc}
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @return Queryset
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function getQueryset()
    {
        return Queryset::createObject(null, $this->model);
    }

    /**
     * {@inheritdoc}
     */
    public function __call($name, $arguments)
    {
        if (!method_exists($this, $name)) {
            return call_user_func_array([$this->getQueryset(), $name], $arguments);
        }
    }

    public function __toString()
    {
        return sprintf('%s object', get_class($this));
    }

    public function getIterator()
    {
        throw new TypeError("'Manager' object is not iterable");
    }
}
