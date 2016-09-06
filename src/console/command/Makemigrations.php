<?php
namespace eddmash\powerorm\console\command;

use eddmash\powerorm\BaseOrm;
use eddmash\powerorm\helpers\FileHandler;
use eddmash\powerorm\helpers\Tools;
use eddmash\powerorm\migrations\AutoDetector;
use eddmash\powerorm\migrations\InteractiveQuestioner;
use eddmash\powerorm\migrations\Loader;
use eddmash\powerorm\migrations\ProjectState;
use Orm;

/**
 * Class Makemigrations
 * @package eddmash\powerorm\console\command
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Makemigrations extends Command
{
    public $help = "Updates database schema. Based on the migrations.";

    public function handle($arg_opts = [])
    {
        if (in_array("--help", $arg_opts)):
            $this->normal($this->help . PHP_EOL);
        endif;

        $registry = Orm::get_registry();

        $loader = new Loader();

        $issues = $loader->find_issues();

        if (!empty($issues)):
            $message = "The following migrations seem to indicate they are both the latest migration :" . PHP_EOL;
            $message .= " %s " . PHP_EOL;
            $this->error(sprintf($message, Tools::stringify($issues)));
            exit;
        endif;

        $autodetector = new AutoDetector($loader->get_project_state(),
            ProjectState::from_apps($registry),
            InteractiveQuestioner::instance()
        );

        $changes = $autodetector->changes($loader->graph);

        if (empty($changes)):
            $this->normal("No changes were detected" . PHP_EOL);
            exit;
        endif;

        if (in_array("--dry-run", $arg_opts)):
            $this->info("Migrations :" . PHP_EOL);

            foreach ($changes as $migration) :
                $this->normal("\t --" . $migration->name . PHP_EOL);
            endforeach;
            exit;
        endif;

        $this->_write_migrations($changes);
    }

    public function _write_migrations($migration_changes)
    {
        $this->info("Creating Migrations :", true);
        foreach ($migration_changes as $migration) :

            $content = $migration->as_string();

            $file_name = $migration->name;
            $this->normal("  " . $file_name . ".php", true);

            foreach ($migration->operations as $op) :
                $this->normal(sprintf("   - %s", ucwords($op->describe())), true);
            endforeach;


            // write content to file.
            $handler = new FileHandler(BaseOrm::get_migrations_path(), $file_name);

            $handler->write($content);
        endforeach;
    }

    public function get_options()
    {
        $options = parent::get_options();
        $options['--dry-run'] = "Just show what migrations would be made; don't actually write them.";
        return $options;
    }
}
