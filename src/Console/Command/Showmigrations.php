<?php

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Migration\Loader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

    public function handle(InputInterface $input, OutputInterface $output)
    {
        $connection = BaseOrm::getDbConnection();

        // get migrations list
        $loader = new Loader($connection, true);

        $leaves = $loader->graph->getLeafNodes();

        foreach ($leaves as $leaf) :
            $list = $loader->graph->getAncestryTree($leaf);

        foreach ($list as $item) :
                $migrationName = array_pop(explode('\\', $item));

        if (in_array($item, $loader->appliedMigrations)):
                    $indicator = '<info>(applied)</info>'; else:
                    $indicator = '(pending)';
        endif;

        $output->writeln(sprintf('%1$s %2$s', $indicator, $migrationName));
        endforeach;
        endforeach;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName($this->guessCommandName())
            ->setDescription($this->help)
            ->setHelp($this->help);
    }
}
