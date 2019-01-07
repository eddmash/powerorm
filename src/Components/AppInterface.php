<?php
/**
 *
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Components;

interface AppInterface extends ComponentInterface
{
    public function getNamespace();

    public function getModelsPath();

    public function getMigrationsPath();

    public function getDbPrefix();
}
