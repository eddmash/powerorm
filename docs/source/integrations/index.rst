####################
Integrating Powerorm
####################


This is guide in setting up and using Powerorm on projects.

Setting up
----------

For projects that use namespace you just need to ensure the orm is loaded early enough.

see below for setup on some common frameworks :

.. toctree::
    :titlesonly:

    codeigniter
    laravel

Powerorm takes several configurations see :doc:`Configs <../intro/configuration>` for options.

Command Line
------------

To be able to use any of the command line command packaged with the orm e.g
commands to create migrations for models in the project.

Copy the ``vendor/eddmash/powerorm/pmanager.php`` file to you projects base directory
e.g. on the same level as vendor directory.
