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

use Eddmash\PowerOrm\BaseObject;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Components\AppInterface;
use Eddmash\PowerOrm\Db\ConnectionInterface;
use Eddmash\PowerOrm\Exception\AmbiguityError;
use Eddmash\PowerOrm\Exception\ClassNotFoundException;
use Eddmash\PowerOrm\Exception\KeyError;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Eddmash\PowerOrm\Helpers\StringHelper;

class Loader extends BaseObject
{
    /**
     * @var Graph
     */
    public $graph;
    public $appliedMigrations;

    /**
     * @var ConnectionInterface
     */
    private $connection;
    private $migratedApps;

    /**
     * Loader constructor.
     *
     * @param ConnectionInterface|null $connection
     * @param bool                     $loadGraph
     *
     * @throws ClassNotFoundException
     * @throws \Eddmash\PowerOrm\Exception\FileHandlerException
     * @throws \Eddmash\PowerOrm\Exception\NodeNotFoundError
     */
    public function __construct(ConnectionInterface $connection = null, $loadGraph = true)
    {
        $this->connection = $connection;
        if ($loadGraph):
            $this->buildGraph();
        endif;
    }

    /**
     * @return State\ProjectState
     *
     * @throws \Eddmash\PowerOrm\Exception\NodeNotFoundError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getProjectState($node = null, $atEnd = true)
    {
        return $this->graph->getState($node, $atEnd);
    }

    /**
     * @throws ClassNotFoundException
     * @throws \Eddmash\PowerOrm\Exception\FileHandlerException
     * @throws \Eddmash\PowerOrm\Exception\NodeNotFoundError
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function buildGraph()
    {
        if (!empty($this->connection)):
            $recoder = new Recorder($this->connection);

            $this->appliedMigrations = $recoder->getApplied();
        endif;

        $migrations = $this->getMigrations();

        $this->graph = new Graph();

        // first add all the migrations into the graph
        foreach ($migrations as $name => $migration) :

            $this->graph->addNode($migration->getName(), $migration);
        endforeach;

        // the for each migration set its dependencies
        /** @var $migration Migration */
        foreach ($migrations as $name => $migration) :
            foreach ($migration->getDependency() as $appName => $parent) :
                $this->graph->addDependency(
                    $name,
                    [$appName => $parent],
                    $migration
                );

            endforeach;

        endforeach;
    }

    /**
     * Returns the migration(s) which match the given prefix.
     *
     * @param $appName
     * @param $prefix
     *
     * @return Migration
     *
     * @throws AmbiguityError
     * @throws ClassNotFoundException
     * @throws KeyError
     * @throws \Eddmash\PowerOrm\Exception\ComponentException
     * @throws \Eddmash\PowerOrm\Exception\FileHandlerException
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getMigrationByPrefix($appName, $prefix)
    {
        $migrations = [];
        /* @var $app AppInterface */

        foreach ($this->getMigrations() as $name => $migration) :
            $app = $migration->getApp();
            if ($migration->getAppLabel() != strtolower($appName)):
                continue;
            endif;
            $shortName = ClassHelper::getNameFromNs(
                $name,
                $app->getNamespace()."\Migrations"
            );

            if (StringHelper::startsWith($name, $prefix) ||
                StringHelper::startsWith($shortName, $prefix)):
                $migrations[] = $migration;
            endif;
        endforeach;

        if (count($migrations) > 1):
            throw new AmbiguityError(
                sprintf(
                    'There is more than one '.
                    "migration with the prefix '%s'",
                    $prefix
                )
            );
        elseif (0 == count($migrations)):
            throw new KeyError(
                sprintf(
                    "There no migrations with the prefix '%s'",
                    $prefix
                )
            );
        endif;

        return $migrations[0];
    }

    /**
     * @param null $connection
     * @param bool $loadGraph
     *
     * @return Loader
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function createObject($connection = null, $loadGraph = true)
    {
        return new static($connection, $loadGraph);
    }

    /**
     * List of migration objects.
     *
     * @return \Eddmash\PowerOrm\Migration\Migration[]
     *
     * @throws ClassNotFoundException
     * @throws \Eddmash\PowerOrm\Exception\FileHandlerException
     */
    public function getMigrations()
    {
        $migrations = [];

        /* @var $migrationName Migration */
        foreach ($this->getMigrationsClasses() as $appName => $classes) :
            foreach ($classes as $fileName) :
                $migrationName = $fileName;
                $migration = $migrationName::createObject($fileName);
                $migration->setAppLabel($appName);
                $this->setMigratedApps($appName);
                $migrations[$fileName] = $migration;
            endforeach;
        endforeach;

        return $migrations;
    }

    /**
     * @return array
     *
     * @throws ClassNotFoundException
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Eddmash\PowerOrm\Exception\FileHandlerException
     */
    public function getMigrationsClasses()
    {
        $appFiles = $this->getMigrationsFiles();
        $classes = [];

        /* @var $component AppInterface */
        foreach ($appFiles as $appName => $migrationFiles) :
            $component = BaseOrm::getInstance()->getComponent($appName);
            foreach ($migrationFiles as $migrationFile) :
                $className = ClassHelper::getClassFromFile($migrationFile);
                $foundClass = ClassHelper::classExists(
                    $className,
                    $component->getNamespace()
                );
                if (!$className):
                    throw new ClassNotFoundException(
                        sprintf(
                            'The class [ %2$s\\%1$s or \\%1$s ] '.
                            'could not be located',
                            $className,
                            $component->getNamespace()
                        )
                    );
                endif;
                $classes[$appName][] = $foundClass;
            endforeach;
        endforeach;

        return $classes;
    }

    /**
     * @return array
     *
     * @throws \Eddmash\PowerOrm\Exception\FileHandlerException
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getMigrationsFiles()
    {
        $files = [];
        foreach (BaseOrm::getInstance()->getComponents() as $component) :
            if ($component instanceof AppInterface):
                $fileHandler = FileHandler::createObject(
                    [
                        'path' => $component->getMigrationsPath(),
                    ]
                );

                $files[$component->getName()] = $fileHandler->getPathFiles();
            endif;
        endforeach;

        return $files;
    }

    /**
     * returns the latest migration number.
     *
     * @return int
     *
     * @throws \Eddmash\PowerOrm\Exception\FileHandlerException
     */
    public function getLatestMigrationVersion()
    {
        $migration_files = $this->getMigrationsFiles();
        $last_version = array_pop($migration_files);
        $last_version = basename($last_version);
        $last_version = preg_split('/_/', $last_version)[0];

        return (int) $last_version;
    }

    /**
     * An application should only have one leaf node more than that means there is an issue somewhere.
     *
     * @return array
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function detectConflicts()
    {
        $conflicts = [];
        $apps = $this->graph->getLeafNodes();
        foreach ($apps as $name => $latest) :
            if (count($latest) > 1):
                $conflicts[$name] = $latest;
            endif;
        endforeach;

        return $conflicts;
    }

    public function setMigratedApps($appName)
    {
        $this->migratedApps[] = $appName;
    }

    /**
     * @return mixed
     */
    public function getMigratedApps()
    {
        return $this->migratedApps;
    }
}
