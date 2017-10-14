<?php

/**
 * This file is part of the ci304 package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Eddmash\PowerOrm\Model\Query\Results;

use Eddmash\PowerOrm\Model\Query\Queryset;

class Mapper
{
    /**
     * @var Queryset
     */
    protected $queryset;

    protected $columnInfoCache = [];
    /**
     * @var bool
     */
    protected $chunkedFetch;

    /**
     * Mapper constructor.
     */
    public function __construct(Queryset $queryset, $chunkedFetch=false)
    {
        $this->queryset = $queryset;
        $this->chunkedFetch = $chunkedFetch;
    }

}
