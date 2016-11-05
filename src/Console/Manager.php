<?php

namespace Eddmash\PowerOrm\Console;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Console\Command\BaseCommand;
use Eddmash\PowerOrm\Console\Command\HelpCommand;
use Eddmash\PowerOrm\Console\Command\ListCommand;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Eddmash\PowerOrm\Helpers\Tools;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Manager.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Manager extends Base
{
    public $defaultCommand = 'help';

    public $defaultHelp = 'Provides help information about console commands.';

    public $commandPath;

    public $managerName;

    public function __construct()
    {
        // default command path
        $this->path = ['Eddmash\PowerOrm\\' => sprintf('%s/Command', dirname(__FILE__))];


        $this->path = $this->addPath($this->getComponentsPath());

        $this->managerName = $this->normalizeKey($this->getShortClassName());


    }

    public function addPath($paths)
    {
        foreach ($paths as $package => $locations) :
            $this->path[$package] = $locations;
        endforeach;
        return $this->path;
    }

    public function defaultCommands()
    {
        return ['help' => '', 'version' => ''];
    }

    public function getComponentsPath()
    {
        $components = BaseOrm::getInstance()->components;

        $paths = [];

        foreach ($components as $name => $path) :
            $paths[$name] = sprintf('%ssrc/Console/Command', $path);
        endforeach;

        return $paths;
    }

    public function execute()
    {
        $console = new Application("");
        $def = new ListCommand();
        $console->add($def);
        $console->setDefaultCommand($def->getName());
        // get console args
//        $argOpts = $_SERVER['argv'];
//
//        // remove the manager from the list
//        $manager = array_shift($argOpts);
//
//        // get command name and remove it from list
//        $commandName = array_shift($argOpts);
//
//        $commandName = (!empty($commandName)) ? $commandName : $this->defaultCommand;
//
//        $commandName = ucfirst($commandName);
//
//        if (in_array($commandName, ['Help']) || empty($commandName)):
//            $this->mainHelpText($argOpts);
//
//            return;
//        endif;
//
//        if (in_array($commandName, ['Version', '--version', '-v'])):
//            $this->normal('PowerOrm Version : ' . $this->ansiFormat(POWERORM_VERSION, Console::FG_CYAN) . PHP_EOL);
//
//            return;
//        endif;
//
//        if (in_array('--command-dir', $argOpts)):
//            $pos = array_search('--command-dir', $argOpts);
//            $this->path[] = $argOpts[$pos + 1];
//        endif;
//
//        $this->fetchCommand($commandName)->execute($argOpts, $manager);

        foreach ($this->path as $path) :
            $files = (new FileHandler($path))->readDir();
            foreach ($files as $file) :
                $command = basename($file, '.php');
                if('BaseCommand'===$command):
                    continue;
                endif;
                $console->add($this->fetchCommand($command));
            endforeach;

        endforeach;

        $output = new ConsoleOutput();

        self::warningText($output);
        self::errorText($output);

        $console->run(null, $output);
    }

    public function mainHelpText($argOpts = [])
    {
        $this->stdout(PHP_EOL);

        if (!empty($argOpts)):
            $subcommand = array_shift($argOpts);

            $command = $this->fetchCommand($subcommand);

            $options = $command->getOptions();
            $help = $command->getHelp();

            $message = sprintf('php %1$s.php %2$s', $this->managerName, $subcommand);
            $this->normal($help . PHP_EOL . PHP_EOL);

            $this->normal(sprintf('Usage : %1$s %2$s',
                    $this->normalizeKey($message), Tools::stringify(array_keys($options), false)) . PHP_EOL . PHP_EOL);

            $this->normal('optional arguments:' . PHP_EOL . PHP_EOL);

            $maxlen = 5;
            foreach ($options as $key => $value) :
                $len = strlen($key) + 2 + ($key === $this->defaultCommand ? 10 : 0);
                if ($maxlen < $len) :
                    $maxlen = $len;
                endif;
            endforeach;

            foreach ($options as $key => $value) :

                $this->stdout(' ' . $this->ansiFormat($key, Console::FG_YELLOW));
                $len = strlen($key) + 2;

                if ($value !== '') {
                    $this->stdout(str_repeat(' ', $maxlen - $len + 2) . Console::wrapText($value, $maxlen + 2));
                }
                $this->stdout("\n");
            endforeach;

            return;
        endif;

        $this->info($this->defaultHelp . PHP_EOL . PHP_EOL);
        $inMessage = $this->ansiFormat(sprintf('php %s.php help <subcommand>', $this->managerName), Console::FG_YELLOW);
        $this->normal(sprintf('Type %s for help on a specific subcommand.', $inMessage) . PHP_EOL . PHP_EOL);
        $this->normal(sprintf('Available Commands : ') . PHP_EOL);

        foreach ($this->path as $path) :
            $path = sprintf($path, dirname(__FILE__));

            $fileHandler = new FileHandler($path);

            $files = $fileHandler->readDir();

            foreach ($files as $file) :
                $file = basename($file, '.php');

                $file = $this->normalizeKey($file);
                // ignore base class
                if ($file == 'command'):
                    continue;
                endif;
                $this->normal("\t " . $file . PHP_EOL);
            endforeach;
        endforeach;


        foreach ($this->defaultCommands() as $name => $command) :

            $file = $this->normalizeKey($name);
            // ignore base class
            if ($file == 'command'):
                continue;
            endif;
            $this->normal("\t " . $file . PHP_EOL);
        endforeach;
    }

    /**
     * @param $name
     *
     * @return BaseCommand
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function fetchCommand($name)
    {
        $name = ucfirst($name);

        $file = null;
        $packageName = null;

        foreach ($this->path as $package => $path) :
            $file_handler = new FileHandler($path);

            $file = $file_handler->getFile($name);
            if ($file !== false):
                $packageName = $package;
                break;
            endif;
        endforeach;


        if (false === $file):
            $this->error(
                sprintf('Unknown command: ` %1$s`. Does the file exists `%2$s/%1$s.php` ?' . PHP_EOL, $name,
                    $this->path));
            $message = $this->ansiFormat(sprintf('php %s.php help', $this->managerName), Console::FG_YELLOW);
            $this->normal(sprintf('Type %s for usage.' . PHP_EOL, $message));

            return false;
        endif;

        // commands are in the commands namespace
        /** @var $className BaseCommand */
        $className = ClassHelper::getFormatNamespace($packageName) . 'Console\Command\\' . $name;

        return new $className();
    }

    public static function run()
    {
        (new static())->execute();
    }

    public static function warningText(OutputInterface $output){
        $style = new OutputFormatterStyle('magenta', 'black', array('bold'));
        $output->getFormatter()->setStyle('warning', $style);
    }
    public static function errorText(OutputInterface $output){
        $style = new OutputFormatterStyle('red', 'black', array('bold'));
        $output->getFormatter()->setStyle('errortext', $style);
    }
}
