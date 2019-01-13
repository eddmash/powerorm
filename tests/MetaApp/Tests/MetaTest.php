<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 1/9/19
 * Time: 10:59 PM.
 */

namespace Eddmash\PowerOrm\Tests\MetaApp\Tests;

use Eddmash\PowerOrm\Tests\AppAwareTest;
use Eddmash\PowerOrm\Tests\MetaApp\MetaApp;
use Eddmash\PowerOrm\Tests\MetaApp\Models\MetaTestUser;

class MetaTest extends AppAwareTest
{
    /**
     * Checks if relatedName is being user on foreignkeys on reverse relation.
     *
     * @throws \Eddmash\PowerOrm\Exception\AppRegistryNotReady
     * @throws \Eddmash\PowerOrm\Exception\LookupError
     */
    public function testReverseForeignKeyFields()
    {
        $fields = $this->registry
            ->getModel(MetaTestUser::class)
            ->getMeta()
            ->getReverseOnlyField();

        $names = array_keys($fields);
        self::assertCount(2, $fields);
        self::assertEquals(['metatestprofile', 'updatedby'], $names);
    }

    protected function getComponents(): array
    {
        return [MetaApp::class];
    }
}
