<?php
namespace powerorm\console\command;


use powerorm\checks\CheckMessage;
use powerorm\checks\Checks;
use powerorm\console\Base;
use powerorm\console\Console;
use powerorm\exceptions\NotImplemented;

/**
 * Class Command
 * @package powerorm\console\command
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Command extends Base
{
    /**
     * If true the command will perfom check before it runs.
     * @var bool
     */
    public $system_check = TRUE;

    public function get_options(){
        return [
            '--help'=>'show this help message and exit',
            '--command-dir'=>'the directory where command is defined'
        ];
    }

    public function get_positional_options(){
        return [];
    }

    /**
     * Returns help information for this controller.
     *
     * You may override this method to return customized help.
     * The default implementation returns help information retrieved from the PHPDoc comment.
     * @return string
     */
    public function get_help()
    {
        return $this->help;
    }

    public function usage()
    {
        $command = $this->get_command_name();

        $options_names = array_merge(array_keys($this->get_positional_options()), array_keys($this->get_options()));

        $options = sprintf("[ %s ]", join(' || ', $options_names));

        $usage = sprintf('Usage : %1$s %2$s %3$s ', $this->manager_name, $command, $options);

        $help = $this->get_help();
        $this->stdout(PHP_EOL);

        if(!empty($help)):
            $this->normal($help, TRUE);
            $this->stdout(PHP_EOL);

            $this->normal(Console::wrapText($usage, 8), TRUE);
        else:
            $this->normal($usage, TRUE);
        endif;

        $this->stdout(PHP_EOL);

        $this->command_options();

    }

    public function get_command_name()
    {
        $name = array_pop(explode("\\", $this->get_class_name()));
        return $this->lower_case($name);
    }
    
    public function handle($arg_opts=[]){
        return new NotImplemented("Subclasses of the class Command must implement the handle()");
    }
    
    public function execute($arg_opts, $manager){

        $marker = $this->ansiFormat("~~~>", Console::FG_YELLOW);
        $version = $this->ansiFormat(POWERORM_VERSION, Console::FG_CYAN);
        $this->normal(sprintf(PHP_EOL.'%2$s Using powerorm version : %1$s '.PHP_EOL, $version, $marker), TRUE);


        $this->manager_name = $manager;

        if(in_array('--help', $arg_opts)):
            $this->usage();
            exit;
        endif;

        if($this->system_check):
            $this->check();
        endif;

        $output = $this->handle($arg_opts);

        if(!empty($output)):
            $this->normal($output.PHP_EOL);
        endif;
    }

    public function command_options()
    {

        $maxlen = 5;
        $default_help = '--help';
        foreach ($this->get_options() as $key=>$value) :
            $len = strlen($key) + 2 + ($key === $default_help ? 10 : 0);
            if ($maxlen < $len) :
                $maxlen = $len;
            endif;
        endforeach;


        $this->normal("Position Arguments:".PHP_EOL.PHP_EOL);
        $positional = $this->get_positional_options();

        if(!empty($positional)):
            foreach ($this->get_positional_options() as $key=>$value) :

                $this->stdout(" " . $this->ansiFormat($key, Console::FG_YELLOW));
                $len = strlen($key) + 2;

                if ($value !== '') {
                    $this->stdout(str_repeat(' ', $maxlen - $len + 2) . Console::wrapText($value, $maxlen + 2));
                }
                $this->stdout(PHP_EOL.PHP_EOL);
            endforeach;
        endif;


        $this->normal("Optional Arguments:".PHP_EOL.PHP_EOL);


        foreach ($this->get_options() as $key=>$value) :

            $this->stdout(" " . $this->ansiFormat($key, Console::FG_YELLOW));
            $len = strlen($key) + 2;

            if ($value !== '') {
                $this->stdout(str_repeat(' ', $maxlen - $len + 2) . Console::wrapText($value, $maxlen + 2));
            }
            $this->stdout("\n");
        endforeach;

    }
    
    public function check(){
        $checks = (new Checks())->run_checks();

        $debugs = [];
        $info = [];
        $warning = [];
        $errors = [];
        $critical = [];

        foreach ($checks as $check) :
            if($check->level < CheckMessage::INFO ):
                $debugs[] = $check;
            endif;

            // info
            if($check->level >= CheckMessage::INFO &&  $check->level < CheckMessage::WARNING):
                $info[] = $check;
            endif;

            // warning
            if($check->level >= CheckMessage::WARNING &&  $check->level < CheckMessage::ERROR):
                $warning[] = $check;
            endif;

            //error
            if($check->level >= CheckMessage::ERROR &&  $check->level < CheckMessage::CRITICAL):
                $errors[] = $check;
            endif;

            //critical
            if($check->level >= CheckMessage::CRITICAL):
                $critical[] = $check;
            endif;
        endforeach;

        $this->normal("Perfoming system checks ...", TRUE);

        $issue = (count($checks)==1)?'issue':'issues';
        $this->normal(sprintf('System check identified %1$s %2$s', count($checks), $issue), TRUE);

        $errors = array_merge($critical, $errors);
        if(!empty($errors)):
            $this->error(join(PHP_EOL, $errors), TRUE);
            exit;
        endif;

        if(!empty($warning)):
            $this->warning(join(PHP_EOL, $warning), TRUE);
        endif;

        if(!empty($info)):
            $this->info(join(PHP_EOL, $info), TRUE);
        endif;

        if(!empty($debugs)):
            $this->normal(join("  ".PHP_EOL, $debugs), TRUE);
        endif;


    }

}