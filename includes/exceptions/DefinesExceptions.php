<?php
/**
 * Global defines file used by /includes/exceptions/*
 *
 * Added: 2015-05-14 by Nathan Weiler (ncweiler2@hotmail.com)
 * Updated: 2015-05-19 by Nathan Weiler (ncweiler2@hotmail.com)
 *
 * This file should be loaded by 'includes/_StartHere.php' before
 * starting the autoloader
 *
**/

if (!defined('KRAVEN')) {
	die('This file is part of Kraven. It is not a valid entry point.');
}

// Database errors
define('DB_DATABASE_MISSING', 100);
define('DB_BAD_QUERY', 101);
define('DB_MISSING_MYSQLI', 102);
define('DB_NO_SRVR_CONNECTION', 103);
?>
