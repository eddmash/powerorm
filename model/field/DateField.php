<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:08 PM.
 */
namespace powerorm\model\field;

/**
 * Creates a Date column i.e. just the date not timestamp.
 */
class DateField extends DateTimeField
{
    /**
     * @ignore
     *
     * @return string
     */
    public function db_type()
    {
        return 'DATE';
    }
}
