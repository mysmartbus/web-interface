<?php
/**
 * File: shahash.php
 *
 * Created: 2014-12-13 by Nathan Weiler (ncweiler2@hotmail.com)
 * Updated: 2015-12-13 by Nathan Weiler (ncweiler2@hotmail.com)
 *
 * Used to generate and retreive a files SHA-1 hash and folder structure
 *
**/

class ShaHash {

    private $_file = NULL; // Full path to file
    private $_sha1hash = ''; // The files SHA-1 hash

    // Directories from the files SHA-1 hash
    private $_dirone = '';
    private $_dirtwo = '';
    private $_dirfull = ''; // Includes leading and trailing slashes (/)

    public function __construct($hash = '') {
        if ($hash != '') {
            // Process an SHA-1 hash
            $this->_sha1hash = $hash;
            $this->shaToDir();
        }
    }

    public function getInfo($file = NULL) {
        /**
         * Returns the SHA-1 hash info
         *
         * @params Required string $file The full path to the file on disk
         *
         * @return associative array
        **/

        if ($file != NULL) {

            $this->_file = $file;

            $this->getSha1Base36();
            $this->shaToDir();

        }

        // Returns all of the info available
        return array('hash' => $this->_sha1hash, 'dirone' => $this->_dirone, 'dirtwo' => $this->_dirtwo, 'dirfull' => $this->_dirfull);
    }

    private function getSha1Base36() {
        /**
         * Get a SHA-1 hash of a file in the local filesystem, in base-36 lower case
         * encoding, zero padded to 31 digits.
         *
         * 160 log 2 / log 36 = 30.95, so the 160-bit hash fills 31 digits in base-36
         * fairly neatly.
         *
         * Taken from 'mediawiki-1.22.1::/includes/filebackend/FSFile.php::Line 200'
         *
         * Added: 2014-09-29
         * Modified: 2014-??-??
         *
         * @param Required string $file full path to the file
         *
         * @return (boolean)False if no file provided during class initialization
        **/

        if ($this->_file == NULL) {
            // Class was initialized without being given a path to a file
            return false;
        }

        // Make sure we were given the full/absolute path to a file
        if (substr($this->_file,0,1) == '/') {

            // Get the SHA-1 hash
            $this->_sha1hash = sha1_file($this->_file);

            if ($this->_sha1hash !== false) {
                // Convert it to base 36
                $this->_sha1hash = kgBaseConvert($this->_sha1hash, 16, 36, 31);
            }

            if ($this->_sha1hash === false) {
                $this->_sha1hash = '';
            }
        }
    }
    // END function getSha1Base36()
    /////

    private function shaToDir() {
        /**
         * Converts an SHA-1 hash to a directory path.
         *
         * $sha1 could actually be an random string of [a-zA-Z0-9] since
         * nothing is done to verify $sha1 as a valid SHA-1 hash
         *
         * @param None
         *
         * @return Nothing
        **/

        $this->_dirone = substr($this->_sha1hash,0,1);
        $this->_dirtwo = substr($this->_sha1hash,0,2);
        $this->_dirfull = $this->_dirone.'/'.$this->_dirtwo;
    }
    // END function shatodir()
    /////

}
