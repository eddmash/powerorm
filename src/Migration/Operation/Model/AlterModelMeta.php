<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 9/29/16.
 */

namespace Eddmash\PowerOrm\Migration\Operation\Model;

use Eddmash\PowerOrm\Backends\SchemaEditor;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ModelState;
use Eddmash\PowerOrm\Migration\State\ProjectState;

class AlterModelMeta extends Operation
{
    public $name;

    protected $meta = [];

    private static $alterableOptions = ['managed', 'verbosename'];

    public static function isAlterableOption($name)
    {
        return in_array(strtolower($name), self::$alterableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function updateState(ProjectState $state)
    {
        /** @var $modelState ModelState */
        $modelState = $state->getModelState($this->name);
        $meta = ($modelState->getMeta()) ? $modelState->getMeta() : [];
        $meta = array_replace($meta, $this->getMeta());

        foreach (self::$alterableOptions as $alterableOption) {
            if (!ArrayHelper::hasKey($this->getMeta(), $alterableOption) && ArrayHelper::hasKey(
                    $meta,
                    $alterableOption
                )) {
                unset($meta[$alterableOption]);
            }
        }
        $modelState->setMeta($meta);
    }

    public function getDescription()
    {
        return sprintf('Changed Meta options on %s', $this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function databaseForwards(SchemaEditor $schemaEditor, ProjectState $fromState, ProjectState $toState)
    {
        parent::databaseForwards($schemaEditor, $fromState, $toState);
    }

    /**
     * {@inheritdoc}
     */
    public function databaseBackwards(SchemaEditor $schemaEditor, ProjectState $fromState, ProjectState $toState)
    {
        parent::databaseBackwards($schemaEditor, $fromState, $toState);
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }
}
