Powerorm Management Commands
############################

pmanager.php
------------

Is Powerorm's command-line utility for administrative tasks. This document outlines all it can do.

Usage::

    php pmanager.php <command> [options]


Available Commands
------------------

.. _migrations_makemigrations:

makemigrations
..............

Creates new migrations based on the changes detected to your models. Migrations, their relationship with apps and more
are covered in depth in the :doc:`migrations documentation <../migration/index>`.

`--dry-run`

Shows what migrations would be made without actually writing any migrations files to disk.
Using this option along with -vvv will also show the complete migrations files that would be written.