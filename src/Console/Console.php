<?php

namespace Eddmash\PowerOrm\Console;

use Eddmash\PowerOrm\Helpers\ArrayHelper;

/**
 * Borrows heavily from the the yii\helpers\BaseConsole class,.
 *
 * Part of the Yii framework.
 *
 * @version    2.0
 *
 * @author Carsten Brandt <mail@cebe.cc>
 *
 * @see http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 * Class Console
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Console
{
    // foreground color control codes
    const FG_BLACK = 30;
    const FG_RED = 31;
    const FG_GREEN = 32;
    const FG_YELLOW = 33;
    const FG_BLUE = 34;
    const FG_PURPLE = 35;
    const FG_CYAN = 36;
    const FG_GREY = 37;
    // background color control codes
    const BG_BLACK = 40;
    const BG_RED = 41;
    const BG_GREEN = 42;
    const BG_YELLOW = 43;
    const BG_BLUE = 44;
    const BG_PURPLE = 45;
    const BG_CYAN = 46;
    const BG_GREY = 47;
    // fonts style control codes
    const RESET = 0;
    const NORMAL = 0;
    const BOLD = 1;
    const ITALIC = 3;
    const UNDERLINE = 4;
    const BLINK = 5;
    const NEGATIVE = 7;
    const CONCEALED = 8;
    const CROSSED_OUT = 9;
    const FRAMED = 51;
    const ENCIRCLED = 52;
    const OVERLINED = 53;

    /**
     * Will return a string formatted with the given ANSI style.
     *
     * @param string $string the string to be formatted
     * @param array  $format An array containing formatting values.
     *                       You can pass any of the FG_*, BG_* and TEXT_* constants
     *                       and also [[xtermFgColor]] and [[xtermBgColor]] to specify a format
     *
     * @return string
     */
    public static function ansiFormat($string, $format = [])
    {
        $code = implode(';', $format);

        return "\033[0m".('' !== $code ? "\033[".$code.'m' : '').$string."\033[0m";
    }

    /**
     * Prints a string to STDOUT.
     *
     * @param string $string the string to print
     *
     * @return int|bool Number of bytes printed or false on error
     */
    public static function stdout($string)
    {
        return fwrite(\STDOUT, $string);
    }

    /**
     * Prints a string to STDERR.
     *
     * @param string $string the string to print
     *
     * @return int|bool Number of bytes printed or false on error
     */
    public static function stderr($string)
    {
        return fwrite(\STDERR, $string);
    }

    /**
     * Asks the user for input. Ends when the user types a carriage return (PHP_EOL). Optionally, It also provides a
     * prompt.
     *
     * @param string $prompt the prompt to display before waiting for input (optional)
     *
     * @return string the user's input
     */
    public static function input($prompt = null)
    {
        if (isset($prompt)) {
            static::stdout($prompt);
        }

        return static::stdin();
    }

    /**
     * Gets input from STDIN and returns a string right-trimmed for EOLs.
     *
     * @param bool $raw If set to true, returns the raw string without trimming
     *
     * @return string the string read from stdin
     */
    public static function stdin($raw = false)
    {
        return $raw ? fgets(\STDIN) : rtrim(fgets(\STDIN), PHP_EOL);
    }

    /**
     * Prints text to STDOUT appended with a carriage return (PHP_EOL).
     *
     * @param string $string the text to print
     *
     * @return int|bool number of bytes printed or false on error
     */
    public static function output($string = null)
    {
        return static::stdout($string.PHP_EOL);
    }

    /**
     * Prints text to STDERR appended with a carriage return (PHP_EOL).
     *
     * @param string $string the text to print
     *
     * @return int|bool number of bytes printed or false on error
     */
    public static function error($string = null)
    {
        return static::stderr($string.PHP_EOL);
    }

    /**
     * Returns true if the stream supports colorization. ANSI colors are disabled if not supported by the stream.
     *
     * - windows without ansicon
     * - not tty consoles
     *
     * @param mixed $stream
     *
     * @return bool true if the stream supports ANSI colors, otherwise false
     */
    public static function streamSupportsAnsiColors($stream)
    {
        return DIRECTORY_SEPARATOR === '\\'
            ? false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI')
            : function_exists('posix_isatty') && @posix_isatty($stream);
    }

    /**
     * Returns true if the console is running on windows.
     *
     * @return bool
     */
    public static function isRunningOnWindows()
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * Usage: list($width, $height) = ConsoleHelper::getScreenSize();.
     *
     * @param bool $refresh whether to force checking and not re-use cached size value.
     *                      This is useful to detect changing window size while the application is running but may
     *                      not get up to date values on every terminal
     *
     * @return array|bool An array of ($width, $height) or false when it was not able to determine size
     */
    public static function getScreenSize($refresh = false)
    {
        static $size;
        if (null !== $size && !$refresh) {
            return $size;
        }

        if (static::isRunningOnWindows()) {
            $output = [];
            exec('mode con', $output);
            if (isset($output, $output[1]) && false !== strpos($output[1], 'CON')) {
                return $size = [(int) preg_replace('~\D~', '', $output[3]), (int) preg_replace('~\D~', '', $output[4])];
            }
        } else {
            // try stty if available
            $stty = [];
            if (exec('stty -a 2>&1', $stty) && preg_match(
                    '/rows\s+(\d+);\s*columns\s+(\d+);/mi',
                    implode(' ', $stty),
                    $matches
                )
            ) {
                return $size = [$matches[2], $matches[1]];
            }

            // fallback to tput, which may not be updated on terminal resize
            if (($width = (int) exec('tput cols 2>&1')) > 0 && ($height = (int) exec('tput lines 2>&1')) > 0) {
                return $size = [$width, $height];
            }

            // fallback to ENV variables, which may not be updated on terminal resize
            if (($width = (int) getenv('COLUMNS')) > 0 && ($height = (int) getenv('LINES')) > 0) {
                return $size = [$width, $height];
            }
        }

        return $size = false;
    }

    /**
     * Word wrap text with indentation to fit the screen size.
     *
     * If screen size could not be detected, or the indentation is greater than the screen size, the text will not be wrapped.
     *
     * The first line will **not** be indented, so `Console::wrapText("Lorem ipsum dolor sit amet.", 4)` will result in the
     * following output, given the screen width is 16 characters:
     *
     * ```
     * Lorem ipsum
     *     dolor sit
     *     amet.
     * ```
     *
     * @param string $text    the text to be wrapped
     * @param int    $indent  number of spaces to use for indentation
     * @param bool   $refresh whether to force refresh of screen size.
     *                        This will be passed to [[getScreenSize()]]
     *
     * @return string the wrapped text
     *
     * @since 2.0.4
     */
    public static function wrapText($text, $indent = 0, $refresh = false)
    {
        $size = static::getScreenSize($refresh);
        if (false === $size || $size[0] <= $indent) {
            return $text;
        }
        $pad = str_repeat(' ', $indent);
        $lines = explode("\n", wordwrap($text, $size[0] - $indent, "\n", true));
        $first = true;
        foreach ($lines as $i => $line) {
            if ($first) {
                $first = false;

                continue;
            }
            $lines[$i] = $pad.$line;
        }

        return implode("\n", $lines);
    }

    /**
     * Prompts the user for input and validates it.
     *
     * @param string $text    prompt string
     * @param array  $options the options to validate the input:
     *
     * - `required`: whether it is required or not
     * - `default`: default value if no input is inserted by the user
     * - `pattern`: regular expression pattern to validate user input
     * - `validator`: a callable function to validate input. The function must accept two parameters:
     * - `input`: the user input to validate
     * - `error`: the error value passed by reference if validation failed
     *
     * @return string the user input
     */
    public static function prompt($text, $options = [])
    {
        $options = ArrayHelper::merge(
            [
                'required' => false,
                'default' => null,
                'pattern' => null,
                'validator' => null,
                'error' => 'Invalid input.',
            ],
            $options
        );
        $error = null;

        top:
        $input = $options['default']
            ? static::input("$text [".$options['default'].'] ')
            : static::input("$text ");

        if ('' === $input) {
            if (isset($options['default'])) {
                $input = $options['default'];
            } elseif ($options['required']) {
                static::output($options['error']);
                goto top;
            }
        } elseif ($options['pattern'] && !preg_match($options['pattern'], $input)) {
            static::output($options['error']);
            goto top;
        } elseif ($options['validator'] &&
            !call_user_func_array($options['validator'], [$input, &$error])
        ) {
            static::output(isset($error) ? $error : $options['error']);
            goto top;
        }

        return $input;
    }

    /**
     * Asks user to confirm by typing y or n.
     *
     * @param string $message to print out before waiting for user input
     * @param bool   $default this value is returned if no selection is made
     *
     * @return bool whether user confirmed
     */
    public static function confirm($message, $default = false)
    {
        while (true) {
            static::stdout($message.' (yes|no) ['.($default ? 'yes' : 'no').']:');
            $input = trim(static::stdin());

            if (empty($input)) {
                return $default;
            }

            if (!strcasecmp($input, 'y') || !strcasecmp($input, 'yes')) {
                return true;
            }

            if (!strcasecmp($input, 'n') || !strcasecmp($input, 'no')) {
                return false;
            }
        }
    }

    /**
     * Gives the user an option to choose from. Giving '?' as an input will show
     * a list of options to choose from and their explanations.
     *
     * @param string $prompt  the prompt message
     * @param array  $options Key-value array of options to choose from
     *
     * @return string An option character the user chose
     */
    public static function select($prompt, $options = [])
    {
        top:
        static::stdout("$prompt [".implode(',', array_keys($options)).',?]: ');
        $input = static::stdin();
        if ('?' === $input) {
            foreach ($options as $key => $value) {
                static::output(" $key - $value");
            }
            static::output(' ? - Show help');
            goto top;
        } elseif (!ArrayHelper::hasKey($options, $input)) {
            goto top;
        }

        return $input;
    }
}
