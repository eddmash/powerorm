<?php
namespace powerorm\console\command;

use powerorm\BaseOrm;
use powerorm\helpers\FileHandler;
use powerorm\migrations\AutoDetector;
use powerorm\migrations\InteractiveQuestioner;
use powerorm\migrations\Loader;
use powerorm\migrations\ProjectState;

/**
 * Class Makemigrations
 * @package powerorm\console\command
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Makemigrations extends Command
{
    public $help = "Updates database schema. Based on the migrations.";
    
    public function handle($arg_opts=[]){

        if(in_array("--help", $arg_opts)):
            $this->normal($this->help.PHP_EOL);
        endif;

        $loader = new Loader();

        $issues = $loader->find_issues();

        if(!empty($issues)):
            $message = "The following migrations seem to indicate they are both the latest migration :".PHP_EOL;
            $message .= " %s ".PHP_EOL;
            $this->error(sprintf($message, stringify($issues, NULL, NULL, NULL)));
            exit;
        endif;

        $autodetector = new AutoDetector($loader->get_project_state(),
            ProjectState::from_apps(),
            InteractiveQuestioner::instance()
        );

        $changes = $autodetector->changes($loader->graph);

        if(empty($changes)):
            $this->normal("No changes were detected".PHP_EOL);
            exit;
        endif;

        if(in_array("--dry-run", $arg_opts)):
            $this->info("Migrations :".PHP_EOL);

            foreach ($changes as $migration) :
                $this->normal("\t --".$migration->file_name().PHP_EOL);
            endforeach;
           exit;
        endif;

        $this->_write_migrations($changes);
    }

    public function _write_migrations($migration_changes){
        $this->info("Creating Migrations :", TRUE);
        foreach ($migration_changes as $migration) :
            $content = $migration->as_string();

            // get last file name and increment todo
            $file_name = $migration->file_name();
            $this->normal("  ".$file_name.".php", TRUE);

            foreach ($migration->operations as $op) :
                $this->normal(sprintf("   - %s", ucwords($op->describe())), TRUE);
            endforeach;


            // write content to file.
            $handler = new FileHandler(BaseOrm::get_migrations_path(), $file_name);

            $handler->write($content);
        endforeach;

    }
    
    public function get_options(){
        $options = parent::get_options();
        $options['--dry-run'] = "Just show what migrations would be made; don't actually write them.";
        return $options;
    }
}