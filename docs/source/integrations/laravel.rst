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