<?php
/**
 * This file is part of the powerorm package.
 *
 * (c) Eddilbert Macharia <edd.cowan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eddmash\PowerOrm\Helpers;

use Eddmash\PowerOrm\BaseOrm;
use Eddmash\PowerOrm\DeConstructableInterface;
use Eddmash\PowerOrm\Exception\CircularDependencyError;
use Eddmash\PowerOrm\Exception\InvalidArgumentException;
use Eddmash\PowerOrm\Model\Field\Field;
use Eddmash\PowerOrm\Model\Meta;
use Eddmash\PowerOrm\Model\Model;

/**
 * Class Helpers.
 *
 * @since  1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Tools
{
    /**
     * sorts the operations in topological order using kahns algorithim.
     * http://faculty.simpson.edu/lydia.sinapova/www/cmsc250/LN250_Weiss/L20-TopSort.htm.
     *
     * @param $operations
     * @param $dependency
     *
     * @return array
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws CircularDependencyError
     */
    public static function topologicalSort($dependency)
    {
        $sorted = [];
        $deps = $dependency;

        while ($deps) {
            $noDeps = [];

            foreach ($deps as $parent => $dep) {
                if (empty($dep)) {
                    $noDeps[] = $parent;
                }
            }

            // we don't have  a vertice with 0 indegree hence we have loop
            if (empty($noDeps)) {
                throw new CircularDependencyError(
                    sprintf(
                        'Cyclic dependency on topological sort %s',
                        json_encode($deps)
                    )
                );
            }

            $sorted = array_merge($sorted, $noDeps);

            $newDeps = [];

            foreach ($deps as $parent => $dep) {
                // if parent has already been added to sort skip it
                if (!in_array($parent, $noDeps)) {
                    //if its already sorted remove it

                    $newDeps[$parent] = array_diff($dep, $sorted);
                }
            }

            $deps = $newDeps;
        }

        return $sorted;
    }

    public static function getFieldNamesFromMeta(Meta $meta)
    {
        $fieldNames = [];
        /** @var $field Field */
        foreach ($meta->getFields() as $field) {
            $fieldNames[] = $field->getName();
        }

        return $fieldNames;
    }

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
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function stringify(
        $data,
        $indent = 1,
        $level = 0,
        $elementLineBreak = false,
        $outerBrackets = true,
        $indentChar = null
    )
    {
        $indentCharacter = str_pad('', 4, ' ');
        if ($indentChar) {
            $indentCharacter = $indentChar;
        }
        $linebreak = PHP_EOL;
        $stringState = '';

        if (false === $indent) {
            $linebreak = '';
            $indentCharacter = '';
            $indent = 0;
        }

        $totalCount = (is_array($data)) ? count($data) : 1;
        $counter = 0;

        // unify everything to an array, on the first round for consistencies.
        if (0 == $level) {
            $data = ($outerBrackets) ? [$data] : (array)$data;
        }

        foreach ($data as $key => $value) {
            $indentation = str_repeat($indentCharacter, $indent);
            $stringState .= $indentation;

            $nextIndent = (false === $indent) ? $indent : $indent + 1;

            if (is_array($value)) {
                // HANDLE VALUE IF ARRAY

                $stringState .= '[' . $linebreak;

                if (!is_numeric($key)) {
                    $stringState .= "'$key'=>";
                }

                $stringState .= self::stringify(
                    $value,
                    $nextIndent,
                    ++$level,
                    $elementLineBreak
                );

                $stringState .= ($elementLineBreak) ? '' : $linebreak;
                $multiplier = ($indent) ? $indent - 1 : 0;
                $stringState .= (false !== $indent) ?
                    str_repeat($indentCharacter, $multiplier) : '';

                $stringState .= $indentation . ']';
            } elseif (is_object($value)) {
                // HANDLE VALUE THAT ARE OBJECTS THAT
                // IMPLEMENT DeConstructableInterface interface

                if ($value instanceof DeConstructableInterface) {
                    $skel = $value->deconstruct();

                    $class = $skel['fullName'];

                    $constructorArgs = $skel['constructorArgs'];

                    $string = self::stringify(
                        reset($constructorArgs),
                        $nextIndent,
                        ++$level,
                        $elementLineBreak
                    );

                    $stringState .= sprintf('%1$s(%2$s)', $class, $string);
                } else {
                    $stringState .= sprintf('%s', $value);
                }
            } else {
                // HANDLE VALUE IF ITS NOT OBJECT OR ARRAY

                $stringState .= (!is_numeric($key)) ? "'$key'=>" : '';

                if (false === $value) {
                    $stringState .= 'false';
                } elseif (true === $value) {
                    $stringState .= 'true';
                } elseif (null === $value) {
                    $stringState .= 'null';
                } elseif (is_numeric($value)) {
                    $stringState .= "$value";
                } else {
                    $stringState .= "'$value'";
                }
            }

            if ($counter != $totalCount) {
                $stringState .= ',';
            }

            if ($level > 1 || $elementLineBreak) {
                $stringState .= $linebreak;
            }

            ++$counter;
        }

        $stringState = rtrim($stringState, ',' . $linebreak);
        $stringState .= $linebreak;

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

        $listCountries = [];

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

        $listCodes = [];

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

        $listCurrency = [];

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
     * Schedule `callback` to be called once `model` and all `related_models` have been imported
     * and registered with the app registry.
     *
     * @param callable $callback will be called with the newly-loaded model
     *                             classes as its optional keyword arguments
     * @param Model $scopeModel the model on which the method was invoked
     * @param mixed $relModel the related models that needs to be resolved
     * @param array $kwargs
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     *
     * @throws \Eddmash\PowerOrm\Exception\AppRegistryNotReady
     */
    public static function lazyRelatedOperation(
        $callback,
        Model $scopeModel,
        $relModel,
        $kwargs = []
    )
    {
        $relModel = self::resolveRelation($scopeModel, $relModel);

        $relModels = (array)$relModel;

        $modelsToResolve = [];
        foreach ($relModels as $relM) {
            if (is_string($relM)) {
                $modelsToResolve[] = $relM;
            } elseif ($relM instanceof Model) {
                $modelsToResolve[] = $relM->getMeta()->getNSModelName();
            }
        }

        $kwargs['scopeModel'] = $scopeModel;
        $scopeModel->getMeta()->getRegistry()
            ->lazyModelOps($callback, $modelsToResolve, $kwargs);
    }

    /**
     * Resolve the model name incase is self-referencing model.
     *
     * @param $model
     * @param $relModel
     *
     * @return mixed
     *
     * @throws \Eddmash\PowerOrm\Exception\AppRegistryNotReady
     */
    public static function resolveRelation($model, $relModel)
    {
        if (is_string($relModel) &&
            BaseOrm::RECURSIVE_RELATIONSHIP_CONSTANT == $relModel) {
            return self::resolveRelation($model, $model);
        } elseif ($relModel instanceof Model) {
            return $relModel->getMeta()->getNSModelName();
        }

        return $relModel;
    }

    /**
     * Converts an exception into a PHP error.
     *
     * This method can be used to convert exceptions inside of methods like `__toString()`
     * to PHP errors because exceptions cannot be thrown inside of them.
     *
     * @param \Exception $exception the exception to convert to a PHP error
     */
    public static function convertExceptionToError($exception)
    {
        trigger_error(static::convertExceptionToString($exception), E_USER_ERROR);
    }

    /**
     * Converts an exception into a simple string.
     *
     * @param \Exception $exception the exception being converted
     *
     * @return string the string representation of the exception
     */
    public static function convertExceptionToString($exception)
    {
        if ($exception instanceof \Exception) {
            $message = "Exception ({$exception->getTraceAsString()})";
        } elseif ($exception instanceof \ErrorException) {
            $message = "{$exception->getTraceAsString()}";
        } else {
            $message = 'Exception';
        }
        $message .= " '" . get_class($exception) . "' with message '{$exception->getMessage()}' \n\nin "
            . $exception->getFile() . ':' . $exception->getLine() . "\n\n"
            . "Stack trace:\n" . $exception->getTraceAsString();

        return $message;
    }

    /**
     * @param $fields
     * @param null $messg
     *
     * @throws InvalidArgumentException
     */
    public static function ensureParamIsArray($fields, $messg = null)
    {
        if (!is_array($fields)) {
            if (is_null($messg)) {
                $messg = sprintf("method '%s()' expects parameters to be an array", __FUNCTION__);
            }

            throw new InvalidArgumentException($messg);
        }
    }

    /**
     * Basically strips off the fake namespace we use when doing migrations.
     *
     * @param $concreteParentName
     *
     * @return mixed
     */
    public static function unifyModelName($modelName)
    {
        return str_replace(
            Model::FAKENAMESPACE . '\\',
            '',
            $modelName
        );
    }

    /**
     * Returns meta settings related to the passed in model instance, passed in
     * class.
     *
     * @param Model $model
     * @param null $class this most of the time will be a parent in the models
     *                       hierarchy that we want to get its settings if it all it set any
     * @param string $method
     *
     * @return array|mixed
     */
    public static function getClassMetaSettings(
        Model $model,
        $class = null,
        $method = 'getMetaSettings'
    )
    {
        $metaSettings = [];
        if ($class) {
            $r = new \ReflectionClass($class);
        } else {
            $r = new \ReflectionObject($model);
        }
        if ($r->hasMethod($method)) {
            $metaMeth = $r->getMethod($method);
            $declaringClass = $metaMeth->getDeclaringClass()->getName();
            if (strtolower($r->getName()) === strtolower($declaringClass)) {
                if ($class) {
                    $method = sprintf('%s::%s', $class, $method);
                }
                $metaSettings = static::invokeCallable([$model, $method]);
            }
        }

        return $metaSettings;
    }

    /**
     * @param      $callable
     * @param null $args
     *
     * @return mixed
     */
    public static function invokeCallable($callable, $args = null)
    {
        if (null != $args) {
            if (is_array($args)) {
                $results = call_user_func_array($callable, $args);
            } else {
                $results = call_user_func($callable, $args);
            }
        } else {
            $results = call_user_func($callable);
        }

        return $results;
    }
}
