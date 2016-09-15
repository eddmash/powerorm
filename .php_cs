<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__.DIRECTORY_SEPARATOR.'src');

return Symfony\CS\Config\Config::create()
    ->fixers(array('-braces'))
    ->finder($finder);