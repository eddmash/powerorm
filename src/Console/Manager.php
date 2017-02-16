<?php

namespace Eddmash\PowerOrm\Console;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\Console\Command\BaseCommand;
use Eddmash\PowerOrm\Console\Command\ListCommand;
use Eddmash\PowerOrm\Helpers\ClassHelper;
use Eddmash\PowerOrm\Helpers\FileHandler;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
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
        $components = (array) BaseOrm::getInstance()->components;

        $paths = [];

        foreach ($components as $name => $path) :
            $paths[$name] = sprintf('%ssrc/Console/Command', $path);
        endforeach;

        return $paths;
    }

    public function execute()
    {
        $console = new Application('');
        $def = new ListCommand();
        $console->add($def);
        $console->setDefaultCommand($def->getName());

        foreach ($this->path as $path) :
            $files = (new FileHandler($path))->readDir();
            foreach ($files as $file) :
                $command = basename($file, '.php');
                if ('BaseCommand' === $command):
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
                sprintf(
                    'Unknown command: ` %1$s`. Does the file exists `%2$s/%1$s.php` ?'.PHP_EOL,
                    $name,
                    $this->path
                )
            );
            $message = $this->ansiFormat(sprintf('php %s.php help', $this->managerName), Console::FG_YELLOW);
            $this->normal(sprintf('Type %s for usage.'.PHP_EOL, $message));

            return false;
        endif;

        // commands are in the commands namespace
        /** @var $className BaseCommand */
        $className = ClassHelper::getFormatNamespace($packageName).'Console\Command\\'.$name;

        return new $className();
    }

    public static function run()
    {
        (new static())->execute();
    }

    public static function warningText(OutputInterface $output)
    {
        $style = new OutputFormatterStyle('magenta', 'black', array('bold'));
        $output->getFormatter()->setStyle('warning', $style);
    }

    public static function errorText(OutputInterface $output)
    {
        $style = new OutputFormatterStyle('red', 'black', array('bold'));
        $output->getFormatter()->setStyle('errortext', $style);
    }
}
