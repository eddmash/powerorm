<?php
/**
 * Created by http://eddmash.com.
 * User: eddmash
 * Date: 5/14/16
 * Time: 6:38 AM.
 */
namespace eddmash\powerorm\migrations;

use eddmash\powerorm\BaseOrm;
use eddmash\powerorm\exceptions\AmbiguityError;
use eddmash\powerorm\helpers\FileHandler;
use eddmash\powerorm\helpers\Strings;
use eddmash\powerorm\Object;

/**
 * This class is responsible for loading the state of the project based on the migration files present.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Loader extends Object
{
    protected $_migrations_path;

    /**
     * @var Graph
     */
    public $graph;
    public $applied_migrations;
    public $connection;

    public function __construct($connection = '', $load_graph = true)
    {
        $this->_migrations_path = BaseOrm::get_migrations_path();
        $this->connection = $connection;
        $this->applied_migrations = [];

        // load the graph if required
        if ($load_graph):
            $this->build_graph();
        endif;
    }

    /**
     * List of migration objects.
     *
     * @return array
     */
    public function app_migrations()
    {
        $migrations = [];

        foreach ($this->get_migrations_classes() as $file_name => $migration_name) :

            $migration_name = sprintf('app\migrations\%s', $migration_name);
//            $migration_name = $this->standard_name($migration_name);
            $migrations[$file_name] = new $migration_name($file_name);
        endforeach;

        return $migrations;
    }

    /**
     * The migration class name.
     *
     * @param $file
     *
     * @return string
     */
    public function migration_class_name($file)
    {
        $file_name = $class_name = trim(basename($file, '.php'));

        return [$file_name, $file_name];
    }

    /**
     * the migrations classes.
     *
     * @return array
     */
    public function get_migrations_classes()
    {
        $classes = [];
        foreach ($this->get_migrations_files() as $file) :
            list($file_name, $migration_class_name) = $this->migration_class_name($file);
            $classes[$file_name] = $migration_class_name;
        endforeach;

        return $classes;
    }

    /**
     * Get all the migration files, Absolute paths to each file.
     *
     * @return array
     */
    public function get_migrations_files()
    {
        $fileHandler = new FileHandler($this->_migrations_path);

        return $fileHandler->read_dir('php');
    }

    /**
     * returns the latest migration number.
     *
     * @return int
     */
    public function latest_migration_version()
    {
        $migration_files = $this->get_migrations_files();
        $last_version = array_pop($migration_files);
        $last_version = basename($last_version);
        $last_version = preg_split('/_/', $last_version)[0];

        return (int) $last_version;
    }

    /**
     * Load the migrations graph from the first to the latest migration.
     */
    public function build_graph()
    {
        if (!empty($this->connection)):
            $recoder = new Recorder($this->connection);

            $this->applied_migrations = $recoder->get_applied();
        endif;

        $app_migrations = $this->app_migrations();

        $this->graph = new Graph();

        // first add all the migrations into the graph
        foreach ($app_migrations as $name => $migration) :

            $this->graph->add_node($name, $migration);
        endforeach;

        // for each migration set its dependencies
        foreach ($app_migrations as $name => $migration) :
            foreach ($migration->get_dependency() as $requires) :

                $this->graph->add_dependency($name, $requires, $migration);

            endforeach;

        endforeach;
    }

    /**
     * Creates a project state based on the migrations.
     */
    public function get_project_state()
    {
        return $this->graph->get_project_state();
    }

    /**
     * An application should only have one leaf node more than that means there is an issue somewhere.
     */
    public function find_issues()
    {
        $latest = $this->graph->leaf_nodes();
        if (count($latest) > 1):
            return $latest;
        endif;

        return [];
    }

    public function get_migration_by_prefix($name_prefix)
    {
        $results = [];

        foreach ($this->app_migrations() as $name => $migration) :

            if (Strings::starts_with($name, $name_prefix)):
                $results[] = $name;
            endif;
        endforeach;

        if (count($results) > 1):
            throw new AmbiguityError(sprintf('There is more than one migration with the prefix %s', $name_prefix));
        elseif (count($results) == 0):
            throw new AmbiguityError(sprintf('There no migrations with the prefix %s', $name_prefix));
        endif;

        return $results;
    }
}
