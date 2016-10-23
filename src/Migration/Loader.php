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

use Doctrine\DBAL\Connection;
use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Exception\AmbiguityError;
use Eddmash\PowerOrm\Exception\KeyError;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Eddmash\PowerOrm\Helpers\StringHelper;
use Eddmash\PowerOrm\Object;

class Loader extends Object
{
    /**
     * @var Graph
     */
    public $graph;
    public $appliedMigrations;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct($connection = null, $loadGraph = true)
    {
        $this->connection = $connection;
        if ($loadGraph):
            $this->buildGraph();
        endif;
    }

    public function getProjectState()
    {
        return $this->graph->getState();
    }

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

            $this->graph->addNode($name, $migration);
        endforeach;

        // the for each migration set its dependencies
        /** @var $migration Migration */
        foreach ($migrations as $name => $migration) :
            foreach ($migration->getDependency() as $parent) :

                $this->graph->addDependency($name, $parent, $migration);

            endforeach;

        endforeach;
    }

    /**
     * Returns the migration(s) which match the given prefix.
     *
     * @param $prefix
     *
     * @return mixed
     *
     * @throws AmbiguityError
     * @throws KeyError
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getMigrationByPrefix($prefix)
    {

        $migrations = [];

        foreach ($this->getMigrations() as $name => $migration) :
            $shortName = ClassHelper::getNameFromNs($name, BaseOrm::getMigrationsNamespace());
            if (StringHelper::startsWith($name, $prefix) || StringHelper::startsWith($shortName, $prefix)):
                $migrations[] = $name;
            endif;
        endforeach;

        if (count($migrations) > 1):
            throw new AmbiguityError(sprintf("There is more than one migration with the prefix '%s'", $prefix));
        elseif (count($migrations) == 0):
            throw new KeyError(sprintf("There no migrations with the prefix '%s'", $prefix));
        endif;

        return $migrations[0];
    }

    /**
     * @param null $connection
     * @param bool $loadGraph
     *
     * @return Loader
     *
     * @since 1.1.0
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
     * @return array
     */
    public function getMigrations()
    {
        $migrations = [];

        /* @var $migrationName Migration */
        foreach ($this->getMigrationsClasses() as $fileName) :
            $migrationName = $fileName;

            $migrations[$fileName] = $migrationName::createObject($fileName);
        endforeach;

        return $migrations;
    }

    public function getMigrationsClasses()
    {
        $migrationFiles = $this->getMigrationsFiles();
        $classes = [];

        $namespace = BaseOrm::getMigrationsNamespace();
        foreach ($migrationFiles as $migrationFile) :
            $classes[] = ClassHelper::getClassNameFromFile($migrationFile, BaseOrm::getMigrationsPath(), $namespace);
        endforeach;

        return $classes;
    }

    public function getMigrationsFiles()
    {
        $fileHandler = FileHandler::createObject(['path' => BaseOrm::getMigrationsPath()]);

        return $fileHandler->getPathFiles();
    }

    /**
     * returns the latest migration number.
     *
     * @return int
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
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function detectConflicts()
    {
        $latest = $this->graph->getLeafNodes();
        if (count($latest) > 1):
            return $latest;
        endif;

        return [];
    }
}
