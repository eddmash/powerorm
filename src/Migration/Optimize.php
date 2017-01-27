<?php

/*
* This file is part of the ci306 package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration;

use Eddmash\PowerOrm\Migration\Operation\Operation;

/**
 * Powers the optimization process,.
 *
 * where you provide a list of Operations and you are returned a list of equal or shorter length - operations
 * are merged into one if possible.
 *
 * For example, a CreateModel and an AddField can be optimized into a new CreateModel, and
 * CreateModel and DeleteModel can be optimized into nothing.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Optimize
{
    /**
     * Main optimization entry point. Pass in a list of Operation instances, get out a new list of Operation instances.
     *
     * Unfortunately, due to the scope of the optimization (two combinable operations might be separated by several
     * hundred others), this can't be done as a peephole optimization with checks/output implemented on
     * the Operations themselves; instead, the optimizer looks at each individual operation and scans forwards in
     * the list to see if there are any matches, stopping at boundaries - operations which can't  be optimized over
     * (RunSQL, operations on the same field/model, etc.).
     *
     * The inner loop is run until the starting list is the same as the result list, and then the result is returned.
     * This means that operation optimization must be stable and always return an equal or shorter list.
     *
     * @param $operations
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function run($operations)
    {
        while (true):
            $results = self::_optimize($operations);
            if ($results == $operations):
                return $results;
            endif;
            $operations = $results;
        endwhile;
    }

    /**
     * Inner optimization loop.
     *
     * @param $operations
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function _optimize($operations)
    {
        $newOperations = [];
        /** @var $outOperation Operation */
        foreach ($operations as $outIndex => $outOperation) :
            $inOperations = array_slice($operations, $outIndex + 1);

            echo PHP_EOL.PHP_EOL;
            if ($inOperations) :
                foreach ($inOperations as $inIndex => $inOperation) :
                    // get how many items to fetch
                    $places = ($outIndex - ($inIndex + 1));
                    if ($places < 0) :
                        $places = 1;
                    endif;

                    $inBetween = array_slice($operations, $outIndex + 1, $places);
                    $inBetween = array_slice($inBetween, 0, -1);

                    $result = $outOperation->reduce($inOperation, $inBetween);

                    if ($result) :

                        // add the result of the two merging
                        $newOperations = array_merge($newOperations, $result);
                        // add points that fell in between those that merged
                        $newOperations = array_merge($newOperations, $inBetween);
                        // add points that come after
                        $newOperations = array_merge($newOperations, array_slice($operations, $outIndex + $inIndex + 2));

                        return $newOperations;
                    else:
                        $newOperations[] = $outOperation;
                        break;
                    endif;

                endforeach;
            else:
                $newOperations[] = $outOperation;
            endif;

        endforeach;

        return $newOperations;
    }
}
