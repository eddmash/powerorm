<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/9/19
 * Time: 10:59 PM.
 */

namespace Eddmash\PowerOrm\Tests\Model;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Tests\TestApp\Models\MetaTestUser;
use PHPUnit\Framework\TestCase;

class MetaTest extends TestCase
{
    /**
     * Checks if relatedName is being user on foreignkeys on reverse relation.
     *
     * @throws \Eddmash\PowerOrm\Exception\AppRegistryNotReady
     * @throws \Eddmash\PowerOrm\Exception\LookupError
     */
    public function testReverseForeignKeyFields()
    {
        $fields = BaseOrm::getRegistry()
            ->getModel(MetaTestUser::class)
            ->getMeta()
            ->getReverseOnlyField();

        $names = array_keys($fields);
        self::assertCount(2, $fields);
        self::assertEquals(['metatestprofile_set', 'updatedby'], $names);
    }
}
