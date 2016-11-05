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
 * Borrowed from fuelphp oil robot.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Robot extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public $systemCheck = false;

    public $help = '';

    public function handle(InputInterface $input, OutputInterface $output)
    {
        $robot = '                    "KILL ALL HUMANS!"
                      _____     /
                     /_____\
                ____[\*---*/]____
               /\ #\ \_____/ /# /\
              /  \# \_.---._/ #/  \
             /   /|\  |   |  /|\   \
            /___/ | | |   | | | \___\
            |  |  | | |---| | |  |  |
            |__|  \_| |_#_| |_/  |__|
            //\\\  <\ _//^\\\_ />  //\\\
            \||/  |\\\//   \\\//|  \||/
                  |   |   |   |
                  |---|   |---|
                  |---|   |---|
                  |   |   |   |
                  |___|   |___|
                  /   \   /   \
                 |_____| |_____|
                 |HHHHH| |HHHHH|';

        $output->writeln($robot);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName($this->guessCommandName())
            ->setDescription('A little fun is good for the soul')
            ->setHelp('A little fun is good for the soul');
    }
}
