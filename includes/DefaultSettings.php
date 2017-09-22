<?php
/**
 * Default values for configuration of Kraven's website.
 *
 * Added: 2017-06-07 by Nathan Weiler (ncweiler2@hotmail.com)
 * Updated: 2017-06-09 by Nathan Weiler (ncweiler2@hotmail.com)
 *
**/

if (!defined('KRAVEN')) {
	die('This file is part of Kraven. It is not a valid entry point.');
}

/**
 * The default language to use during startup.
 *
 * This is the language that will be used if the one selected by the user
 * is not available and/or the database is not available.
 *
 * The value in the database will override the value set here.
 *
 * Must be one of the ISO 639-1 two-letter codes from http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
 *
 * Default language is English (en)
 *
 * Added: 2017-06-07
 * Modified: 2017-06-07
**/
$kgDefaultLanguage = 'en';

/**
 * jQuery theme to use.
 *
 * Added: 2017-06-19
 * Modified: 2017-06-19
**/
$kgJqueryTheme = 'ui-darkness';

/**
 * 
**/
$kgLeanModalJS = $RP.'includes/js/jquery.leanModal.min.v1.1.js';

/**
 * This is where files and images will be placed.
 *
 * Do not include a leading or trailing forward slash.
 *
 * Added: 2017-06-09
 * Modified: 2017-06-09
**/
$kgSiteFiles = $RP.'files';

/**
 * This is were the auto-generated thumbnail versions of images will be placed.
 *
 * For easy backups, it is recommended to keep this as a subfolder of $kgSiteFiles.
 *
 * Do not include a leading or trailing forward slash.
 *
 * Added: 2017-06-09
 * Modified: 2017-06-09
**/
$kgSiteThumbnails = $kgSiteFiles.'/thumb';

/**
 * The location for the lightbox css and javascript files
 *
 * Added: 2017-06-15
 * Modified: 2017-06-15
**/
$kgSiteLightBoxCss = $RP.'skins/css/lightbox.css';
$kgSiteLightBoxJava = $RP.'includes/js/lightbox.js';

/**
 ***************************************
 * Exception and error handling settings
**/

/**
 * Set to true to include the SQL statement that caused the exception.
 *
 * This should only used for debugging purposes as it may include
 * usernames, passwords and other information useful to an attacker.
 *
 * Default setting is false
 *
 * Added: 2017-06-07
 * Modified: 2017-06-07
**/
$kgShowSqlInException = true;

/**
 * Set to true to show the backtrace in the output from the exception.
 *
 * This should only used for debugging purposes as it will not make sense to
 * most users and could include information useful to an attacker.
 *
 * Example backtrace:
 *   #0 /home/nathan/public_html/main/includes/_StartHere.php(125): genesis->fetchAssoc(boolean)
 *   #1 /home/nathan/public_html/main/exceptiontesting.php(2): require(string)
 *   #2 {main}
 *
 * Added: 2017-06-07
 * Modified: 2017-06-07
**/
$kgShowBackTraceInException = true;
?>
