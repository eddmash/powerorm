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

use Eddmash\PowerOrm\ContributorInterface;
use Eddmash\PowerOrm\Model\Field\Field;

interface MetaInterface extends ContributorInterface
{
    /**
     * Returns a list of all forward fields on the model and its parents,excluding ManyToManyFields.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getFields();

    /**
     * Returns a field instance given a field name. The field can be either a forward or reverse field,
     * unless $manyToMany is specified; if it is, only forward fields will be returned.
     *
     * @param $name
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getField($name);

    /**
     * Returns a list of all concrete fields on the model and its parents.
     *
     * @return Field[]
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getConcreteFields();

    /**
     *  Returns all related objects pointing to the current model. The related objects can come from a one-to-one,
     * one-to-many, or many-to-many field relation type.
     * As this method is very expensive and is accessed frequently (it looks up every field in a model, in every app),
     * it is computed on first access and then is set as a property on every model.
     *
     * @return Field[]
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getReverseRelatedObjects();

    /**
     * Adds a field into the meta object.
     *
     * @param Field $field
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function addField(Field $field);
}
