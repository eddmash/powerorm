<?php
/**
 * This file is part of the store
 *
 *
 * Created by : Eddilber Macharia (edd.cowan@gmail.com)<eddmash.com>
 * On Date : 1/11/19 4:09 PM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Tests;


use Eddmash\PowerOrm\App\Settings;

abstract class AppAwareTest extends PowerormTest
{

    protected $registry;

    protected function setUp(): void
    {
        parent::setUp();


        $this->orm = $this->getNewOrm(new Settings(
            [
                'components' => $this->getComponents(),
            ]
        ));

        $this->registry = $this->orm->getRegistryCache();
    }

    protected abstract function getComponents(): array;
}