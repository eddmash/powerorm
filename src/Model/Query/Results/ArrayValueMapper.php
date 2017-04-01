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

class ArrayValueMapper extends Mapper
{
    /**
     * @return \Eddmash\PowerOrm\Model\Model[]
     *
     * @internal param Model $model
     * @internal param array $results
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function __invoke()
    {
        $results = $this->queryset->query->execute($this->queryset->connection)->fetchAll();
        $values = [];
        foreach ($results as $result) :
            $values[] = end($result);
        endforeach;

        return $values;
    }
}
