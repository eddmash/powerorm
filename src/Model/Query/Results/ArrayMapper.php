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

class ArrayMapper extends Mapper
{
    /**
     * @return \Eddmash\PowerOrm\Model\Model[]
     *
     * @internal param Models $model
     * @internal param array $results
     *
     * @since    1.1.0
     *
     * @author   Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function __invoke()
    {
        $connection = $this->queryset->connection;

        $sqlCompiler = $this->queryset->query->getSqlCompiler($connection);
        $resultsStatement = $sqlCompiler->executeSql($this->chunkedFetch);
        $colList = $sqlCompiler->query->valueSelect;

        $colList = array_merge($colList, array_keys($sqlCompiler->query->annotations));
        $resuts = [];
        foreach ($sqlCompiler->getResultsIterator($resultsStatement) as $result) {
            $resuts[] = array_combine($colList, $result);
        }

        return $resuts;
    }
}
