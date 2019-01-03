<?php
/**
 * Created by PhpStorm.
 * User: eddmash
 * Date: 3/20/17
 * Time: 7:38 PM.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */

namespace Eddmash\PowerOrm\Model\Manager;

use Eddmash\PowerOrm\Model\Query\Queryset;

/**
 * Gets related data from the one side of the relationship.
 *
 * user has many cars so this will query cars related to a particular user in
 * this the default attribute to be used will be ::
 *
 *  $user->car_set->all()
 *
 * @return Queryset
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class O2MManager extends M2OManager
{
}
