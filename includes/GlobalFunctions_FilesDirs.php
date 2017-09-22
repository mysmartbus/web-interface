<?php
/**
 * File: GlobalFunctions_FilesDirs.php
 *
 * Created: 2017-06-04 by Nathan Weiler (ncweiler2@hotmail.com)
 * Updated: 2017-06-24 by Nathan Weiler (ncweiler2@hotmail.com)
 *
 * These are functions that create lists of files and directories and optionally
 * filter through those lists.
 *
**/

if (!defined('KRAVEN')) {
	die('This file is part of Kraven. It is not a valid entry point.');
}

function kgGetDirlist($dir) {
    /**
     * Return a list of directory names inside $dir
     *
     * Does a non-recursive search.
     * Any files inside $dir are ignored.
     *
     * Added: 2017-06-24
     * Modified: 2017-06-24
     *
     * @param Required string $dir List the directories found inside this directory
     *
     * @return array
    **/

    global $IP;

    // Array that holds file list
    $retval = array();

    if (substr($dir, 0, strlen($IP)) != $IP ||
        strpos($dir, DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR) !== false) {
        // Someone tried to access files outside the servers document root
        // Give them an empty list for attempting to do something nasty
        return $retval;
    }

    // Adds trailing slash if missing
    if (substr($dir, -1) != "/") $dir .= "/";

    // The directory needs to exist or the function will crash
    if (is_dir("$dir")) {

        // Opens the directory and gets list of contents
        $d = @dir($dir) or die("kgGetDirlist: Failed opening directory $dir for reading");
        while (false !== ($entry = $d->read())) {

            // Skip hidden files and directories
            if ($entry[0] == ".") continue;

            if (is_dir("$dir$entry")) {
                // Add directory name to array
                $retval[] = "$entry";
            }
        }
        $d->close();
    }

    // Return the list of files or an empty array
    return $retval;
}

function kgGetFileList($dir, $depth=false) {

    /**
     * SECURITY: The list returned by this function could include the full path
     *           to the file revealing the directory structure of the server.
     *
     *           Use kgGetFileList() if you only want the file names.
    **/

    
    /**
     * This function will list all files it finds.
     *
     * If you want to filter the results by file extension use kgFilterFileList().
     *
     * Source: http://wiki.kraven.rat/PHP_Directory_Listing
     *
     * Added: 2017-06-04
     * Modified; 2017-06-44
     *
     * @param Required string $dir MUST be an absolute path on the server file system
     * @param Optional bool|integer $depth
     *                                    boolean(False): List all files in $dir but not those in it's subdirectories
     *                                    integer: Limit the depth of the recursion to this many levels below $dir
     *
     * @return array
    **/

    // Array that holds file list
    $retval = array();

    if (substr($dir, 0, strlen($_SERVER{'DOCUMENT_ROOT'})) != $_SERVER{'DOCUMENT_ROOT'} ||
        strpos($dir, DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR) !== false) {
        // Someone tried to access files outside the servers document root
        // Give them an empty list for attempting to do something nasty
        return $retval;
    }

    // Adds trailing slash if missing
    if (substr($dir, -1) != "/") $dir .= "/";

    // The directory needs to exist or the script will crash
    if (is_dir("$dir")) {

        // Opens the directory and gets list of files
        $d = @dir($dir) or die("getFileList: Failed opening directory $dir for reading");
        while (false !== ($entry = $d->read())) {

            // Skip hidden files
            if ($entry[0] == ".") continue;

            if (is_dir("$dir$entry")) {
                // Do nothing unless recursion is enabled

                if ($recursion === true && is_readable("$dir$entry/")) {
                    if ($depth === false) {
                        // Unlimited recursion
                        $retval = array_merge($retval, kgGetFileListSafe("$dir$entry/", true));
                    } elseif ($depth > 0) {
                        // Limit recursion to $depth directories deep
                        $retval = array_merge($retval, kgGetFileListSafe("$dir$entry/", true, $depth-1));
                    }
                }

            } elseif (is_readable("$dir$entry")) {
                /**
                 * SECURITY: Full path included
                **/
                // Add full path and file name to array
                $retval[] = "$dir$entry";
            }
        }
        $d->close();
    }

    // Return the list of files or an empty array
    return $retval;

}
// END function kgGetFileList()
/////

function kgGetFileListSafe($dir, $depth=false) {
    /**
     * This function will list all files it finds. Only the file name will be
     * stored in the array $retval.
     *
     * If you want to filter the results by file extension use kgFilterFileList().
     *
     * Source: http://wiki.kraven.rat/PHP_Directory_Listing
     *
     * Added 2017-06-04
     * Modified 2017-06-04
     *
     * @param Required string $dir MUST be an absolute path on the server file system
     * @param Optional bool|integer $depth
     *                                    boolean(False): List all files in $dir but not those in it's subdirectories
     *                                    integer: Limit the depth of the recursion to this many levels below $dir
     *
     * @return array $retval An empty array if no files found OR an a array with file names only
    **/

    global $IP;

    // Array that holds file list
    $retval = array();

    if (substr($dir, 0, strlen($IP)) != $IP ||
        strpos($dir, DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR) !== false) {
        // Someone tried to access files outside the servers document root
        // Give them an empty list for attempting to do something nasty
        return $retval;
    }

    // Adds trailing slash if missing
    if (substr($dir, -1) != "/") $dir .= "/";

    // The directory needs to exist or the script will crash
    if (is_dir("$dir")) {

        // Opens the directory and gets list of files
        $d = @dir($dir) or die("getFileList: Failed opening directory $dir for reading");
        while (false !== ($entry = $d->read())) {

            // Skip hidden files
            if ($entry[0] == ".") continue;

            if (is_dir("$dir$entry")) {
                // Does nothing unless recursion is enabled

                if ($depth === false) {
                    // Unlimited recursion
                    $retval = array_merge($retval, kgGetFileListSafe("$dir$entry/", $depth));
                } elseif ($depth > 0) {
                    // Limit recursion to $depth directories deep
                    $retval = array_merge($retval, kgGetFileListSafe("$dir$entry/", $depth-1));
                }

            } elseif (is_readable("$dir$entry")) {
                // Add file name to array
                $retval[] = "$entry";
            }
        }
        $d->close();
    }

    // Return the list of files or an empty array
    return $retval;

}
// END function kgGetFileListSafe()
/////

function kgFilterFileList($dir, $extension, $safelist=false, $depth=false) {

    /**
     * SECURITY: The list returned by this function could include the full path
     *           to the file revealing the directory structure of the server
     *
     *           Set $safelist to (bool)true to remove the directory info from the array
    **/

    /**
     * This function filters out the files that do not have the $extension extension
     *
     * Added: 2017-06-07
     * Modified: 2017-06-07
     *
     * @param Required string $dir MUST be an absolute path on the server file system
     * @param Required string|array $extension The file extension(s) you want to filter by
     *                                         Leading dot required
     * @param Optional bool $safelist Determines what info $filelist will contain
     *                                True: File names only
     *                                False: Full path to each file with file name
     * @param Optional bool|integer $depth
     *                                    boolean(False): List all files in $dir but not those in it's subdirectories
     *                                    integer: Limit the depth of the recursion to this many levels below $dir
     *
     * @return array $filteredlist An empty array OR an array populated only with the list of files we want
    **/

    // Contains the names of the files we want
    $filteredlist = array();

    // Get the list of all files in $dir
    if ($safelist) {
        $filelist = kgGetFileListSafe($dir, $depth);
    } else {
        $filelist = kgGetFileList($dir, $depth);
    }

    // Now filter the list
    foreach($filelist as $dummy => $file) {
        if (is_array($extension) === true) {
            // Multiple file extensions to filter
            foreach ($extension as $dummy => $ext) {
                if(!preg_match("/\\$ext$/", $file)) continue;

                $filteredlist[] = $file;
            }
        } else {
            // Only one file extension to filter by
            if(!preg_match("/\\$extension$/", $file)) continue;

            $filteredlist[] = $file;
        }
    }

    return $filteredlist;

}
// END function kgFilterFileList()
/////

function kgListFolderContents($foldername, $filesonly = false) {
    /**
     * Retreives the list of files and sub-folders under $foldername from Kravens main database
     *
     * Added: 2017-06-16
     * Modified: 2017-06-16
     *
     * @param Required string $foldername Name of folder to generate a list for
     * @param Optional boolean $filesonly If True will only list the files contained in $foldername
     *                                    If False will list files and sub-folders in $foldername
     *
     * @return array $listing This is a nested array
     *      array('Folders') An array of the folders found
     *                  'FolderID' (key) => 'FolderName' (value)
     *      array('FolderCount') Number of folders found
     *      array('Files') An array of the files found
     *                  'FileSHA1' (key) => 'FileName' (value)
     *      array('FileCount') Number of files found
    **/

    global $KG_DBC;

    $listing = array(
        'FolderCount' => 0,
        'FileCount' => 0
    );

    // Get folder id for $foldername
    $folderid = $KG_DBC->fieldValue($KG_DBC->select('`Files`.`FileFolders`', 'FolderName = "'.$foldername.'"', 'FolderID'));

    if (is_numeric($folderid) && $folderid > 0) {

        if ($filesonly === false) {

            // Get all visible sub-folders in this folder
            $query = $KG_DBC->select('`Files`.`FileFolders`', array('FolderParent' => $folderid, 'FolderHidden' => 0), array('FolderName', 'FolderID'), array('ORDER BY' => 'FolderName ASC'));
            if ($KG_DBC->numRows($query) > 0) {

                while ($row = $KG_DBC->fetchAssoc($query)) {
                    $listing['Folders'][$row['FolderID']] = $row['FolderName'];
                }
            } else {
                // Empty array
                $listing['Folders'] = array();
            }

            // Count number of folders found
            $listing['FolderCount'] = count($listing['Folders']);
        }

        // Now list the files
        $query = $KG_DBC->select('`Files`.`FileMultiFolder`', 'FolderID = '.$folderid, 'FileSHA1');

        if ($KG_DBC->numRows($query) > 0) {
            while ($row = $KG_DBC->fetchAssoc($query)) {
                $fileinfo = $KG_DBC->fetchAssoc($KG_DBC->select('`Files`.`FileInfo`', 'FileSHA1 = "'.$row['FileSHA1'].'"', array('FileName', 'FileSHA1')));
                $listing['Files'][$row['FileSHA1']] = $fileinfo['FileName'];
            }

        } else {
            // Empty array
            $listing['Files'] = array();
        }

        // Count number of files found
        $listing['FileCount'] = count($listing['Files']);
    }

    return $listing;

}
// END function kgListFolderContents()
/////

function kgMakeFolders($dir, $mode = 0755) {
    /**
     * Creates the folder and parent folder(s) if needed
     *
     * Added: 2017-06-29
     * Modified: 2017-06-29
     *
     * @param Required string $dir Folder(s) to check and create if needed
     * @param Optional integer $mode Permissions to apply to all folders created
     *                               $mode is from one to four octal digits (0-7),
     *                               derived by adding up the bits with values 4,
     *                               2, and 1. Omitted digits are assumed to be
     *                               leading zeros.
     *
     * @return boolean
    **/

    // Assume folder already exists
    $status = true;

    if (is_dir($dir) !== true) {
        // Folder not found so try to create it
        // Also creates parent folders if needed
        // Beware typos in folder names and missing slashes (/)
        $status = mkdir($dir, $mode, true);
    }

    return $status;

}
// END function kgMakeFolders()
/////

function kgShaToDir($sha) {
    /**
     * Converts an SHA-1 hash into the name of the folder under /files in
     * which the file is stored.
     *
     * Added: 2017-06-09
     * Modified: 2017-06-09
     *
     * @param Required string $sha The SHA-1 hash to use
     *
     * @return string
    **/

    return substr($sha,0,1).'/'.substr($sha,0,2);
}
// END function kgShaToDir()
/////
?>
