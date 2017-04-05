Integrating with Laravel
========================

This is recipe for using Powerorm with laravel.

Create Powerorm Service Provider
--------------------------------

Make a Powerorm service provider that is both a wrapper and a bootstrapfor Powerorm.

.. code-block:: php

    php artisan make:provider PowerormServiceProvider

Adjust the ``register`` method to look as shown below.

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
            \Eddmash\PowerOrm\Application::webRun(config('powerorm'));
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

Create Config file
------------------

Create a configaration file ``config/powerorm.php``.

.. code-block:: php

    return [
        'database' => [
            'host' => env('DB_HOST'),
            'dbname' => env('DB_DATABASE'),
            'user' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'driver' => env('DB_CONNECTION'),
        ],
        'migrations' => [
            'path' => dirname(dirname(__FILE__)) . '/app/Migrations',
        ],
        'models' => [
            'path' => dirname(dirname(__FILE__)) . '/app/Models',
    //        'namespace' => 'App\Models',
            'autoload' => false,
        ],
        'dbPrefix' => 'demo_',
        'charset' => 'utf-8',
    ];


Create Laravel Command
----------------------

To be able to run :doc:`commands <../ref/commands>` provided by powerorm, we need to create a laravel
command that will enable us interact with powerorm.

Create a powerom command using artisan this will be placed at ``app/Console/Commands`` as show below.

.. code-block:: php

    php artisan make:command Powerorm

Register the new command with laravel, This is done on the file ``app/Console/Kernel.php`` as shown below

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
        protected $signature = 'powerorm';

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


With that you can run all the :doc:`commands <../ref/commands>` that powerorm provides as follows:

.. code-block:: php

    php artisan powerorm
    php artisan powerorm makemigrations
    php artisan powerorm makemigrations --dry-run
    php artisan powerorm makemigrations --dry-run -vvv
    php artisan powerorm makemigrations -h
    php artisan powerorm migrate
    php artisan powerorm migrate zero
    php artisan powerorm robot

See :doc:`commands <../ref/commands>` for all the availabel commands.