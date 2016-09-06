<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 6/23/16
 * Time: 3:55 PM.
 */
namespace powerorm\form\widgets;

use powerorm\exceptions\NotImplemented;
use powerorm\Object;

/**
 * base class for all widgets, should never initialized
 * Class Widget.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class Widget extends Object
{
    public $attrs;
    public $needs_multipart_form = false;
    public $is_required = false;

    public function __construct($attrs = [], $kwargs = [])
    {
        $this->attrs = $attrs;
        $this->init();
    }

    public static function instance($attrs = [], $kwargs = [])
    {
        return new static($attrs, $kwargs);
    }

    public function build_attrs($attrs = [], $kwargs = [])
    {
        $final_attrs = array_merge($this->attrs, $kwargs);

        if (!empty($attrs)):
            $final_attrs = array_merge($final_attrs, $attrs);
        endif;

        return $final_attrs;
    }

    public function render($name, $value, $attrs = [], $kwargs = [])
    {
        throw new NotImplemented('subclasses of Widget must provide a render() method');
    }

    /**
     * Prepare value for use on HTML widget.
     *
     * @param $value
     *
     * @return mixed
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function prepare_value($value)
    {
        return $value;
    }

    public function value_from_data_collection($data, $name)
    {
        return (isset($data[$name])) ? $data[$name] : null;
    }

    public function is_hidden()
    {
        return ($this->has_property('input_type')) ? $this->input_type === 'hidden' : false;
    }

    public function flat_attrs($attrs)
    {
        $str_attrs = '';
        foreach ($attrs as $key => $attr) :
            if ($attrs === true || $attrs === false):
                $str_attrs .= ' '.$key; else:
                $str_attrs .= sprintf(' %1$s = %2$s', $key, $attr);
        endif;
        endforeach;

        return $str_attrs;
    }

    /**Returns the HTML ID attribute of this Widget for use by a <label>,
     * given the ID of the field. Returns None if no ID is available.
     * This hook is necessary because some widgets have multiple HTML
     * elements and, thus, multiple IDs. In that case, this method should
     * return an ID value that corresponds to the first ID in the widget's
     * tags.
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function get_id_for_label($id)
    {
        return $id;
    }
}
