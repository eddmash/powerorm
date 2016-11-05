<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/2/16.
 */
namespace Eddmash\PowerOrm\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class CheckCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public $systemCheck = false;

    public $help = 'Runs systems check for potential problems';

    public function handle(InputInterface $input, OutputInterface $output)
    {
        $this->check($input, $output);
    }


    /**
     * {@inheritDoc}
     */
    protected function configure()
    {

        $this->setName($this->guessCommandName())
            ->setDescription($this->help)
            ->setHelp($this->help);
    }
}
