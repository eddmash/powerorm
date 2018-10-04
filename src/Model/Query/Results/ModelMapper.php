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

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Model\Model;
use Eddmash\PowerOrm\Model\Query\Expression\Col;

class ModelMapper extends Mapper
{
    /**
     * @return \Eddmash\PowerOrm\Model\Model[]
     *
     * @internal param Model $model
     * @internal param array $results
     *
     * @since    1.1.0
     *
     * @author   Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     */
    public function __invoke()
    {
        $connection = $this->queryset->connection;

        $sqlCompiler = $this->queryset->query->getSqlCompiler($connection);
        $resultsStatement = $sqlCompiler->executeSql($this->chunkedFetch);
        $klassInfo = $sqlCompiler->klassInfo;
        $select = $sqlCompiler->select;
        $annotationMap = $sqlCompiler->annotations;

        $selectedFields = $klassInfo['select_fields'];

        $initList = [];

        $modelFieldsStart = reset($selectedFields);
        $modelFieldsEnd = count($selectedFields);

        /* @var $col Col */
        foreach (array_slice($select, $modelFieldsStart, $modelFieldsEnd) as $colInfo) {
            $col = $colInfo[0];
            $initList[] = $col->getTargetField()->getAttrName();
        }

        /* @var $modelClass Model */
        $modelClass = ArrayHelper::getValue($klassInfo, 'model');
        $mapped = [];
        $relatedPopulators = static::getRelatedMapper($klassInfo, $select, $connection);

        foreach ($sqlCompiler->getResultsIterator($resultsStatement) as $result) {
            $vals = array_slice($result, $modelFieldsStart, $modelFieldsEnd);

            $obj = $modelClass::fromDb($connection, $initList, $vals);

            foreach ($annotationMap as $name => $pos) {
                $obj->{$name} = $result[$pos];
            }
            if ($relatedPopulators) {
                foreach ($relatedPopulators as $relatedPopulator) {
                    $relatedPopulator->populate($result, $obj);
                }
            }
            $mapped[] = $obj;
        }

        return $mapped;
    }

    /**
     * Creates mappers to use for related model.
     *
     * @param            $klassInfo
     * @param Connection $connection
     *
     * @return RelatedMappers[]
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Eddmash\PowerOrm\Exception\KeyError
     */
    public static function getRelatedMapper($klassInfo, $select, ConnectionInterface $connection)
    {
        $mappers = [];
        $relatedKlassInfo = ArrayHelper::getValue($klassInfo, 'related_klass_infos', []);
        foreach ($relatedKlassInfo as $klassInfo) {
            $mappers[] = new RelatedMappers($klassInfo, $select, $connection);
        }

        return $mappers;
    }
}
