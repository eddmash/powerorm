<?php
namespace powerorm\console\command;


use powerorm\BaseOrm;
use powerorm\console\Console;
use powerorm\migrations\Loader;

/**
 * Class Showmigrations
 * @package powerorm\console\command
 * @since 1.0.2
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Showmigrations extends Command
{

    public $help = "Provides help information about console commands.";


    public function handle($arg_opts=[]){

        $connection = BaseOrm::dbconnection();

        // get migrations list
        $loader = new Loader($connection, TRUE);

        $leaves = $loader->graph->leaf_nodes();

        foreach ($leaves as $leaf) :
            $list = $loader->graph->before_lineage($leaf);

            foreach ($list as $item) :
                $migration_name = array_pop(explode("\\", $item));

                if(in_array($item, $loader->applied_migrations)):
                    $indicator = $this->ansiFormat("(applied)", Console::FG_GREEN);
                else:
                    $indicator ="(pending)";
                endif;

                $this->normal(sprintf('%1$s %2$s', $indicator, $migration_name), TRUE);
            endforeach;
        endforeach;
    }
}