############################
Powerorm Management Commands
############################

.. contents::
    :local:
    :depth: 2

pmanager.php
============

Is Powerorm's command-line utility for administrative tasks. This document outlines all it can do.

Usage::

    php pmanager.php <command> [options]



Available Commands
==================
help
----

Displays help for a command

list
----

Lists commands

.. _migrations_makemigrations:

makemigrations
--------------

Creates new migrations based on the changes detected to your models. Migrations, their relationship with apps and more
are covered in depth in the :doc:`migrations documentation <../migration/index>`.

`--dry-run`

Shows what migrations would be made without actually writing any migrations files to disk.
Using this option along with -vvv will also show the complete migrations files that would be written.

checks
------
Inspects the models(as per now) in the project for common problems.

`--list-tags`

Lists all available tags.

`--tag TAGS, -t TAGS`

As of now this command runs checks on models but in future this command might perfom different types of checks that
are categorized with tags.

You can use these tags to restrict the checks performed to just those in a particular category.

For example, to perform only models and compatibility checks, run:

::

    php pmanager.php check -t MODEL


`--fail-level {CRITICAL,ERROR,WARNING,INFO,DEBUG}`

Specifies the message level that will cause the command to exit with a non-zero status. Default is ERROR.

.. _migrations_migrate:

migrate
-------

Synchronizes the database state with the current set of models and migrations. Migrations, their relationship with
apps and more are covered in depth in the :doc:`migrations documentation <../migration/index>`.

The behavior of this command changes depending on the arguments provided:
    - No arguments: All models have all of their migrations run.
    - **<migrationname>**: Brings the database schema to a state where the named migration is applied,but no later
      migrations in the same app are applied. This may involve unapplying migrations if you have previously migrated
      past the named migration.
      Use the name **zero** to unapply all migrations for an app.

`--fake`

Tells Powerorm to mark the migrations as having been applied or unapplied, but without actually running the SQL to
change your database schema.

This is intended for advanced users to manipulate the current migration state directly if they're manually applying
changes; be warned that using **--fake** runs the risk of putting the migration state table into a state where manual
recovery will be needed to make migrations run correctly.

generatedata
------------

This generates dummy data for the models detected by powerorm on you project. This might be usefull when developing.
This command depends on the library ``powerorfaker`` which can be installed via composer


showmigrations
--------------

Shows all migrations in a project. i.e. lists all the migrations available, and whether or not each migration is
applied (marked by an (applied) next to the migration name).

makemodel
---------

Generate a model class.
::

    php pmanager.php makemodel 'App\Models\Author' -p application/Models

**<model_name>**

The name of the model to generate. Use the name "zero" to unapply all migrations.

`-p, --path`

The location the generated model will be place relative to vendor folder. defaults to the same level as the vendor
folder.

any path provided should be relative to the vendor folder e.g.
::

 -p app/models

will look for directory name `app` on the same level as vendor directory.

`-f, --force`

Force overwrite if model already exists.
if this option is not available the command will through an ``CommandError`` if the model already exists.

robot
-----

A little fun is good for the soul, draws a robot because...why not ?
