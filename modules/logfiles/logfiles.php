<?php
// Last Updated: 2017-08-13
$dbc = new genesis();
$db = $dbc->connectServer();
$dbc->connectDatabase('apache_logs', 0.02);

function filterlogs() {

    global $dbc;

    $query = $dbc->select('accesslogs');

    while ($row = $dbc->fetchAssoc($query)) {
        echo '<pre>';
        print_r($row);
        echo '</pre>';
    }
}
// END function filterlogs()
/////

if ($dbc->isConnectedDB() === true) {

    filterlogs();
} else {

    echo 'oops';

} // END if ($dbc->isConnectedDB() === true)
?>
