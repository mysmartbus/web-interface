<?php
/**
 * Get information about a file
 *
 * Created: 2014-12-13 by Nathan Weiler (ncweiler2@hotmail.com)
 * Updated: 2014-12-13 by Nathan Weiler (ncweiler2@hotmail.com)
 *
**/

class FileSpec {

    private $_file = ''; // Full path to file
    private $_filemime = ''; // Files mime type
    private $_fileextmime = ''; // Files extension according to class MimeMagic()
    private $_fileextorig = ''; // Files original extension
    private $_filesize = 0; // File size in bytes

    public function __construct($file = NULL) {

        global $IP;

        if ($file != NULL) {
            // Process the file to get its info

            $this->_file = $file;
            unset($file);

            require_once $IP.'/includes/filehandling/cl_MimeMagic.php';
            $magic = MimeMagic::singleton();

            // Get file extension and convert to lowercase
            $this->_fileextorig = strtolower(substr(strrchr($this->_file, "."), 1));

            $filemime = MimeMagic::singleton()->guessMimeType($this->_file);
            $this->_filemime = $magic->improveTypeFromExtension($filemime, $this->_fileextorig);

            // Get known file extensions for given mime type
            // $magicext will be a space seperated string of possible file extensions
            $magicext = $magic->getExtensionsForType($this->_filemime);
            $magicext = explode(' ',$magicext);

            // $magicext[0] is the main file extension used with the mime type
            $this->_fileextmime = $magicext[0];

            // File size in bytes
            $this->_filesize = filesize($this->_file);
        }
    }

    public function getMimeType() {
        /**
         * Returns the files mime type
         *
         * @param None
         *
         * @return string
        **/

        return $this->_filemime;
    }

    public function getFileExts() {
        /**
         * Returns the files original extension and the extension set by $this->_filemime
         *
         * @param None
         *
         * @return array
        **/

        return array('orig' => $this->_fileextorig, 'mime' => $this->_fileextmime);
    }

    public function getStat() {
        /**
         * Returns the files extension
         *
         * How to read stat()'s output
         *
         * Numeric    Associative    Description
         * 0          dev            device number
         * 1          ino            inode number +
         * 2          mode           inode protection mode
         * 3          nlink          number of links
         * 4          uid            userid of owner +
         * 5          gid            groupid of owner +
         * 6          rdev           device type, if inode device
         * 7          size           size in bytes
         * 8          atime          time of last access (Unix timestamp)
         * 9          mtime          time of last modification (Unix timestamp)
         * 10         ctime          time of last inode change (Unix timestamp)
         * 11         blksize        blocksize of filesystem IO ++
         * 12         blocks         number of 512-byte blocks allocated ++
         *
         * + On Windows this will always be 0.
         * ++ Only valid on systems supporting the st_blksize type - other systems (e.g. Windows) return -1. 

         *
         * @param None
         *
         * @return string
        **/

        return stat($this->_file);
    }

    public function getFileSize() {
        /**
         * Returns the files size in bytes
         *
         * @param None
         *
         * @return string
        **/

        return $this->_filesize;
    }

}
