<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:08 PM
 */

namespace eddmash\powerorm\model\field;

/**
 * Create a DateTime column i.e. date and timestamp.
 * @package eddmash\powerorm\model\field
 */
class DateTimeField extends Field
{
    /**
     * Automatically set the field to now when the object is first created. Useful for creation of timestamps.
     * Note that the current date is always used;
     * it’s not just a default value that you can override.
     * @var bool
     */
    public $on_creation = false;
    /**
     * Automatically set the field to now every time the object is saved.
     * Useful for “last-modified” timestamps. Note that the current date is always used;
     * it’s not just a default value that you can override.
     * @var bool
     */
    public $on_update = false;

    /**
     * {@inheritdoc}
     * @param array $field_options
     * @throws OrmExceptions
     */
    public function __construct($field_options = [])
    {
        parent::__construct($field_options);
        // todo move to checks
        if ($this->on_creation && $this->on_update):
            throw new OrmExceptions(sprintf('%s expects either `on_creation` or `on_update` to be set and not both', $this->name));
        endif;
    }

    /**
     * @ignore
     * @return string
     */
    public function db_type($connection)
    {
        return "DATETIME";
    }
}
