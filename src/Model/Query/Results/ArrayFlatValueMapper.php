<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Query\Results;

class ArrayFlatValueMapper extends Mapper
{
    public function __invoke()
    {
        $sqlCompiler = $this->queryset->query->getSqlCompiler($this->queryset->connection);
        $results = $sqlCompiler->executeSql();

        $values = [];
        foreach ($sqlCompiler->getResultsIterator($results) as $result) {
            foreach ($result as $name => $item) {
                $values[] = $item;
            }
        }

        return $values;
    }
}
