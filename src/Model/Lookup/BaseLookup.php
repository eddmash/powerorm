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
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Expression\Col;
use Eddmash\PowerOrm\Model\ToSqlInterface;

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
    public $prepareRhs = true;
    protected $rhs;

    protected $operator;

    /**
     * @var Col
     */
    protected $lhs;
    protected $rhsValueIsIterable = false;

    public function __construct($lhs, $rhs)
    {
        $this->rhs = $rhs;
        $this->lhs = $lhs;

        $this->rhs = $this->prepareLookup();

    }

    public static function createObject($rhs, $lhs)
    {
        return new static($rhs, $lhs);
    }

    public function processLHS(Connection $connection)
    {
        return $this->lhs->asSql($connection);
    }

    /**
     * Preperes the rhs for use in the lookup.
     *
     * @return mixed
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function prepareLookup()
    {
        if ($this->rhsValueIsIterable) :

            $preparedValues = [];
            foreach ($this->rhs as $rh) :
                if ($this->prepareRhs && method_exists($this->lhs->getOutputField(), 'prepareValue')):

                    $preparedValues[] = $this->lhs->getOutputField()->prepareValue($rh);
                endif;
            endforeach;

            return $preparedValues;
        else:
            if(method_exists($this->rhs, '_prepare')):
                return $this->rhs->_prepare($this->lhs->getOutputField());
            endif;
            if ($this->prepareRhs && method_exists($this->lhs->getOutputField(), 'prepareValue')):
                return $this->lhs->getOutputField()->prepareValue($this->rhs);
            endif;
        endif;

        // it might be this is just a pure php value
        return $this->rhs;

    }

    /**
     * Prepare the rhs for use on database queries.
     *
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function prepareLookupForDb($values, Connection $connection)
    {
        $preparedValues = [];
        if ($this->rhsValueIsIterable) :

            foreach ($values as $value) :
                $preparedValues[] = $this->lhs->getOutputField()->convertToDatabaseValue($value, $connection);
            endforeach;
        else:
            $preparedValues[] = $this->lhs->getOutputField()->convertToDatabaseValue($values, $connection);
        endif;

        return $preparedValues;

    }

    public function processRHS(Connection $connection)
    {
        if ($this->rhs instanceof Model):
            // get pk field
            $pk = $this->rhs->meta->primaryKey->getAttrName();
            $this->rhs = $this->rhs->{$pk};
        elseif (method_exists($this->rhs, '_toSql')):
            list($sql, $params) = $this->rhs->_toSql();

            return [sprintf('( %s )', $sql), $params];
        elseif ($this->rhs instanceof ToSqlInterface):
            list($sql, $params) = $this->rhs->asSql($connection);

            return [sprintf('( %s )', $sql), $params];
        endif;

        return ['?', $this->prepareLookupForDb($this->rhs, $connection)];
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
        list($lhs_sql, $params) = $this->processLHS($connection);
        list($rhs_sql, $rhs_params) = $this->processRHS($connection);

        $params = array_merge($params, $rhs_params);
        $rhs_sql = $this->getLookupOperation($rhs_sql);

        return [sprintf('%s %s', $lhs_sql, $rhs_sql), $params];
    }

    public function valueIsDirect()
    {
        return !(method_exists($this->rhs, '_toSql'));
    }

    public function __toString()
    {
        return get_class($this);
    }
}
