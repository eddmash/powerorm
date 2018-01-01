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
 * a php shell.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Shell extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public $systemCheck = false;

    public $help = '';

    public function handle(InputInterface $input, OutputInterface $output)
    {
        if (class_exists('\Psy\Shell')):
            $shell = new \Psy\Shell();
        $shell->run(); else:

            $message = sprintf(
                '<error>%s</error>',
                "Shell command depends on Psych, please install as shown 'composer require psy/psysh:@stable'"
            );
        $output->writeln($message);
        endif;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName($this->guessCommandName())
            ->setDescription('A php interactive shell')
            ->setHelp('A php interactive shell');
    }
}
