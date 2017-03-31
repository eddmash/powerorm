[![Documentation Status](https://readthedocs.org/projects/powerorm/badge/?version=latest)](http://powerorm.readthedocs.io/en/latest/?badge=latest)
[![StyleCI](https://styleci.io/repos/50822043/shield?style=flat)](https://styleci.io/repos/50822043)
[![Build Status](https://travis-ci.org/eddmash/powerorm.svg?branch=master)](https://travis-ci.org/eddmash/powerorm)
[![Latest Stable Version](https://poser.pugx.org/eddmash/powerorm/v/stable)](https://packagist.org/packages/eddmash/powerorm)
[![Dependency Status](https://dependencyci.com/github/eddmash/powerorm/badge)](https://dependencyci.com/github/eddmash/powerorm)
[![Total Downloads](https://poser.pugx.org/eddmash/powerorm/downloads)](https://packagist.org/packages/eddmash/powerorm)
[![Latest Unstable Version](https://poser.pugx.org/eddmash/powerorm/v/unstable)](https://packagist.org/packages/eddmash/powerorm)
[![License](https://poser.pugx.org/eddmash/powerorm/license)](https://packagist.org/packages/eddmash/powerorm)

# PowerOrm
A light weight easy to use CodeIgniter ORM.

# Help and Documentation
Get the ORM documentation here [PowerOrm Documentation !](http://powerorm.readthedocs.io/).
Get assistance on the ORM here [PowerOrm Help !](https://groups.google.com/d/forum/powerorm-users).

# Introduction
I created this project because i required a lightweight easy to use orm that i could use in my Codeigniter projects 
with the least amount of configuration .

Sort of `plug and play` if you will. While at the same time reducing repetition and providing a consistent way to deal
with databases.

That is, i wanted to avoid the repetitive actions of creating migration files, creating query method to query the 
database and also wanted to be able to see all my database table fields on my models without me going to the database 
tables themselves and use this fields to interact with the database.

This ORM is heavily inspired by Django ORM. Because i personally love how there orm works.

# Install

- Via Composer

`composer require eddmash/powerorm:@dev`

- Download or Clone package from github. 

# Features
 - Allows to fully think of the database and its table in an object oriented manner i.e. 
    table are represented by model and columns are represented by fields.
 - Create automatic migrations.
 - Create forms automatically based on models.
 - All fields visible on the model, no need to look at the database table when you want to interact with the database.
 - Provides database interaction methods
 
# Dependencies

The ORM has the following dependencies:

- [Doctrine dbal Library](http://www.doctrine-project.org/projects/dbal.html). 
- [Symfony console component](http://symfony.com/doc/current/components/console.html). 
- [Symfony polyfill-mbstring component](http://symfony.com/blog/new-in-symfony-2-8-polyfill-components). 
 
# How It works

Learn from here [Documentation](http://powerorm.readthedocs.io/).

# supports
php 5.6+ and 7+

 # Credits
 I have used the following frameworks as a guiding hand, and in most cases i have replicated how Django framework has
 approached a problem, and in some cases i have borrowed some source code :
 
 - Django framework
 - FuelPHP framework
 - Yii2 framework
 - CakePHP framework
 - Laravel framework
 - Symfony2 framework
 - Codeigniter 4 framework
