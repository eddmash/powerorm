<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration;

/**
 * The base class for all migrations.
 *
 * Migration files will import this from Eddmash\PowerOrm\Migration\Migration and subclass it as a class
 * called Migration.
 *
 * It will have one or more of the following attributes:
 * - getOperations: An array of Operation instances, probably from Eddmash\PowerOrm\Migration\Migration\Operation.
 * - getDependency: An array of strings of (migration_name)
 *
 * Note that all migrations come out of migrations and into the Loader or Graph as instances, having been
 * initialized with their app name.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
interface MigrationInterface
{
    /**
     * Operations to apply during this migration, in order.
     *
     * @return mixed
     */
    public function getOperations();

    /**Other migrations that should be run before this migration.
     * Should be a array of (migration_name).
     * @return mixed
     */
    public function getDependency();
}
