<?php

namespace Eddmash\PowerOrm\Console;

use Eddmash\PowerOrm\BaseObject;

/**
 * Class Base.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Base extends BaseObject
{
    const EXIT_CODE_NORMAL = 0;
    const EXIT_CODE_ERROR = 1;

    /**
     * @var bool whether to run the command interactively
     */
    public $interactive = true;

    /**
     * @var bool whether to enable ANSI color in the output.
     *           If not set, ANSI color will only be enabled for terminals that support it
     */
    public $color;

    public $help = '';
    public $summary = '';

    public function wrapText($message, $indent, $refresh = false)
    {
        return Console::wrapText($message, $indent, $refresh);
    }

    /**
     * Returns a value indicating whether ANSI color is enabled.
     *
     * ANSI color is enabled only if [[color]] is set true or is not set
     * and the terminal supports ANSI color.
     *
     * @param resource $stream the stream to check
     *
     * @return bool Whether to enable ANSI style in output
     */
    public function isColorEnabled($stream = \STDOUT)
    {
        return null === $this->color ? Console::streamSupportsAnsiColors($stream) : $this->color;
    }

    /**
     * Formats a string with ANSI codes.
     *
     * You may pass additional parameters using the constants defined in [[\Eddmash\PowerOrm\ConsoleConsole]].
     *
     * Example:
     *
     * ```
     * echo $this->ansiFormat('This will be red and underlined.', Console::FG_RED, Console::UNDERLINE);
     * ```
     *
     * @param string $string the string to be formatted
     *
     * @return string
     */
    public function ansiFormat($string)
    {
        if ($this->isColorEnabled()) {
            $args = func_get_args();
            array_shift($args);
            $string = Console::ansiFormat($string, $args);
        }

        return $string;
    }

    /**
     * Prints a string to STDOUT.
     *
     * You may optionally format the string with ANSI codes by
     * passing additional parameters using the constants defined in [[\Eddmash\PowerOrm\ConsoleConsole]].
     *
     * Example:
     *
     * ```
     * $this->stdout('This will be red and underlined.', Console::FG_RED, Console::UNDERLINE);
     * ```
     *
     * @param string $string the string to print
     *
     * @return int|bool Number of bytes printed or false on error
     */
    public function stdout($string)
    {
        if ($this->isColorEnabled()) {
            $args = func_get_args();
            array_shift($args);
            $string = Console::ansiFormat($string, $args);
        }

        return Console::stdout($string);
    }

    /**
     * Prints a string to STDERR.
     *
     * You may optionally format the string with ANSI codes by
     * passing additional parameters using the constants defined in [[\Eddmash\PowerOrm\ConsoleConsole]].
     *
     * Example:
     *
     * ```
     * $this->stderr('This will be red and underlined.', Console::FG_RED, Console::UNDERLINE);
     * ```
     *
     * @param string $string the string to print
     *
     * @return int|bool Number of bytes printed or false on error
     */
    public function stderr($string)
    {
        if ($this->isColorEnabled(\STDERR)) {
            $args = func_get_args();
            array_shift($args);
            $string = Console::ansiFormat($string, $args);
        }

        return fwrite(\STDERR, $string);
    }

    public function normal($message, $newline = false)
    {
        if ($newline):
            $message = $message.PHP_EOL;
        endif;
        $this->stdout(' '.$message);
    }

    public function success($message, $newline = false)
    {
        if ($newline):
            $message = $message.PHP_EOL;
        endif;
        $this->stdout(' '.$message, Console::FG_GREEN);
    }

    public function info($message, $newline = false)
    {
        if ($newline):
            $message = $message.PHP_EOL;
        endif;
        $this->stdout(' '.$message, Console::FG_CYAN);
    }

    public function error($message, $newline = false)
    {
        if ($newline):
            $message = $message.PHP_EOL;
        endif;
        $this->stderr(' '.$message, Console::FG_RED);
    }

    public function warning($message, $newline = false)
    {
        if ($newline):
            $message = $message.PHP_EOL;
        endif;
        $this->stdout(' '.$message, Console::FG_PURPLE);
    }

    public function input($message = ' ')
    {
        return Console::input($message);
    }
}
