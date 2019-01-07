<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query\Results;

class ArrayValueMapper extends Mapper
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
        $sqlCompiler = $this->queryset->query->getSqlCompiler($this->queryset->connection);
        $results = $sqlCompiler->executeSql();

        $values = [];
        foreach ($sqlCompiler->getResultsIterator($results) as $result) {
            $vals = [];
            foreach ($result as $name => $item) {
                $vals[] = $item;
            }
            $values[] = $vals;
        }

        return $values;
    }
}
