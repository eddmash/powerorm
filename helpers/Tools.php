<?php
namespace powerorm\helpers;

/**
 * Class Tools
 * @package powerorm\helpers
 * @since 1.0.1
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Tools{

    /**
     * Takes an array and turns it into a string .
     * @param array $data the array to be converted to string
     * @param int $indent how to indent the items in the array
     * @param string $close item to come at the after of the arrays closing braces
     * @param string $start item to come at the before of the arrays opening braces
     * @param bool|FALSE $flat if the array should be indented
     * @return string
     */
    public static function stringify($data=array(), $indent=1, $close='', $start='', $flat=FALSE){

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
                    $string_state .= self::stringify($value, $indent+1, ',');
                endif;

                if(is_object($value)):

                    if($value instanceof \powerorm\model\field\Field):
                        $skel = $value->skeleton();
                        $class = $skel['class'];
                        $opts = $skel['field_options'];
                        $string_state .= sprintf("%1\$s(%2\$s)", $class, self::stringify($opts));
                    endif;
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

    /**
     * Reads a json file and return the files data converted to there respective php types
     * @param string $full_file_path path to the json file to read
     * @param bool|FALSE $ass_array [optional]  When <b>TRUE</b>, returned objects will be converted into
     * associative arrays.
     * @return mixed
     */
    public  static function read_json($full_file_path, $ass_array=FALSE){
        $data = file_get_contents($full_file_path);
        return json_decode($data,$ass_array);
    }

    /** returns a list of countries and there codes
     * @return array
     */
    public  static function countries_list(){
        $path = dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR.'data/countries.json';
        $countries = self::read_json($path, TRUE);

        $list_countries = array();

        foreach ($countries['Names'] as $code=>$value) {
            $list_countries[$code]=$value;
        }


        return $list_countries;
    }

    /**
     * Fetches Countries based on the code passed in
     * @param string $code the country code to search for.
     * @return mixed
     */
    public static function get_country($code){
        $list_countries = self::countries_list();

        return $list_countries[$code];
    }

    /**
     * returns a list of phones codes and there country code
     * @return array
     */
    public static function phone_codes_list(){
        $path = dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR.'data/phone.json';
        $phone_codes = self::read_json($path, TRUE);

        $list_codes = array();

        foreach ($phone_codes as $code=>$value) {
            $list_codes[$code]=$value;
        }


        return $list_codes;
    }

    /**
     * Fetches the country based on the phone code passed in
     * @param string $code the phone code to search for.
     * @param bool|FALSE $show_country_code if to show the country code or its full name
     * @return mixed
     */
    public static function get_phone_code_country($code, $show_country_code=FALSE){
        $list_phone_codes = self::phone_codes_list();
        $country = $list_phone_codes[$code];

        if($show_country_code){

            $country = self::get_country($country);
        }


        return $country;
    }

    /**
     * returns a list of currency and there codes
     * @return array
     */
    public static function currency_list(){
        $path = dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR.'data/currency.json';
        $currency = self::read_json($path, TRUE);

        $list_currency = array();

        foreach ($currency['Names'] as $code=>$value) {
            $list_currency[$code]=$value[1];
        }


        return $list_currency;
    }


    /**
     * Fetches currency based on the code passed in
     * @param string $code the currency code to search for.
     * @return mixed
     */
    public static function get_currency($code){
        $list_currency = self::currency_list();
        return $list_currency[$code];
    }

}

