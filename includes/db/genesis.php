<?php
/**
 * This file loads the database connection class
 *
 * To add support for a database type:
 * 1. Copy the following three lines and past them above the "default:" line.
    case '<type>':
        require 'genesis_<type>.php';
        break;
 * 2. Replace the <type> marker with the database type.
 * 3. Copy one of the existing genesis_<type>.php files and edit the
 *    database specific statements/commands inside the functions.
 *    DO NOT RENAME THE FUNCTIONS.
**/

switch ($kgDatabase['type']) {
    case 'couchdb':
        echo 'On the TODO list. Support not available yet.';
        exit();
    case 'mysqli':
        require 'mysqli.php';
        break;
    default:
        echo 'Unknown database type set: '.$kgDatabase['type'];
        exit();
}
?>
