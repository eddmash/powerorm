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

use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Model\Lookup\BaseLookup;
use Eddmash\PowerOrm\Model\Query\Results\ModelMapper;

class Prefetch
{
    /**
     * @var string path to the attribute that stores the result
     */
    public $prefetchTo;
    /**
     * @var string path we traverse to perform the prefetch
     */
    public $prefetchThrough;

    /**
     * @var Queryset
     */
    public $queryset;
    /**
     * @var null
     */
    private $toAttr;

    /**
     * Prefetch constructor.
     *
     * @param string        $lookups
     * @param Queryset|null $queryset
     * @param string|null   $toAttr
     *
     * @throws ValueError
     */
    public function __construct($lookups, Queryset $queryset = null, $toAttr = null)
    {
        $this->prefetchTo = $lookups;
        $this->prefetchThrough = $lookups;

        // we are mapping this prefetches to models so ensure we have an Model mapper
        if (!is_null($queryset) && !is_subclass_of($queryset->getMapper(), ModelMapper::class)):
            throw new ValueError('Prefetch querysets cannot use values().');
        endif;
        $this->queryset = $queryset;

        if ($toAttr):
            //            $this->prefetchTo =
        endif;
        $this->toAttr = $toAttr;
    }

    public function addPrefix($prefix)
    {
    }

    /**
     * Returns the name to map the prefetch values to.
     * we go from top level e.g. entry__authors__user
     * so we start entry,
     * then entry__authors
     * then entry__authors__user.
     *
     * @param $level
     *
     * @return string
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getCurrentPrefetchTo($level)
    {
        $lookupNames = StringHelper::split(BaseLookup::$lookupPattern, $this->prefetchTo);
        $lookupNames = array_slice($lookupNames, 0, $level + 1);

        return implode(BaseLookup::LOOKUP_SEPARATOR, $lookupNames);
    }
}
