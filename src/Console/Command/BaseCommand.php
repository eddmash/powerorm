<?php

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\Checks\CheckMessage;
use Eddmash\PowerOrm\Checks\Checks;
use Eddmash\PowerOrm\Checks\ChecksRegistry;
use Eddmash\PowerOrm\Console\Console;
use Eddmash\PowerOrm\Exception\NotImplemented;
use Eddmash\PowerOrm\Exception\SystemCheckError;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Command.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
abstract class BaseCommand extends Command
{
    /**
     * If true the command will perfom check before it runs.
     *
     * @var bool
     */
    public $systemCheck = true;

    /**
     * Name of the manager file.
     *
     * @var
     */
    private $managerName;

    public $headerMessage = '
    **********************************************************%4$s****
        *    ___   ___           ___  ___  ___  ___           %3$s*
        *   /___/ /  / \  /\  / /__  /__/ /  / /__/ /\  /\    %3$s*
        *  /     /__/   \/  \/ /__  /  \ /__/ /  \ /  \/  \(%1$s) %2$s*
        * /     by Eddilbert Macharia (www.eddmash.com)    \  %3$s*
        *                                                     %3$s*
    **********************************************************%4$s****

    ';

    protected function configure()
    {
        $this->setName($this->guessCommandName());
    }

    public function handle(InputInterface $input, OutputInterface $output)
    {
        return new NotImplemented('Subclasses of the class Command must implement the handle()');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = sprintf("<info>%s</info>", $this->headerMessage);

        $pad = 2;
        $maxLength = strlen(POWERORM_VERSION) + $pad;
        $padLength = $maxLength - strlen(POWERORM_VERSION);
        $versionPad = str_pad('', $padLength, ' ');
        $inLinePad = str_pad('', $maxLength, ' ');
        $outLinePad = str_pad('', $maxLength, '*');

        $output->writeln(sprintf($message, POWERORM_VERSION, $versionPad, $inLinePad, $outLinePad));

        if ($this->systemCheck):
            try{
                $this->check($input, $output);
            }catch (SystemCheckError $e){
                // we get a system check error, stop further processing
                return;
            }
        endif;

        $out = $this->handle($input, $output);

        if (!empty($output)):
            $output->writeln($out);
        endif;
    }

    public function command_options()
    {
        $maxlen = 5;
        $default_help = '--help';
        foreach ($this->getOptions() as $key => $value) :
            $len = strlen($key) + 2 + ($key === $default_help ? 10 : 0);
            if ($maxlen < $len) :
                $maxlen = $len;
            endif;
        endforeach;

        $this->normal('Position Arguments:' . PHP_EOL . PHP_EOL);
        $positional = $this->getPositionalOptions();

        if (!empty($positional)):
            foreach ($this->getPositionalOptions() as $key => $value) :

                $this->stdout(' ' . $this->ansiFormat($key, Console::FG_YELLOW));
                $len = strlen($key) + 2;

                if ($value !== '') {
                    $this->stdout(str_repeat(' ', $maxlen - $len + 2) . Console::wrapText($value, $maxlen + 2));
                }
                $this->stdout(PHP_EOL . PHP_EOL);
            endforeach;
        endif;

        $this->normal('Optional Arguments:' . PHP_EOL . PHP_EOL);

        foreach ($this->getOptions() as $key => $value) :

            $this->stdout(' ' . $this->ansiFormat($key, Console::FG_YELLOW));
            $len = strlen($key) + 2;

            if ($value !== '') {
                $this->stdout(str_repeat(' ', $maxlen - $len + 2) . Console::wrapText($value, $maxlen + 2));
            }
            $this->stdout("\n");
        endforeach;
    }

    public function check(InputInterface $input, OutputInterface $output, $failLevel=null)
    {
        $checks = (new ChecksRegistry())->runChecks();

        $debugs = [];
        $info = [];
        $warning = [];
        $errors = [];
        $critical = [];
        $serious = [];

        /**@var $check CheckMessage*/
        foreach ($checks as $check) :

            if($check->isSerious($failLevel) && !$check->isSilenced()):
                $serious[] = $check;
            endif;

            if ($check->level < CheckMessage::INFO && !$check->isSilenced()):
                $debugs[] = $check;
            endif;

            // info
            if ($check->level >= CheckMessage::INFO && $check->level < CheckMessage::WARNING && !$check->isSilenced()):
                $info[] = $check;
            endif;

            // warning
            if ($check->level >= CheckMessage::WARNING && $check->level < CheckMessage::ERROR && !$check->isSilenced()):
                $warning[] = $check;
            endif;

            //error
            if ($check->level >= CheckMessage::ERROR && $check->level < CheckMessage::CRITICAL && !$check->isSilenced()):
                $errors[] = $check;
            endif;

            //critical
            if ($check->level >= CheckMessage::CRITICAL && !$check->isSilenced()):
                $critical[] = $check;
            endif;
        endforeach;

        $output->writeln('Perfoming system checks ...');

        $issue = (count($checks) == 1) ? 'issue' : 'issues';

        $output->writeln(sprintf('System check identified %1$s %2$s', count($checks), $issue));
        $output->writeln(PHP_EOL);

        $errors = array_merge($critical, $errors);


        if (!empty($info)):
            $output->writeln("INFO: ");
            $output->writeln(sprintf("<info>%s</info>",implode(PHP_EOL, $info)));
            $output->writeln(PHP_EOL);
        endif;

        if (!empty($debugs)):
            $output->writeln("DEBUG: ");
            $output->writeln(sprintf("<error>%s</error>", implode(PHP_EOL, $debugs)));
            $output->writeln(PHP_EOL);
        endif;

        if (!empty($warning)):
            $output->writeln("WARNINGS: ");
            $output->writeln(sprintf("<warning>%s</warning>", implode(PHP_EOL, $warning)));
            $output->writeln(PHP_EOL);
        endif;

        if (!empty($errors)):
            $output->writeln("ERROR: ");
            $output->writeln(sprintf("<errortext>%s</errortext>",implode(PHP_EOL, $errors)));
            $output->writeln(PHP_EOL);
        endif;

        if(!empty($serious)):
            throw new SystemCheckError;
        endif;
    }

    public function guessCommandName()
    {
        $name = get_class($this);
        $name = substr($name, strripos($name, "\\") + 1);
        $name = (false === strripos($name, 'Command')) ? $name : substr($name, 0, strripos($name, 'Command'));
        return strtolower($name);
    }

}
