<?php

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Migration\Loader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Showmigrations.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Showmigrations extends BaseCommand
{
    public $help = 'Shows all migrations in a project.';

    public function handle(InputInterface $input, OutputInterface $output)
    {
        $connection = BaseOrm::getDbConnection();

        // get migrations list
        $loader = new Loader($connection, true);

        $leaves = $loader->graph->getLeafNodes();

        foreach ($leaves as $appName => $appLeaves) :
            $leaf = $appLeaves[0];
        $list = $loader->graph->getAncestryTree($appName, $leaf);

        foreach ($list as $ansAppName => $migrations) :

                $output->writeln(
                    sprintf(
                        '<options=bold>%s</>',
                        ucfirst($ansAppName)
                    )
                );
        foreach ($migrations as $item) :
                    $itemArr = explode('\\', $item);
        $migrationName = array_pop($itemArr);

        if (!empty($loader->appliedMigrations[$ansAppName][$item])):
                        $indicator = '<info>(applied)</info>'; else:
                        $indicator = '<fg=yellow>(pending)</>';
        endif;
        $output->writeln(
                        str_pad(' ', 2, ' ').
                        sprintf(
                            '%1$s %2$s',
                            $indicator,
                            $migrationName
                        )
                    );
        endforeach;
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
