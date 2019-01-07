<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

        foreach ($leaves as $appName => $appLeaves) {
            $leaf = $appLeaves[0];
            $list = $loader->graph->getAncestryTree($appName, $leaf);

            $output->writeln(
                sprintf(
                    '<options=bold>%s</>',
                    ucfirst($appName)
                )
            );

            foreach ($list as $item => $itemApp) {
                if ($itemApp != $appName) {
                    continue;
                }

                $itemArr = explode('\\', $item);
                $migrationName = array_pop($itemArr);

                if (!empty($loader->appliedMigrations[$itemApp][$item])) {
                    $indicator = '<info>(applied)</info>';
                } else {
                    $indicator = '<fg=yellow>(pending)</>';
                }
                $output->writeln(
                    str_pad(' ', 2, ' ') .
                    sprintf(
                        '%1$s %2$s',
                        $indicator,
                        $migrationName
                    )
                );
            }
        }
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
