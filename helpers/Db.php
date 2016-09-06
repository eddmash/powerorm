<?php

namespace powerorm\helpers;

use powerorm\queries\Queryset;

class Db
{
    public static function create_queryset($model, $query = null, $params = '', $query_builder_override = null)
    {
        return create_queryset($model, $query, $params, $query_builder_override);
    }
}

/**
 * @ignore
 * Borrowed from CI &DB(), disable creating a connection immediately, reason for this is because before a queryset is
 * evaluated we don't actually need a connection we just need the query_builder class only
 *
 * @param string $params
 * @param null   $query_builder_override
 *
 * @return mixed
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
function create_queryset($model, $query = null, $params = '', $query_builder_override = null)
{
    // Load the DB config file if a DSN string wasn't passed
    if (is_string($params) && strpos($params, '://') === false) {
        // Is the config file in the environment folder?
        if (!file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/database.php')
            && !file_exists($file_path = APPPATH.'config/database.php')) {
            show_error('The configuration file database.php does not exist.');
        }

        include $file_path;

        // Make packages contain database config files,
        // given that the controller instance already exists
        if (class_exists('CI_Controller', false)) {
            foreach (get_instance()->load->get_package_paths() as $path) {
                if ($path !== APPPATH) {
                    if (file_exists($file_path = $path.'config/'.ENVIRONMENT.'/database.php')) {
                        include $file_path;
                    } elseif (file_exists($file_path = $path.'config/database.php')) {
                        include $file_path;
                    }
                }
            }
        }

        if (!isset($db) or count($db) === 0) {
            show_error('No database connection settings were found in the database config file.');
        }

        if ($params !== '') {
            $active_group = $params;
        }

        if (!isset($active_group)) {
            show_error('You have not specified a database connection group via $active_group in your config/database.php file.');
        } elseif (!isset($db[$active_group])) {
            show_error('You have specified an invalid database connection group ('.$active_group.') in your config/database.php file.');
        }

        $params = $db[$active_group];
    } elseif (is_string($params)) {
        /**
         * Parse the URL from the DSN string
         * Database settings can be passed as discreet
         * parameters or as a data source name in the first
         * parameter. DSNs must have this prototype:
         * $dsn = 'driver://username:password@hostname/database';.
         */
        if (($dsn = @parse_url($params)) === false) {
            show_error('Invalid DB Connection String');
        }

        $params = [
            'dbdriver'    => $dsn['scheme'],
            'hostname'    => isset($dsn['host']) ? rawurldecode($dsn['host']) : '',
            'port'        => isset($dsn['port']) ? rawurldecode($dsn['port']) : '',
            'username'    => isset($dsn['user']) ? rawurldecode($dsn['user']) : '',
            'password'    => isset($dsn['pass']) ? rawurldecode($dsn['pass']) : '',
            'database'    => isset($dsn['path']) ? rawurldecode(substr($dsn['path'], 1)) : '',
        ];

        // Were additional config items set?
        if (isset($dsn['query'])) {
            parse_str($dsn['query'], $extra);

            foreach ($extra as $key => $val) {
                if (is_string($val) && in_array(strtoupper($val), ['TRUE', 'FALSE', 'NULL'])) {
                    $val = var_export($val, true);
                }

                $params[$key] = $val;
            }
        }
    }

    // No DB specified yet? Beat them senseless...
    if (empty($params['dbdriver'])) {
        show_error('You have not selected a database type to connect to.');
    }

    if (!class_exists('CI_DB', false)) {
        /*
         * CI_DB
         *
         * Acts as an alias for both CI_DB_driver and CI_DB_query_builder.
         *
         * @see	CI_DB_query_builder
         * @see	CI_DB_driver
         */
//        class CI_DB extends \CI_DB_query_builder { }

        eval('class CI_DB extends \CI_DB_query_builder{}');
    }


    // Load the DB driver
    $driver_file = BASEPATH.'database/drivers/'.$params['dbdriver'].'/'.$params['dbdriver'].'_driver.php';

    file_exists($driver_file) or show_error('Invalid DB driver');
    require_once $driver_file;

    // Instantiate the DB adapter
    $driver = 'CI_DB_'.$params['dbdriver'].'_driver';
    $DB = new $driver($params);

    // Check for a subdriver
    if (!empty($DB->subdriver)) {
        $driver_file = BASEPATH.'database/drivers/'.$DB->dbdriver.'/subdrivers/'.$DB->dbdriver.'_'.$DB->subdriver.'_driver.php';

        if (file_exists($driver_file)) {
            require_once $driver_file;
            $driver = 'CI_DB_'.$DB->dbdriver.'_'.$DB->subdriver.'_driver';
            $DB = new $driver($model, $query, $params);
        }
    }

    return Queryset::instance($model, $DB);
}
