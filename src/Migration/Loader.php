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
use Eddmash\PowerOrm\Helpers\FileHandler;
use Eddmash\PowerOrm\Object;

class Loader extends Object
{
    /**
     * @var Graph
     */
    public $graph;
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
        if(!empty($this->connection)):
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

    public function getMigrationByPrefix($name) {
        return $name;
    }

    public static function createObject() {
        return new static();
    }

    /**
     * List of migration objects.
     *
     * @return array
     */
    public function getMigrations() {
        $migrations = [];

        /** @var $migrationName Migration */
        foreach ($this->getMigrationsClasses() as $migrationName) :
            $fileName = $migrationName;
            $migrationName = sprintf('app\migrations\%s', $migrationName);
            $migrations[$fileName] = $migrationName::createObject($fileName);
        endforeach;

        return $migrations;
    }

    public function getMigrationsClasses() {
        $migrationFiles = $this->getMigrationsFiles();

        $classes = [];
        foreach ($migrationFiles as $migrationFile) :
            $classes[] = trim(basename($migrationFile, '.php'));
        endforeach;

        return $classes;
    }

    public function getMigrationsFiles() {
        $fileHandler = FileHandler::createObject(['path' => BaseOrm::getMigrationsPath()]);

        return $fileHandler->getPathFiles();
    }

    /**
     * returns the latest migration number.
     *
     * @return int
     */
    public function getLatestMigrationVersion() {
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
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function detectConflicts() {
        $latest = $this->graph->getLeafNodes();
        if(count($latest) > 1):
            return $latest;
        endif;

        return [];
    }
}
