<?php

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Console\Console;
use Eddmash\PowerOrm\Migrations\Loader;

/**
 * Class Showmigrations.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Showmigrations extends BaseCommand
{
    public $help = 'Provides help information about console commands.';

    public function handle($argOpts = [])
    {
        $connection = BaseOrm::getDbConnection();

        // get migrations list
        $loader = new Loader($connection, true);

        $leaves = $loader->graph->leafNodes();

        foreach ($leaves as $leaf) :
            $list = $loader->graph->before_lineage($leaf);

            foreach ($list as $item) :
                $migrationName = array_pop(explode('\\', $item));

                if (in_array($item, $loader->applied_migrations)):
                    $indicator = $this->ansiFormat('(applied)', Console::FG_GREEN);
                else:
                    $indicator = '(pending)';
                endif;

                $this->normal(sprintf('%1$s %2$s', $indicator, $migrationName), true);
            endforeach;
        endforeach;
    }
}
