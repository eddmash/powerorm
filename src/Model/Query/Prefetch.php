<?php

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query;

use Eddmash\PowerOrm\Exception\AttributeError;
use Eddmash\PowerOrm\Exception\FieldDoesNotExist;
use Eddmash\PowerOrm\Exception\KeyError;
use Eddmash\PowerOrm\Exception\ObjectDoesNotExist;
use Eddmash\PowerOrm\Exception\ValueError;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Model\Lookup\BaseLookup;
use Eddmash\PowerOrm\Model\Manager\ManagerInterface;
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
     * @param string        $lookups
     * @param Queryset|null $queryset
     * @param string|null   $toAttr
     *
     * @throws ValueError
     */
    public function __construct($lookups, Queryset $queryset = null, string $toAttr = null)
    {
        $this->prefetchTo = $lookups;
        $this->prefetchThrough = $lookups;

        // we are mapping this prefetches to models so ensure we have a Model mapper
        if ($queryset && !$queryset->getMapper() instanceof ModelMapper) {
            throw new ValueError('Prefetch querysets cannot use asArray().');
        }

        if ($toAttr) {
            //todo
            $this->prefetchTo = $toAttr;
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
     * @param array             $instances
     * @param PrefetchInterface $prefetcher
     * @param $lookup
     * @param $level
     *
     * @return array
     */
    public static function prefetchByLevel(array $instances, PrefetchInterface $prefetcher,
                                           Prefetch $lookup, $level): array
    {
        $additionaLookups = [];
        /** @var $relQs QuerysetInterface used to fetch related records */
        /** @var callable
         * a call back that returns the value of the related model, given a model
         * e.g. if we are fetching all roles belonging to a user, this callable will return the value of
         * user in the role model pass
         */
        /** @var callable
         * a callable that returns value of the instance, e.g. value of a user
         */
        $resp = $prefetcher->getPrefetchQueryset($instances, $lookup->getCurrentQueryset($level));
        list($relQs, $valOnRelatedCallable, $valOnInstanceCallable, $isMany, $cachename) = $resp;

        $relatedObjects = $relQs->getResults();

        $relMap = [];
        foreach ($relatedObjects as $relatedObject) {
            $relAttrVal = $valOnRelatedCallable($relatedObject);
            $relMap[$relAttrVal][] = $relatedObject;
        }

        list($toAttr, $hasAsAttr) = $lookup->getCurrentToAttr($level);
        /** @var $instance Model */
        foreach ($instances as $instance) {
            $instanceVal = $valOnInstanceCallable($instance);
            $vals = ArrayHelper::getValue($relMap, $instanceVal, []);

            if (!$isMany) {
                $val = $vals ? $vals[0] : null;
                if ($hasAsAttr) {
                    $instance->{$toAttr} = $val;
                } else {
                    // so we store using the cachename
                    $instance->_fieldCache[$cachename] = $val;
                }
            } else {
                if ($hasAsAttr) {
                    $instance->{$toAttr} = $vals;
                } else {
                    /** @var $manger ManagerInterface */
                    $manger = $instance->{$toAttr};
                    $qs = $manger->getQueryset();
                    $qs->_prefetchRelatedDone = true;
                    $qs->_resultsCache = $vals;
                    $instance->_prefetchedObjectCaches[$cachename] = $qs;
                }
            }
        }

        return [$relatedObjects, $additionaLookups];
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
            $descriptor = ArrayHelper::getValue($instance->_fieldCache, $throughAttr, null);
            $hasAttr = true;

            if ($descriptor) {
                // when we are moving from the many side to the one side
                // the descriptor implements PrefetchInterface
                // e.g. users has many cars
                // if we fetching the owner of a car i.e. car->owner
                if ($descriptor instanceof PrefetchInterface) {
                    $prefetcher = $descriptor;
                    // check if the value is already cached
                    // most likely by a selectRelated()
                    if ($prefetcher->isCached($instance)) {
                        $isFetched = true;
                    }
                } else {
                    // otherwise we go ahead and get the related manager,
                    // which will be used to perform queries and will also have
                    // implemented PrefetchInterface
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

    /**
     * Populate prefetched object caches for a list of model instances based on
     * the lookups/Prefetch instances given.
     *
     * @param Model[]        $instances
     * @param Prefetch|array $lookups
     *
     * @throws AttributeError
     * @throws KeyError
     * @throws ValueError
     */
    public static function prefetchRelatedObjects(array $instances, array $lookups)
    {
        if (!$instances) {
            return;
        }

        // We need to be able to dynamically add to the list of prefetchRelated
        // lookups that we look up (see below).  So we need some book keeping to
        // ensure we don't do duplicate work.
        $doneQueries = [];  // assoc_array of things like 'foo__bar': [results]
        $lookups = Prefetch::normalizePrefetchLookup($lookups);

        /* @var $lookup Prefetch */
        while ($lookups) {
            $lookup = array_shift($lookups);

            // have already worked on a lookup that has similar name
            if (array_key_exists($lookup->prefetchTo, $doneQueries)) {
                // does this lookup contain a queryset
                // this means its not a duplication but a different request just
                // containing the same name
                if ($lookup->queryset) {
                    throw new ValueError(
                        sprintf(
                            "'%s' lookup was already seen with a different queryset. ".
                            'You may need to adjust the ordering of your lookups.',
                            $lookup->prefetchTo
                        )
                    );
                }

                // just pass this is just a duplication
                continue;
            }

            $objList = $instances;

            $throughtAttrs = StringHelper::split(BaseLookup::$lookupPattern,
                $lookup->prefetchThrough);

            // we are going through the lookups e.g.
            // User::objects()->prefetchRelated(['roles__permission'])
            // level 0 = roles , level 1 = permissions, etc
            // $objList will change based on which level we are
            // level 0 = users , level 1 = roles , etc
            foreach ($throughtAttrs as $level => $throughtAttr) {
                if (!$objList) {
                    break;
                }

                $prefetchTo = $lookup->getCurrentPrefetchTo($level);

                if (array_key_exists($prefetchTo, $doneQueries)) {
                    try {
                        $objList = ArrayHelper::getValue($doneQueries, $prefetchTo, []);
                    } catch (KeyError $e) {
                    }

                    continue; //if its already fetched skip it
                }
                // we need to check if all the instances support prefetch
                $goodForPretch = true;
                foreach ($objList as $obj) {
                    if (!is_object($obj)) {
                        $goodForPretch = false;
                        break;
                    }
                }
                if (!$goodForPretch) {
                    break;
                }

                // we assume all objects are the same(homogeneous)
                // meaning whatever applies for one object applies for all
                $oneObject = $objList[0];
                list($toAttr) = $lookup->getCurrentPrefetchTo($level);

                // we try to get the PrefetchInterface implementation to use
                // when performing prefect queryset.
                list($prefetcher, $descriptor, $attrFound, $isFetched) = Prefetch::getPrefetcher(
                    $oneObject,
                    $throughtAttr,
                    $toAttr
                );

                if (!$attrFound) {
                    throw new AttributeError(
                        sprintf("Cannot find '%s' on %s object, '%s' is an invalid ".
                            'parameter to prefetch_related()', $throughtAttr,
                            $oneObject->getMeta()->getModelName()));
                }

                if ($prefetcher && !$isFetched) {
                    list($objList, $additionalLookups) = Prefetch::prefetchByLevel(
                        $objList, $prefetcher, $lookup, $level);
                } else {
                    // Either, the related object has already been fetched,
                    // most likely by a selectRelated() or hopefully some other property
                    // that doesn't support prefetching but needs to be traversed.

                    // we need to replace the $objList with the related objects
                    // this way we can proceed down the prefetch lookups

                    $newObjList = [];
                    foreach ($objList as $instance) {
                        // if the related objects have already been prefeched
                        // use the cache, instaed of doing another query
                        if (array_key_exists($throughtAttr, $instance->_prefetchedObjectCaches)) {
                            $newObj = ArrayHelper::getValue($throughtAttr, $instance->_prefetchedObjectCaches, []);
                        } else {
                            // try to get the value from the model
                            try {
                                $newObj = $instance->{$throughtAttr};
                            } catch (ObjectDoesNotExist $exception) {
                                continue;
                            }
                        }

                        if (!$newObj) {
                            continue;
                        }

                        if (is_array($newObj)) {
                            $newObjList = array_merge($newObjList, $newObj);
                        } else {
                            $newObjList[] = $newObj;
                        }
                    }
                    $objList = $newObjList;
                }
            }
        }
    }
}
