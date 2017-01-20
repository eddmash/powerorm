<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/19/16
 * Time: 1:17 AM.
 */
namespace Eddmash\PowerOrm\Model\Lookup;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Queryset;

/**
 * Class Filter.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class BaseLookup implements LookupInterface
{
    const AND_CONNECTOR = 'and';
    const OR_CONNECTOR = 'or';
    const LOOKUP_SEPARATOR = '__';
    public static $lookupName = null;

    public static $lookupPattern = '/(?<=\w)__[!?.]*/';
    public static $whereConcatPattern = '/(?<=^~)/';
    protected $rhs;

    protected $operator;

    /**
     * @var Field
     */
    protected $lhs;

    public function __construct($lhs, $rhs)
    {
        $this->rhs = $rhs;
        $this->lhs = $lhs;
    }

    public static function createObject($rhs, $lhs)
    {
        return new static($rhs, $lhs);
    }

    public function processLHS(Connection $connection, QueryBuilder $queryBuilder)
    {

        return $this->lhs->getColumnName();
    }

    public function processRHS(Connection $connection, QueryBuilder $queryBuilder)
    {
        if($this->rhs instanceof Model):
            $this->rhs = $this->rhs->id;
        elseif($this->rhs instanceof Queryset):
            return $this->rhs->toSql($connection);
        endif;

        return $queryBuilder->createNamedParameter($this->rhs);
    }

    public function getLookupOperation($rhs)
    {
        if ($this->operator):

            return sprintf('%s %s', $this->operator, $rhs);
        endif;

        throw new NotImplemented('The no operator was given for the lookup');
    }

    public function asSql(Connection $connection, QueryBuilder $queryBuilder)
    {
        $lhs_sql = $this->processLHS($connection, $queryBuilder);
        $rhs_sql = $this->processRHS($connection, $queryBuilder);

        $rhs_sql = $this->getLookupOperation($rhs_sql);

        return sprintf('%s %s', $lhs_sql, $rhs_sql);

    }
}
