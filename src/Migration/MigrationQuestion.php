<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 9/30/16
 * Time: 10:54 AM
 */

namespace Eddmash\PowerOrm\Migration;


use Eddmash\PowerOrm\Console\Question\ConfirmationQuestion;

class MigrationQuestion
{

    public static function hasModelRenamed($oldModelName, $newModelName)
    {
        $msg = 'Did you rename the %s model to %s? [y/N]';
        return new ConfirmationQuestion(sprintf($msg, $oldModelName, $newModelName));
    }
}