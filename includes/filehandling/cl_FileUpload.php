<?php
/**
 * Kravens file upload class
 *
 * Added: 2017-06-29 by Nathan Weiler (ncweiler2@hotmail.com)
 * Modified: 2017-06-29 by Nathan Weiler (ncweiler2@hotmail.com)
 *
**/

class FileUpload {

    private $_doUpload = false;   // If true allow upload; if false deny upload
    private $_updateData = false; // If true update file info in database
                                  // If false insert file info into database

    // Files with one of these extensions will not be uploaded
    // Do not include leading dot (.)
    private $_disallowedExtensions = array('exe');

    // The file directory where the files will be sorted into
    private $_fileDir = '';

    // The image file extensions
    private $_imgexts = array();

    private $_destFile = '';
    private $_fileMimeExt = '';

    function __construct() {

        global $kgSiteFiles, $IP;

        $this->_fileDir = $IP.'/'.$kgSiteFiles;

        /**
         * The image file extensions
         * The file extensions in this array will be considered to be pictures
        **/
        $this->_imgexts = kgGetImageExts();
    }

    private function addFolder($info) {
        /**
         * Adds a new folder/gallery to the database and returns the ID number
         *
         * Added: 2017-06-29
         * Modified: 2017-06-29
         *
         * @param Required array $info Contains the folder/gallery name, description and parent folder/gallery ID
         *
         * @return integer $id ID number for the new folder/gallery
        **/

        global $dbc;

        // Get short name of folder/gallery
        $info['FolderName'] = kgGenShortName($info['FolderName']);

        // Add folder info
        $dbc->insert('`Files`.`FileFolders`',$info);

        // Get ID number for this folder
        $id = $dbc->GetInsertID();

        return $id;

    }
    // END function addFolder()

    private function addMultiFolder($sha1, $folderid) {
        /**
         * Sets the folder that the file is in
         *
         * Added: 2017-06-29
         * Modified: 2017-06-29
         *
         * @param Required string $sha1 The files SHA-1 Hash
         * @param Required integer $folderid The folder ID
         *
         * @return Nothing
        **/

        global $dbc;

        // Check if this combination of $sha1 and $folderid already exist
        $dupsquery = $dbc->select('`Files`.`FileMultiFolder`', array('BINARY FileSHA1' => $sha1, 'FolderID' => $folderid));

        if ($dbc->numRows($dupsquery) <= 0) {
            // Add info

            $data = array(
                'FileSHA1' => $sha1,
                'FolderID' => $folderid
            );
            $dbc->insert('`Files`.`FileMultiFolder`',$data);
        }
    }
    // END function addMultiFolder()
    /////

    private function allowedExtension($ext) {
        /**
         * Checks the file extension to see if it is allowed
         *
         * Added: 2017-06-29
         * Modified: 2017-06-29
         *
         * @param Required string $ext The file extension to check
         *                            Do not include leading dot (.)
         *
         * @return boolean
         *         (boolean)True If $ext is not in $this->_disallowedExtensions
         *         (boolean)False If $ext is in $this->_disallowedExtensions
        **/

        return (in_array($ext, $this->_disallowedExtensions) === true) ? false : true;
    }
    // END private function allowedExtension()
    /////

    public function getMimeExt() {
        /**
         * Returns the file extension as set by class MimeType()
         *
         * Added: 2017-06-29
         * Modified: 2017-06-29
         *
         * @param None
         *
         * @return string
        **/
        return $this->_fileMimeExt;
    }

    public function doUpload($fileinfo, $path) {
        /**
         * Call this function to upload one file at a time
         *
         * Also takes care of adding the file info to the database
         *
         * To upload multiple files, you will need to use a foreach() loop
         *  Example:
         *    foreach ($_FILES as $key => $filearray) {
         *        list($result, $msg) = $upload->doUpload($filearray);
         *    }
         *
         * Added: 2017-06-29
         * Modified: 2017-06-29
         *
         * @param Required array  $fileinfo This is the info contained in $filearray
         * @param Required string $path     Folder structure to add to database
         *
         * @return array
         *      array[0] One of the KG_UPLOAD_* constants found in /includes/Defines.php
         *               Will be (boolean)false if the user is not logged in
         *      array[1] String if array[0] is KG_UPLOAD_SUCCESS or KG_UPLOAD_FILE_EXISTS,
         *                      will be file name on server. Otherwise will be error message
         *      array[2] String if array[0] is KG_UPLOAD_SUCCESS or KG_UPLOAD_FILE_EXISTS,
         *                      will be files SHA-1 hash. Otherwise will be blank ('')
        **/

        global $KG_SECURITY;

        // Uploading something requires changes to the database.
        // The user needs to be logged in so Kraven can track who did what.
        if ($KG_SECURITY->isLoggedIn() === false) {
            return array(false, get_lang('NotLoggedIn'), '');
        }

        global $dbc, $userinfo;

        if ($fileinfo['error'] !== UPLOAD_ERR_OK) {
            // Upload failed. Get reason why.

            $filename = ' '.get_lang('file').': '.$fileinfo['name'];

            switch ($fileinfo['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    // Value: 1
                    $message = get_lang('UploadError1_PHPMax').$filename;
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    // Value: 2
                    $message = get_lang('UploadError2_HTMLFormMax').$filename;
                    break;
                case UPLOAD_ERR_PARTIAL:
                    // Value: 3
                    $message = get_lang('UploadError3_PartialUpload').$filename;
                    break;
                case UPLOAD_ERR_NO_FILE:
                    // Value: 4
                    // Do I need to return a message for this one?
                    $message = get_lang('UploadError4_NothingUploaded');
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    // Value: 6
                    $message = get_lang('UploadError6_MissingTempFolder');
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    // Value: 7
                    $message = get_lang('UploadError7_NotWritten').$filename;
                    break;
                case UPLOAD_ERR_EXTENSION:
                    // Value: 8
                    $message = get_lang('UploadError8_BadPHPExt').$filename;
                    break;
                default:
                    $message = get_lang('UploadErrorUnknown').$errcode.$filename;
                    break;
            }

            // Cancel upload
            return array(KG_UPLOAD_FAILED, $message, '');
        }

        // Get info about the file
        $filespec = new FileSpec($fileinfo['tmp_name']);
        $ext = $filespec->getFileExts();
        $filedata['FileMimeType'] = $filespec->getMimeType();
        $this->_fileMimeExt = $ext['mime'];

        if ($this->allowedExtension($ext['orig']) === false) {
            // File extension not allowed

            // Cancel upload
            return array(KG_UPLOAD_FAILED, get_lang('UploadError_InvalidExtension').implode(', .', $this->_disallowedExtensions), '');

        }

        // Get files SHA-1 hash and related info
        $shahash = new ShaHash();
        $shainfo = $shahash->getInfo($fileinfo['tmp_name']);

        // The location where the file will be stored. Includes file name
        $destPath = $this->_fileDir.'/'.$shainfo['dirfull'];
        $destFile = $destPath.'/'.$fileinfo['name'];

        // Renames file if needed
        if ($ext['orig'] != '' && $ext['orig'] != $ext['mime'] && $ext['mime'] != '.') {
            $fileinfo['name'] = str_replace($ext['orig'], '', $fileinfo['name']);
            $fileinfo['name'] .= $ext['mime'];

            $destFile = $destPath.'/'.$fileinfo['name'];
        }

        $this->_destFile = $destFile;

        // Check if info for a file with matching SHA1 hash is already in the database
        $query = $dbc->select('`Files`.`FileInfo`', 'BINARY FileSHA1 = "'.$shainfo['hash'].'"', 'FileName');

        if ($dbc->numRows($query) > 0) {
            // Matching SHA1 hash found. Compare file names

            // Get file name from database
            $name = $dbc->fieldValue($query);

            if ($name == $fileinfo['name']) {
                // It seems this file was already uploaded.
                // Check to see if it actually is on the server.

                if (file_exists($destFile) && is_file($destFile)) {
                    // This file was already uploaded to the server.

                    // Cancel upload
                    return array(KG_UPLOAD_FILE_EXISTS, $fileinfo['name'], $shainfo);
                } else {

                    // File is not on the server but there is some data about it in the database.
                    // Upload the file and update the database.
                    $this->_doUpload = true;
                    $this->_updateData = true;
                }
            } else {

                // File with matching SHA1 hash found but file names differ.
                // Most likely cause of this is the same file with two different names.

                // Cancel upload
/**
 * Should I do something here or in the calling function/file to allow the
 * user to view the existing file?
**/
                return array(KG_UPLOAD_FILE_EXISTS, $fileinfo['name'], $shainfo);
            }
            // END if ($name == $fileinfo['name'])
        } else {
            // SHA1 hash not found. Upload file.

            $this->_doUpload = true;
        }
        // END if ($dbc->numRows($query) > 0)

        if ($this->_doUpload == true) {
            // Upload the file and update the database

            // Creates the destination folders if needed
            kgMakeFolders($destPath);

            if (move_uploaded_file($fileinfo['tmp_name'], $destFile)) {
                // File was successfully uploaded. Now gather some data to place into the database

                // This foreach loop sets $type to 'pic' or 'file'.
                // $type is used to determine what file info gets added to the database
                foreach ($this->_imgexts as $key => $extension) {
                    if($ext['mime'] == $extension) {
                        // Image
                        $type = 'pic';
                        break;
                    } else {
                        // File
                        $type = 'file';
                        // No break here or the script won't be able to check all of
                        // the $this->_imgexts array against $extension
                    }
                }

                // Get ID number for the folder this file goes in.
                // This info is used in the file browser and picture gallery
                $folderid = $this->setFolder($path);

                // The $filedata array contains the data to be placed into the database
                // Using += to include any keys previously set
                $filedata += array(
                    'FileName' => $fileinfo['name'],
                    'FileSize' => $filespec->getFileSize(),
                    'FileDescription' => '', // This info will eventually come from a form field
                    'FileUserID' => $userinfo['UserID'], // User ID of the person who uploaded the file
                    'FileDateUploaded' => date("Y-m-d"), // Todays date
                    'FileTimeUploaded' => date("H:i:s"), // Current time, 24 hour format
                    'FileSHA1' => $shainfo['hash'],
                    'FileDateModified' => date("Y-m-d", filemtime($destFile)), // Date file was last modified
                    'FileTimeModified' => date("H:i:s", filemtime($destFile)) // Time file was last modified
                );

                // Add parent folder info for file
                $this->addMultiFolder($filedata['FileSHA1'], $folderid);

                if ($type == 'pic' && ($filedata['FileMimeType'] == 'mime/jpeg' || $filedata['FileMimeType'] == 'mime/tiff')) {
                    // PHP's exif_read_data() only works on jpeg and tiff images.
                    // getimagesize() will be used for other pictures to get some info.

                    // Get EXIF data for the file
                    $exif->extractData($destFile);
                    $exifdata = $exif->getFilteredData();
                    $getfiledata = $exif->getFileData();

                    // Add the image data to the array
                    $filedata['FileWidth'] = (is_numeric($getfiledata['Width']) ? $getfiledata['Width'] : 0);
                    $filedata['FileHeight'] = (is_numeric($getfiledata['Height']) ? $getfiledata['Height'] : 0);
                    $filedata['FileMetadata'] = serialize($exifdata);
                    $filedata['FileBitsPerPixel'] = 0; // Would this be $exifdata['CompressedBitsPerPixel']?
                    $filedata['FileCameraMake'] = (array_key_exists('Make',$exifdata) === true ? $exifdata['Make'] : '');
                    $filedata['FileCameraModel'] = (array_key_exists('Model',$exifdata) === true ? $exifdata['Model'] : '');
                    $filedata['FileOrientation'] = (array_key_exists('Orientation',$exifdata) === true ? $exifdata['Orientation'] : 1);
                    $filedata['FileXResolution'] = $xresolution;
                    $filedata['FileYResolution'] = $yresolution;
                    $filedata['FileDateTimeTaken'] = (array_key_exists('DateTime',$exifdata) === true ? $exifdata['DateTime'] : '0000-00-00 00:00:00');

                } else {
                    // Use PHP's getimagesize() to get limited info about the file

                    $data = getimagesize($destFile);

                    if ($data !== false) {
                        // Image width and height in pixels
                        $filedata['FileWidth'] = $data[0];
                        $filedata['FileHeight'] = $data[1];
                    }

                }
            /**
             * Done gathering data.
             * Upload file and update database
            **/

                if ($this->_updateData === true) {
                    // Update/Overwrite existing data

                    // Need to remove some data from the $filedata array to avoid $dbc->update() errors
                    $name = $filedata['FileName'];
                    unset($filedata['FileName']);
                    unset($filedata['FileSHA1']);

                    // Update file info
                    if (!$dbc->update('`Files`.`FileInfo`', array('FileName' => $name, 'BINARY FileSHA1' => $shainfo['hash']), $filedata)) {
                        // Update failed

                        // Delete the file to avoid orphans
                        // This file will need to reuploaded once the database error is fixed
                        unlink($destFile);

                        return array(KG_UPLOAD_FAILED_DB_UPDATE, get_lang('UploadError_DatabaseError'), '');
                    } else {
                        // Update succeeded
                        // Upload finished

                        return array(KG_UPLOAD_SUCCESS, $name, $shainfo);
                    }
                } else {
                    // Add a new file to the database

                    if (!$dbc->insert('`Files`.`FileInfo`', $filedata)) {
                        // Insert failed

                        // Delete the file to avoid orphans
                        // This file will need to reuploaded once the database error is fixed
                        unlink($destFile);

                        return array(KG_UPLOAD_FAILED_DB_INSERT, get_lang('UploadError_DatabaseError'), '');
                    } else {
                        // Insert succeeded
                        // Upload finished

                        return array(KG_UPLOAD_SUCCESS, $filedata['FileName'], $shainfo);
                    }
                }
                // END if ($this->_updateData === true)

            } else {
                // Should never get here but just incase an error isn't properly
                // handled above

                return array(KG_UPLOAD_FAILED, get_lang('UploadError_UnexpectedFailure'), '');
            }
        }
    }
    // END public function doUpload()
    /////

    private function setFolder($filepath) {
        /**
         * Adds folder info to the `FileFolders` table if needed
         *
         * Multiple folders/galleries can have the same name, but they
         * are not allowed to have the same parent folder
         *
         * Added: 2017-06-29
         * Modified: 2017-06-29
         *
         * @param Required string $filepath The path to the file in Kraven's $kgSiteSettings['Uploads'] directory
         *                                  Leading and trailing slashes (/) do not need to be removed
         *
         * @return integer The ID number of the folder the file will go in
        **/

        global $dbc;

        // Remove $this->_fileDir from path
        $path = str_replace($this->_fileDir,'',$filepath);

        if ($path == '') {
            // Only parent folder for this file is $kgSiteSettings['Uploads']
            // Do not add any folder info to the database
            return 0;
        }

        // Create an array using the remaining folder names
        $folders = explode('/',$path);

        $parentid = 0;

        foreach ($folders as $key => $foldername) {

            // Occurs when there is a leading or trailing slash in $path
            if ($foldername == '') {
                // Skip
                continue;
            }

            $data = array(
                'FolderName' => $foldername,
                'FolderParent' => $parentid
            );

            // Query to check if this folder is already in the database
            $query = $dbc->select('`Files`.`FileFolders`', array('FolderName' => $foldername, 'FolderParent' => $parentid), 'FolderID');

            if ($dbc->numRows($query) <= 0) {
                // Add new folder
                $parentid = $this->addFolder($data);

            } else {

                // Folder exists, set parent folder id for next folder
                $parentid = $dbc->fieldValue($query);
            }

        }

        return $parentid;
    }
    // END function setFolder()
    /////
}
