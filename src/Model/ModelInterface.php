<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Model;

use Eddmash\PowerOrm\DeConstructableInterface;

/**
 * Interface ModelInterface.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
interface ModelInterface extends DeConstructableInterface
{
    /**
     * Creates a Queryset that is used to interaract with the database.
     *
     * @param string $opts
     *
     * @return Queryset
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getQueryset($opts);

    /**
     * All the model fields are set on this model.
     *
     * <pre><code>public function fields(){
     *      $this->username = ORM::CharField(['max_length'=>30]);
     *      $this->first_name = ORM::CharField(['max_length'=>30]);
     *      $this->last_name = ORM::CharField(['max_length'=>30]);
     *      $this->password = ORM::CharField(['max_length'=>255]);
     *      $this->phone_number = ORM::CharField(['max_length'=>30]);
     * }</code></pre>.
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function unboundFields();

    /**
     * Should return an instance of the Meta class. which is used to hold meta information about the particular model.
     * Override this method to provide a different Meta class or to customize.
     *
     * @param array $configs any values that need to be set on the meta object
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepareMeta($configs = []);

    /**
     * This method should return an array of all the configurations that need to made on the meta.
     *
     * <pre>public function getMetaSettings(){
     *   return [
     *      'proxy' => $this->proxy,
     *      'managed' => $this->managed,
     *      'verboseName' => $this->verboseName,
     *  ];
     * }
     * </pre>
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getMetaSettings();
}
