<?php

/**
 * Set/load globally available variables and functions
**/

// Make IE8 turn off content sniffing. Everybody else should ignore this.
// See: https://blogs.msdn.microsoft.com/ie/2008/09/02/ie8-security-part-vi-beta-2-update/
header( 'X-Content-Type-Options: nosniff' );

/**
 * A simple security measure
 *
 * If not set, the files in /includes will not work
**/
define('KRAVEN', True);

/**
 * Set the install path (a.k.a root directory) for this website.
 *
 * SECURITY: This is the full/absolute path to the root directory for this website.
 *
 * Assigning $_SERVER['DOCUMENT_ROOT'] to a variable allows me to change
 * how the document root is determined without having to edit half the files
 * that make up this website.
 *
 * Use this variable for PHP functions that require the full path (is_file, is_dir).
 *
 * The trailing forward slash is removed due to personal preferences.
**/
$IP = rtrim($_SERVER['DOCUMENT_ROOT'], '/');

/**
 * Set the realative path for this website.
 *
 * Use this variable in include(_once) and require(_once)
 *
 * Leading forward slash has been removed to make this a relative path.
**/
$RP = explode("/", $_SERVER['PHP_SELF']);
array_pop($RP);
$RP = substr(join("/", $RP), 1);
if ($RP != '') {
    // If website is placed in subdirectory of $_SERVER['DOCUMENT_ROOT'],
    // make sure a trailing slash is included
    $RP = rtrim($RP, '/').'/';
}

// Start a PHP session
session_start();

/**
 * Load the autoloader class
 *
 * This eliminates the need to know the location of a class file.
 *
 * Instead of doing this in every file:
 *      require $RP.'path/to/class.file.php';
 *      $class = new class();
 *
 * You only need to do this:
 *      $class = new class();
**/
require $IP.'/includes/AutoLoader.php';

// Global constants used throughout the site
require $IP.'/includes/Defines.php';

// Load the session handler class
$KG_SESSION = new Sessions();

// Load the global functions
require $IP.'/includes/GlobalFunctions.php';
require $IP.'/includes/GlobalFunctions_FilesDirs.php';
require $IP.'/includes/GlobalFunctions_Images.php';

// Load the default settings file.
require $IP.'/includes/DefaultSettings.php';

// Load the language functions
$lang_dir = $IP.'/includes/lang';
require_once $lang_dir.'/get_lang.php';

// Load the default global language file
if (is_file($lang_dir.'/'.$kgDefaultLanguage.'.php')) {
    require_once $lang_dir.'/'.$kgDefaultLanguage.'.php';
} else {
    $path = str_replace($IP, '', $lang_dir);
    die('Default language file not found: '.$path.'/<b>'.$kgDefaultLanguage.'</b>.php');
}

// Load the database interface
require $IP.'/includes/db/_config.php';
require $IP.'/includes/db/genesis.php';

// This database connection is used by the class files (cl_*)
// and other files in $RP.'/includes'
//
// DO NOT use this connection in your modules.
// All modules must establish their own connection to the database server
$KG_DBC = new genesis();
$db = $KG_DBC->connectServer();
$rv = $KG_DBC->connectDatabase('Kraven', 0.66);
if ($rv === false) {
    die('Unable to connect to database');
}

// Set database strict mode
if($kgDatabase['strict_mode'] == -1) {
    //Turn off MySQL 5 strict mode
	$KG_DBC->query("SET sql_mode = ''");
} elseif ($kgDatabase['strict_mode'] == 1) {
    //Turn on MySQL 5 strict mode
	$KG_DBC->query("SET sql_mode = 'TRADITIONAL'");
}

// Get default values to use for items the user has not yet customized
$defaultvalues = $KG_DBC->fetchAssoc($KG_DBC->select('DefaultValues'));
if (!is_array($defaultvalues)) {
    die ('Unable to load default values');
}

// Fill array with defaults.
// User is considered a guest until they login.
$userinfo = array(
    'GroupID' => GRP_GUEST,
    'UserID' => 0
);

// Get user info if they are logged in
if ($KG_SESSION->sessionStarted() === true) {

    // Limiting the fields to get info from for security
    $fields = array(
        'UserID',
        'UserName',
        'GroupID',
        'Language',
        'Skin',
        'DateFormat',
        'DefaultModule',
        'Disabled'
    );

    // Attempt data retrieval
    $temp = $KG_DBC->fetchAssoc($KG_DBC->select('Users', 'SesID = "'.$KG_SESSION->getVar('sessionid').'" AND UserID = '.$KG_SESSION->getVar('userid'), $fields));

    if (is_array($temp)) {
        // Retreival succedded
        $userinfo = $temp;
    }

    unset($temp);
}

/**
 * Add checks here to see if the account is disabled (2017-06-24)
**/

// Set default values for items ($key) if they are not already set ($value)
foreach ($defaultvalues as $key => $value) {
    if (!array_key_exists($key, $userinfo)) {
        $userinfo[$key] = $value;
    }

    // Checking for blank values
    if (array_key_exists($key, $userinfo) && $userinfo[$key] == '') {
        $userinfo[$key] = $value;
    }
}

// Load the color scheme
$kgSkin = $RP.'skins/'.$userinfo['Skin'];
require $IP.'/skins/'.$userinfo['Skin'].'/colors.php';

// The security class
$KG_SECURITY = new Security();

// Initialize the validation class
$valid = new Validate();

// Place holder until I logins/users working again
$user_language = 'en';
?>
