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
use const Eddmash\PowerOrm\Model\Query\Expression\AND_CONNECTOR;
use const Eddmash\PowerOrm\Model\Query\Expression\OR_CONNECTOR;

class JoinPromoter
{
    public $votes = [];

    public $effectiveConnector;

    /**
     * @var mixed
     */
    private $connector;

    /**
     * @var int
     */
    private $childrenCount;

    private $currentNegated;

    /**
     * JoinPromoter constructor.
     *
     * @param mixed $connector
     * @param int   $childrenCount
     * @param       $currentNegated
     */
    public function __construct($connector, $childrenCount, $currentNegated)
    {
        $this->connector = $connector;
        $this->currentNegated = $currentNegated;

        if ($this->currentNegated) {
            if (AND_CONNECTOR === $this->connector) {
                $this->effectiveConnector = OR_CONNECTOR;
            } else {
                $this->effectiveConnector = AND_CONNECTOR;
            }
        } else {
            $this->effectiveConnector = $this->connector;
        }
        $this->childrenCount = $childrenCount;
    }

    public function addVotes($neededInner)
    {
        foreach ($neededInner as $need) {
            $count = ArrayHelper::getValue($this->votes, $need, 0) + 1;
            $this->votes[$need] = $count;
        }
    }

    public function updateJoinType(Query $query)
    {
        $changeToInnerJoin = [];
        $changeToOuterJoin = [];

        foreach ($this->votes as $table => $votes) {
            if (OR_CONNECTOR == $this->effectiveConnector && $votes < $this->childrenCount) {
                $changeToOuterJoin[] = $table;
            }

            if (AND_CONNECTOR === $this->effectiveConnector ||
                (OR_CONNECTOR == $this->effectiveConnector && $votes === $this->childrenCount)
            ) {
                $changeToInnerJoin[] = $table;
            }
        }

        $query->changeToInnerjoin($changeToInnerJoin);
        $query->changeToOuterJoin($changeToOuterJoin);

        return $changeToInnerJoin;
    }
}
