<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Db;

use Eddmash\PhpGis\Db\Backends\Features\BaseFeatures;
use Eddmash\PhpGis\Db\Backends\Operations\BaseOperations;

class Connection extends \Doctrine\DBAL\Connection implements ConnectionInterface
{
    /**
     * @return \Eddmash\PhpGis\Db\Backends\Operations\OperationsInterface|void
     */
    public function getOperations()
    {
    }

    public function getFeatures()
    {
    }

    /**
     * @return SchemaEditor
     */
    public function getSchemaEditor($getSql = false)
    {
        return SchemaEditor::createObject($this, $getSql);
    }
}
