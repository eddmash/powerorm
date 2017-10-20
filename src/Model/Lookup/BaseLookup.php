<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/19/16
 * Time: 1:17 AM.
 */

namespace Eddmash\PowerOrm\Model\Lookup;

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Exception\AttributeError;
use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Compiler\CompilerInterface;
use Eddmash\PowerOrm\Model\Query\Expression\BaseExpression;
use Eddmash\PowerOrm\Model\Query\Expression\Col;

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

    /**
     * @param $value
     * @param BaseExpression $lhs
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private static function getNomalizedValue($value, $lhs)
    {
        if ($value instanceof Model):
            $path = $lhs->getOutputField()->getPathInfo();
            $sources = end($path)['targetFields'];

            /** @var $source Field */
            foreach ($sources as $source) :

                while (!$value instanceof $source->scopeModel && $source->relation):
                    $name = $source->relation->getName();
                    $source = $source->relation->getFromModel()->meta->getField($name);
                endwhile;

                try {
                    return $value->{$source->getAttrName()};
                } catch (AttributeError $attributeError) {
                    return $value->pk;
                }
            endforeach;
        endif;

        return $value;
    }

    public function processLHS(CompilerInterface $compiler, Connection $connection)
    {
        return $compiler->compile($this->lhs);
    }

    /**
     * Preperes the rhs for use in the lookup.
     *
     * @return mixed
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function prepareLookup()
    {
        $value = $this->rhs;
        if ($this->rhsValueIsIterable) :

            $preparedValues = [];
            foreach ($this->rhs as $rh) :
                if ($this->prepareRhs && method_exists($this->lhs->getOutputField(), 'prepareValue')):

                    $preparedValues[] = $this->lhs->getOutputField()->prepareValue($rh);
                endif;
            endforeach;

            return $preparedValues;
        else:
            $this->rhs = static::getNomalizedValue($value, $this->lhs);
            if (method_exists($this->rhs, '_prepare')):
                return $this->rhs->_prepare($this->lhs->getOutputField());
            endif;

            if ($this->prepareRhs && method_exists($this->lhs->getOutputField(), 'prepareValue')):
                return $this->lhs->getOutputField()->prepareValue($this->rhs);
            endif;
        endif;

        // it might be, this is just a pure php value
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

    public function processRHS(CompilerInterface $compiler, Connection $connection)
    {
        $value = $this->rhs;
        if (method_exists($value, 'getSqlCompiler')):
            $value = $value->getSqlCompiler($connection);
        endif;

        if (method_exists($value, 'asSql')):
            list($sql, $params) = $compiler->compile($value);

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

    public function asSql(CompilerInterface $compiler, Connection $connection)
    {
        list($lhs_sql, $params) = $this->processLHS($compiler, $connection);
        list($rhs_sql, $rhs_params) = $this->processRHS($compiler, $connection);

        $params = array_merge($params, $rhs_params);
        $rhs_sql = $this->getLookupOperation($rhs_sql);

        return [sprintf('%s %s', $lhs_sql, $rhs_sql), $params];
    }

    public function valueIsDirect()
    {
        return !(
            method_exists($this->rhs, 'getCompiler') &&
            method_exists($this->rhs, '_toSql')
        );
    }

    public function __toString()
    {
        return get_class($this);
    }
}
