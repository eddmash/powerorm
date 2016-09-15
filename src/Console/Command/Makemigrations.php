<?php

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Console\Question\InteractiveAsker;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Migration\AutoDetector;
use Eddmash\PowerOrm\Migration\Loader;
use Eddmash\PowerOrm\Migration\State\ProjectState;
use Orm;

/**
 * Class Makemigrations.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Makemigrations extends BaseCommand
{
    public $help = 'Updates database schema. Based on the migrations.';

    public function handle($argOpts = [])
    {
        if (in_array('--help', $argOpts)):
            $this->normal($this->help.PHP_EOL);
        endif;

        $registry = Orm::getRegistry();

        $loader = new Loader();

        $issues = $loader->detectConflicts();

        if (!empty($issues)):
            $message = 'The following migrations seem to indicate they are both the latest migration :'.PHP_EOL;
            $message .= ' %s '.PHP_EOL;
            $this->error(sprintf($message, Tools::stringify($issues)));
            exit;
        endif;

        var_dump(ProjectState::fromApps($registry));
        $autodetector = new AutoDetector($loader->getProjectState(),
            ProjectState::fromApps($registry),
            InteractiveAsker::createObject()
        );

        $changes = $autodetector->changes($loader->graph);

        if (empty($changes)):
            $this->normal('No changes were detected'.PHP_EOL);
            exit;
        endif;

        if (in_array('--dry-run', $argOpts)):
            $this->info('Migrations :'.PHP_EOL);

            foreach ($changes as $migration) :
                $this->normal("\t --".$migration->name.PHP_EOL);
            endforeach;
            exit;
        endif;

        $this->_writeMigrations($changes);
    }

    public function _writeMigrations($migrationChanges)
    {
        $this->info('Creating Migrations :', true);
        foreach ($migrationChanges as $migration) :

            $content = $migration->asString();

            $fileName = $migration->name;
            $this->normal('  '.$fileName.'.php', true);

            foreach ($migration->operations as $op) :
                $this->normal(sprintf('   - %s', ucwords($op->describe())), true);
            endforeach;

            // write content to file.
            $handler = new FileHandler(BaseOrm::getMigrationsPath(), $fileName);

            $handler->write($content);
        endforeach;
    }

    public function getOptions()
    {
        $options = parent::getOptions();
        $options['--dry-run'] = "Just show what migrations would be made; don't actually write them.";

        return $options;
    }
}
