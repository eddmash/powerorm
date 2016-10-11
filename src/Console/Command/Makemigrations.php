<?php

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Console\Question\InteractiveAsker;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Migration\AutoDetector;
use Eddmash\PowerOrm\Migration\Loader;
use Eddmash\PowerOrm\Migration\Migration;
use Eddmash\PowerOrm\Migration\MigrationFile;
use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ProjectState;

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

        $registry = BaseOrm::getRegistry();

        $loader = new Loader();

        $issues = $loader->detectConflicts();

        if (!empty($issues)):
            $message = 'The following migrations seem to indicate they are both the latest migration :'.PHP_EOL;
            $message .= ' %s '.PHP_EOL;
            $this->error(sprintf($message, Tools::stringify($issues)));
            exit;
        endif;

        $autodetector = new AutoDetector(
            $loader->getProjectState(),
            ProjectState::fromApps($registry),
            InteractiveAsker::createObject()
        );

        $changes = $autodetector->getChanges($loader->graph);

        var_dump($changes);
        if (empty($changes)):
            $this->normal('No changes were detected'.PHP_EOL);
            exit;
        endif;

        if (in_array('--dry-run', $argOpts)):
            $this->info('Migrations :'.PHP_EOL);

            /** @var $migration Migration */
            foreach ($changes as $migration) :
                $this->normal('  -- '.$migration->getName().PHP_EOL);
            endforeach;
            exit;
        endif;

        $this->_writeMigrations($changes);
    }

    public function _writeMigrations($migrationChanges)
    {
        $this->info('Creating Migrations :', true);

        /** @var $migration Migration */
        /* @var $op Operation */
        foreach ($migrationChanges as $migration) :

            $migrationFile = MigrationFile::createObject($migration);

            $fileName = $migrationFile->getFileName();
            $this->normal('  '.$fileName, true);

            $operations = $migration->getOperations();
            foreach ($operations as $op) :
                $this->normal(sprintf('     - %s', ucwords($op->getDescription())), true);
            endforeach;

            // write content to file.
            $handler = new FileHandler(BaseOrm::getMigrationsPath(), $fileName);

            $handler->write($migrationFile->getContent());
        endforeach;
    }

    public function getOptions()
    {
        $options = parent::getOptions();
        $options['--dry-run'] = "Just show what migrations would be made; don't actually write them.";

        return $options;
    }
}
