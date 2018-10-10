<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 10/9/18
 * Time: 5:30 AM.
 */

namespace Eddmash\PowerOrm\Tests\Db;

use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Db\SchemaEditor;

class ConnectionMock extends DbalConnectionMock implements ConnectionInterface
{
    /**
     * @return OperationsInterface
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getOperations()
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
