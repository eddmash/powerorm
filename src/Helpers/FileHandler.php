<?php

/*
* This file is part of the powerorm package.
*
* (c) Eddilbert Macharia <edd.cowan@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eddmash\PowerOrm\Helpers;

use Eddmash\PowerOrm\BaseObject;
use SplFileInfo;

/**
 * Responsible for creating files. creates files with the extension "php".
 *
 * @since  1.0.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class FileHandler extends BaseObject
{
    public $path;

    public $content;

    public $fileName;

    /**
     * @param string $folder   the absolute path to the folder to be created/read from
     * @param string $fileName the specific file to handle in the folder specified
     */
    public function __construct($folder, $fileName = '')
    {
        $this->path = $folder;

        $this->fileName = $fileName;
    }

    public static function createObject($param = [])
    {
        $fileName = (empty($param['fileName'])) ? '' : $param['fileName'];

        return new static($param['path'], $fileName);
    }

    /**
     * @param string $content the content to write
     */
    public function write($content)
    {
        // create folder if it does not exist
        if (!file_exists($this->path)) {
            mkdir($this->path);
        }

        // absolute path to file
        $file = $this->path.DIRECTORY_SEPARATOR.$this->fileName;

        $fileHandle = fopen($file, 'w');

        if ($fileHandle) {
            fprintf($fileHandle, $content);

            fclose($fileHandle);
            chmod($file, 0777);
        }

        return file_exists($file);
    }

    public function getPathFiles($ext = 'php', $recurse = true)
    {
        return $this->readDir($ext, $recurse);
    }

    public function getFile($name = '', $ext = 'php')
    {
        $ext = $this->stableExt($ext);
        $files = $this->getDirContent($ext, true, true);

        $name = $this->normalizeKey($name);

        /** @var $file SplFileInfo */
        foreach ($files as $file) {
            $fileName = $file->getBaseName('.'.$ext);
            if ($this->normalizeKey($fileName) == $name && $file->getExtension() == $ext) {
                return $file;
            }
        }

        return false;
    }

    /**
     * searches for files in a directory recursively.
     *
     * @param string    $ext     the extension of files to return defualt is "php"
     * @param bool|true $recurse if true checks inside directories within the directory default is true
     *
     * @return array
     */
    public function readDir($ext = 'php', $recurse = true)
    {
        return $this->getDirContent($ext, $recurse);
    }

    /**
     * Read contents inside a directory.
     *
     * @param string     $ext
     * @param bool|true  $recurse
     * @param bool|false $_fileObj if true returns a file object, if false returns a file pathname
     *
     * @return array
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function getDirContent($ext = 'php', $recurse = true, $_fileObj = false)
    {
        $_fileList = [];

        // check if some put the extension beginning with the "."
        $ext = $this->stableExt($ext);

        $directory = $this->stableDir($this->path);
        if (!file_exists($directory)) {
            return [];
        }

        $dirIterator = new \DirectoryIterator($directory);

        foreach ($dirIterator as $file) {
            if ($file->isDot()) {
                continue;
            }

            if ($file->isDir() && $recurse) {
                $_fileList = array_merge($_fileList, (new static($file->getRealPath()))->readDir($ext, $recurse));
            } else {
                $_fileList = $this->addFile($_fileList, $file, $ext, $_fileObj);
            }
        }

        return $_fileList;
    }

    /**
     * @param array        $_fileList
     * @param \SplFileInfo $file
     * @param              $ext
     * @param bool|false   $_fileObj  if true returns a file object, if false returns a file pathname
     *
     * @return array
     *
     * @since  1.1.0
     *
     * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
     */
    private function addFile(array $_fileList, \SplFileInfo $file, $ext, $_fileObj)
    {
        if (!empty($ext)) {
            if ($ext == $file->getExtension()) {
                if ($_fileObj) {
                    $_fileList[] = clone $file;
                } else {
                    $_fileList[] = $file->getRealPath();
                }
            }

            return $_fileList;
        }

        // add everything
        if ($_fileObj) {
            $_fileList[] = clone $file;
        } else {
            $_fileList[] = $file->getRealPath();
        }

        return $_fileList;
    }

    private function stableExt($ext)
    {
        // does it start with a `.` trim it
        return (preg_match("/^\./", $ext)) ? ltrim($ext, '.') : $ext;
    }

    private function stableDir($name)
    {
        return (preg_match("/\/$/", $name)) ? $name : $name.'/';
    }
}
