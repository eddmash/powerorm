<?php

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Console\Question\InteractiveAsker;
use Eddmash\PowerOrm\Console\Question\NonInteractiveAsker;
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
 * @since 1.1.0
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
            ->addArgument('migration_name',
                InputArgument::OPTIONAL,
                'Database state will be brought to the state after that migration. '.
                'Use the name "zero" to unapply all migrations.')
            ->addOption(
                'fake',
                null,
                InputOption::VALUE_OPTIONAL,
                'Mark migrations as run without actually running them.',
                null
            );
    }

    public function handle(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('migration_name');

        if ($input->getOption('fake')):
            $fake = true;
        else:
            $fake = false;
        endif;

        $connection = BaseOrm::getDbConnection();
        $registry = BaseOrm::getRegistry();

        $executor = Executor::createObject($connection);

        // target migrations to act on
        if (!empty($name)):
            if ($name == 'zero'):
                $targets = [$name];
            else:
                $targets = $executor->loader->getMigrationByPrefix($name);
            endif;
        else:
            $targets = $executor->loader->graph->getLeafNodes();
        endif;

        // get migration plan
        $plan = $executor->getMigrationPlan($targets);

        BaseOrm::signalDispatch('powerorm.migration.pre_migrate', $this);

        $output->writeln('<comment>Running migrations:</comment>');

        if (empty($plan)):
            $output->writeln('  No migrations to apply.');

            if ($input->getOption('no-interaction')):
                $asker = NonInteractiveAsker::createObject($input, $output);
            else:
                $asker = InteractiveAsker::createObject($input, $output);
            endif;

            //detect if we need to make migrations
            $auto_detector = new AutoDetector(
                $executor->loader->getProjectState(),
                ProjectState::fromApps($registry),
                $asker);

            $changes = $auto_detector->getChanges($executor->loader->graph);

            if (!empty($changes)):

                $output->writeln('<warning>  Your models have changes that are not yet reflected '.
                    "in a migration, and so won't be applied.</warning>");

                $output->writeln("<warning>  Run 'php pmanager.php makemigrations' to make new ".
                    "migrations, and then re-run 'php pmanager.php migrate' to apply them.</warning>");

            endif;
        else:
            // migrate
            $executor->migrate($targets, $plan, $fake);

        endif;

        BaseOrm::signalDispatch('powerorm.migration.post_migrate', $this);
    }
}
