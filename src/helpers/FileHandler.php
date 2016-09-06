<?php
namespace eddmash\powerorm\helpers;

use eddmash\powerorm\Object;

/**
 * Responsible for creating files. creates files with the extension "php".
 * @package eddmash\powerorm\migrations
 * @since 1.0.0
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class FileHandler extends Object
{
    public $path;
    public $content;
    public $file_name;

    /**
     * @param string $folder the absolute path to the folder to be created/read from
     *
     * @param string $file_name the specific file to handle in the folder specified
     */
    public function __construct($folder, $file_name = '')
    {
        $this->path = $folder;
        $this->file_name = $file_name;
    }

    /**
     * @param string $content the content to write
     */
    public function write($content)
    {

        // create folder if it does not exist
        if (!file_exists($this->path)):
            mkdir($this->path);
        endif;

        // absolute path to file
        $file = $this->path . $this->file_name . ".php";

        $file_handle = fopen($file, "w");
        if ($file_handle):
            fprintf($file_handle, $content);
            fclose($file_handle);

            chmod($file, 0777);

        endif;
    }

    public function get_path_files($ext = 'php', $recurse = true)
    {
        return $this->read_dir($ext, $recurse);
    }

    public function get_file($name = '', $ext = 'php')
    {
        $ext = $this->stable_ext($ext);
        $files = $this->_read_dir($ext, true, true);

        $name = $this->standard_name($name);

        foreach ($files as $file) :
            $file_name = $file->getBaseName('.' . $ext);
            if ($this->standard_name($file_name) == $name && $file->getExtension() == $ext):
                return $file;
            endif;

        endforeach;

        return null;
    }

    /**
     * searches for files in a directory recursively.
     * @param string $ext the extension of files to return defualt is "php"
     * @param bool|TRUE $recurse if true checks inside directories within the directory default is true
     * @return array
     */
    public function read_dir($ext = 'php', $recurse = true)
    {
        return $this->_read_dir($ext, $recurse);
    }

    /**
     * Read contents inside a directory
     * @param string $ext
     * @param bool|TRUE $recurse
     * @param bool|false $file_obj if true returns a file object, if false returns a file pathname
     * @return array
     * @since 1.1.0
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    public function _read_dir($ext = 'php', $recurse = true, $file_obj = false)
    {
        $file_list = [];

        // check if some put the extension beginning with the "."
        $ext = $this->stable_ext($ext);

        $directory = $this->stable_dir($this->path);

        $dirIterator = new \DirectoryIterator($directory);

        foreach ($dirIterator as $file) :
            if ($file->isDot()):
                continue;
            endif;

            if ($file->isDir() && $recurse):
                foreach ($file as $inner_file) :
                    $file_list = $this->_add_file($file_list, $inner_file, $ext, $file_obj);
                endforeach;
            else:
                $file_list = $this->_add_file($file_list, $file, $ext, $file_obj);
            endif;
        endforeach;

        return $file_list;
    }

    public function _add_file(array $file_list, \SplFileInfo $file, $ext, $file_obj)
    {
        if (!empty($ext)):
            if ($ext == $file->getExtension()):
                if ($file_obj):
                    $file_list[] = clone $file;
                else:
                    $file_list[] = $file->getPathname();
                endif;
            endif;

            return $file_list;
        endif;

        // add everything
        if ($file_obj):
            $file_list[] = clone $file;
        else:
            $file_list[] = $file->getPathname();
        endif;


        return $file_list;
    }

    public function stable_ext($ext)
    {
        // does it start with a `.` trim it
        return (preg_match("/^\./", $ext)) ? ltrim($ext, '.') : $ext;
    }

    public function stable_dir($name)
    {
        return (preg_match("/\/$/", $name)) ? $name : $name . "/";
    }
}
