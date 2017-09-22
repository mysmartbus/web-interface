<?php
/**
 * This file is used to configure the connection to the database server and sets
 * the name of the main database.
 *
 * This file does not need to be edited unless you are having trouble connecting
 * to the database
**/

//Database server connection settings
$kgDatabase['type'] = 'mysqli';
$kgDatabase['user'] = 'kraven';
$kgDatabase['passwd'] = 'vbit120434';
$kgDatabase['database'] = 'Kraven';
$kgDatabase['server'] = 'kraven'; // Comes from mysqlnd_ms_plugin.ini

//Do not change strict_mode, unless you know what you are doing
$kgDatabase['strict_mode'] = -1;

/**
 * Enable debug mode for the database interface.
 *
 * Logs the serialized version of the incoming data for the functions
 * listed except query(). Query() gets logged as plain text.
 *
 * Valid values are:
 *    Enabled: 1, 2 or 3
 *    Disabled: Any other value
 *
 * Debug levels are:
 *     1 - Adds 1 new message to the SystemMessages database per query.
 *         Functions:
 *             query()
 *     2 - Adds 2 new messages to the SystemMessages database per query.
 *         Functions:
 *             query()
 *             selectSQLText()
 *     3 - Adds 9 new messages to the SystemMessages database per query.
 *         Functions:
 *             query()
 *             selectSQLText()
 *             buildBetween()
 *             buildConcatWS()
 *             buildMatchAgainst()
 *             makeGroupByWithHaving()
 *             makeOrderBy()
 *             makeSelectOptions()
 *             makeWhereList()
**/
$kgDatabase['Debug'] = false;
?>
