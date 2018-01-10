<?php
/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Migration\Operation\Model;

use Eddmash\PowerOrm\Migration\Operation\Operation;

abstract class ModelOperation extends Operation
{
    public $name;

    public function referencesModel($modelName)
    {
        return strtolower($this->name) === strtolower($modelName);
    }

    public function __debugInfo()
    {
        $arr = parent::__debugInfo();
        $arr['name'] = $this->name;

        return $arr;
    }
}
