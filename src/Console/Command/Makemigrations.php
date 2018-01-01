<?php

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Components\AppInterface;
use Eddmash\PowerOrm\Console\Question\InteractiveAsker;
use Eddmash\PowerOrm\Console\Question\NonInteractiveAsker;
use Eddmash\PowerOrm\Helpers\ArrayHelper;
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
 * @since  1.1.0
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
            $message = '<error>The following migrations seem to indicate they'.
                ' are both the latest migration :</error>'.PHP_EOL;
        $message .= '  %s '.PHP_EOL;
        $output->writeln(sprintf(
                $message,
                Tools::stringify($issues)
            ));

        return;
        endif;

        if ($input->getOption('no-interaction')):
            $asker = NonInteractiveAsker::createObject($input, $output); else:
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

        $this->writeMigrations($changes, $input, $output);
    }

    private function writeMigrations($migrationChanges, InputInterface $input, OutputInterface $output)
    {
        /* @var $migration Migration */
        /* @var $op Operation */

        foreach (BaseOrm::getInstance()->getComponents() as $component) :
            if ($component instanceof AppInterface):
                if (ArrayHelper::hasKey($migrationChanges, $component->getName())) :

                    $output->writeln(
                        sprintf(
                            '<fg=green;options=bold>Migrations for '.
                            'the application "%s" :</>',
                            $component->getName()
                        )
                    );
        $migration = ArrayHelper::getValue(
                        $migrationChanges,
                        $component->getName()
                    );
        $migrationFile = MigrationFile::createObject($migration);

        $fileName = $migrationFile->getFileName();

        $output->writeln(sprintf('  <options=bold>%s</>', $fileName));

        $operations = $migration->getOperations();
        foreach ($operations as $op) :
                        $output->writeln(
                            sprintf(
                                '    - %s',
                                ucwords($op->getDescription())
                            )
                        );
        endforeach;

        if ($input->getOption('dry-run')):

                        if (OutputInterface::VERBOSITY_DEBUG === $output->getVerbosity()) :
                            $output->writeln($migrationFile->getContent());
        endif;

        continue;
        endif;
        $handler = new FileHandler(
                        $component->getMigrationsPath(),
                        $fileName
                    );

        $handler->write($migrationFile->getContent());
        endif;
        endif;
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
                InputOption::VALUE_NONE,
                'Just show what migrations would be made; don\'t actually write them.',
                null
            );
    }
}
