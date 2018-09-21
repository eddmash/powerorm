<?php

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Console\Question\NonInteractiveAsker;
use Eddmash\PowerOrm\Exception\AmbiguityError;
use Eddmash\PowerOrm\Exception\CommandError;
use Eddmash\PowerOrm\Exception\KeyError;
use Eddmash\PowerOrm\Migration\AutoDetector;
use Eddmash\PowerOrm\Migration\Executor;
use Eddmash\PowerOrm\Migration\State\ProjectState;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Migrate.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Migrate extends BaseCommand
{
    public $help = 'Shows all available migrations for the current project.';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName($this->guessCommandName())
            ->setDescription($this->help)
            ->setHelp($this->help)
            ->addArgument(
                'app_label',
                InputArgument::OPTIONAL,
                'App label of the application containing' .
                ' the migration.'
            )
            ->addArgument(
                'migration_name',
                InputArgument::OPTIONAL,
                'Database state will be brought to the state after that migration. ' .
                'Use the name "zero" to unapply all migrations.'
            )
            ->addOption(
                'fake',
                null,
                InputOption::VALUE_OPTIONAL,
                'Mark migrations as run without actually running them.',
                null
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return \Eddmash\PowerOrm\Exception\NotImplemented|void
     *
     * @throws CommandError
     * @throws \Eddmash\PowerOrm\Exception\ClassNotFoundException
     * @throws \Eddmash\PowerOrm\Exception\ComponentException
     * @throws \Eddmash\PowerOrm\Exception\FileHandlerException
     * @throws \Eddmash\PowerOrm\Exception\NodeNotFoundError
     * @throws \Eddmash\PowerOrm\Exception\NotImplemented
     * @throws \Eddmash\PowerOrm\Exception\OrmException
     * @throws \Eddmash\PowerOrm\Exception\TypeError
     * @throws \Exception
     */
    public function handle(InputInterface $input, OutputInterface $output)
    {
        $appLabel = $input->getArgument('app_label');
        $name = $input->getArgument('migration_name');

        if ($input->getOption('fake')):
            $fake = true;
        else:
            $fake = false;
        endif;

        $connection = BaseOrm::getDbConnection();
        $registry = BaseOrm::getRegistry();

        $executor = Executor::createObject($connection);
        $targets = [];
        // target migrations to act on
        if ($appLabel && $name):
            if ('zero' == $name):
                $targets[$appLabel] = $name;
            else:
                try {
                    $migration = $executor->loader->getMigrationByPrefix(
                        $appLabel,
                        $name
                    );
                } catch (AmbiguityError $e) {
                    throw new CommandError(
                        sprintf(
                            "More than one migration matches '%s' in " .
                            "app '%s'. Please be more specific.",
                            $name,
                            $appLabel
                        )
                    );
                } catch (KeyError $e) {
                    throw new CommandError(
                        sprintf(
                            "Cannot find a migration matching '%s' " .
                            "from app '%s'. Is App registered with the ORM ?",
                            $name,
                            $appLabel
                        )
                    );
                }
                $targets[$migration->getAppLabel()] = $migration->getName();
            endif;
        elseif ($appLabel):
            $migratedApps = $executor->loader->getMigratedApps();

            if (!in_array($appLabel, $migratedApps)):
                throw new CommandError(
                    sprintf(
                        "App '%s' does not have migrations.",
                        $appLabel
                    )
                );
            endif;
            $leaves = $executor->loader->graph->getLeafNodes();
            foreach ($leaves as $app => $appLeaves) :
                if ($appLabel == $app):
                    $targets[$app] = $appLeaves[0];
                    break;
                endif;
            endforeach;
        else:
            $leaves = $executor->loader->graph->getLeafNodes();

            foreach ($leaves as $app => $appLeaves) :
                $targets[$app] = $appLeaves[0];
            endforeach;
        endif;

        // get migration plan
        $plan = $executor->getMigrationPlan($targets);

        BaseOrm::signalDispatch('powerorm.migration.pre_migrate', $this);

        $output->writeln('<comment>Running migrations:</comment>');

        if (empty($plan)):
            $output->writeln('  No migrations to apply.');

            //detect if we need to make migrations
            $auto_detector = new AutoDetector(
                $executor->loader->getProjectState(),
                ProjectState::currentAppsState($registry),
                NonInteractiveAsker::createObject($input, $output)
            );

            $changes = $auto_detector->getChanges($executor->loader->graph);

            if (!empty($changes)):

                $output->writeln(
                    '<warning>  Your models have changes that are not yet reflected ' .
                    "in a migration, and so won't be applied.</warning>"
                );

                $output->writeln(
                    "<warning>  Run 'php pmanager.php makemigrations' " .
                    'to make new migrations, and then re-run ' .
                    "'php pmanager.php migrate' to apply them.</warning>"
                );

            endif;
        else:
            // migrate
            $executor->migrate($targets, $plan, $fake);

        endif;

        BaseOrm::signalDispatch('powerorm.migration.post_migrate');
    }
}
