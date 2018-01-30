<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/29/18
 * Time: 10:25 PM.
 */

namespace Eddmash\PowerOrm\Tests\TestModels;

class AbstractWithFieldsBaseProxyModel extends AbstractModel
{
    public function getMetaSettings()
    {
        return [
            'proxy' => true,
        ];
    }
}
