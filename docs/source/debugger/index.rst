Powerorm Debugbar Guide
#######################

Displays a debug bar in the browser with information from php. No more var_dump() in your code!

.. image:: https://raw.githubusercontent.com/maximebf/php-debugbar/master/docs/screenshot.png

This is just wrapper to the `PHP Debug Bar <http://phpdebugbar.com/>`_

Installation
------------

Via composer **(recommended)**::

	composer require eddmash/powerormdebug:"@dev"

Or add this to the composer.json file::

	{
	   "require": {
	       "eddmash/powerormdebug": "@dev"
	   }
	}

.. _debugbar_setup:

Setup
-----

To enable the debugbar, add it as component of the orm on the :ref:`components <config_components>` setting as shown
below.

.. code-block:: php

    $config = [
        // ..., other orm settings

        'components' => [
            "debugger" => function (BaseOrm $orm) {
                $debugger = new Debugger($orm);
                $debugger->setDebugBar(new StandardDebugBar());
                return $debugger;
            },
        ]
    ];

Usage
-----
DebugBar is very easy to use and you can add it to any of your projects in no time.
The easiest way is using the show() function.

.. note:: invoke the show() function at the end of the page so that its able to get all the sql queries performed

.. code-block:: php

    <?php

    $orm = \Eddmash\PowerOrm\Application::webRun(\App\Config\Powerorm::asArray());

    /**@var $debugger \Eddmash\PowerOrmDebug\Debugger*/
    $debugger =$orm->debugger;
    $debugger->getDebugBar()["messages"]->addMessage("hello world!");
    ?>
    <html>
        <head>
        </head>
        <body>
            ...
            <?php echo $debugger->show() ?>
        </body>
    </html>