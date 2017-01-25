<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/19/16
 * Time: 1:17 AM.
 */

namespace Eddmash\PowerOrm\Model\Lookup;

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Model;

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

    public function processLHS(Connection $connection)
    {

        return $this->lhs->getColumnName();
    }

    public function processRHS(Connection $connection)
    {
        if($this->rhs instanceof Model):
            // get pk field
            $pk = $this->rhs->meta->primaryKey->getAttrName();
            $this->rhs = $this->rhs->{$pk};
        elseif(method_exists($this->rhs, 'toSql')):
            list($sql, $params) = $this->rhs->toSql();

            return [sprintf('( %s )', $sql), $params];
        endif;

        return [' ? ', $this->rhs];
    }

    public function getLookupOperation($rhs)
    {
        if ($this->operator):

            return sprintf('%s %s', $this->operator, $rhs);
        endif;

        throw new NotImplemented('The no operator was given for the lookup');
    }

    public function asSql(Connection $connection)
    {
        $lhs_sql = $this->processLHS($connection);
        list($rhs_sql, $rhs_params) = $this->processRHS($connection);

        $rhs_sql = $this->getLookupOperation($rhs_sql);

        return [sprintf('%s %s', $lhs_sql, $rhs_sql), $rhs_params];

    }

    public function valueIsDirect()
    {
        return !(method_exists($this->rhs, 'toSql'));
    }
}
