<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/25/16
 * Time: 7:48 PM
 */

namespace eddmash\powerorm\console;

use eddmash\powerorm\exceptions\ValueError;

class Parser
{
    private $tokens;
    private $parsed;
    private $prefix;

    /**
     * @param array $argv An array of parameters from the CLI (in the argv format)
     */
    public function __construct(array $argv = null)
    {
        if (null === $argv) :
            $argv = $_SERVER['argv'];
        endif;

        $this->prefix = '-';

        // strip the application name
        array_shift($argv);
        $this->tokens = $argv;
    }

    public function parse()
    {
        $parseOptions = true;
        $this->parsed = $this->tokens;
        while (null !== $token = array_shift($this->parsed)) {
            //            echo $token.PHP_EOL;
            if ($parseOptions && '' == $token) {
                $this->parseArgument($token);
            } elseif ($parseOptions && '--' == $token) {
                $parseOptions = false;
            } elseif ($parseOptions && 0 === strpos($token, '--')) {
                //                $this->parseLongOption($token);
            } elseif ($parseOptions && '-' === $token[0] && '-' !== $token) {
                //                $this->parseShortOption($token);
            } else {
                //                $this->parseArgument($token);
            }
        }
    }


    /**
     * name or flags - Either a name or a list of option strings, e.g. foo or -f, --foo.
     * action - The basic type of action to be taken when this argument is encountered at the command line.
     * nargs - The number of command-line arguments that should be consumed.
     * const - A constant value required by some action and nargs selections.
     * default - The value produced if the argument is absent from the command line.
     * type - The type to which the command-line argument should be converted.
     * choices - A container of the allowable values for the argument.
     * required - Whether or not the command-line option may be omitted (optionals only).
     * help - A brief description of what the argument does.
     * metavar - A name for the argument in usage messages.
     * dest - The name of the attribute to be added to the object returned by parse_args().
     *
     * @param array $opts
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws ValueError
     */
    public function add_argument(array $opts = null)
    {
        $name = null;
        $dest = null;
        extract($opts);

        if (!empty($name) && count($name) == 1 && substr($name, 1, 1) == $this->prefix):
            if ($dest !== null):
                throw new ValueError('dest supplied twice for a none optional argument');
        endif;
        $mandatory = $this->get_mandatory_arguments(); else:
            $optional = $this->get_optional_arguments();
        endif;
    }
}
