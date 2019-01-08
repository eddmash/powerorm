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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class DatabasePlatformMock extends AbstractPlatform
{
    /** @var string */
    private $sequenceNextValSql = '';

    /** @var bool */
    private $prefersIdentityColumns = true;

    /** @var bool */
    private $prefersSequences = false;

    public function prefersIdentityColumns()
    {
        return $this->prefersIdentityColumns;
    }

    public function prefersSequences()
    {
        return $this->prefersSequences;
    }

    public function getSequenceNextValSQL($sequenceName)
    {
        return $this->sequenceNextValSql;
    }

    /**
     * {@inheritdoc}
     */
    public function getBooleanTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getIntegerTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBigIntTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getVarcharTypeDeclarationSQL(array $field)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getClobTypeDeclarationSQL(array $field)
    {
    }

    /* MOCK API */

    /**
     * @param bool $prefersIdentityColumns
     */
    public function setPrefersIdentityColumns($prefersIdentityColumns)
    {
        $this->prefersIdentityColumns = $prefersIdentityColumns;
    }

    public function setPrefersSequences($bool)
    {
        $this->prefersSequences = $bool;
    }

    public function setSequenceNextValSql($sql)
    {
        $this->sequenceNextValSql = $sql;
    }

    public function getName()
    {
        return 'mock';
    }

    protected function initializeDoctrineTypeMappings()
    {
    }

    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBlobTypeDeclarationSQL(array $field)
    {
        throw DBALException::notSupported(__METHOD__);
    }
}
