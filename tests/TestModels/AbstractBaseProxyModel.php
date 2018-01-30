<?php

namespace Eddmash\PowerOrm\Tests\TestModels;

/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/29/18
 * Time: 10:26 PM.
 */
class AbstractBaseProxyModel extends \Eddmash\PowerOrm\Model\Model
{
    public function getMetaSettings()
    {
        return [
            'proxy' => true,
        ];
    }
}
