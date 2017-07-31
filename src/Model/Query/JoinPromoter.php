<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Eddmash\PowerOrm\Model\Query;

use Eddmash\PowerOrm\Helpers\ArrayHelper;

class JoinPromoter
{
    public $votes = [];
    /**
     * @var mixed
     */
    private $connector;
    /**
     * @var int
     */
    private $count;
    private $currentNegated;

    /**
     * JoinPromoter constructor.
     *
     * @param mixed $connector
     * @param int   $count
     * @param $currentNegated
     */
    public function __construct($connector, $count, $currentNegated)
    {
        $this->connector = $connector;
        $this->count = $count;
        $this->currentNegated = $currentNegated;
    }

    public function addVotes($neededInner)
    {
        foreach ($neededInner as $need):
            $count = ArrayHelper::getValue($this->votes, $need, 0) + 1;
            $this->votes[$need] = $count;
        endforeach;
    }

    public function updateJoinType(Query $query)
    {
        return [];
    }
}
