<?php
/**
 * File: GlobalFunctions_Images.php
 *
 * Created: 2014-11-06 by Nathan Weiler (ncweiler2@hotmail.com)
 * Updated: 2015-12-13 by Nathan Weiler (ncweiler2@hotmail.com)
 *
 * These are functions that only deal with image files
 *
**/

if (!defined('KRAVEN')) {
	die('This file is part of Kraven. It is not a valid entry point.');
}

function kgGetImageExts() {
    // A static function that returns a list of image extensions
    return array('.gif','.jpg', '.jpeg', '.png', '.bmp', '.tif', '.tiff', '.svg');
}
// END function kgGetImageExts()

function kgGetImageLink($name, $data='') {
    /**
     * Creates a URL and <img> link to the specified image according to the keys in $data
     *
     * Added: 2014-10-31
     * Modified: 2016-01-24
     *
     * @param Required string $name Name of file or SHA1 hash
     * @param Optional array $data
     *                       Configures the <img> tag attributes
     *                       -Keys (Key names are case sensitive)
     *                           'MAXHEIGHT'
     *                              Maximum height of image in pixels. Image ratio will be maintained
     *                              Default is THUMBNAIL_HEIGHT_MEDIUM
     *                           'EXTRATEXT'
     *                              Extra text to add to the title="" and alt="" parts of the <img> tag
     *                           'NO_ALT_TEXT'
     *                              If set, the alt="" attribute will not be included. Value is not used.
     *                           'NO_TITLE_TEXT'
     *                              If set, the title="" attribute will not be included. Value is not used.
     *                           'NO_THUMBNAIL'
     *                              If set, the thumbnail image link will not be created. Value is not used.
     *                           'NO_HREF'
     *                              If set, the <img> tag will not be enclosed by the href tag <a></a>. Value is not used.
     *
     * @return array $imginfo Contains image URL, <img> tag, an <a href> link to the
     *                          full size version if ($maxheight == null) OR
     *                          resized version if ($maxheight != null)
     *                        Empty if file name/SHA-1 hash not found in database
     *                        Contains array('mm' => true) if multiple files with the same name were found
     *                        Contains array('missing' => true) if info found in database but
     *                          file is not on server
    **/

    global $dbc, $IP, $kgSiteFiles, $kgSiteThumbnails;

    // Set configuration options according to the values in the $data array
    if (is_array($data) === true) {

        // MAXHEIGHT
        if (array_key_exists('MAXHEIGHT',$data) === true) {
            $maxheight = $data['MAXHEIGHT'];
        } else {
            $maxheight = null;
        }

        // EXTRATEXT
        if (array_key_exists('EXTRATEXT',$data) === true && $data['EXTRATEXT'] != '') {
            $extratext = $data['EXTRATEXT'];
        } else {
            $extratext = '';
        }

        // NO_ALT_TEXT
        if (array_key_exists('NO_ALT_TEXT',$data) === true) {
            $noalttext = true;
        } else {
            $noalttext = false;
        }

        // NO_TITLE_TEXT
        if (array_key_exists('NO_TITLE_TEXT',$data) === true) {
            $notitletext = true;
        } else {
            $notitletext = false;
        }

        // NO_THUMBNAIL
        if (array_key_exists('MAXHEIGHT',$data) === true) {
            $nothumbnail = true;
        } else {
            $nothumbnail = false;
        }

        // NO_HREF
        if (array_key_exists('NO_HREF',$data) === true) {
            $nohref = true;
        } else {
            $nohref = false;
        }
    } else {
        // Not an array so set defaults
        $maxheight = null;
        $extratext = '';
        $noalttext = false;
        $notitletext = false;
        $nothumbnail = false;
        $nohref = false;
    }

    $imginfo = array();

    // Only thing needed to create the URL/link is the images SHA-1 Hash.
    // Getting the width and height incase $maxheight != null
    $query = $dbc->select('`Files`.`FileInfo`', 'FileName = "'.$name.'"', array('FileSHA1', 'FileWidth', 'FileHeight'));
    if ($dbc->numRows($query) < 1) {
        // Image name not found. Lets see if an SHA-1 hash was given.

        $query = $dbc->select('`Files`.`FileInfo`', 'BINARY FileSHA1 = "'.$name.'"', array('FileName', 'FileSHA1', 'FileWidth', 'FileHeight'));

        if ($dbc->numRows($query) < 1) {

            // SHA-1 hash not found
            // Return empty array
            return array();
        }

    } elseif ($dbc->numRows($query) > 1) {
        // (M)ultiple (M)atches found for $name
        // This will only occur if a file name was given and more than one
        // row was found in the database with that particular file name
        return array('mm' => true);
    }

    // Get image info from database
    $querydata = $dbc->fetchAssoc($query);

    if (array_key_exists('FileName',$querydata) === true) {
        // Setting $name here means $name was originally the files SHA-1 hash
        $name = $querydata['FileName'];
    }

    // Generate partial URL from SHA-1 Hash
    $shadir = kgShaToDir($querydata['FileSHA1']);

    // Image URLs 
    $imginfo['URL'] = '/'.$kgSiteFiles.'/'.$shadir.'/'.$name;
    $imginfo['URLThumb'] = '/'.$kgSiteThumbnails.'/'.$shadir.'/'.$name;

    $diskpath = $IP.'/'.$imginfo['URL'];

    if (!file_exists($diskpath) || !is_file($diskpath)) {
        // Info found in database but file is not on server
        return array('missing' => true);
    }

    // Set title="" text for the <img> tag and alt="" text for the <img> tag
    if ($notitletext === true) {
        $titletext = '';
    } else {
        if ($extratext != '') {
            $titletext = ' text="'.$extratext.'"';
        } else {
            $titletext = ' text="'.$name.' ('.get_lang('ViewFullsize').')"';
        }
    }

    // Set alt="" text for the <img> tag
    if ($noalttext === true) {
        $alttext = '';
    } else {
        if ($extratext != '') {
            $alttext = ' alt="'.$extratext.'"';
        } else {
            $alttext = ' alt="'.$name.' ('.get_lang('ViewFullsize').')"';
        }
    }

    if ($maxheight == null) {
        // This link will show the image at full size

        $imginfo['ImgTag'] = '<img src="'.$imginfo['URL'].'"'.$titletext.$alttext.'>';

        $imginfo['Width'] = $querydata['FileWidth'];
        $imginfo['Height'] = $querydata['FileHeight'];

    } else {
        // This link will show the image at a reduced size

        // Incase $maxheight was sent as (string)'640px' instead of (integer)'640'
        if (substr($maxheight,-2) == 'px') {
            $temp = substr($maxheight,0,-2);
            if (is_numeric($temp) && $temp > 0 && $temp < THUMBNAIL_HEIGHT_MAX) {
                // Set maximum display height in pixels
                $maxheight = substr($value,0,-2);
            }
        }

        // Make sure $maxheight is a valid number
        if (!is_numeric($maxheight)) {
            $maxheight = THUMBNAIL_HEIGHT_MEDIUM;
        }

        // Width and height for display
        list($width,$height) = kgResizeDimensions($querydata['FileWidth'],$querydata['FileHeight'],$maxheight);

        // Create the <img> tag with title text and alt text
        $imginfo['ImgTag'] = '<img src="'.$imginfo['URL'].'"'.$titletext.$alttext.' width="'.$width.'" height="'.$height.'">';

        // Create the <img> tag without title text
        $imginfo['ImgTagNoTitle'] = '<img src="'.$imginfo['URL'].'"'.$alttext.' width="'.$width.'" height="'.$height.'">';

        $imginfo['Width'] = $width;
        $imginfo['Height'] = $height;
    }

    // View thumbnail version of image
    $imginfo['ImgTagThumb'] = '<img src="'.$imginfo['URLThumb'].'"'.$titletext.$alttext.'>';

    // The <a href> link to the full size image displaying the full size image
    $imginfo['UrlTag'] = '<a href="'.$imginfo['URL'].'">'.$imginfo['ImgTag'].'</a>';

    // The <a href> link to the full size image but displaying the thumbnail image
    $imginfo['UrlTagThumb'] = '<a href="'.$imginfo['URL'].'">'.$imginfo['ImgTagThumb'].'</a>';

    return $imginfo;
}
// END function kgGetImageLink()
/////

function kgResizeDimensions($width,$height,$maxheight=800) {
    /**
     * Generates new dimensions for displaying an image while
     * maintaining the images aspect ratio (4:3, 16:9, etc)
     *
     * @param Required string $width Original width of the image
     * @param Required string $height Original height of the image
     * @param Optional string $maxheight Maximum height allowed for displaying the image
     *
     * @returns array array($newWidth Image display width,
     *                $newHeight Image display height)
    **/

   if ($width > $height && $height > $maxheight) {
       // The image is wider ($width) than it is tall ($height),
       // And it's taller than the max height.
       // EG. The image is 1152(w)x864(h) and $maxheight = 800
       $ratio = $height / $width;
       $newHeight = $maxheight;
       $newWidth = $newHeight / $ratio;
   } else if ($height > $width && $height > $maxheight) {
       // The image is taller than it is wide, and it's taller than the max size...
       $ratio = $width / $height;
       $newHeight = $maxheight;
       $newWidth = $newHeight * $ratio;
   } else if ($height > $maxheight) {
       // The image is as tall as it is wide (square), and it's taller than the max size...
       $newHeight = $maxheight;
       $newWidth = $maxheight;
   } else {
       // The image is not taller than the max size
       // No changes to size
       $newHeight = $height;
       $newWidth = $width;
   }

    $newHeight = ceil($newHeight);
    $newWidth = ceil($newWidth);

   return array($newWidth,$newHeight);
}
// END function kgResizeDimensions()
/////

?>
