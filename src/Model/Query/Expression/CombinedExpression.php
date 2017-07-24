<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query\Expression;

use Eddmash\PowerOrm\Model\Field\Field;

class CombinedExpression extends BaseExpression
{
    private $lhs;
    private $connector;
    private $rhs;

    /**
     * {@inheritdoc}
     */
    public function __construct($lhs, $connector, $rhs, Field $outputField = null)
    {
        parent::__construct($outputField);
        $this->lhs = $lhs;
        $this->connector = $connector;
        $this->rhs = $rhs;
    }

}
