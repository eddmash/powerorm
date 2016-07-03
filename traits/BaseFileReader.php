<?php
namespace powerorm\traits;

/**
 * Class BaseFileReader
 * @package powerorm\traits
 * @since 1.0.2
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
trait BaseFileReader {

    /**
     * searches for files in a directory recursively.
     * @param string $directory the directory to fetch and return all its files
     * @param string $ext the extension of files to return defualt is "php"
     * @param bool|TRUE $recurse if true checks inside directories within the directory default is true
     * @return array
     */
    public function get_directory_files($directory, $ext='php', $recurse=TRUE){
        $file_list = [];

        // check if some put the extension begining with the "."
        $ext = $this->stable_ext($ext);
        $directory = $this->stable_dir($directory);

        foreach (glob($directory."*".$ext) as $file) :
            if(is_dir($file) && $recurse):
                foreach (glob($file."/*.".$ext) as $file) :
                    $file_list[]=$file;
                endforeach;
            else:
                $file_list[] = $file;
            endif;
        endforeach;

        return $file_list;
    }

    public function stable_ext($ext){
        return (preg_match("/^\./", $ext)) ? $ext : ".".$ext;
    }

    public function stable_dir($name){
        return (preg_match("/\/$/",  $name)) ? $name : $name."/";
    }
}