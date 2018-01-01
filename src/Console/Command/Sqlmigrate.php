<?php
/**
 * Created by PhpStorm.
 * User: edd
 * Date: 12/29/17
 * Time: 9:07 PM.
 */

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Exception\AmbiguityError;
use Eddmash\PowerOrm\Exception\ClassNotFoundException;
use Eddmash\PowerOrm\Exception\CommandError;
use Eddmash\PowerOrm\Exception\ComponentException;
use Eddmash\PowerOrm\Exception\FileHandlerException;
use Eddmash\PowerOrm\Exception\KeyError;
use Eddmash\PowerOrm\Migration\Executor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Sqlmigrate extends BaseCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setDescription(
            'Prints the SQL statements for '.
            'the named migration.'
        )
            ->addArgument(
                'app_label',
                InputArgument::REQUIRED,
                'App label of the application containing'.
                ' the migration.'
            )
            ->addArgument(
                'migration_name',
                InputArgument::REQUIRED,
                'Migration name to print the SQL for.'
            )->addOption(
                'backward',
                null,
                InputOption::VALUE_NONE,
                'Creates SQL to unapply the migration,'.
                ' rather than to apply it'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return \Eddmash\PowerOrm\Exception\NotImplemented|void
     *
     * @throws ClassNotFoundException
     * @throws CommandError
     * @throws ComponentException
     * @throws FileHandlerException
     * @throws \Eddmash\PowerOrm\Exception\OrmException
     */
    public function handle(InputInterface $input, OutputInterface $output)
    {
        $appName = $input->getArgument('app_label');
        $migrationName = $input->getArgument('migration_name');

        $connection = BaseOrm::getDbConnection();

        $executor = Executor::createObject($connection);
        $migratedApps = $executor->loader->getMigratedApps();

        if (!in_array($appName, $migratedApps)):
            throw new CommandError(
                sprintf("App '%s' does not have migrations", $appName)
            );
        endif;

        try {
            $migration = $executor->loader->getMigrationByPrefix(
                $appName,
                $migrationName
            );
        } catch (AmbiguityError $e) {
            throw new CommandError(
                sprintf(
                    "More than one migration matches '%s' in ".
                    "app '%s'. Please be more specific.",
                    $migrationName,
                    $appName
                )
            );
        } catch (KeyError $e) {
            throw new CommandError(
                sprintf(
                    "Cannot find a migration matching '%s' ".
                    "from app '%s'. Is App registered with the ORM ?",
                    $migrationName,
                    $appName
                )
            );
        }
        $appName = $migration->getAppLabel();
        $name = $migration->getName();

        $plan = [
            $input->getOption('backward') => $executor->loader
                ->graph->nodes[$appName][$name],
        ];

        $sql = $executor->getSql($plan);
        $output->writeln(implode(PHP_EOL, $sql));
    }
}
