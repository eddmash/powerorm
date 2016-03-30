<?php
/**
 * Creates Migrations
 */

/**
 *
 */
defined('BASEPATH') OR exit('No direct script access allowed');

use powerorm\checks\Checks;
use powerorm\cli\ColorCLi;
use powerorm\exceptions\AmbiguityError;
use powerorm\migrations\AutoDetector;

use powerorm\migrations\ProjectState;


function load_migration_library(){
    $ci =& get_instance();

    if(!class_exists('Migration', FALSE) OR !isset($ci->migration)):
        $ci->load->library('migration');
    endif;
}

load_migration_library();

/**
 * Responsible for creating migration files
 * @package powerorm\migrations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class Migrator
{
    public static $tab = "\t\t";

    public static $_migrations_path;

    /**
     *Initiates the migration files generation process.
     */
    public static function makemigrations(){
        $detect = new AutoDetector(ProjectState::from_models(), ProjectState::from_migrations());
        Migrator::make_files($detect->get_operations());
    }

    public static function migrate(){
        ColorCLi::info('Running Migration Files ...');
        $version = get_instance()->migration->latest();
        if(is_string($version)):
            ColorCLi::success('Migration run successfully');
        endif;
    }

    public static function rollback($version){
        $db_version = get_instance()->migration->version($version);

        if(is_string($db_version) && $db_version == $version):
            ColorCLi::success(sprintf('Rolling back to migration version `%s` successfully', $version));
        endif;
    }

    /**
     * Makes a migration file for each of the passed in operations.
     * @param array $operations an array of operations to create migrations for.
     */
    public static function make_files(array $operations){

        foreach ($operations as $key=>$operation) :
            Migrator::_writer($operation);
        endforeach;
    }

    /**
     * Ensures the string output has enough indents for proper alignment in the migration file.
     * @param mixed $items the item to format
     * @param int $indent hhw many tab characters to use.
     * @param bool|TRUE $newline if TRUE create a newline after current item is output.
     * @return string
     */
    public static function _format($items, $indent=1, $newline=TRUE){
        $statement ='';
        $indent_character = "\t";

        $tab = "";
        $count = 1;
        while($count<=$indent):
            $tab .= $indent_character;
            $count++;
        endwhile;

        if(is_array($items) && count($items)==0):
            return '';
        endif;

        if(empty($items)):
            return $items;
        endif;

        if(is_array($items) && !empty($items)):
            foreach ($items as $item) :
                $statement .= $tab;
                $statement .= $item;
                if($newline):
                    $statement .= PHP_EOL;
                endif;
            endforeach;
        endif;

        if(!is_array($items) && !is_object($items)):
            $statement .= $tab;
            $statement .= $items;
            if($newline):
                $statement .= PHP_EOL;
            endif;
        endif;


        return $statement;
    }

    /**
     * @param powerorm\migrations\operations\Operation $operation the operation being worked on.
     * @param int $timestamp the timestamp to ensure name uniqueness.
     * @return mixed|string
     */
    public static function class_name($operation, $timestamp){
        $name = sprintf('migration_%1$s_%2$s',
            ucwords(strtolower($operation['operation']->message())),
            ucwords(strtolower($operation['model_name'])));

        $name = str_replace("_", " ", $name);
        $name = ucwords(strtolower($name));
        $name = str_replace(" ", "_", $name);

        $name .= "_$timestamp";
        return $name;
    }

    /**
     * Generates a Migration class for an Operation passed in.
     * @param powerorm\migrations\operations\Operation $operation the operation to create the migration class for.
     * @param int $timestamp timestamp to ensure uniqueness.
     * @return string
     */
    public static function migration_template($operation, $timestamp){
        $linebreak = PHP_EOL;
        $class_name = Migrator::class_name($operation, $timestamp);

        $model_name =$operation['model_name'];

        $depends = stringify($operation['dependency'], NULL, ';', NULL, TRUE);

        $migration_file = Migrator::_format("<?php", NULL);
        $migration_file .= $linebreak;
        $migration_file .= Migrator::_format('use powerorm\migrations\RunSql;', NULL);
        $migration_file .= $linebreak;
        $migration_file .=Migrator::_format("class $class_name extends CI_Migration{", NULL);
        $migration_file .= $linebreak;
        $migration_file .=Migrator::_format("public \$model= '$model_name';");
        $migration_file .=Migrator::_format("public \$depends= $depends");
        $migration_file .= $linebreak;
        $migration_file .=Migrator::_format("public function up(){");
        $migration_file .= Migrator::_format($operation['operation']->up(), 2);
        $migration_file .=Migrator::_format("}");
        $migration_file .= $linebreak;
        $migration_file .=Migrator::_format("public function down(){");
        $migration_file .= Migrator::_format($operation['operation']->down(), 2);
        $migration_file .=Migrator::_format("}");
        $migration_file .= $linebreak;
        $migration_file .=Migrator::_format("public function state(){");
        $migration_file .= Migrator::_format("return", 2, FALSE);
        $migration_file .= stringify($operation['operation']->state(), 1, ';');
        $migration_file .=Migrator::_format("}");
        $migration_file .= $linebreak;
        $migration_file .=Migrator::_format("}", NULL);

        return $migration_file;
    }

    /**
     * Creates a migration file that represent operation passed in.
     * @param powerorm\migrations\operations\Operation $operation the operation to write into file.
     */
    public static function _writer($operation){

        // make file and class as unique as possible
        $microtime = microtime(TRUE);
        $time = floor($microtime);
        $micro = floor(10000 * ($microtime-$time));

        $timestamp = sprintf('%1$s_%2$s', $time, $micro);

        $file_name = sprintf('%1$s_%2$s_%3$s_%4$s',
           Migrator::file_stamp(),
            strtolower($operation['operation']->message()),
            strtolower($operation['model_name']),
            $timestamp);

        $template = Migrator::migration_template($operation, $timestamp);

        Migrator::$_migrations_path =  APPPATH.'migrations/';
        // create folder if it does not exist
        if(!file_exists(Migrator::$_migrations_path)):
            mkdir(Migrator::$_migrations_path);
        endif;


        // absolute path to file
        $file = Migrator::$_migrations_path.$file_name.".php";

        $file_handle = fopen($file,"w");
        if($file_handle):
            fprintf($file_handle, $template);
            fclose($file_handle);

            chmod($file, 0777);

        endif;


    }

    /**
     * Ensure the generated stamp and the stamp used previously match, this is to avoid having timestamp based
     * migration files and sequential based migration files on the same application
     * @param $migration_stamp
     * @throws AmbiguityError
     */
    public static function _validate_stamp($migration_stamp){
        $past = Migrator::last_stamp();

        if($migration_stamp == 'timestamp' && (strlen($past)==3)):
            throw new AmbiguityError("Migration files seem to use `sequential` but the config file is set to `timestamp` ");
        endif;

        if($migration_stamp == 'sequential' && (strlen($past)==14)):
            throw new AmbiguityError("Migration files seem to use  `timestamp` but the config file is set to `sequential` ");
        endif;
    }

    /**
     * Create a stamp for each migration file, this will generate timestamp or sequntial numbers base on the
     * `migration_type` setting on the migration config file.
     * @return bool|int|string the stamp to use.
     * @throws AmbiguityError
     */
    public static function _stamp(){
        $ci = get_instance();
        $ci->config->load('migration');
        $migration_stamp = $ci->config->item('migration_type');

        Migrator::_validate_stamp($migration_stamp);

        $stamp = 0;
        if($migration_stamp == 'timestamp'):
            $microtime = microtime(TRUE);
            $time = floor($microtime);
            $micro =($microtime - $time) * 100;

            $stamp =  date("YmdHis", ($time+$micro));
            // incase generated timestamp is less than the last timestamp fast forward
            if($stamp <= Migrator::last_stamp()):
                $stamp = Migrator::last_stamp()+1;
            endif;
        endif;

        if($migration_stamp == 'sequential'):
            $last_stamp = Migrator::last_stamp();
            if(empty($last_stamp)):
                $stamp = 1;
            else:
                $stamp = Migrator::last_stamp()+1;
            endif;

            $stamp = sprintf('%03d', $stamp);
        endif;
        return $stamp;
    }

    /**
     * Gets the stamp to use on migration file.
     * @return bool|int|string
     */
    public static function file_stamp(){
        return Migrator::_stamp();
    }

    /**
     * Gets the latest migration stamp.
     * @return int
     */
    public static function last_stamp(){
        $model_files = [];
        foreach (glob(APPPATH.'migrations/'."*.php") as $file) :
            $model_files[]=$file;
        endforeach;
        rsort($model_files);

        foreach ($model_files as $file) :
            $file = basename($file, '.php');
            return preg_split('/_/', $file, 2)[0];
        endforeach;

    }

}
