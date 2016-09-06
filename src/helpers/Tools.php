<?php
namespace eddmash\powerorm\helpers;

use eddmash\powerorm\DeConstructable;

/**
 * Class Tools
 * @package eddmash\powerorm\helpers
 * @since 1.1.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Tools
{
    public static function invoke_callback($callback, $model, $kwargs = [])
    {
        $callback($model, $kwargs);
        ;
    }

    /**
     * Takes an array and turns it into a string .
     * @param mixed $data the array to be converted to string
     * @param int $indent how to indent the items in the array
     * @param string $close item to come at the after of the arrays closing braces
     * @param string $start item to come at the before of the arrays opening braces
     * @param int $level at what level we are operating at, level=0 is the encasing level,just unifies the different
     * forms of data
     * @return string
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function stringify($data, $indent = 1, $close = '', $start = '', $level = 0)
    {
        $indent_character = "\t";
        $linebreak = PHP_EOL;
        $string_state = '';

        if ($indent == false):
            $linebreak = '';
        $indent_character = '';
        $indent = 0;
        endif;

        if (!empty($start)):
            $string_state .= str_repeat($indent_character, $indent) . "$start";
        endif;

        $totalCount = (is_array($data)) ? count($data) : 1;
        $counter = 1;

        // unify everything to an array, on the first round for consistencies.
        if ($level == 0):
            $data = [$data];
        endif;

        foreach ($data as $key => $value) :

            $string_state .= str_repeat($indent_character, $indent);

        $next_indent = ($indent == false) ? $indent : $indent + 1;

        if (is_array($value)):
                // HANDLE VALUE IF ARRAY

                $string_state .= "[" . $linebreak;

        if (!is_numeric($key)):
                    $string_state .= "'$key'=>";
        endif;

        $string_state .= self::stringify($value, $next_indent, $close, $start, ++$level);


        $string_state .= $linebreak;
        $string_state .= ($indent != false) ? str_repeat($indent_character, $indent - 1) : '';
        $string_state .= "]"; elseif (is_object($value)):

                // HANDLE VALUE THAT ARE OBJECTS THAT IMPLEMENT DeConstructable interface

                if ($value instanceof DeConstructable):
                    $skel = $value->skeleton();


        $class = $skel['full_name'];

        $constructor_args = $skel['constructor_args'];

        $string = self::stringify(reset($constructor_args), $next_indent, $close, $start, ++$level);

        $string_state .= sprintf("%1\$s(%2\$s)", $class, $string);
        endif; else:

                // HANDLE VALUE IF ITS NOT OBJECT OR ARRAY

                $string_state .= (!is_numeric($key)) ? "'$key'=>" : '';

        if ($value === false):
                    $string_state .= "FALSE"; elseif ($value === true):
                    $string_state .= "TRUE"; elseif ($value === null):
                    $string_state .= "NULL"; elseif (is_numeric($value)):
                    $string_state .= "$value"; else:
                    $string_state .= "'$value'";
        endif;

        endif;


        if ($counter != $totalCount && !$level == 0):
                $string_state .= ', ' . $linebreak;
        endif;

        $counter++;
        endforeach;

        if (!empty($close)):
            $string_state .= $close;
        $string_state .= $linebreak;
        endif;


        return $string_state;
    }

    /**
     * Reads a json file and return the files data converted to there respective php types
     * @param string $full_file_path path to the json file to read
     * @param bool|FALSE $ass_array [optional]  When <b>TRUE</b>, returned objects will be converted into
     * associative arrays.
     * @return mixed
     */
    public static function read_json($full_file_path, $ass_array = false)
    {
        $data = file_get_contents($full_file_path);
        return json_decode($data, $ass_array);
    }

    /** returns a list of countries and there codes
     * @return array
     */
    public static function countries_list()
    {
        $path = dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'data/countries.json';
        $countries = self::read_json($path, true);

        $list_countries = array();

        foreach ($countries['Names'] as $code => $value) {
            $list_countries[$code] = $value;
        }


        return $list_countries;
    }

    /**
     * Fetches Countries based on the code passed in
     * @param string $code the country code to search for.
     * @return mixed
     */
    public static function get_country($code)
    {
        $list_countries = self::countries_list();

        return $list_countries[$code];
    }

    /**
     * returns a list of phones codes and there country code
     * @return array
     */
    public static function phone_codes_list()
    {
        $path = dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'data/phone.json';
        $phone_codes = self::read_json($path, true);

        $list_codes = array();

        foreach ($phone_codes as $code => $value) {
            $list_codes[$code] = $value;
        }


        return $list_codes;
    }

    /**
     * Fetches the country based on the phone code passed in
     * @param string $code the phone code to search for.
     * @param bool|FALSE $show_country_code if to show the country code or its full name
     * @return mixed
     */
    public static function get_phone_code_country($code, $show_country_code = false)
    {
        $list_phone_codes = self::phone_codes_list();
        $country = $list_phone_codes[$code];

        if ($show_country_code) {
            $country = self::get_country($country);
        }


        return $country;
    }

    /**
     * returns a list of currency and there codes
     * @return array
     */
    public static function currency_list()
    {
        $path = dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'data/currency.json';
        $currency = self::read_json($path, true);

        $list_currency = array();

        foreach ($currency['Names'] as $code => $value) {
            $list_currency[$code] = $value[1];
        }


        return $list_currency;
    }


    /**
     * Fetches currency based on the code passed in
     * @param string $code the currency code to search for.
     * @return mixed
     */
    public static function get_currency($code)
    {
        $list_currency = self::currency_list();
        return $list_currency[$code];
    }
}
