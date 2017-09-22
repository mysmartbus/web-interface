<?php
// Last Updated: 2017-05-11
require '../includes/_StartHere.php';

$dbc = new genesis();
$db = $dbc->connectServer();
$dbc->connectDatabase('Cookbook', 0.23);

$action = $valid->get_value('ajaxacton');
$format = $valid->get_value('format');

if ($action == "getgroups") {
    echo getgroups($format);
} elseif ($action == "addgroup") {
    $name = $valid->get_value('newgroup');

    // A group name can only contain a-z, A-Z, 0-9 and spaces
    $name = preg_replace("/[^a-zA-Z0-9 ]/", '', $name);

    if ($name != '') {
        // Convert name to Title Case
        $name = ucwords(strtolower($name));

        // Save to database
        $dbc->insert('Groups',array('Name' => $name));
    }
    echo getgroups($format);
} elseif ($action == 'dupnamecheck') {
    // Checks if the recipe name is already in use
    $newname = $valid->get_value('newname');

    $query = $dbc->select('Recipes','Name = "'.$newname.'"');

    if ($dbc->numRows($query) > 0) {
        // Name already in use
        echo '1';
    } else {
        echo '0';
    }
}

function getgroups($format) {
    // Creates a <select></select> list of groups currently in the database
    global $dbc;
    $clean = array();

    $query = $dbc->select('Groups','','', array('ORDER BY' => 'Name ASC'));

    if ($format == '') {
        $select = '<select name="group[]" id="group" class="someclass">';
    }
    while ($row = $dbc->fetchAssoc($query)) {
        if ($format == '') {
            $select .= '<option value="'.$row['GroupID'].'"'.($row['Name'] == 'None' ? ' selected' : '').'>'.$row['Name'].'</option>';
        } else {
            $clean[] = array($row['GroupID'],$row['Name']);
        }
    }
    if ($format == '') {
        // Returns the formatted <select></select> list
        $select .= '</select>';
        return $select;
    } else {
        // Returns an array of the data to be processed later
        return $clean;
    }
}
?>
