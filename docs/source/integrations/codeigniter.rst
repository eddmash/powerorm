Integrating with Codeigniter
============================

This is recipe for using Powerorm with codeigniter.



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


Codeigniter  4
--------------

For codeigniter 4 and any other projects that use namespace(see :doc:`Laravel <laravel>`)
you just need to ensure the orm is loaded early enough.

In Codeigniter 4 *(i'm still exploring codeigniter 4, but as of now)*
powerorm can be loaded at any one of the environment files under **application/Config/Boot/** .

Depending on the environment in use add the following line at the bottom.

.. code-block:: php

    // create a config file for the orm, the orm expects configs
    // to be an array you could create the method as
    // array the returns the configs in array format.

    $config = (new \Config\Orm())->asArray();
    \Eddmash\PowerOrm\Application::webRun($configs);
