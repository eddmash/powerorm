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
use Eddmash\PowerOrm\Model\Field\Field;

class BaseExpression
{
    /**
     * @var Field
     */
    private $outputField;

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
        return '';
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

}
