<?php
namespace powerorm\console;

use powerorm\helpers\FileHandler;

/**
 * Class Manager
 * @package powerorm\console
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Manager extends Base{

    public $default_command = 'help';

    public $default_help = "Provides help information about console commands.";

    public $command_path;
    
    public $manager_name;

    public function __construct(){
        $this->path = sprintf("%s/commands", dirname(__FILE__));

        $this->manager_name = $this->lower_case($this->get_class_name());
    }

    public function default_commands(){
        return ['help'=>'', 'version'=>''];
    }

    public function execute(){

        // get console args
        $arg_opts = $_SERVER['argv'];
        $arg_count = $_SERVER['argc'];

        // remove the manager from the list
        $manager = array_shift($arg_opts);

        // get command name and remove it from list
        $command_name = array_shift($arg_opts);

        $command_name = (!empty($command_name)) ?  $command_name: $this->default_command;

        $command_name = ucfirst($command_name);

        if(in_array($command_name, ['Help']) || empty($command_name)):
            $this->main_help_text($arg_opts);
            exit;
        endif;

        if(in_array($command_name, ['Version', '--version', '-v'])):
            $this->normal("PowerOrm Version : ".$this->ansiFormat(POWERORM_VERSION, Console::FG_CYAN).PHP_EOL);
            exit;
        endif;


        $this->fetch_command($command_name)->execute($arg_opts, $manager, $command_name);

    }

    public function main_help_text($arg_opts=[]){
        $this->stdout(PHP_EOL);


        if(!empty($arg_opts)):
            $subcommand = array_shift($arg_opts);

            $command = $this->fetch_command($subcommand);

            $options = $command->get_options();
            $help = $command->get_help();

            $message = sprintf('php %1$s.php %2$s', $this->manager_name, $subcommand);
            $this->normal($help.PHP_EOL.PHP_EOL);

            $this->normal(sprintf('Usage : %1$s %2$s',
                    $this->lower_case($message), stringify(array_keys($options), NULL, NULL, NULL, TRUE)).PHP_EOL.PHP_EOL);

            $this->normal("optional arguments:".PHP_EOL.PHP_EOL);

            $maxlen = 5;
            foreach ($options as $key=>$value) :
                $len = strlen($key) + 2 + ($key === $this->default_command ? 10 : 0);
                if ($maxlen < $len) :
                    $maxlen = $len;
                endif;
            endforeach;

            foreach ($options as $key=>$value) :

                $this->stdout(" " . $this->ansiFormat($key, Console::FG_YELLOW));
                $len = strlen($key) + 2;

                if ($value !== '') {
                    $this->stdout(str_repeat(' ', $maxlen - $len + 2) . Console::wrapText($value, $maxlen + 2));
                }
                $this->stdout("\n");
            endforeach;

            exit;
        endif;

        $this->info($this->default_help.PHP_EOL.PHP_EOL);
        $in_message = $this->ansiFormat(sprintf("php %s.php help <subcommand>", $this->manager_name), Console::FG_YELLOW);
        $this->normal(sprintf('Type %s for help on a specific subcommand.', $in_message).PHP_EOL.PHP_EOL);
        $this->normal(sprintf('Available Commands : ').PHP_EOL);

        $path = sprintf("%s/commands", dirname(__FILE__));

        $files = (new FileHandler($path))->get_path_files();

        foreach ($files as $file) :
            $file = basename($file, '.php');

            $file = $this->lower_case($file);
            // ignore base class
            if($file=='command'):
                continue;
            endif;
            $this->normal("\t ".$file.PHP_EOL);
        endforeach;

        foreach ($this->default_commands() as $name=>$command) :

            $file = $this->lower_case($name);
            // ignore base class
            if($file=='command'):
                continue;
            endif;
            $this->normal("\t ".$file.PHP_EOL);
        endforeach;

    }

    public function fetch_command($name){
        $name = ucfirst($name);

        $file_handler = new FileHandler($this->path, $name);

        $file = $file_handler->get_file();

        if(empty($file)):
            $this->error(sprintf("Unknown command: %s".PHP_EOL, $name));
            $message = $this->ansiFormat(sprintf('php %s.php help', $this->manager_name), Console::FG_YELLOW);
            $this->normal(sprintf("Type %s for usage.".PHP_EOL, $message));
            exit;
        endif;

        require_once $file;

        // commands are in the commands namespace
        $name = 'powerorm\console\command\\'.$name;


        return new $name();
    }

    public static function run()
    {
        (new static)->execute();
    }
}
