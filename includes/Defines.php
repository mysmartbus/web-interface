<?php
/**
 * Global defines file
 *
 * Added 2017-06-12
 * Updated 2017-06-12
**/

if (!defined('KRAVEN')) {
	die('This file is part of Kraven. It is not a valid entry point.');
}

// Identifies $_POST/$_GET variables as having been created by Kraven
// KG stands for Kraven Global
define('PARAM_PREFIX','KG_');

// ID number for the guest group
// All other group IDs must be set in the database
// Do not set them here. 
define('GRP_GUEST',0);

// Invalid/unexpected data
define('INVALID_DATA',-1);

// Height in pixels for picture thumbnails
define('THUMBNAIL_HEIGHT_SMALL',64);
define('THUMBNAIL_HEIGHT_MEDIUM',128);
define('THUMBNAIL_HEIGHT_LARGE',192);

// Maximum height to display any picture on any page
// EXCEPT when displaying the image at its full size
define('THUMBNAIL_HEIGHT_MAX',640);

// Database connection error messages
define('DATABASE_CONNECT_ERROR', "<strong>Could not connect to database server<br/>Please check the database settings and the database server.</strong>");
define('DATABASE_SELECT_ERROR', "<strong>Could not connect to the requested database.<br/>Please make sure that make sure you have permission to connect to the requested database.</strong>");

// File Uploads
define('KG_UPLOAD_SUCCESS', 100);
define('KG_UPLOAD_FILE_EXISTS', 101);
define('KG_UPLOAD_FAILED', 102);
define('KG_UPLOAD_FAILED_DB_UPDATE', 103);
define('KG_UPLOAD_FAILED_DB_INSERT', 104);
?>
