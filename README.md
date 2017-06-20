[![Documentation Status](https://readthedocs.org/projects/powerorm/badge/?version=latest)](http://powerorm.readthedocs.io/en/latest/?badge=latest)
[![StyleCI](https://styleci.io/repos/50822043/shield?style=flat)](https://styleci.io/repos/50822043)
[![Build Status](https://travis-ci.org/eddmash/powerorm.svg?branch=master)](https://travis-ci.org/eddmash/powerorm)
[![Latest Stable Version](https://poser.pugx.org/eddmash/powerorm/v/stable)](https://packagist.org/packages/eddmash/powerorm)
[![Dependency Status](https://dependencyci.com/github/eddmash/powerorm/badge)](https://dependencyci.com/github/eddmash/powerorm)
[![Total Downloads](https://poser.pugx.org/eddmash/powerorm/downloads)](https://packagist.org/packages/eddmash/powerorm)
[![Latest Unstable Version](https://poser.pugx.org/eddmash/powerorm/v/unstable)](https://packagist.org/packages/eddmash/powerorm)
[![License](https://poser.pugx.org/eddmash/powerorm/license)](https://packagist.org/packages/eddmash/powerorm)

# PowerOrm
A light weight easy to use PHP ORM.

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

Setup

To load powerorm use the following code and pass the Configs needed for powerorm to work.
ensure the orm is loaded early enough in you application.

``````
\Eddmash\PowerOrm\Application::webRun($config);
``````

see also [integration](http://powerorm.readthedocs.io/en/master/orm/integrations/index.html) with your
framework of choice

[configaration](http://powerorm.readthedocs.io/en/master/orm/intro/configuration.html) takes 
the following form

``````
$config = [
    'database' => [
        'host' => '127.0.0.1',
        'dbname' => 'tester',
        'user' => 'admin',
        'password' => 'admin',
        'driver' => 'pdo_pgsql',
    ],
    'migrations' => [
        'path' => dirname(__FILE__) . '/application/Migrations',
    ],
    'models' => [
        'path' => dirname(__FILE__) . '/application/Models',
        'namespace' => 'App\Models',
    ],
    'dbPrefix' => 'demo_',
    'charset' => 'utf-8',
    'timezone'=>'Africa/Nairobi'
];
``````

Once the orm is loaded, now create Models,[The Model](http://powerorm.readthedocs.io/en/master/orm/model/index.html)

Create and author model which represents the author database table

``````
use Eddmash\PowerOrm\Model\Model;

class Author extends Model
{


    private function unboundFields()
    {
        return [
            'name'=>Model::CharField(['maxLength'=>200]),
            'email'=>Model::EmailField()
        ];
    }


}
``````

[Migrations](http://powerorm.readthedocs.io/en/master/orm/migration/index.html)

Now we create the table that represents the author model on the database.

Copy the vendor/eddmash/powerorm/pmanager.php file to you projects base directory e.g. on the same 
level as vendor directory.

``````
php pmanager.php makemigrations // generate the database agonistic migration file
``````

``````
php pmanager.php  migrate // creates the actual tables represented by the model on the database
``````

See [integration](http://powerorm.readthedocs.io/en/master/orm/integrations/index.html) on how to 
access the command line tools from the choosen framework.

In CI4 once integrated, you have access to  powerorm:pmanager from ci.php script

``````
php ci.php powerorm:pmanager makemigrations // generate the database agonistic migration file
``````

``````
php ci.php powerorm:pmanager  migrate // creates the actual tables represented by the model on the database
``````
Apart fron the migrations commands the orm has other [commands](http://powerorm.readthedocs.io/en/master/orm/ref/commands.html) that assit you in developing an application.

e.g. if you want to generate dummy data you can use the 

[generatedata](http://powerorm.readthedocs.io/en/master/orm/ref/commands.html#generatedata) command
to use this command you need to install the powerfaker component.
``````
composer require eddmash\powerormfaker:@dev
``````
Once installed you can use the command as shown

``````
php pmanager.php generatedata -o 'App\Models\Author'
``````

Now we can perform [Queries](http://powerorm.readthedocs.io/en/master/orm/queries/index.html) as follows

``````
Author::objects->get(['pk'=>1]) // retrieves the user id primary key of 1
Author::objects->all(); // retrieves all users
Author::object->filter(['name'=>'ken']); // where name is ken
Author::objects->filter(['name__startwith'=>"p"]) // where name like p%
``````
or perform saves

``````
$author = new Author();
$author->name="example"
$author->email="example@example.com"
$author->save();
``````

Visit the  [Documentation](http://powerorm.readthedocs.io/) to learn more.

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
