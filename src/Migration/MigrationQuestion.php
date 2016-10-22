<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 9/30/16.
 */
namespace Eddmash\PowerOrm\Migration;

use Eddmash\PowerOrm\Console\Console;
use Eddmash\PowerOrm\Console\Question\Asker;
use Eddmash\PowerOrm\Console\Question\ChoiceQuestion;
use Eddmash\PowerOrm\Console\Question\ConfirmationQuestion;
use Eddmash\PowerOrm\Console\Question\Question;
use Eddmash\PowerOrm\Model\Field\Field;

class MigrationQuestion
{
    public static function hasModelRenamed($oldModelName, $newModelName)
    {
        $msg = 'Did you rename the %s model to %s? [y/N]';

        return new ConfirmationQuestion(sprintf($msg, $oldModelName, $newModelName));
    }

    /**
     * @param $modelName
     * @param $oldName
     * @param $newName
     * @param Field $fieldObj
     *
     * @return ConfirmationQuestion
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function hasFieldRenamed($modelName, $oldName, $newName, $fieldObj) {
        $msg = 'Did you rename %1$s.%2$s to %1$s.%3$s (a %4$s)? [y/N]';

        return new ConfirmationQuestion(sprintf($msg, $modelName, $oldName, $newName, $fieldObj->getShortClassName()));
    }

    /**
     * @param Asker $asker
     *
     * @return ChoiceQuestion
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function askNotNullAddition($asker, $modelName, $fieldName) {
        $msg = 'You are trying to add a non-nullable field "%s" to %s without a default; '.PHP_EOL.
                ' we can\'t do that (the database needs something to populate existing rows).'.PHP_EOL.
                ' Please select a fix:'.PHP_EOL;

        $choices = [
            'Provide a one-off default now (will be set on all existing rows)',
            'Quit, and let me add a default in model file',
        ];

        foreach ($choices as $index => $choice) :

            $msg .= sprintf("\t%s. %s".PHP_EOL, $index + 1, $choice);
        endforeach;

        $msg .= ' Select an option:';

        $selected = (int) $asker->ask(new Question(sprintf($msg, $fieldName, $modelName)));

        if($selected == 2):
            exit;
        endif;

        $default_val = '';
        $msg = 'Please enter the default value now, as valid PHP'.PHP_EOL;
        while(true):
            $default = $asker->ask(new Question($msg));
            if(empty($default)):
                $msg = " Please enter some value, or 'exit' (with no quotes) to exit.".PHP_EOL;
            elseif($default == 'exit'):
                exit;
            elseif($default === false):
                Console::error(PHP_EOL.' An error occured while trying to set default value');
                exit;
            else:
                $default_val = $default;
                break;
            endif;
        endwhile;

        return self::_getDefault($asker);
    }

    /**
     * @param Asker $asker
     * @param $modelName
     * @param $fieldName
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function askNotNullAlteration($asker, $modelName, $fieldName) {
        $msg = 'You are trying to add a non-nullable field "%s" to %s without a default; '.PHP_EOL.
                ' we can\'t do that (the database needs something to populate existing rows).'.PHP_EOL.
                ' Please select a fix:'.PHP_EOL;

        $choices = [
            'Provide a one-off default now (will be set on all existing rows)',
            'Ignore for now, and let me handle existing rows with NULL myself ',
            'Quit, and let me add a default in model file',
        ];

        foreach ($choices as $index => $choice) :

            $msg .= sprintf("\t%s. %s".PHP_EOL, $index + 1, $choice);
        endforeach;

        $msg .= 'Select an option:';

        $selected = (int) $asker->ask(new Question(sprintf($msg, $fieldName, $modelName)));

        if($selected == 2):
            return NOT_PROVIDED;
        elseif($selected == 3):
            exit;
        endif;

        return self::_getDefault($asker);
    }

    /**
     * @param Asker $asker
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private static function _getDefault($asker) {
        $default_val = '';
        $msg = 'Please enter the default value now, as valid PHP '.PHP_EOL;
        while(true):
            $default = $asker->ask(new Question($msg));
            if(empty($default)):
                $msg = " Please enter some value, or 'exit' (with no quotes) to exit.".PHP_EOL;
            elseif($default == 'exit'):
                exit;
            elseif($default === false):
                Console::error(PHP_EOL.' An error occured while trying to set default value');
                exit;
            else:
                $default_val = $default;
                break;
            endif;
        endwhile;

        return $default_val;
    }
}
