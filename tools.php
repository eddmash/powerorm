<?php
/**
 * @package powerorm
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// the Codeigniter instance
$CI =& get_instance();

if(!function_exists('checks_html_output')){

    /**
     * @param $checks
     */
    function checks_html_output($checks){

        $out = "<span>Please resolve the following issues:</span><br><br><br>";
        function inner_htm_display($checks, $out='', $indent=0)
        {
            $pad = 2;
            if ($indent > 0):
                $i = 0;
                while ($i < $indent):
                    $pad = $pad+ $indent;
                    $i++;
                endwhile;
            endif;

            foreach ($checks as $name => $value) :

                if (!is_numeric($name)):
                    $out .= "<span style='padding-left:".$pad."em;'>". $name . "</span><br>";
                endif;


                if (is_array($value)):
                    $out .= inner_htm_display($value,'',2);
                endif;

                if (is_string($value)):
                    $pad = $pad+2;
                    $out .=  "<span style='padding-left: ".$pad."em;'>" . $value . "</span><br><br><br>";
                endif;
            endforeach;
            return $out;
        }

        return inner_htm_display($checks, $out);
    }
}

if(! function_exists('uploaded')){

    /**
     * Returns the full url to an uploaded files
     *
     * @param string $asset
     * @return string
     */
    function uploaded($asset=''){
        global $CI;

        if(startswith($asset, '/')){
           $asset = substr_replace($asset, '', 0, 1);
        }
        // the the upload config file
        $CI->config->load('upload');
        $assets_folder = $CI->config->item('upload_path');
        if(endswith($assets_folder, '/')){
            $assets_folder = substr_replace($assets_folder, '', -1, 1);
        }
        return $CI->config->item('base_url').$assets_folder.'/'.$asset;
    }
}

if(!function_exists('read_json')){

    /**
     * Reads a json file and return the files data converted to there respective php types
     * @param string $full_file_path path to the json file to read
     * @param bool|FALSE $ass_array [optional]  When <b>TRUE</b>, returned objects will be converted into
     * associative arrays.
     * @return mixed
     */
    function read_json($full_file_path, $ass_array=FALSE){
        $data = file_get_contents($full_file_path);
        return json_decode($data,$ass_array);
    }
}

if(!function_exists('countries_list')){

    /** returns a list of countries and there codes
     * @return array
     */
    function countries_list(){
        $path = dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR.'data/countries.json';
        $countries = read_json($path, TRUE);

        $list_countries = array();

        foreach ($countries['Names'] as $code=>$value) {
            $list_countries[$code]=$value;
        }


        return $list_countries;
    }
}

if(!function_exists('currency_list')){

    /**
     * returns a list of currency and there codes
     * @return array
     */
    function currency_list(){
        $path = dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR.'data/currency.json';
        $currency = read_json($path, TRUE);

        $list_currency = array();

        foreach ($currency['Names'] as $code=>$value) {
            $list_currency[$code]=$value[1];
        }


        return $list_currency;
    }
}

if(!function_exists('phone_codes_list')){

    /**
     * returns a list of phones codes and there country code
     * @return array
     */
    function phone_codes_list(){
        $path = dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR.'data/phone.json';
        $phone_codes = read_json($path, TRUE);

        $list_codes = array();

        foreach ($phone_codes as $code=>$value) {
            $list_codes[$code]=$value;
        }


        return $list_codes;
    }
}

if(!function_exists('get_country')){

    /**
     * Fetches Countries based on the code passed in
     * @param string $code the country code to search for.
     * @return mixed
     */
    function get_country($code){
        $list_countries = countries_list();

        return $list_countries[$code];
    }
}

if(!function_exists('get_currency')){

    /**
     * Fetches currency based on the code passed in
     * @param string $code the currency code to search for.
     * @return mixed
     */
    function get_currency($code){
        $list_currency = currency_list();
        return $list_currency[$code];
    }
}

if(!function_exists('get_phone_code_country')){

    /**
     * Fetches the country based on the phone code passed in
     * @param string $code the phone code to search for.
     * @param bool|FALSE $show_country_code if to show the country code or its full name
     * @return mixed
     */
    function get_phone_code_country($code, $show_country_code=FALSE){
        $list_phone_codes = phone_codes_list();
        $country = $list_phone_codes[$code];

        if($show_country_code){

            $country = get_country($country);
        }


        return $country;
    }
}

if(!function_exists('stringify')):


    /**
     * Takes an array and turns it into a string .
     * @param array $data the array to be converted to string
     * @param int $indent how to indent the items in the array
     * @param string $close item to come at the after of the arrays closing braces
     * @param string $start item to come at the before of the arrays opening braces
     * @param bool|FALSE $flat if the array should be indented
     * @return string
     */
    function stringify($data=array(), $indent=1, $close='', $start='', $flat=FALSE){

        $indent_character = "\t";
        $linebreak = PHP_EOL;
        $outer_indent = '';

        $count = 1;
        if($flat):
            $linebreak = '';
            $indent_character = '';
        endif;

        while($count<=$indent):
            $outer_indent .= $indent_character;
            $count++;
        endwhile;

        $outer_indent .= $indent_character;
        $inner_indent = $outer_indent.$indent_character;

        $string_state = '';
        if(!empty($start)):
            $string_state .=$outer_indent."$start";
        endif;

        $string_state .= $indent_character."[".$linebreak;

        if(!empty($data)):
            foreach ($data as $key=>$value) :

                if(is_array($value)):
                    if(!is_numeric($key)):
                        $string_state .= "$inner_indent'$key'=>";
                    endif;
                    $string_state .= stringify($value, $indent+1, ',');
                endif;

                if(is_numeric($key) && !is_array($value) && !is_object($value)):

                    if($value===FALSE):
                        $string_state .= $inner_indent."FALSE,".$linebreak;
                    elseif($value===TRUE):
                        $string_state .= $inner_indent."TRUE,".$linebreak;
                    elseif($value===NULL):
                        $string_state .= $inner_indent."NULL,".$linebreak;
                    elseif(is_numeric($value)):
                        $string_state .= $inner_indent."$value,".$linebreak;
                    else:
                        $string_state .= $inner_indent."'$value',".$linebreak;
                    endif;
                endif;

                if(!is_numeric($key) && !is_array($value) && !is_object($value)):

                    if($value===FALSE):
                        $string_state .= $inner_indent."'$key'=> FALSE,".$linebreak;
                    elseif($value===TRUE):
                        $string_state .= $inner_indent."'$key'=> TRUE,".$linebreak;
                    elseif($value===NULL):
                        $string_state .= $inner_indent."'$key'=> NULL,".$linebreak;
                    elseif(is_numeric($value)):
                        $string_state .= $inner_indent."'$key'=>$value,".$linebreak;
                    else:
                        $string_state .= $inner_indent."'$key'=>'$value',".$linebreak;
                    endif;

                endif;
            endforeach;
        endif;

        $string_state .= $outer_indent."]";

        if(!empty($close)):
            $string_state .=  $close;
            $string_state .=  $linebreak;
        endif;

        return $string_state;
    }
endif;


