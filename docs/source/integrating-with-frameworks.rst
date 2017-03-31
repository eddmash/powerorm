Integrating with Projects
=========================

This is recipe for using Powerorm projects



Codeigniter  3
--------------

.. note::

    This might not work for all CodeIgniter versions and may require
    slight adjustments.


Here is how to set it up:

Make a CodeIgniter library that is both a wrapper and a bootstrap
for Powerorm.CodeIgniter

Setting up the file structure
-----------------------------

Here are the steps:

-  Ensure powerorm is installed via composer require eddmash/powerorm

-  Add a php file to your application/libraries folder
   called Powerorm.php. This is going to be your wrapper/bootstrap for

-  Open your config/autoload.php file and autoload
   your Powerorm library.

.. code-block:: php

   $autoload['libraries'] = array('powerorm');

Creating your Powerorm CodeIgniter library
------------------------------------------

Now, here is what your Powerorm.php file should look like.
Customize it to your needs.

.. code-block:: php

    class Powerorm
    {

        function __construct($config)
        {
            $autoLoader = require_once FCPATH.'vendor/autoload.php';
            $this->instance = \Eddmash\PowerOrm\Application::webRun($config, $autoLoader);
        }

    }


Codeigniter  4 and others projects
----------------------------------

For codeigniter 4 and any other projects that use namespace do the following:

- ensure the orm is set early enough :

  e.g In Codeigniter 4 (as of now) i have not found a better location than in
  the **application/Config/Boot/** files dependening on the environment in use add the following line.

.. code-block:: php

    // create a config file for the orm, the orm expects configs
    // to be an array you could create the method as array the returns the configs in array format.
    $config = (new \Config\Orm())->asArray();
    \Eddmash\PowerOrm\Application::webRun($configs);

see :doc:`Configs <intro/configuration>` for options.

Running migrations
==================

Copy the **pmanager.php** on the powerorm base directory to you projects base directory i.e
on the same level as index.php.