<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR)
    ->in(__DIR__.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR);

return Symfony\CS\Config\Config::create()
    ->fixers(array('-braces', 'empty_return', 'whitespacy_lines'))
    ->finder($finder);