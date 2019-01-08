<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests\Backends;

use Eddmash\PowerOrm\Backends\ConnectionInterface;
use Eddmash\PowerOrm\Backends\Operations\OperationsInterface;
use Eddmash\PowerOrm\Backends\SchemaEditor;

class ConnectionMock extends DbalConnectionMock implements ConnectionInterface
{
    /**
     * @return OperationsInterface
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getOperations(): OperationsInterface
    {
        // TODO: Implement getOperations() method.
    }

    /**
     * @return BaseFeatures
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getFeatures()
    {
        // TODO: Implement getFeatures() method.
    }

    /**
     * @param bool $getSql
     *
     * @return SchemaEditor
     */
    public function getSchemaEditor($getSql = false)
    {
        // TODO: Implement getSchemaEditor() method.
    }
}
