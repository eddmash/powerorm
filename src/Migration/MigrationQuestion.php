<?php
/**
 * Created by eddmash <http://eddmash.com>
 * Date: 9/30/16.
 */

namespace Eddmash\PowerOrm\Migration;

use Eddmash\PowerOrm\Console\Console;
use Eddmash\PowerOrm\Console\Question\Asker;
use Eddmash\PowerOrm\Model\Field\Field;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class MigrationQuestion
{
    /**
     * @param Asker $asker
     * @param       $oldModelName
     * @param       $newModelName
     *
     * @return ConfirmationQuestion
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function hasModelRenamed(Asker $asker, $oldModelName, $newModelName)
    {
        $msg = 'Did you rename the %s model to %s? [y/N]';

        $q = new ConfirmationQuestion(sprintf($msg, $oldModelName, $newModelName));

        return $asker->ask($q);
    }

    /**
     * @param Asker $asker
     * @param       $modelName
     * @param       $oldName
     * @param       $newName
     * @param Field $fieldObj
     *
     * @return ConfirmationQuestion
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function hasFieldRenamed(Asker $asker, $modelName, $oldName, $newName, Field $fieldObj)
    {
        $msg = 'Did you rename %1$s.%2$s to %1$s.%3$s (a %4$s)? [y/N]';

        $q = new ConfirmationQuestion(sprintf($msg, $modelName, $oldName, $newName, $fieldObj->getShortClassName()));

        return $asker->ask($q);
    }

    /**
     * @param Asker $asker
     * @param       $modelName
     * @param       $fieldName
     * @param Field $field
     *
     * @return string|void
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function askNotNullAddition(Asker $asker, $modelName, $fieldName, $field)
    {
        $msg = 'You are trying to add a non-nullable field "%s" to %s without a default; '.PHP_EOL.
            'we can\'t do that (the database needs something to populate existing rows).'.PHP_EOL.
            'Please select a fix:'.PHP_EOL;

        $choices = [
            'Provide a one-off default now (will be set on all existing rows)',
            'Quit, and let me add a default in model file',
        ];

        foreach ($choices as $index => $choice) :

            $msg .= sprintf("\t%s. %s".PHP_EOL, $index + 1, $choice);
        endforeach;

        $msg .= 'Select an option: ';

        $selected = (int) $asker->ask(new Question(sprintf($msg, $fieldName, $modelName)));

        if (2 == $selected):
            return;
        endif;

        return self::getDefault($asker);
    }

    /**
     * @param Asker $asker
     * @param       $modelName
     * @param       $fieldName
     *
     * @return string
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function askNotNullAlteration(Asker $asker, $modelName, $fieldName)
    {
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

        if (2 == $selected):
            return NOT_PROVIDED;
        elseif (3 == $selected):
            return;
        endif;

        return self::getDefault($asker);
    }

    /**
     * @param Asker $asker
     *
     * @return string
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private static function getDefault(Asker $asker)
    {
        $default_val = '';
        $msg = PHP_EOL.'Please enter the default value now, ensure its a valid PHP '.PHP_EOL;
        while (true):

            $default = $asker->ask(new Question($msg));
            if (empty($default)):
                $msg = " Please enter some value, or 'exit' (with no quotes) to exit.".PHP_EOL;
            elseif ('exit' == $default):
                break;
            elseif (false === $default):
                Console::error(PHP_EOL.' An error occured while trying to set default value');

                break;
            else:
                $default_val = $default;

                break;
            endif;
        endwhile;

        return $default_val;
    }
}
