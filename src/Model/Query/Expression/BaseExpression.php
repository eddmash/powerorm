<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model\Query\Expression;

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\ToSqlInterface;

class BaseExpression extends ToSqlInterface implements ResolvableExpInterface
{
    /**
     * @var Field
     */
    protected $outputField;

    /**
     * BaseExpression constructor.
     *
     * @param Field $outputField
     */
    public function __construct(Field $outputField = null)
    {
        $this->outputField = $outputField;
    }

    public function asSql(Connection $connection)
    {
        throw new NotImplemented('Subclasses must implement asSql()');
    }

    public function getLookup($lookup)
    {
        return $this->outputField->getLookup($lookup);
    }

    /**
     * @return Field
     */
    public function getOutputField()
    {
        return $this->outputField;
    }

    /**
     * Hook used by Lookup.prepareLookup() to do custom preparation.
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function _prepare() {
        return $this;
    }

    /**
     * Provides the chance to do any preprocessing or validation before being added to the query.e.g.
     * in Exp::Count('username') we need the username to converted to an actual model field.
     *
     * @param ExpResolverInterface $resolver
     * @param bool                 $allowJoins
     * @param null                 $reuse
     * @param bool                 $summarize
     * @param bool                 $forSave
     *
     * @internal param null $query
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     */
    public function resolveExpression(ExpResolverInterface $resolver, $allowJoins = true, $reuse = null, $summarize =
    false, $forSave = false)
    {

    }

}
