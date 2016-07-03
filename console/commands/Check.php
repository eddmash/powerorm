<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/2/16
 * Time: 10:20 PM
 */

namespace powerorm\console\command;

/**
 * @package powerorm\console\command
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Check extends Command
{

    /**
     * @inheritdoc
     */
    public $system_check = FALSE;

    public $help = "Runs systems check for potential problems";

    public function handle(){
        $this->check();
    }

}