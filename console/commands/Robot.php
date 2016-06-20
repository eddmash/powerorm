<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/2/16
 * Time: 10:20 PM
 */

namespace powerorm\console\command;

/**
 * Borrowed from fuelphp oil robot
 * @package powerorm\console\command
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Robot extends Command
{

    /**
     * @inheritdoc
     */
    public $system_check = FALSE;

    public $help = "A little fun is good for the soul";

    public function handle(){
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
            //\\  <\ _//^\\_ />  //\\
            \||/  |\//// \\\\/|  \||/
                  |   |   |   |
                  |---|   |---|
                  |---|   |---|
                  |   |   |   |
                  |___|   |___|
                  /   \   /   \
                 |_____| |_____|
                 |HHHHH| |HHHHH|';

        $this->normal($robot, TRUE);
    }

}