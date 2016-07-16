<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 4:05 PM
 */

namespace powerorm\model\field;



/**
 * Inherits from CharField.
 *
 * A file upload field.
 * - The primary_key and unique arguments are not supported on this field.
 *
 * FileField has one optional argument:
 *   - upload_to this is the path relative to the application base_url where the files will be uploaded.
 *
 * @package powerorm\model\field
 */
class FileField extends CharField{
    /**
     * @ignore
     * @var bool
     */
    protected $passed_pk;

    /**
     * @ignore
     * @var bool
     */
    protected $passed_unique;

    /**
     * The path relative to the application base_url where the files will be uploaded
     * @var
     */
    public $upload_to;

    /**
     * {@inheritdoc}
     */
    public function __construct($field_options=[]){
        $field_options['max_length'] = 100;
        $this->passed_pk = (array_key_exists('primary_key', $field_options))? TRUE: FALSE;
        $this->passed_unique = (array_key_exists('unique', $field_options))? TRUE: FALSE;
        parent::__construct($field_options);
    }

    /**
     * {@inheritdoc}
     */
    public function check(){
        $checks = parent::check();

        $checks = $this->add_check($checks, $this->_check_primarykey());

        $checks = $this->add_check($checks, $this->_check_unique());
        return $checks;
    }

    /**
     * @ignore
     * @return array
     */
    public function _check_unique(){
        if($this->passed_unique):
            return [
                Checks::error([
                    "message"=>sprintf("'unique' is not a valid argument for a %s.", get_class($this)),
                    'hint'=>NULL,
                    'context'=>$this,
                    'id'=>'fields.E200'
                ])
            ];
        endif;
        return [];
    }

    /**
     * @ignore
     * @return array
     */
    public function _check_primarykey(){
        if($this->passed_pk):

            return [
                Checks::error([
                    "message"=>sprintf("'primary_key' is not a valid argument for a %s.", get_class($this)),
                    'hint'=>NULL,
                    'context'=>$this,
                    'id'=>'fields.E201'
                ])
            ];
        endif;

        return [];
    }
}