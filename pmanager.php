<?php

if (strtolower(basename(__DIR__)) === 'powerorm'):
    require 'Application.php';

else:
    require __DIR__.'/application/libraries/powerorm/Application.php';
endif;
Application::consoleRun(['baseDir' => __DIR__]);
