<?php
/**
 * Created by http://eddmash.com.
 * User: edd
 * Date: 4/21/16
 * Time: 12:07 PM
 */

namespace eddmash\powerorm\migrations;

use eddmash\powerorm\console\Base;

/**
 * Class Questioner
 * @package eddmash\powerorm\migrations
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Questioner extends Base
{
    public static $instance;
    public $out;


    public static function instance($args = [])
    {
        if (null === static::$instance) {
            static::$instance = new static($args);
        }

        return static::$instance;
    }

    public function ask_not_null_default($field_name, $model_name)
    {
        return null;
    }

    public function ask_not_null_alteration($field_name, $model_name)
    {
        return null;
    }

    public function ask_rename_model($old_model_name, $new_model_name)
    {
        return null;
    }

    public function ask_rename($model_name, $new_field_name, $old_field_name, $field)
    {
        return null;
    }
}

/**
 * Class InteractiveQuestioner
 * @package eddmash\powerorm\migrations
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class InteractiveQuestioner extends Questioner
{
    /**
     * Adding a NOT NULL field to a model
     */
    public function ask_not_null_default($field_name, $model_name)
    {
        $message = "You are trying to add a non-nullable field '%1\$s' to '%2\$s' model without a default;" . PHP_EOL .
            " we can't do that (the database needs something to populate existing rows)" . PHP_EOL .
            " Please choose a fix :" . PHP_EOL .
            "\t1. Provide a one-off default now (will be set on all existing rows)" . PHP_EOL .
            "\t2. Quit, and let me add a default in %2\$s" . PHP_EOL;

        $this->normal(sprintf($message, $field_name, ucwords($model_name)));

        return $this->_get_choice();
    }

    public function ask_not_null_alteration($field_name, $model_name)
    {
        $message = "You are trying to change the nullable field '%1\$s' on %2\$s to non-nullable without a default;" . PHP_EOL .
            "  we can't do that (the database needs something to populate existing rows)" . PHP_EOL .
            " Please choose a fix :" . PHP_EOL .
            "\t1. Provide a one-off default now (will be set on all existing rows)" . PHP_EOL .
            "\t2. Quit, and let me add a default in %2\$s" . PHP_EOL;

        $this->normal(sprintf($message, $field_name, ucwords($model_name)));

        return $this->_get_choice();
    }

    public function ask_rename_model($old_model_name, $new_model_name)
    {
        $message = "Did you rename the [ %1\$s ] model to [ %2\$s ] ? [y/n]";
        $this->normal(sprintf($message, ucwords($old_model_name), ucwords($new_model_name)));
        return $this->_boolean_input();
    }

    public function ask_rename($model_name, $new_field_name, $old_field_name, $field)
    {
        $message = 'Did you rename the [ %1$s->%2$s ] field to [ %1$s->%3$s ] ? [y/n]';
        $this->normal(sprintf($message, ucwords($model_name), $new_field_name, $old_field_name));
        return $this->_boolean_input();
    }

    public function _get_choice()
    {
        $this->normal('Select an option: ');
        $choice = strtolower(trim(fgets(STDIN)));

        if ($choice == 2):
            exit;
        endif;

        if ($choice == 1):

            $this->normal(PHP_EOL . " Please enter the default value now, as valid PHP", true);
        $default_val = '';

        while (true):
                $default = strtolower($this->input());

        if (empty($default)):
                    $this->normal(PHP_EOL . " Please enter some value, or 'exit' (with no quotes) to exit.", true); elseif ($default == 'exit'):
                    exit; elseif ($default === false):
                    $this->error(PHP_EOL . " An error occured while trying to set default value", true);
        exit; else:
                    $default_val = $default;
        break;
        endif;
        endwhile;
        return $default_val;
        endif;
    }

    public function _boolean_input()
    {
        $input = strtolower(trim(fgets(STDIN)));

        while (!in_array($input[0], ['y', 'n'])):
            $message = "Please answer with yes / no :";
        $this->normal($message);
        $input = strtolower(trim(fgets(STDIN)));
        endwhile;

        $input = strtolower($input);

        return $input[0] === 'y';
    }
}
