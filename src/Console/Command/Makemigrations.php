<?php

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Console\Question\InteractiveAsker;
use Eddmash\PowerOrm\Console\Question\NonInteractiveAsker;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Eddmash\PowerOrm\Helpers\Tools;
use Eddmash\PowerOrm\Migration\AutoDetector;
use Eddmash\PowerOrm\Migration\Loader;
use Eddmash\PowerOrm\Migration\Migration;
use Eddmash\PowerOrm\Migration\MigrationFile;
use Eddmash\PowerOrm\Migration\Operation\Operation;
use Eddmash\PowerOrm\Migration\State\ProjectState;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

    public function handle(InputInterface $input, OutputInterface $output)
    {
        $registry = BaseOrm::getRegistry();

        $loader = new Loader();

        $issues = $loader->detectConflicts();

        if (!empty($issues)):
            $message = 'The following migrations seem to indicate they are both the latest migration :'.PHP_EOL;
            $message .= ' %s '.PHP_EOL;
            $output->writeln(sprintf($message, Tools::stringify($issues)));

            return;
        endif;

        if ($input->getOption('no-interaction')):
            $asker = NonInteractiveAsker::createObject($input, $output);
        else:
            $asker = InteractiveAsker::createObject($input, $output);
        endif;

        $autodetector = new AutoDetector(
            $loader->getProjectState(),
            ProjectState::fromApps($registry),
            $asker
        );

        $changes = $autodetector->getChanges($loader->graph);

        if (empty($changes)):
            $output->writeln('No changes were detected');

            return;
        endif;

        if ($input->getOption('dry-run')):

            $output->writeln('<info>Migrations :</info>');

            /** @var $migration Migration */
            foreach ($changes as $migration) :
                $output->writeln('  -- '.$migration->getName());
            endforeach;

            return;
        endif;

        $this->writeMigrations($changes, $input, $output);
    }

    private function writeMigrations($migrationChanges, InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Creating Migrations :');

        /** @var $migration Migration */
        /* @var $op Operation */
        foreach ($migrationChanges as $migration) :

            $migrationFile = MigrationFile::createObject($migration);

            $fileName = $migrationFile->getFileName();

            $output->writeln('  '.$fileName);

            $operations = $migration->getOperations();
            foreach ($operations as $op) :
                $output->writeln(sprintf('     - %s', ucwords($op->getDescription())));
            endforeach;

            // write content to file.
            $handler = new FileHandler(BaseOrm::getMigrationsPath(), $fileName);

            $handler->write($migrationFile->getContent());
        endforeach;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName($this->guessCommandName())
            ->setDescription($this->help)
            ->setHelp($this->help)
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_OPTIONAL,
                'Just show what migrations would be made; don\'t actually write them.',
                null
            );
    }
}
