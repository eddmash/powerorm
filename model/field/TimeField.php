<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:08 PM.
 */
namespace powerorm\model\field;

/**
 * Creates a Timestamp column i.e no date.
 */
class TimeField extends DateTimeField
{
    /**
     * @ignore
     *
     * @return string
     */
    public function db_type()
    {
        return 'TIME';
    }
}
