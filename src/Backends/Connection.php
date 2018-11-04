<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Backends;

use Eddmash\PowerOrm\Backends\Operations\OperationsInterface;

class Connection extends \Doctrine\DBAL\Connection implements ConnectionInterface
{
    /**
     * @return OperationsInterface
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getOperations(): OperationsInterface
    {
        $name = sprintf("Eddmash\PowerOrm\Backends\Operations\%s",
            ucfirst($this->getDatabasePlatform()->getName()));

        return new $name();
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
