<?php

/**
 * This file is part of the powercomponents package.
 *
 * (c) Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Serializer;

use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Model;

interface SerializerInterface
{
    public static function serialize($item, $fields = []);

    public function handleForeignField(Model $model, Field $field);

    public function handleM2MField(Model $model, Field $field);

    public function handleField(Model $model, Field $field);

    /**
     * Invoked when serialization starts.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function startSerialization();

    /**
     * invoked when serialization ends.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function endSerialization();

    /**
     * Invoked when creating of a serial representation of an item starts.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function startObject(Model $model);

    /**
     * Invoked when ending the serial representation of an item starts.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function endObject(Model $model);

    /**
     * Returns the serialize object/objects.
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getValue();
}
