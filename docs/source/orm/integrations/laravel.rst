Integrating with Laravel
========================

This is recipe for using Powerorm with laravel.
Make sure to install powerorm via composer.

``composer require eddmash/powerorm``

Create Powerorm Service Provider
--------------------------------

Make a Powerorm service provider that is both a wrapper and a bootstrap
for Powerorm.

.. code-block:: php

    php artisan make:provider PowerormServiceProvider

Register the service provider in the ``config/app.php`` configuration file.
This file contains a ``providers`` array where you can list the class names of
your service providers.

To register ``PowerormServiceProvider``, simply add it to the array:

.. code-block:: php

    'providers' => [
        // Other Service Providers

        App\Providers\PowerormServiceProvider::class,
    ],

Make sure it looks like the one below.

.. code-block:: php

    namespace App\Providers;

    use Illuminate\Support\ServiceProvider;

    class PowerormServiceProvider extends ServiceProvider
    {

        /**
         * Bootstrap the application services.
         *
         * @return void
         */
        public function boot()
        {
            \Eddmash\PowerOrm\Loader::webRun(config('powerorm'));
        }

        /**
         * Register the application services.
         *
         * @return void
         */
        public function register()
        {
            $this->app->singleton(\Eddmash\PowerOrm\BaseOrm::class);

        }
    }

Create Application Class
------------------------

The orm requires application to register there information with it for it to
work. some of the information the application needs to know about an application
are where to find the models, where to place migrations..visit
:doc:`Components<../intro/components>` to learn more.

Powerorm needs some :doc:`configurations <../intro/configuration>` for it to
work e.g. the database settings.

We create this class inside the `app` folder on the same level as providers
folder.

.. code-block:: php

   namespace App;

   use Eddmash\PowerOrm\BaseOrm;
   use Eddmash\PowerOrm\Components\Application;

   class Powerorm extends Application
   {
       public static function configs()
       {
           return [
               'database' => [
                   'host' => '127.0.0.1',
                   'dbname' => 'tester',
                   'user' => 'root',
                   'password' => '',
                   'driver' => 'pdo_mysql',
               ],
               'components' => [
                   'app' => static::class,
               ],
               'dbPrefix' => 'test_',
               'charset' => 'utf-8',
           ];

       }

       /**
        * @inheritdoc
        */
       public function ready(BaseOrm $baseOrm)
       {
       }
   }



Create Laravel Command
----------------------

To be able to run :doc:`commands <../ref/commands>` provided by powerorm, we
need to create a laravel command that will enable us interact with powerorm.

Create a powerom command using artisan this will be placed at
``app/Console/Commands`` as show below.

.. code-block:: php

    php artisan make:command Powerorm

Register the new command with laravel, This is done on the file
``app/Console/Kernel.php`` as shown below

.. code-block:: php

    protected $commands = [
        //
        Powerorm::class
    ];

Make powerorm command look like the one below ``app/Console/Commands/Powerorm.php``

.. code-block:: php

    namespace App\Console\Commands;

    use Eddmash\PowerOrm\Console\Manager;
    use Illuminate\Console\Command;
    use Symfony\Component\Console\Input\ArgvInput;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class Powerorm extends Command
    {
        /**
         * The name and signature of the console command.
         *
         * @var string
         */
        protected $signature = 'powerorm:pmanager';

        /**
         * The console command description.
         *
         * @var string
         */
        protected $description = 'Display commands provided by powerorm.';

        /**
         * We stop laravel from running the command and pass control to powerorm
         * {@inheritdoc}
         */
        public function run(InputInterface $input, OutputInterface $output)
        {
            // remove the 'artisan' from the arguments
            $input = new ArgvInput(array_slice($_SERVER['argv'], 1));

            // launch powerorm console
            Manager::run(true, $input);
        }
    }


With that you can run all the :doc:`commands <../ref/commands>` that powerorm
provides as follows:

.. code-block:: php

    php artisan powerorm:pmanager
    php artisan powerorm:pmanager makemigrations
    php artisan powerorm:pmanager makemigrations --dry-run
    php artisan powerorm:pmanager makemigrations --dry-run -vvv
    php artisan powerorm:pmanager makemigrations -h
    php artisan powerorm:pmanager migrate
    php artisan powerorm:pmanager migrate zero
    php artisan powerorm:pmanager robot

See :doc:`commands <../ref/commands>` for all the availabel commands.