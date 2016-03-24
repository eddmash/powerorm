<?php

namespace powerorm\cli;


use Migrator;
use powerorm\checks\Checks;

abstract class Command{

    protected $opts;

    public function __construct($opts=[]){
        $this->opts = $opts;

        // runchecks
        Checks::run();

        //execute command
        $this->run();
    }

    /**
     * Contains the logic of the command, i.e. what you want executed when command is run.
     * @return mixed
     */
    public abstract function run();
}

class MakeMigrations extends Command{

    public function run(){
        Migrator::makemigrations();
    }
}
class Migrate extends Command{

    public function run(){
        Migrator::migrate();
    }
}
class RollBack extends Command{

    public function run(){
        if(!isset($this->opts['version'])):
            ColorCLi::error("rollback expects a version to be passed in, none was provided");
        endif;

        Migrator::rollback($this->opts['version']);
    }
}