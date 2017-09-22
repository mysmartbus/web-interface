<?php
// Added: 2015-1?-??
// Modified: 2017-08-07
require '../../includes/_StartHere.php';

define('FAILED','~:~');

// Add the language array from the info.php file for the users chosen language
require $IP.'/modules/calendar/calendar.info.php';
$modlang = $moduledata['Lang'][$userinfo['Language']];

$action = $valid->get_value('ajaxacton');
if ($action == "addnewlocation") {
    // Add a new location to the database

    $contacttype = $valid->get_value("ContactTypeID", 0);
    $contactid = kgFindIdNumber('contacts','ContactID');
    $insertarray = array();

    if ($contacttype == 1) {
        // Personal Contact/Location

        $insertarray = array(
            'ContactID' => $contactid,
            'ContactTypeID' => $contacttype,
            'FirstName' => $valid->get_value('FirstName'),
            'LastName' => $valid->get_value('LastName'),
            'CellPhone' => kgFormatPhoneNumber($valid->get_value('CellPhone'),true),
            'WorkPhone' => kgFormatPhoneNumber($valid->get_value('WorkPhone'),true),
            'EmailAddress' => $valid->get_value('EmailAddress')
        );
    } elseif ($contacttype == 2) {
        // Business Contact/Location

        $insertarray = array(
            'ContactID' => $contactid,
            'ContactTypeID' => $contacttype,
            'CompanyName' => $valid->get_value('CompanyName'),
            'WorkPhone' => kgFormatPhoneNumber($valid->get_value('WorkPhone'),true),
            'FaxNumber' => kgFormatPhoneNumber($valid->get_value('FaxNumber'),true),
            'EmailAddress' => $valid->get_value('EmailAddress')
        );
    }

    // Only attempt an SQL insert if there is something to insert into the database
    if (empty($insertarray) === false) {

        // Connect to the 'contacts' database
        // Should I be using $KG_DBC here? (2017-05-11)
        $KG_DBC->connectDatabase('contacts', 0.25);

        if ($KG_DBC->insert('contacts', $insertarray)) {
            // Insert succeeded
            echo $contactid;
        } else {
            // Insert failed
            echo FAILED;
        }
    }

} elseif ($action == "geneventrow") {
    // Generate a new event row
    // These rows allow the user to specify the start time, end time and add a reminder
    $day = $valid->get_value_numeric("eventreminderday");

    if ($day < 1) {
        // Day numbers start at 1
        echo FAILED;
    } else {
        $starttime = $valid->get_value("starttime");
        $endtime = $valid->get_value("endtime");

        if ($starttime == '') {
            $starttime = $modlang['clicktosettime'];
        }

        if ($endtime == '') {
            $endtime = $modlang['clicktosettime'];
        }

        $form = new HtmlForm();

        $form->echo_off();
        $form->escape_on();

        $form->set_onblur('validate()');
        $rv = '"<td class=\"dttcol2\">'.$form->add_text('starttime['.$day.']', $starttime, 5, 'time start').'</td>';

        $form->set_onblur('validate()');
        $rv .= '<td class=\"dttcol3\">'.$form->add_text('endtime['.$day.']', $endtime, 5, 'time end').'</a></td>';

        $rv .= '<td class=\"centertext dttcol4\">'.$form->add_button_generic('removedate', $modlang['remove'], 'removeDateRow('.$day.')').'</td></tr>';

        $rv .= '<script>';
        $rv .= '$(\"#daterow'.$day.' .time\").timepicker({ \"showDuration\": true, \"step\": 15});';
        $rv .= '$(\"#daterow'.$day.'\").datepair({\"defaultTimeDelta\": 7200000}';
        $rv .= ');</script>"';

        $form->echo_on();
        $form->escape_off();

        echo $rv;
    }
}

/*
2017-08-07: No longer used
} elseif ($action == 'genreminderrow') {
    // Generate a reminder row
    // These rows allow the user to schedule an event reminder

    $day = $valid->get_value_numeric('day', 0);
    $eventdate = $valid->get_value('eventdate');  // Formatted as Y-m-d (PHP)
    $starttime = $valid->get_value('starttime');
    $reminderdate = $valid->get_value('reminderdate');
    $remindertime = $valid->get_value('remindertime');
    

    if ($day < 1 || $starttime == '' || $eventdate == '') {
        // Day numbers start at 1
        // Event start time required
        // Event date required
        echo FAILED;
    } else {
        $form = new HtmlForm();

        $form->echo_off();
        $form->escape_on();

        $rv = '"<td class=\"rtcol2\">'.$starttime.'</td>';
        $form->set_id('reminderdate'.$day);
        $form->set_onblur('validate()');
        $rv .= '<td class=\"rtcol3\">'.$form->add_text('reminderdate['.$day.']', $reminderdate, 10).'</td>';
        $form->set_id('remindertime'.$day);
        $form->set_onblur('validate()');
        $rv .= '<td class=\"rtcol4\">'.$form->add_text('remindertime['.$day.']', $remindertime, 5).'</td></tr>';
        $rv .= '<script>$(function() {';
        $rv .= '$(\"#reminderdate'.$day.'\").datepicker({dateFormat: \"yy-mm-dd\",  changeMonth: true, changeYear: true});';
        $rv .= '$(\"#remindertime'.$day.'\").timepicker({ \"step\": 15 });';    // 15 minute increments
        $rv .= ' });</script>"';

        $form->echo_on();
        $form->escape_off();

        echo $rv;
    }
*/
?>
