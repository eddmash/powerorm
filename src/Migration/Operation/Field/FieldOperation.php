<?php
/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Eddmash\PowerOrm\Migration\Operation\Field;

use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Model\Field\Field;

abstract class FieldOperation extends Operation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $modelName;
    /**
     * @var Field
     */
    public $field;

    /**
     * @var bool
     */
    public $preserveDefault = false;

    public function referencesModel($modelName)
    {
        return $this->modelName === $modelName;
    }
}
