<?php
/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Migration;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Components\AppInterface;
use Eddmash\PowerOrm\Components\Application;
use Eddmash\PowerOrm\Db\SchemaEditor;
use Eddmash\PowerOrm\Exception\CommandError;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ProjectState;

/**
 * The base class for all migrations.
 *
 * Migration files will import this from Eddmash\PowerOrm\Migration\Migration and subclass it as a class
 * called Migration.
 *
 * It will have one or more of the following attributes:
 * - getOperations: A list of Operation instances, probably from Eddmash\PowerOrm\Migration\Migration\Operation.
 * - getDependency: A list of tuples of (app_path, migration_name)
 *
 * Note that all migrations come out of migrations and into the Loader or Graph as instances, having been
 * initialized with their app name.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Migration implements MigrationInterface
{
    protected $name;
    protected $operations;
    protected $description;
    protected $dependency = [];
    private $appLabel;

    public function __construct($name)
    {
        $this->name = $name;

        $this->operations = $this->getOperations();
        $this->requires = $this->getDependency();
    }

    public static function createObject($param)
    {
        return new static($param);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public static function createShortName($name, $ns = false)
    {
        $pos = strripos($name, '\\');
        if ($pos):
            if (!$ns):
                $name = trim(substr($name, $pos), '\\');
            else:
                $name = trim(substr($name, 0, $pos), '\\');
            endif;
        endif;

        return $name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Operations to apply during this migration, in order.
     *
     * @return mixed
     */
    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * @param mixed $operations
     */
    public function setOperations($operations)
    {
        $this->operations = $operations;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Operations to apply during this migration, in order.
     *
     * @return mixed
     */
    public function getDependency()
    {
        return $this->dependency;
    }

    /**
     * @param mixed $dependency
     */
    public function setDependency($appName, $dependency)
    {
        $this->dependency[$appName] = $dependency;
    }

    /**
     * @param mixed $dependency
     */
    public function addDependency($dependency)
    {
        $this->dependency = $dependency;
    }

    /**
     * Takes a project_state representing all migrations prior to this one and
     * a schema for a live database and
     * applies the migration  in a forwards order.
     *
     * Returns the resulting project state for efficient re-use by following Migrations.
     *
     * @param ProjectState $state
     * @param SchemaEditor $schemaEditor
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Eddmash\PowerOrm\Exception\NotImplemented
     * @throws \Exception
     */
    public function apply(ProjectState $state, SchemaEditor $schemaEditor)
    {
        /** @var $operation Operation */
        foreach ($this->operations as $operation) :

            if (!$operation->isReducibleToSql()):
                $schemaEditor->addSql(
                    '-- MIGRATION NOW PERFORMS'.
                    ' OPERATION THAT CANNOT BE WRITTEN AS SQL:'
                );
            endif;
            $schemaEditor->addSql(
                sprintf(
                    '<fg=yellow>-- %s</>',
                    $operation->getDescription()
                )
            );

            if (!$operation->isReducibleToSql()):
                continue;
            endif;
            echo $operation->getDescription().'--';
            // preserve state before operation
            $oldState = $state->deepClone();

            $operation->updateState($state);
            $operation->setAppLabel($this->getAppLabel());

            $schemaEditor->connection->beginTransaction();

            try {
                $operation->databaseForwards($schemaEditor, $oldState, $state);
                $schemaEditor->connection->commit();
            } catch (\Exception $e) {
                $schemaEditor->connection->rollBack();
                throw new CommandError($e->getMessage());
            }

        endforeach;

        return $state;
    }

    /**
     *  Takes a project_state representing all migrations prior to this one
     * and a schema for a live database and applies
     * the migration in a reverse order.
     *
     * The backwards migration process consists of two phases:
     *      1. The intermediate states from right before the first until right
     *         after the last operation inside this migration are preserved.
     *      2. The operations are applied in reverse order using the states
     * recorded in step 1.
     *
     * @param ProjectState $state
     * @param SchemaEditor $schemaEditor
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Eddmash\PowerOrm\Exception\NotImplemented
     * @throws \Exception
     */
    public function unApply($state, $schemaEditor)
    {
        // we
        $itemsToRun = [];

        // Phase 1 --
        /* @var $operation Operation */
        /** @var $newState ProjectState */
        $newState = $state;
        // we need to reverse the operations so that foreignkeys are removed
        // before model is destroyed
        foreach ($this->operations as $operation) :
            //Preserve new state from previous run to not tamper the same
            // state over all operations
            $newState = $newState->deepClone();
            $oldState = $newState->deepClone();
            $operation->updateState($newState);
            $operation->setAppLabel($this->getAppLabel());
            /*
             * we insert them in the reverse order so the last operation is
             * run first
             */
            array_unshift(
                $itemsToRun,
                [
                    'operation' => $operation,
                    'oldState' => $oldState,
                    'newState' => $newState,
                ]
            );
        endforeach;

        // Phase 2 -- Since we are un applying the old state is where we want
        // to go back to
        //   and the new state is where we are moving away from i.e
        //   we are moving from $newState to $oldState

        foreach ($itemsToRun as $runItem) :

            $schemaEditor->connection->beginTransaction();
            try {
                /** @var $operation Operation */
                $operation = $runItem['operation'];

                if (!$operation->isReducibleToSql()):
                    $schemaEditor->addSql(
                        '-- MIGRATION NOW PERFORMS'.
                        ' OPERATION THAT CANNOT BE WRITTEN AS SQL:'
                    );
                endif;
                $schemaEditor->addSql(
                    sprintf(
                        '<fg=yellow>-- %s </>',
                        ucfirst($operation->getDescription())
                    )
                );

                if ($operation->isReducibleToSql()):

                    $operation->databaseBackwards(
                        $schemaEditor,
                        $runItem['newState'],
                        $runItem['oldState']
                    );
                endif;
                $schemaEditor->connection->commit();
            } catch (\Exception $exception) {
                $schemaEditor->connection->rollBack();
                throw new CommandError($exception->getMessage());
            }
        endforeach;

        return $state;
    }

    /**
     * Takes a ProjectState and returns a new one with the migration's
     * operations applied to it.
     *
     * Preserves the original object state by default and will return a
     * mutated state from a copy.
     *
     * @param ProjectState $state
     * @param bool|true    $preserveState
     *
     * @return mixed
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Eddmash\PowerOrm\Exception\NotImplemented
     */
    public function updateState($state, $preserveState = true)
    {
        $newState = $state;
        if ($preserveState):
            $newState = $state->deepClone();
        endif;

        /** @var $operation Operation */
        foreach ($this->operations as $operation) :
            $operation->setAppLabel($this->getAppLabel());
            $operation->updateState($newState);

        endforeach;

        return $newState;
    }

    public function __toString()
    {
        return sprintf('<Migration %s>', $this->name);
    }

    public function setAppLabel($label)
    {
        $this->appLabel = $label;
    }

    /**
     * @return mixed
     */
    public function getAppLabel()
    {
        return $this->appLabel;
    }

    /**
     * @return AppInterface|null
     */
    public function getApp()
    {
        try {
            $app = BaseOrm::getInstance()->getComponent($this->getAppLabel());

            /* @var $app AppInterface */
            return $app;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param $app Application
     *
     * @return string
     */
    public function getNamespace($app = null)
    {
        if (null == $app):
            $app = $this->getApp();
        endif;

        return sprintf(
            "%s\Migrations",
            ClassHelper::getFormatNamespace(
                $app->getNamespace(),
                false,
                false
            )
        );
    }
}
