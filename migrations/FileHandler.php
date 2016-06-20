<?php
namespace powerorm\helpers;
use powerorm\traits\BaseFileReader;


/**
 * Responsible for creating files. creates files with the extension "php".
 * @package powerorm\migrations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class FileHandler{

    public $path;
    public $content;
    public $file_name;

    use BaseFileReader;

    /**
     * @param string $path the absolute path to the file to be created
     * @param string $file_name the filename to create
     */
    public function __construct($path, $file_name=''){
        $this->path =  $path;
        $this->file_name = $file_name;
    }

    /**
     * @param string $content the content to write
     */
    public function write($content){
        // create folder if it does not exist
        if(!file_exists($this->path)):
            mkdir($this->path);
        endif;


        // absolute path to file
        $file = $this->path.$this->file_name.".php";

        $file_handle = fopen($file,"w");
        if($file_handle):
            fprintf($file_handle, $content);
            fclose($file_handle);

            chmod($file, 0777);

        endif;
    }

    public function get_path_files($ext='php', $recurse=TRUE){
        return $this->get_directory_files($this->path, $ext, $recurse);
    }

    public function get_file($name='', $ext='php'){
        $name = (empty($name))? $this->file_name : $name;
        $files = $this->get_path_files($ext);
        $file_name = sprintf('%1$s%2$s', $name, $this->stable_ext($ext));


        foreach ($files as $file) :
            if(basename($file) === $file_name):
                return $file;
            endif;

        endforeach;

        return NULL;

    }

}

