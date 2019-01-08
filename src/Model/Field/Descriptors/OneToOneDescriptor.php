<?php

/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Model\Field\Descriptors;

/**
 * Accessor to the related object on the forward side of a one-to-one relation.
 *
 * In the example::
 *
 * class Restaurant extends Model{
 *
 *      private function unboundFields(){
 *          place = OneToOneField(['to'=>Place::class, 'related_name'=>'restaurant'])
 *      }
 *
 * }
 *
 * ``restaurant->place`` is a ``ManyToOneDescriptor`` instance which returns an object instead of a manager
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class OneToOneDescriptor extends ManyToOneDescriptor
{
}
