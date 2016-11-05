<?php

namespace Eddmash\PowerOrm\Helpers;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\DeConstructableInterface;
use Eddmash\PowerOrm\Model\Model;

/**
 * Class Tools.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Tools
{
    public static function normalizeKey($name)
    {
        return strtolower($name);
    }

    /**
     * Takes an array and turns it into a string .
     *
     * @param mixed $data the array to be converted to string
     * @param int $indent how to indent the items in the array
     * @param string $close item to come at the after of the arrays closing braces
     * @param string $start item to come at the before of the arrays opening braces
     * @param int $level at what level we are operating at, level=0 is the encasing level,just unifies the different
     *                       forms of data
     *
     * @return string
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function stringify($data, $indent = 1, $close = '', $start = '', $level = 0)
    {
        $indentCharacter = "\t";
        $linebreak = PHP_EOL;
        $stringState = '';

        if ($indent == false) {
            $linebreak = '';
            $indentCharacter = '';
            $indent = 0;
        }

        if (!empty($start)) {
            $stringState .= str_repeat($indentCharacter, $indent) . "$start";
        }

        $totalCount = (is_array($data)) ? count($data) : 1;
        $counter = 1;

        // unify everything to an array, on the first round for consistencies.
        if ($level == 0) {
            $data = [$data];
        }

        foreach ($data as $key => $value) {
            $stringState .= str_repeat($indentCharacter, $indent);

            $nextIndent = ($indent == false) ? $indent : $indent + 1;

            if (is_array($value)) {
                // HANDLE VALUE IF ARRAY

                $stringState .= '[' . $linebreak;

                if (!is_numeric($key)) {
                    $stringState .= "'$key'=>";
                }

                $stringState .= self::stringify($value, $nextIndent, $close, $start, ++$level);

                $stringState .= $linebreak;
                $stringState .= ($indent != false) ? str_repeat($indentCharacter, $indent - 1) : '';
                $stringState .= ']';
            } elseif (is_object($value)) {

                // HANDLE VALUE THAT ARE OBJECTS THAT IMPLEMENT DeConstructableInterface interface

                if ($value instanceof DeConstructableInterface) {
                    $skel = $value->deconstruct();

                    $class = $skel['fullName'];

                    $constructorArgs = $skel['constructorArgs'];

                    $string = self::stringify(reset($constructorArgs), $nextIndent, $close, $start, ++$level);

                    $stringState .= sprintf('%1$s(%2$s)', $class, $string);
                }
            } else {

                // HANDLE VALUE IF ITS NOT OBJECT OR ARRAY

                $stringState .= (!is_numeric($key)) ? "'$key'=>" : '';

                if ($value === false) {
                    $stringState .= 'false';
                } elseif ($value === true) {
                    $stringState .= 'true';
                } elseif ($value === null) {
                    $stringState .= 'null';
                } elseif (is_numeric($value)) {
                    $stringState .= "$value";
                } else {
                    $stringState .= "'$value'";
                }
            }

            if ($counter != $totalCount && $level > 1) {
                $stringState .= ', ' . $linebreak;
            }

            ++$counter;
        }

        if (!empty($close)) {
            $stringState .= $close;
            $stringState .= $linebreak;
        }

        return $stringState;
    }

    /**
     * Reads a json file and return the files data converted to there respective php types.
     *
     * @param string $full_file_path path to the json file to read
     * @param bool|false $ass_array [optional]  When <b>TRUE</b>, returned objects will be converted into
     *                                   associative arrays
     *
     * @return mixed
     */
    public static function readJson($full_file_path, $ass_array = false)
    {
        $data = file_get_contents($full_file_path);

        return json_decode($data, $ass_array);
    }

    /** returns a list of countries and there codes
     * @return array
     */
    public static function countriesList()
    {
        $path = dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'data/countries.json';
        $countries = self::readJson($path, true);

        $listCountries = array();

        foreach ($countries['Names'] as $code => $value) {
            $listCountries[$code] = $value;
        }

        return $listCountries;
    }

    /**
     * Fetches Countries based on the code passed in.
     *
     * @param string $code the country code to search for
     *
     * @return mixed
     */
    public static function getCountry($code)
    {
        $listCountries = self::countriesList();

        return $listCountries[$code];
    }

    /**
     * returns a list of phones codes and there country code.
     *
     * @return array
     */
    public static function phoneCodesList()
    {
        $path = dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'data/phone.json';
        $phone_codes = self::readJson($path, true);

        $listCodes = array();

        foreach ($phone_codes as $code => $value) {
            $listCodes[$code] = $value;
        }

        return $listCodes;
    }

    /**
     * Fetches the country based on the phone code passed in.
     *
     * @param string $code the phone code to search for
     * @param bool|false $show_country_code if to show the country code or its full name
     *
     * @return mixed
     */
    public static function getPhoneCodeCountry($code, $show_country_code = false)
    {
        $listPhoneCodes = self::phoneCodesList();
        $country = $listPhoneCodes[$code];

        if ($show_country_code) {
            $country = self::getCountry($country);
        }

        return $country;
    }

    /**
     * returns a list of currency and there codes.
     *
     * @return array
     */
    public static function currencyList()
    {
        $path = dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'data/currency.json';
        $currency = self::readJson($path, true);

        $listCurrency = array();

        foreach ($currency['Names'] as $code => $value) {
            $listCurrency[$code] = $value[1];
        }

        return $listCurrency;
    }

    /**
     * Fetches currency based on the code passed in.
     *
     * @param string $code the currency code to search for
     *
     * @return mixed
     */
    public static function getCurrency($code)
    {
        $listCurrency = self::currencyList();

        return $listCurrency[$code];
    }

    /**
     * Schedule `callback` to be called once `model` and all `related_models` have been imported and registered with
     * the app registry.
     *
     * @param callback $callback will be called with the newly-loaded model classes as its any optional keyword arguments
     * @param Model $scopeModel the model on which the method was invoked
     * @param mixed $relModel the related models that need to be resolved
     * @param array $kwargs
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function lazyRelatedOperation($callback, $scopeModel, $relModel, $kwargs = [])
    {

        $relModel = self::resolveRelation($scopeModel, $relModel);

        $relModels = (is_array($relModel)) ? $relModel : [$relModel];

        $relatedModels = [];
        foreach ($relModels as $relM) :
            if (is_string($relM)):

                $relatedModels[] = $relM;

            elseif ($relM instanceof Model):
                $relatedModels[] = $relM->meta->modelName;
            endif;
        endforeach;

        $kwargs['scopeModel'] = $scopeModel;
        $scopeModel->meta->registry->lazyModelOps($callback, $relatedModels, $kwargs);
    }

    public static function resolveRelation($model, $relModel)
    {
        if ($relModel == BaseOrm::RECURSIVE_RELATIONSHIP_CONSTANT):
            return self::resolveRelation($model, $model);
        elseif ($relModel instanceof Model):
            return $relModel->meta->modelName;
        endif;

        return $relModel;
    }

}
