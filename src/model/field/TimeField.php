<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:08 PM
 */

namespace eddmash\powerorm\model\field;

/**
 * Creates a Timestamp column i.e no date.
 * @package eddmash\powerorm\model\field
 */
class TimeField extends DateTimeField
{

    /**
     * @ignore
     * @return string
     */
    public function db_type($connection)
    {
        return 'TIME';
    }
}
