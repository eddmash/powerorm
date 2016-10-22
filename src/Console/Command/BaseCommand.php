<?php

namespace Eddmash\PowerOrm\Console\Command;

use Eddmash\PowerOrm\Checks\CheckMessage;
use Eddmash\PowerOrm\Checks\Checks;
use Eddmash\PowerOrm\Checks\ChecksRegistry;
use Eddmash\PowerOrm\Console\Base;
use Eddmash\PowerOrm\Console\Console;
use Eddmash\PowerOrm\Exception\NotImplemented;

/**
 * Class Command.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class BaseCommand extends Base
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

    public function getOptions()
    {
        return [
            '--help' => 'show this help message and exit',
            '--command-dir' => 'the directory where command is defined',
        ];
    }

    public function getPositionalOptions()
    {
        return [];
    }

    /**
     * Returns help information for this controller.
     *
     * You may override this method to return customized help.
     * The default implementation returns help information retrieved from the PHPDoc comment.
     *
     * @return string
     */
    public function getHelp()
    {
        return $this->help;
    }

    public function usage()
    {
        $command = $this->getCommandName();

        $options_names = array_merge(array_keys($this->getPositionalOptions()), array_keys($this->getOptions()));

        $options = sprintf('[ %s ]', implode(' || ', $options_names));

        $usage = sprintf('Usage : %1$s %2$s %3$s ', $this->managerName, $command, $options);

        $help = $this->getHelp();
        $this->stdout(PHP_EOL);

        if (!empty($help)):
            $this->normal($help, true);
            $this->stdout(PHP_EOL);

            $this->normal(Console::wrapText($usage, 8), true);
        else:
            $this->normal($usage, true);
        endif;

        $this->stdout(PHP_EOL);

        $this->command_options();
    }

    public function getCommandName()
    {
        $name = array_pop(explode('\\', $this->getShortClassName()));

        return $this->normalizeKey($name);
    }

    public function handle($argOpts = [])
    {
        return new NotImplemented('Subclasses of the class Command must implement the handle()');
    }

    public function execute($argOpts, $manager)
    {
        $message = $this->ansiFormat($this->headerMessage.PHP_EOL, Console::FG_GREEN);

        $pad = 2;
        $maxLength = strlen(POWERORM_VERSION) + $pad;
        $padLength = $maxLength - strlen(POWERORM_VERSION);
        $versionPad = str_pad('', $padLength, ' ');
        $inLinePad = str_pad('', $maxLength, ' ');
        $outLinePad = str_pad('', $maxLength, '*');

        $this->normal(sprintf($message, POWERORM_VERSION, $versionPad, $inLinePad, $outLinePad), true);

        $this->managerName = $manager;

        if (in_array('--help', $argOpts)):
            $this->usage();
            exit;
        endif;

        if ($this->systemCheck):
            $this->check();
        endif;

        $output = $this->handle($argOpts);

        if (!empty($output)):
            $this->normal($output.PHP_EOL);
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

        $this->normal('Position Arguments:'.PHP_EOL.PHP_EOL);
        $positional = $this->getPositionalOptions();

        if (!empty($positional)):
            foreach ($this->getPositionalOptions() as $key => $value) :

                $this->stdout(' '.$this->ansiFormat($key, Console::FG_YELLOW));
                $len = strlen($key) + 2;

                if ($value !== '') {
                    $this->stdout(str_repeat(' ', $maxlen - $len + 2).Console::wrapText($value, $maxlen + 2));
                }
                $this->stdout(PHP_EOL.PHP_EOL);
            endforeach;
        endif;

        $this->normal('Optional Arguments:'.PHP_EOL.PHP_EOL);

        foreach ($this->getOptions() as $key => $value) :

            $this->stdout(' '.$this->ansiFormat($key, Console::FG_YELLOW));
            $len = strlen($key) + 2;

            if ($value !== '') {
                $this->stdout(str_repeat(' ', $maxlen - $len + 2).Console::wrapText($value, $maxlen + 2));
            }
            $this->stdout("\n");
        endforeach;
    }

    public function check()
    {
        $checks = (new ChecksRegistry())->runChecks();

        $debugs = [];
        $info = [];
        $warning = [];
        $errors = [];
        $critical = [];

        foreach ($checks as $check) :
            if ($check->level < CheckMessage::INFO):
                $debugs[] = $check;
            endif;

            // info
            if ($check->level >= CheckMessage::INFO && $check->level < CheckMessage::WARNING):
                $info[] = $check;
            endif;

            // warning
            if ($check->level >= CheckMessage::WARNING && $check->level < CheckMessage::ERROR):
                $warning[] = $check;
            endif;

            //error
            if ($check->level >= CheckMessage::ERROR && $check->level < CheckMessage::CRITICAL):
                $errors[] = $check;
            endif;

            //critical
            if ($check->level >= CheckMessage::CRITICAL):
                $critical[] = $check;
            endif;
        endforeach;

        $this->normal('Perfoming system checks ...', true);

        $issue = (count($checks) == 1) ? 'issue' : 'issues';
        $this->normal(sprintf('System check identified %1$s %2$s', count($checks), $issue), true);

        $errors = array_merge($critical, $errors);
        if (!empty($errors)):
            $this->error(implode(PHP_EOL, $errors), true);
            exit;
        endif;

        if (!empty($warning)):
            $this->warning(implode(PHP_EOL, $warning), true);
        endif;

        if (!empty($info)):
            $this->info(implode(PHP_EOL, $info), true);
        endif;

        if (!empty($debugs)):
            $this->normal(implode('  '.PHP_EOL, $debugs), true);
        endif;
    }
}
