<?php

namespace Eddmash\PowerOrm\Console;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Console\Command\BaseCommand;
use Eddmash\PowerOrm\Console\Command\ListCommand;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Manager.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Manager extends Base
{
    public $defaultCommand = 'help';

    public $defaultHelp = 'Provides help information about console commands.';

    public $commandPath;

    public $managerName;

    protected $defaultCommandsPaths;

    public function __construct()
    {
        // default command path
        $this->defaultCommandsPaths = ['Eddmash\PowerOrm\\' => sprintf('%s/Command', dirname(__FILE__))];

        $this->managerName = $this->normalizeKey($this->getShortClassName());
    }

    public function defaultCommands()
    {
        return ['help' => '', 'version' => ''];
    }

    public function getComponentCommands()
    {
        $components = BaseOrm::getInstance()->getComponents();

        $comands = [];

        foreach ($components as $component) {
            foreach ($component->getCommands() as $command) {
                if (is_object($command) && $command instanceof BaseCommand) {
                    $comands[] = $command;
                }
                if (is_string($command)) {
                    $comands[] = new $command();
                }
            }
        }

        return $comands;
    }

    public function getDefaultCommands()
    {
        $commands = [];
        foreach ($this->defaultCommandsPaths as $path) {
            $files = (new FileHandler($path))->readDir();
            foreach ($files as $file) {
                $command = basename($file, '.php');
                if ('BaseCommand' === $command) {
                    continue;
                }
                $commands[] = $this->fetchCommand($command);
            }
        }

        return $commands;
    }

    public static function getCommands()
    {
        $manager = new static();

        return array_merge($manager->getDefaultCommands(), $manager->getComponentCommands());
    }

    /**
     * @param $name
     *
     * @return BaseCommand
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function fetchCommand($name)
    {
        $name = ucfirst($name);

        $file = null;
        $packageName = null;

        foreach ($this->defaultCommandsPaths as $package => $path) {
            $file_handler = new FileHandler($path);

            $file = $file_handler->getFile($name);
            if (false !== $file) {
                $packageName = $package;

                break;
            }
        }

        if (false === $file) {
            $this->error(
                sprintf(
                    'Unknown command: ` %1$s`. Does the file exists `%2$s/%1$s.php` ?'.PHP_EOL,
                    $name,
                    $this->defaultCommandsPaths
                )
            );
            $message = $this->ansiFormat(sprintf('php %s.php help', $this->managerName), Console::FG_YELLOW);
            $this->normal(sprintf('Type %s for usage.'.PHP_EOL, $message));
        }

        // commands are in the commands namespace
        /** @var $className BaseCommand */
        $className = ClassHelper::getFormatNamespace($packageName).'Console\Command\\'.$name;

        return new $className();
    }

    /**
     * @param bool $autoRun
     *
     * @return Application
     * @author: Eddilbert Macharia (http://eddmash.com)<edd.cowan@gmail.com>
     *
     * @throws \Exception
     */
    public static function run($autoRun = true, InputInterface $input = null, OutputInterface $output = null)
    {
        $console = new Application('');
        $def = new ListCommand();
        $console->add($def);
        $console->setDefaultCommand($def->getName());

        $console->addCommands(self::getCommands());
        if (null === $output) {
            $output = new ConsoleOutput();
        }
        self::warningText($output);
        self::errorText($output);

        if ($autoRun) {
            $console->run($input, $output);
        }

        return $console;
    }

    public static function warningText(OutputInterface $output)
    {
        $style = new OutputFormatterStyle('magenta', 'black', ['bold']);
        $output->getFormatter()->setStyle('warning', $style);
    }

    public static function errorText(OutputInterface $output)
    {
        $style = new OutputFormatterStyle('red', 'black', ['bold']);
        $output->getFormatter()->setStyle('errortext', $style);
    }
}
