<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 9/29/16.
 */

namespace Eddmash\PowerOrm\Migration\Operation\Model;

use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ModelState;

class AlterModelMeta extends Operation
{
    public $name;
    public $meta = [];

    private static $alterableOptions = ['managed', 'verbosename'];

    public static function isAlterableOption($name)
    {
        return in_array(strtolower($name), self::$alterableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function updateState($state)
    {
        /** @var $modelState ModelState */
        $modelState = $state->modelStates[$this->name];
        $meta = ($modelState->meta) ? $modelState->meta : [];
        $meta = array_replace($meta, $this->meta);

        foreach (self::$alterableOptions as $alterableOption) :
            if (!ArrayHelper::hasKey($this->meta, $alterableOption) && ArrayHelper::hasKey($meta, $alterableOption)):

                unset($meta[$alterableOption]);
        endif;
        endforeach;
        $modelState->meta = $meta;
    }

    public function getDescription()
    {
        return sprintf('Changed Meta options on %s', $this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function databaseForwards($schemaEditor, $fromState, $toState)
    {
        parent::databaseForwards($schemaEditor, $fromState, $toState);
    }

    /**
     * {@inheritdoc}
     */
    public function databaseBackwards($schemaEditor, $fromState, $toState)
    {
        parent::databaseBackwards($schemaEditor, $fromState, $toState);
    }
}
