<?php

namespace Eddmash\PowerOrm\Checks;

/**
 * Checks for ORM integrity.
 *
 * This checks run on console only.
 *
 * @since 1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class ChecksRegistry
{
    protected $registeredChecks;

    public function __construct()
    {
        $this->checks = [];
        $this->registeredChecks = [];
    }

    /**
     * @return static
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public static function createObject()
    {
        return new static();
    }

    /**
     * Register checks to be run with the check registry.
     *
     * @param callable $check
     * @param array    $tags
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function register($check, $tags = [])
    {
        $check = ['check' => $check, 'tags' => []];
        if (!empty($tags)):
            $check['tags'] = $tags;
        endif;

        $this->registeredChecks[] = $check;
    }

    /**
     * Run all registered checks and return list of Errors and Warnings.
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function runChecks($tags = [])
    {
        $errors = [];

        $checks = $this->getChecks();

        if (!empty($tags)):
            $taggedChecks = [];
        foreach ($checks as $check) :
                // a check can have many tags
                // true if any of the tags provided appears in the check tags
                if (array_intersect($check['tags'], $tags)):
                    $taggedChecks[] = $check;
        endif;
        endforeach;
        $checks = $taggedChecks;
        endif;

        foreach ($checks as $check) :

            $functionName = '';
        if (is_array($check['check'])):
                if (count($check['check']) > 1):
                    $obj = reset($check['check']);
        $method = end($check['check']);
        $functionName = get_class($obj).'::'.$method; else:
                    $functionName = reset($check['check']);
        endif;
        endif;

        $errors = array_merge($errors, call_user_func($check['check']));

        assert(is_array($errors), sprintf('The function %s did not return a list. All functions registered ".
            "with the checks registry must return a list.', $functionName));
        endforeach;

        return $errors;
    }

    /**
     * Get registered checks.
     *
     * @return array
     *
     * @since 1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function getChecks()
    {
        return $this->registeredChecks;
    }

    public function tagsAvailable()
    {
        $availableChecks = [];

        $checks = $this->getChecks();

        foreach ($checks as $check) :

            $availableChecks = array_merge($availableChecks, $check['tags']);
        endforeach;

        return $availableChecks;
    }
    public function tagExists($tag)
    {
        return in_array($tag, $this->tagsAvailable());
    }
}
