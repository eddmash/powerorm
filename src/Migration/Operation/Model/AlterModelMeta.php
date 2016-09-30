<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 9/29/16
 * Time: 2:08 PM
 */

namespace Eddmash\PowerOrm\Migration\Operation\Model;


use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ModelState;

class AlterModelMeta extends Operation
{
    public $name;
    public $meta;

    private static $alterableOptions = ['managed', 'verbose_name'];

    public static function isAlterableOption($name)
    {
        return in_array(strtolower($name), self::$alterableOptions);
    }

    /**
     * @inheritDoc
     */
    public function updateState($state)
    {
        /**@var $modelState ModelState*/
        $modelState = $state->modelStates[$this->name];
        $meta = $modelState->meta;
        $meta = array_replace($meta, $this->meta);

        foreach (self::$alterableOptions as $alterableOption) :
            if(!array_key_exists($alterableOption, $this->meta) && array_key_exists($alterableOption, $meta)):
                unset($meta[$alterableOption]);
            endif;
        endforeach;
        $modelState->meta = $meta;
    }

    public function getDescription()
    {
        return sprintf("Changed Meta options on %s", $this->name);
    }

}