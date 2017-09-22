<?php
/*
    Updated: 2015-04-15

    Usage/Calling methods:

        Method 1 (creates popupwindow):
        $form->add_button_generic('submit',get_lang('List'),'openwindow("contacts_industries","industrypl");');

        Method 2 (reuses current browser window):
        $form->buttononlyform(kgGetScriptNameListPopup().'?db=Contacts&listname=<listname>&field=<field>','post','frmexample',get_lang('Button Label'))
*/

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
if (!isset($IP)) {
    $IP = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
}

/**
 * Set the realative path for this website.
 *
 * Use this variable in include(_once) and require(_once)
 *
 * Leading forward slash has been removed to make this a relative path.
**/
if (!isset($RP)) {
    $RP = explode("/", $_SERVER['PHP_SELF']);
    array_pop($RP);
    $RP = substr(join("/", $RP), 1);
    if ($RP != '') {
        // If website is placed in subdirectory of $_SERVER['DOCUMENT_ROOT'],
        // make sure a trailing slash is included
        $RP = rtrim($RP, '/').'/';
    }

    // Prevent duplicating the /includes folder
    if (substr($RP, 0, strlen('includes')) == 'includes') {
        $RP = substr($RP, strlen('includes'));
    }
}

// This is a separate window so we need to call _StartHere.php so everything gets loaded
require $IP.'/includes/_StartHere.php';

function contacts_companies($field) {
    // List names of companies in the contacts database
    // ContactTypeID = 2

    global $dbc, $valid;

    $form = new HtmlForm();

    $query = $dbc->select('Contacts', array('ContactTypeID' => 2, 'OR', array('Field' => 'CompanyName', 'Operator' => '!=', 'Values' => '')), 'CompanyName');
    if ($dbc->numRows($query) > 0) {
        // At least one name found
        while ($row = $dbc->fetchAssoc($query)) {
            $list[$row['CompanyName']] = $row['CompanyName'];
        }
        // Make it easy for user to undo their selection
        $list[""] = get_lang('None');

        $form->onsubmit("changeparent('".$field."')");
        $form->start_form("",'post','frmselectcompany');
        $form->add_select_match_key($field,$list);
        $form->add_button_submit('Select');
        $form->end_form();
    } else {
        // No names found
        echo get_lang('NoCompanyNamesFound');
    }

}

function contacts_industries($field) {
    // List industries from the contacts database where Industry != ''

    global $dbc, $valid;

    $form = new HtmlForm();

    $query = $dbc->select('Contacts', 'Industry != ""', 'Industry');
    if ($dbc->numRows($query) > 0) {
        // At least one industry found
        while ($row = $dbc->fetchAssoc($query)) {
            $list[$row['Industry']] = $row['Industry'];
        }
        // Make it easy for user to undo their selection
        $list[""] = get_lang('None');

        $form->onsubmit("changeparent('".$field."')");
        $form->start_form("",'post','frmselectindustry');
        $form->add_select_match_key($field,$list);
        $form->add_button_submit('Select');
        $form->end_form();
    } else {
        // No industries found
        echo get_lang('NoIndustriesFound');
    }
}
// END function contacts_industries()
/////

function contacts_viewinfo($field) {

    global $dbc, $valid, $KG_SECURITY;

    $table = new HtmlTable();
    $grouptable = new HtmlTable();
    $table = new HtmlTable();

    if ($field > 0) {
        // Get data from database
        $contactdata = $dbc->fetchAssoc($dbc->select('Contacts','ContactID = '.$field));
    } else {
        // Invalid ID number
        echo get_lang('ContactID').' '.$contactid.' '.get_lang('NotFound');
    }

    // Javascript code for switching between layouts
    echo '<script type="text/javascript">

function switchContactType(type) {

    if (type == 1) {
        // Personal Contact
        document.getElementById("personalcontact").className = "";
        document.getElementById("businesscontact").className = "hidden";

    } else if (type == 2) {
        // Business Contact
        document.getElementById("personalcontact").className = "hidden";
        document.getElementById("businesscontact").className = "";

    }

}
</script>';

    // Contact layout tables
    echo '<div id="personalcontact" class="hidden">';

        echo '<!-- Begin Personal Layout -->';
        $table->new_table();

        // Last name
        $table->new_row();
        $table->set_width(140,'px');
        $table->new_cell();
        echo get_lang('LastName').':';
        $table->set_width(150,'px');
        $table->new_cell();
        echo $contactdata['LastName'];

        // Middle Initial
        $table->set_width(160,'px');
        $table->new_cell();
        echo get_lang('MiddleInitial').':';
        $table->set_width(150,'px');
        $table->new_cell();
        echo $contactdata['MiddleInitial'];

        // First Name
        $table->new_row();
        $table->new_cell();
        echo get_lang('FirstName').':';
        $table->new_cell();
        echo $contactdata['FirstName'];

        // Dear
        $table->new_cell();
        echo get_lang('Dear').':';
        $table->new_cell();
        echo $contactdata['Dear'];

        // Address Line 1
        $table->new_row();
        $table->new_cell();
        echo get_lang("AddressLine1").':';
        $table->new_cell();
        echo $contactdata['AddressLine1'];
        $table->blank_cell(2);

        // Only show Address Line 2 if we are adding or editing a contact or it has data
        if ((strlen($contactdata['AddressLine2']) > 0)) {
            $table->new_row();
            $table->new_cell();
            echo get_lang("AddressLine2").':';
            $table->new_cell();
            echo $contactdata['AddressLine2'];
            $table->blank_cell(2);
        }
        // City
        $table->new_row();
        $table->new_cell();
        echo get_lang("City").':';
        $table->new_cell();
        echo $contactdata['City'];

        // State Or Provice
        $table->new_cell();
        echo get_lang('StateOrProvince').':';
        $table->new_cell();
        echo $contactdata['StateOrProvince'];

        // Zipcode/Postalcode
        $table->new_row();
        $table->new_cell();
        echo get_lang("PostalCode").':';
        $table->new_cell();
        echo ($contactdata['PostalCode'] ? $contactdata['PostalCode'] : '');

        // Country
        $table->new_cell();
        echo get_lang("Country").':';
        $table->new_cell();
        echo $contactdata['Country'];

        /////
        // Phone Number
        // -Display first one found
        if ($contactdata['WorkPhone'] != '') {
            $table->new_row();
            $table->new_cell();
            // Work Phone
            echo get_lang("WorkPhone").':';
            $table->new_cell();
            list($tf,$pn) = kgFormatPhoneNumber($contactdata['WorkPhone'],0);
            echo ($pn != 'x' ? $pn : '').($contactdata['WorkExtension'] != '' ? ' x'.$contactdata['WorkExtension'] : '');
        } elseif ($contactdata['HomePhone'] != '') {
            $table->new_row();
            $table->new_cell();
            // Home Phone
            echo get_lang("HomePhone").':';
            $table->new_cell();
            list($tf,$pn) = kgFormatPhoneNumber($contactdata['HomePhone'],0);
            echo ($pn != 'x' ? $pn : '');
        } elseif ($contactdata['CellPhone'] != '') {
            $table->new_row();
            $table->new_cell();
            // Cell Phone
            echo get_lang("CellPhone").':';
            $table->new_cell();
            list($tf,$pn) = kgFormatPhoneNumber($contactdata['CellPhone'],0);
            echo ($pn != 'x' ? $pn : '');
        }

        // Email Address
        if ($contactdata['EmailAddress'] != '') {
            $table->new_row();
            $table->new_cell();
            echo get_lang("EmailAddress").':';
            $table->new_cell();
            echo $contactdata['EmailAddress'];
            $table->blank_cell(2);
        }

        $table->end_table();

        echo '<!-- End Personal Layout -->';
    echo '</div><div id="businesscontact" class="hidden">';

        echo '<!-- Begin Business Layout -->';

        $table->set_width(100,'%');
        $table->new_table();

        // Business name
        $table->new_row();
        $table->set_width(170,'px');
        $table->new_cell();
        echo get_lang('BusinessName').':';
        $table->set_width(295,'px');
        $table->new_cell();
        echo $contactdata['CompanyName'];

        // Industry
        $table->set_width(185,'px');
        $table->new_cell();
        echo get_lang('Industry').':';
        $table->set_width(295,'px');
        $table->new_cell();
        echo $contactdata['Industry'];

            // Address Line 1
            $table->new_row();
            $table->new_cell();
            echo get_lang("AddressLine1").':';
            $table->new_cell();
            echo $contactdata['AddressLine1'];
            $table->blank_cell(2);

            // Only show Address Line 2 if we are adding or editing a contact or it has data
            if ((strlen($contactdata['AddressLine2']) > 0)) {
                $table->new_row();
                $table->new_cell();
                echo get_lang("AddressLine2").':';
                $table->new_cell();
                echo $contactdata['AddressLine2'];
                $table->blank_cell(2);
            }
            // City
            $table->new_row();
            $table->new_cell();
            echo get_lang("City").':';
            $table->new_cell();
            echo $contactdata['City'];

            // State Or Provice
            $table->new_cell();
            echo get_lang('StateOrProvince').':';
            $table->new_cell();
            echo $contactdata['StateOrProvince'];

            // Zipcode/Postalcode
            $table->new_row();
            $table->new_cell();
            echo get_lang("PostalCode").':';
            $table->new_cell();
            echo ($contactdata['PostalCode'] ? $contactdata['PostalCode'] : '');

            // Country
            $table->new_cell();
            echo get_lang("Country").':';
            $table->new_cell();
            echo $contactdata['Country'];

            /////
            // Phone Number
            // -Display first one found
            if ($contactdata['WorkPhone'] != '') {
                $table->new_row();
                $table->new_cell();
                // Office Phone
                echo get_lang("OfficePhone").':';
                $table->new_cell();
                list($tf,$pn) = kgFormatPhoneNumber($contactdata['WorkPhone'],0);
                echo ($pn != 'x' ? $pn : '');
            } elseif ($contactdata['FaxNumber'] != '') {
                $table->new_row();
                $table->new_cell();
                // Fax Number
                echo get_lang("FaxNumber").':';
                $table->new_cell();
                list($tf,$pn) = kgFormatPhoneNumber($contactdata['FaxNumber'],0);
                echo ($pn != 'x' ? $pn : '');
            }

        // Website
        $table->new_row();
        $table->new_cell();
        echo get_lang('WebsiteLink').':';
        echo '<a href="'.$contactdata['Website'].'" target="_blank">'.$contactdata['Website'].'</a>';
        $table->blank_cell(2);

        // Email Address
        $table->new_row();
        $table->new_cell();
        echo get_lang("EmailAddress").':';
        $table->new_cell();
        echo $contactdata['EmailAddress'];
        $table->blank_cell(2);

        $table->end_table();

        echo '<!-- End Business Layout -->';
    echo '</div></div>';

    $table->end_table();

    // Display the correct contact info
    echo '<script type="text/javascript">switchContactType('.$contactdata['ContactTypeID'].');</script>';

}
// END function contacts_viewinfo()
/////

function javascript() {
    // Put all javascript & jquery code in this php function so it doesn't get duplicated
    echo '<script language="javascript">
    function changeparent(fid) {

        var wod = window.opener.document.getElementById(fid);

        // Different field types use different methods of updating
        if (wod.type == "select-one" || wod.type == "select-multiple") {
            // Select lists
            // Adds item to bottom of list and selects it
            var option = document.createElement("option");
            option.text = document.getElementById(fid).value;
            wod.add(option);
            wod.selectedIndex = wod.options.length-1;
        } else {
            wod.value=document.getElementById(fid).value;
        }
    }
    </script>';
}

function EndPage() {

    $form = new HtmlForm();

    echo '<br/><br/>';
    $form->onsubmit('javascript:self.close();');
    $form->buttononlyform("",'post','frmselfclose',get_lang('CloseWindow'));

    require $IP.'/includes/_PageBottom.php';
}

require $IP.'/includes/_PageTop.php';

$form = new HtmlForm();

$db = $valid->get_value('db');
$listname = $valid->get_value('listname');
$field = $valid->get_value('field');
$KG_MODULE_NAME = $valid->get_value('kg_module');

// Database name required
if ($db == '') {
    echo get_lang('DBNameRequired');

    EndPage();

    exit;
}

$dbc->connectDatabase($db);

if ($KG_MODULE_NAME != '' && $KG_MODULE_NAME != 'index') {
    // Load the modules info.php file
    // Only thing needed here is the langauge array
    $module_info_file = $IP.'/modules/'.$KG_MODULE_NAME.'/'.$KG_MODULE_NAME.'.info.php';
    if (is_file($module_info_file)) {
        require $module_info_file;

        if (array_key_exists($userinfo['Language'], $moduledata['Lang']) === true && is_array($moduledata['Lang'][$userinfo['Language']]) === true) {
            // Add the language array from the info.php file for the users chosen language
            $lang[$KG_MODULE_NAME] = array();
            $lang[$KG_MODULE_NAME] = array_merge($lang[$KG_MODULE_NAME], $moduledata['Lang'][$userinfo['Language']]);
        }
    }
}

if ($listname == 'contacts_companies') {
    // List names of companies in the contacts database

    javascript();

    contacts_companies($field);

} elseif ($listname == 'contacts_industries') {
    // List industries from the contacts database

    javascript();

    contacts_industries($field);

} elseif ($listname == 'contacts_viewinfo') {
    // View info about a specific contact

    javascript();

    contacts_viewinfo($field);

} else {
    // $listname not given
    echo get_lang('InvalidListName');

}
// END if ($listname == 'contacts_companies')

EndPage();
?>
