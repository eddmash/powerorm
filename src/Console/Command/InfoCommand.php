<?php
/**
 * This file is part of the store
 *
 *
 * Created by : Eddilber Macharia (edd.cowan@gmail.com)<eddmash.com>
 * On Date : 1/4/19 11:01 AM
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Console\Command;


use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Components\AppComponent;
use Eddmash\PowerOrm\Components\AppInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends BaseCommand
{

    /**
     * {@inheritdoc}
     */
    public $systemCheck = false;

    public $help = 'Display information about the orm state';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName($this->guessCommandName())
            ->setDescription($this->help)
            ->setHelp($this->help)
            ->addOption(
                'models',
                'm',
                null,
                'Show models.'
            )
            ->addOption(
                'components',
                'c',
                null,
                'Show components.'
            );
    }


    public function handle(InputInterface $input, OutputInterface $output)
    {
        $showComponets = $input->getOption('components');
        $showModels = $input->getOption('models');

        $showAll = !$showModels && !$showComponets;

        $baseOrm = BaseOrm::getInstance();
        $registry = BaseOrm::getRegistry();
        if ($showComponets || $showAll) {
            $output->writeln(" <options=bold>***** registered components **** </>");

            foreach ($baseOrm->getComponents() as $item) {
                if ($item instanceof AppInterface) {
                    $output->writeln(sprintf(" - %s <fg=green;>(application)</>", $item->getName()));
                } else {
                    $output->writeln(sprintf(' - %s', $item->getName()));
                }
            }
        }
        if ($showModels || $showAll) {
            $output->writeln(" <options=bold>***** registered models **** </>");
            foreach ($baseOrm->getComponents(true) as $app) {
                $output->writeln(sprintf(" - Application <options=bold>`%s`</> models", $app->getName()));

                foreach ($registry->getModels(true, $app->getName()) as $model) {
                    if ($model->getMeta()->autoCreated) {
                        $output->writeln(sprintf("   - %s <fg=green>(autocreated)</>",
                            $model->getMeta()->getNSModelName()));
                    } else {
                        $output->writeln(sprintf("   - %s", $model->getMeta()->getNSModelName()));
                    }
                }
            }
        }

    }
}