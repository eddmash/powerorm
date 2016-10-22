<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 6/2/16.
 */
namespace Eddmash\PowerOrm\Console\Command;

/**
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Check extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public $system_check = false;

    public $help = 'Runs systems check for potential problems';

    public function handle()
    {
        $this->check();
    }
}
