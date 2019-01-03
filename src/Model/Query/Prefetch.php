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

use Eddmash\PowerOrm\Exception\FieldDoesNotExist;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Model\Lookup\BaseLookup;
use Eddmash\PowerOrm\Model\Model;
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
     * @param string $lookups
     * @param Queryset|null $queryset
     * @param string|null $toAttr
     *
     * @throws ValueError
     */
    public function __construct($lookups, Queryset $queryset = null, string $toAttr = null)
    {
        $this->prefetchTo = $lookups;
        $this->prefetchThrough = $lookups;

        // we are mapping this prefetches to models so ensure we have an Model mapper
        if (!is_null($queryset) &&
            !is_subclass_of($queryset->getMapper(), ModelMapper::class)) {
            throw new ValueError('Prefetch querysets cannot use asArray().');
        }

        if ($toAttr) {
            //todo
            //            $this->prefetchTo =
        }
        $this->queryset = $queryset;

        $this->toAttr = $toAttr;
    }

    public function addPrefix($prefix)
    {
    }

    /**
     * Perform the actual fetching of related objects by level.
     *
     * @param array $objList
     * @param PrefetchInterface $prefetcher
     * @param $lookup
     * @param $level
     *
     * @return array
     */
    public static function prefetchByLevel(array $objList, PrefetchInterface $prefetcher,
                                           Prefetch $lookup, $level): array
    {
        /** @var $relQs QuerysetInterface */
        list($relQs) = $prefetcher->getPrefetchQueryset($objList,
            $lookup->getCurrentQueryset($level));

        $relatedObjects = $relQs->getSql();
//        foreach ($relatedObjects as $relatedObject) {
//
//        }
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

    /**
     * This is the attribute to which the values will be placed.
     *
     * @param $level
     *
     * @return array
     */
    public function getCurrentToAttr($level)
    {
        $lookupNames = StringHelper::split(BaseLookup::$lookupPattern, $this->prefetchTo);
        $toAttr = $lookupNames[$level];
        $hasAsAttr = $this->toAttr && $level === count($lookupNames) - 1;
        return [$toAttr, $hasAsAttr];
    }

    /**
     * For the provided '$throughAttr' finds the PrefetchInterface implementation to
     * be used to perform prefetching queryset, for the the instance provided.
     *
     * @param Model $instance
     * @param $throughAttr
     * @param $toAttr
     *
     * @return array Return a 4 item array containing:
     *               - (the object with get_prefetch_queryset (or None),
     *               - the descriptor object representing this relationship (or None),
     *               - a boolean that is False if the attribute was not found at all,
     *               - a boolean that is True if the attribute has already been fetched)
     */
    public static function getPrefetcher(Model $instance, $throughAttr, $toAttr): array
    {
        /** @var $prefetcher PrefetchInterface */
        $prefetcher = null;
        $isFetched = false;

        // try getting the descriptor from the meta of the instance to avoid
        // invoking queries
        try {
            $descriptor = $instance->_fieldCache[$throughAttr];
            $hasAttr = true;

            if ($descriptor) {
                // this is normally descriptors that return a single item.
                if ($descriptor instanceof PrefetchInterface) {
                    $prefetcher = $descriptor;
                    if ($prefetcher->isCached($instance)) {
                        $isFetched = true;
                    }
                } else {
                    // we go ahead and get the related manager, which will be used
                    // to perform queries
                    $relObj = $instance->{$throughAttr};
                    if ($relObj instanceof PrefetchInterface) {
                        $prefetcher = $relObj;
                    }

                    if ($throughAttr !== $toAttr) {
                        $isFetched = property_exists($instance, $toAttr);
                    } else {
                        $isFetched = array_key_exists($throughAttr, $instance->_prefetchedObjectCaches);
                    }
                }
            }
        } catch (FieldDoesNotExist $e) {
            $hasAttr = property_exists($instance, $throughAttr);
        }

        return [$prefetcher, $descriptor, $hasAttr, $isFetched];
    }

    /**
     * Ensures all prefetch looks are of the same form i.e. instance of Prefetch.
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @param      $lookups
     * @param null $prefix
     *
     * @return Prefetch[]
     * @throws ValueError
     */
    public static function normalizePrefetchLookup($lookups, $prefix = null): array
    {
        $results = [];
        foreach ($lookups as $lookup) {
            if (!$lookup instanceof Prefetch) {
                $lookup = new Prefetch($lookup);
            }
            if ($prefix) {
                $lookup->addPrefix($prefix);
            }
            $results[] = $lookup;
        }

        return $results;
    }

    /**
     * @param $level
     *
     * @return Queryset|null
     */
    public function getCurrentQueryset($level): ?Queryset
    {
        if ($this->prefetchTo === $this->getCurrentPrefetchTo($level)) {
            return $this->queryset;
        }
        return null;
    }
}
