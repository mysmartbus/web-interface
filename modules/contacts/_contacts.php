<?php
/**
 * Pulled these functions out of the contacts.php module file to make it easier
 * to find and edit stuff.
 *
 * Created 2014-??-?? by Nathan Weiler (ncweiler2@hotmail.com)
 * Updated 2017-07-19 by Nathan Weiler (ncweiler2@hotmail.com)
**/

$dbc = new genesis();
$db = $dbc->connectServer();
$dbc->connectDatabase('contacts', 0.25);

// maritalstatusid constants
// These need to match what is in the database
define('UNKNOWN', 1);
define('SINGLE', 2);
define('MARRIED', 3);
define('WIDOWED', 4);
define('DIVORCED', 5);
define('SINGLEDIVORCED', 6);

function GenMapUrl($contactdata) {
    /**
     * Generate a Google maps URL
     *
     * NOTE: Currently on handle USA address'.
     *
     * Added: 2017-07-23
     * Modified: 2017-07-24
     *
     * @param Required array $contactdata Contact data used to generate the URL
     * @param Optional boolean $showdiv   True: Show the map button div (default)
     *                                    False: Only generate the URL
     *
     * @return nothing|string
    **/

    if ((strtolower($contactdata['country']) != 'us') && (strtolower($contactdata['country']) != 'usa')) {
        // Country not set so check if the state is

        $states = array(
            'AL', 'AK', 'AZ', 'AR', 'CA',
            'CO', 'CT', 'DE', 'FL', 'GA',
            'HI', 'ID', 'IL', 'IN', 'IA',
            'KS', 'KY', 'LA', 'ME', 'MD',
            'MA', 'MI', 'MN', 'MS', 'MO',
            'MT', 'NE', 'NV', 'NH', 'NJ',
            'NM', 'NY', 'NC', 'ND', 'OH',
            'OK', 'OR', 'PA', 'RI', 'SC',
            'SD', 'TN', 'TX', 'UT', 'VT',
            'VA', 'WA', 'WV', 'WI', 'WY'
        );
        if (!in_array(strtoupper($contactdata['stateorprovince']), $states)) {
            // State and country not valid. Unable to generate link to map
            echo '<!-- <div class="vc_map_url_div">function GenMapUrl() does not work with address outside the United States</div> -->';
            return;
        }
    }

    global $kgSkin;

    if ($contactdata['addressline1'] != '') {
        $link = $contactdata['addressline1'];
    }
    if ($contactdata['city'] != '') {
        if ($link != '') {
            $link .= ', ';
        }
        $link .= $contactdata['city'];
    }
    if ($contactdata['stateorprovince'] != '') {
        if ($link != '') {
            $link .= ', ';
        }
        $link .= $contactdata['stateorprovince'];
    }
    if ($contactdata['postalcode'] != '') {
        if ($link != '') {
            $link .= ' ';
        }
        $link .= $contactdata['postalcode'];
    }
    if ($contactdata['country'] != '') {
        if ($link != '') {
            $link .= ' ';
        }
        $link .= $contactdata['country'];
    }

    if (($link != '') && (strlen($link) > 10)) {
        // Display or return the link if there is something to link to and
        // the length of the link is greater than 10 characters
        echo '<div class="vc_map_url_div"><a href="http://maps.google.com/maps?q='.$link.'" title="'.get_lang('clicktoviewmap').'" target="_blank">'.get_lang('viewmap').'</a></div>';
    }

}
// END GenMapUrl()
/////

function MailingAddress($dowhat, $contactdata, $type) {
    // Used by AeBusinessLayout() and AePersonalLayout()
    // The mailing address is the only part of the layout that is the same between both layouts

    $table = new HtmlTable();
    $form = new HtmlForm();

    echo '<div class="ae_mailing_address_div">';

    echo '<div class="page_title_div">'.get_lang('MailingAddress').'</div>';

    // Address Line 1
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("addressline1").':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['addressline1'];
    } else {
        $text = '';
    }
    $form->add_text($type.'addressline1',$text,25);
    echo '</span></div>';

    // Address Line 2
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("addressline2").':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['addressline2'];
    } else {
        $text = '';
    }
    $form->add_text($type.'addressline2',$text,25);
    echo '</span></div>';

    // City
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("city").':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['city'];
    } else {
        $text = '';
    }
    $form->add_text($type.'city',$text,25);
    echo '</span></div>';

    // State Or Provice
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang('stateorprovince').':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['stateorprovince'];
    } else {
        $text = '';
    }
    $form->add_text($type.'stateorprovince',$text,25);
    echo '</span></div>';

    // Zipcode/Postalcode
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("postalcode").':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['postalcode'];
    } else {
        $text = '';
    }
    $form->add_text($type.'postalcode',$text);
    echo '</span></div>';    

/*
    // 2017-07-15:
    //      Will probably move this to the business layout but haven't decided yet.
    //      Thinking this would be used to specify a sales or marketing region for a business
    //      instead of part of the mailing address.
    // region
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("region").':</span>';
    echo '<span class="fieldvalue_span">';
    $form->add_text($type.'region',$contactdata['region'],30);
    echo '</span></div>';
*/

    // Country
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("country").':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['country'];
    } else {
        $text = '';
    }
    $form->add_text($type.'country',$text,25);
    echo '</span></div>';

    echo "\n</div><!-- END ae_mailing_address_div -->\n";
}
// END function MailingAddress()
/////

function NamesInnotes($note) {
    /**
     * Creates links to contacts found in the notes
     *
     * Added: 2017-07-19
     * Modified: 2017-07-19
     *
     * @param Required string $note The string to search in
     *
     * @return string
    **/

    global $dbc;

    // Find the tagged names
    /* $matches = Array(
        [0] => Array(
            [0] => <contact><dear>Mr.</dear> <lastname>Stark</lastname></contact>
            [1] => <contact>
            [2] => contact
            [3] => <dear>Mr.</dear> <lastname>Stark</lastname>
            [4] => </contact>
        ),
        [n] => ...
     */
    preg_match_all("/(<([\w]+)[^>]*>)(.*?)(<\/\\2>)/", $note, $matches, PREG_SET_ORDER);

    foreach ($matches as $key => $value) {
        // Run again to pull out the relevant data
        /* $nextset = Array(
            [0] => Array(
                [0] => <dear>Mr.</dear>
                [1] => <dear>
                [2] => dear
                [3] => Mr.
                [4] => </dear>
            ),
            [1] => Array(
                [0] => <lastname>Stark</lastname>
                [1] => <lastname>
                [2] => lastname
                [3] => Stark
                [4] => </lastname>
            )
        )
        */
        preg_match_all("/(<([\w]+)[^>]*>)(.*?)(<\/\\2>)/", $value[3], $nextset, PREG_SET_ORDER);

        if (count($nextset) == 2) {
            // Personal contact
            $query = $dbc->query('select contactid from contacts where '.$nextset[0][2].' = "'.$nextset[0][3].'" and '.$nextset[1][2].' = "'.$nextset[1][3].'" and contacttypeid = '.PERSONAL);
            if ($dbc->numRows($query) == 1) {
                $contactid = $dbc->fieldValue($query);
                $link = kgCreateLink($nextset[0][3].' '.$nextset[1][3], array('ACTON' => 'viewcontact', 'contactid' => $contactid));
                $note = str_replace($value[0], $link, $note);
            }
        } elseif (count($nextset) == 1) {
            // Business contact
            $query = $dbc->query('select contactid from contacts where companyname = "'.$nextset[0][3].'" and contacttypeid = '.BUSINESS);
            if ($dbc->numRows($query) == 1) {
                $contactid = $dbc->fieldValue($query);
                $link = kgCreateLink($nextset[0][3], array('ACTON' => 'viewcontact', 'contactid' => $contactid));
                $note = str_replace($value[0], $link, $note);
            }
        }
    }
    // END foreach ($matches as $key => $value)

    // Remove the tags.
    // More of a just incase check since all of the tags
    // should have been removed when generating the links.
    $tags = array(
        '<contact>',
        '</contact>',
        '<dear>',
        '</dear>',
        '<lastname>',
        '</lastname>',
        '<firstname>',
        '</firstname>',
        '<companyname>',
        '</companyname>'
    );
    $note = str_replace($tags, '', $note);

    return $note;
}
// END function NamesInnotes()
/////

function ChildrensNamesLinks($names, $lastname) {
    /**
     * Creates links to contacts found in childrens name list
     *
     * Added: 2017-07-21
     * Modified: 2017-07-21
     *
     * @param Required string $names A comma seperated list of names to parse
     * @param Required string $lastname Last name of currently displayed contact
     *
     * @return string
    **/

    global $dbc;

    $list = explode(',', $names);

    foreach ($list as $key => $child) {
        $child = trim($child);
        $query = $dbc->query('select contactid from contacts where firstname = "'.$child.'" and lastname = "'.$lastname.'" and contacttypeid = '.PERSONAL);
        if ($dbc->numRows($query) > 0) {
            $contactid = $dbc->fieldValue($query);
            $link = kgCreateLink($child, array('ACTON' => 'viewcontact', 'contactid' => $contactid));
            $names = str_replace($child, $link, $names);
        }
    }

    return $names;
}
// END function ChildrensNamesLinks()
/////

function ReferredByToLink($name) {
    /**
     * Creates a link to the contact in $name if contact is in database.
     * Name given is assumed to be a person, not a business.
     *
     * Added: 2017-07-21
     * Modified: 2017-07-21
     *
     * @param Required string $names Name of person to link to
     *
     * @return string
    **/

    global $dbc;

    $splits = explode(' ', $name);

    $query = $dbc->query('select contactid from contacts where firstname = "'.$splits[0].'" and lastname = "'.$splits[1].'" and contacttypeid = '.PERSONAL);
    if ($dbc->numRows($query) > 0) {
        $name = kgCreateLink($name, array('ACTON' => 'viewcontact', 'contactid' => $contactid));
    }

    return $name;
}
// END function ReferredByToLink()
/////

function AeBusinessLayout($dowhat, $contactdata) {

    global $dbc;

    echo '<!-- Begin Business Layout -->';

    $table = new HtmlTable();
    $grouptable = new HtmlTable();
    $innertables = new HtmlTable();
    $form = new HtmlForm();

    // Business name
    echo '<div class="ae_businessname_div">';
    echo '<span>'.get_lang('BusinessName').':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['companyname'];
    } else {
        $text = '';
    }
    $form->add_text('businessname',$text,30);
    echo '</span></div>';

/*
    // Industry
    echo '<div class="ae_businessindustry_div">';
    echo '<span>'.get_lang('industry').':</span>';
    echo '<span>';
    $form->add_text('industrybl',$contactdata['industry'],20);
    $form->add_button_generic('submit',get_lang('list'),'openwindow("Contacts","contacts_industries","industrybl");');
    echo '</span></div>';
*/

    echo '<div class="clearboth" style="margin-bottom:10px;"></div>';

    //Mailing Address
    MailingAddress($dowhat, $contactdata, 'bl');

    /////
    //Phone Numbers
    echo '<div class="ae_phone_numbers_div">';

    echo '<div class="page_title_div">'.get_lang('phonenumbers').'</div>';

    // Office Phone
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang('OfficePhone').':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        list($tf,$pn) = kgFormatPhoneNumber($contactdata['workphone']);
        $form->add_text('officephone',($pn != 'x' ? $pn : ''));
    } else {
        $form->add_text('officephone');
    }
    echo '</span></div>';

    // Fax Number
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("faxnumber").':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        list($tf,$pn) = kgFormatPhoneNumber($contactdata['faxnumber']);
        $form->add_text('faxnumber',($pn != 'x' ? $pn : ''));
    } else {
        $form->add_text('faxnumber');
    }
    echo '</span></div>';

    echo "\n</div><!-- END ae_phone_numbers_div -->\n";
    // END Phone Numbers
    /////

    // Website
    echo '<div class="ae_website_div">';
    echo '<span>'.get_lang('website').':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['website'];
    } else {
        $text = '';
    }
    $form->add_text('website',$text, 50);
    echo '</span></div>';

    // Email Address
    echo '<div class="ae_email_div">';
    echo '<span>'.get_lang("emailaddress").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['emailaddress'];
    } else {
        $text = '';
    }
    $form->add_text('emailaddressbl',$text,35);
    echo '</span></div>';

    // Last Meeting Date
    echo '<div class="ae_lastmeeting_div">';
    echo '<span>'.get_lang("lastmeetingdate").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {

    // Use jQuery's Datepicker
echo '<script>
    $(function() {
        $( "#lastmeetingdatebl" ).datepicker({dateFormat: "yy-mm-dd",  changeMonth: true, changeYear: true, yearRange: "c-40:c"';

        if ($contactdata['lastmeetingdate'] != '0000-00-00') {
            // Set date for jQuery's datepicker to use
            list($year,$month,$day) = explode('-',$contactdata['lastmeetingdate']);
            echo '}).datepicker(\'setDate\', \''.$year.'-'.$month.'-'.$day.'\');';
        } else {
            // Date unknown
            echo '})';
        }
echo "\n".'    });
</script>';

        if (($contactdata['lastmeetingdate'] == '--') || ($contactdata['lastmeetingdate'] == '0000-00-00')) {
            // Date not set
            $text = get_lang('ClickToSelectDate');
        } else {
            $text = $contactdata['lastmeetingdate'];
        }
    } else {
        $text = get_lang('ClickToSelectDate');
    }
    $form->add_text('lastmeetingdatebl',$text);
    echo '</span></div>';

    // Referred By
    echo '<div class="ae_referedby_div">';
    echo '<span>'.get_lang("referredby").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['referredby'];
    } else {
        $text = '';
    }
    $form->add_text('referredbybl',$text);
    echo '</span></div>';

    // Notes
    echo '<div class="ae_notes_div">';
    echo '<span class="fieldname_span">'.get_lang("notes").':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['notes'];
    } else {
        $text = '';
    }
    $form->add_textarea('notesbl',$text,80,2);
    echo '</span></div>';

    echo '<!-- End Business Layout -->';
}
// END function AeBusinessLayout()

function AePersonalLayout($dowhat, $contactdata) {
    // Personal contact layout

    global $dbc;

    $form = new HtmlForm();

    echo '<!-- Begin Personal Layout -->';


echo '<script type="text/javascript">
function showGroup(group) {
    if (group == 1) {
        // Show address and phone number(s)
        document.getElementById(\'pl_group_one_div\').className = "";
        document.getElementById(\'pl_group_two_div\').className = "hidden";
        document.getElementById(\'pl_addressphonebutton\').className = "hidden";
        document.getElementById(\'pl_moreinfobutton\').className = "";
    } else {
        document.getElementById(\'pl_group_one_div\').className = "hidden";
        document.getElementById(\'pl_group_two_div\').className = "";
        document.getElementById(\'pl_addressphonebutton\').className = "";
        document.getElementById(\'pl_moreinfobutton\').className = "hidden";
    }
}
</script>';

    // Name table
    echo "\n".'<div class="ae_name_table_div">';

    // Dear
    echo '<div class="ae_dear_div">';
    echo '<span>'.get_lang('dear').':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['dear'];
    } else {
        $text = '';
    }
    $form->add_text('dear',$text, 5);
    echo '</span></div>';

    // First Name
    echo '<div class="ae_firstname_div">';
    echo '<span>'.get_lang('firstname').':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['firstname'];
    } else {
        $text = '';
    }
    $form->add_text('firstname',$text, 15);
    echo '</span></div>';

    // Last name
    echo '<div class="ae_lastname_div">';
    echo '<span>'.get_lang('lastname').':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['lastname'];
    } else {
        $text = '';
    }
    $form->add_text('lastname',$text, 15);
    echo '</span></div>';

    // Middle Initial
    echo '<div class="ae_middle_initial_div">';
    echo '<span>'.get_lang('middleinitial').':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['middleinitial'];
    } else {
        $text = '';
    }
    $form->add_text('middleinitial',$text, 5);
    echo '</span></div>';

    // Prefered name
    echo '<div class="ae_prefered_name_div">';
    echo '<span>'.get_lang('preferedname').':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['preferedname'];
    } else {
        $text = '';
    }
    $form->add_text('preferedname',$text);
    echo '</span></div>';

    echo "\n</div><!-- END ae_name_table_div -->\n";

    echo '<div id="pl_group_one_div">';
    //Mailing Address
    MailingAddress($dowhat, $contactdata, 'pl');

    /////
    //Phone Numbers
    echo '<div class="ae_phone_numbers_div">';

    echo '<div class="page_title_div">'.get_lang('phonenumbers').'</div>';

    // Cell Phone
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("cellphone").':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        list($tf,$pn) = kgFormatPhoneNumber($contactdata['cellphone']);
        $text = ($pn != 'x' ? $pn : '');
    } else {
        $text = '';
    }
    $form->add_text('cellphone', $text, 12);
    echo '</span></div>';

    // Home Phone
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("homephone").':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        list($tf,$pn) = kgFormatPhoneNumber($contactdata['homephone']);
        $text = ($pn != 'x' ? $pn : '');
    } else {
        $text = '';
    }
    $form->add_text('homephone',$text, 12);
    echo '</span></div>';

    // Workphone and extension
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("workphone").':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        list($tf,$pn) = kgFormatPhoneNumber($contactdata['workphone']);
        $text = ($pn != 'x' ? $pn : '');
    } else {
        $text = '';
    }
    $form->add_text('workphone',$text, 12);
    echo '&nbsp;&nbsp;'.get_lang('phoneextension');
    if ($dowhat == 'editcontact') {
        $text = $contactdata['workextension'];
    } else {
        $text = '';
    }
    $form->add_text('workextension', $text, 3);
    echo '</span></div>';

    // Fax Number
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("faxnumber").':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        list($tf,$pn) = kgFormatPhoneNumber($contactdata['faxnumber']);
        $text = ($pn != 'x' ? $pn : '');
    } else {
        $text = '';
    }
    $form->add_text('faxnumber',$text, 12);
    echo '</span></div>';

    echo "\n</div><!-- END ae_phone_numbers_div -->\n";
    // END Phone Numbers
    /////

    echo '<div class="ae_email_birthdate_div">';

    // Email Address
    echo '<div class="ae_email_div">';
    echo '<span class="fieldname_span">'.get_lang("emailaddress").':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['emailaddress'];
    } else {
        $text = '';
    }
    $form->add_text('emailaddresspl',$text,20);
    echo '</span></div>';

    // Birthdate
    echo '<div class="ae_birthday_div">';
    echo '<span class="fieldname_span">'.get_lang("birthdate").':</span>';
    echo '<span class="fieldvalue_span">';
    if ($dowhat == 'editcontact') {

// Use jQuery's Datepicker
echo '<script>
    $(function() {
        $( "#birthdate" ).datepicker({dateFormat: "yy-mm-dd",  changeMonth: true, changeYear: true, yearRange: "c-80:c"';

        if ($contactdata['birthdate'] != '0000-00-00') {
            // Set date for jQuery's datepicker to use
            list($year,$month,$day) = explode('-',$contactdata['birthdate']);
            echo '}).datepicker(\'setDate\', \''.$year.'-'.$month.'-'.$day.'\');';
        } else {
            // Date unknown
            echo '})';
        }
echo "\n".'    });
</script>';

        if (($contactdata['birthdate'] == '--') || ($contactdata['birthdate'] == '0000-00-00')) {
            // Date not set
            $text = get_lang('ClickToSelectDate');
        } else {
            $text = $contactdata['birthdate'];
        }
    } else {
        $text = get_lang('ClickToSelectDate');
    }
    $form->add_text('birthdate',$text, 10);
    echo '</span></div>';

    echo "\n</div><!-- END ae_email_birthdate_div -->\n";

    // Last Meeting Date
    echo '<div class="ae_lastmeeting_div">';
    echo '<span>'.get_lang("lastmeetingdate").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {

// Use jQuery's Datepicker
echo '<script>
    $(function() {
        $( "#lastmeetingdatebl" ).datepicker({dateFormat: "yy-mm-dd",  changeMonth: true, changeYear: true, yearRange: "c-40:c"';

        if ($contactdata['lastmeetingdate'] != '0000-00-00') {
            // Set date for jQuery's datepicker to use
            list($year,$month,$day) = explode('-',$contactdata['lastmeetingdate']);
            echo '}).datepicker(\'setDate\', \''.$year.'-'.$month.'-'.$day.'\');';
        } else {
            // Date unknown
            echo '})';
        }
echo "\n".'    });
</script>';

        if (($contactdata['lastmeetingdate'] == '--') || ($contactdata['lastmeetingdate'] == '0000-00-00')) {
            // Date not set
            $text = get_lang('ClickToSelectDate');
        } else {
            $text = $contactdata['lastmeetingdate'];
        }
    } else {
        $text = get_lang('ClickToSelectDate');
    }
    $form->add_text('lastmeetingdatebl',$text);
    echo '</span></div>';

    echo "\n</div><!-- END pl_group_one_div-->\n";

    /**
     * Group seperator marker
    **/

    echo '<div id="pl_group_two_div" class="hidden">';
    // Job title
    echo '<div class="ae_job_title_div">';
    echo '<span>'.get_lang("jobtitle").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['title'];
    } else {
        $text = '';
    }
    $form->add_text('title',$text,20);
    echo '</span></div>';

    echo '<div class="ae_worksat_industry_div">';
    // Works At
    echo '<div>';
    echo '<span>'.get_lang("WorksAt").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['companyname'];
    } else {
        $text = '';
    }
    $form->add_text('companyname',$text,20);
    $form->add_button_generic('submit',get_lang('list'),'openwindow("Contacts","contacts_companies","companyname");');
    echo '</span></div>';

    // Industry
    echo '<div>';
    echo '<span>'.get_lang("industry").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['industry'];
    } else {
        $text = '';
    }
    $form->add_text('industrypl',$text,20);
    $form->add_button_generic('submit',get_lang('list'),'openwindow("Contacts","contacts_industries","industrypl");');
    echo '</span></div>';
    echo "\n</div><!-- END ae_worksat_industry_div -->\n";

    // Hometown
    echo '<div class="ae_hometown_div">';
    echo '<span>'.get_lang("hometown").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['hometown'];
    } else {
        $text = '';
    }
    $form->add_text('hometown',$text);
    echo '</span></div>';

    // Referred By
    echo '<div class="ae_referredby_div">';
    echo '<span>'.get_lang("referredby").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['referredby'];
    } else {
        $text = '';
    }
    $form->add_text('referredbypl',$text);
    echo '</span></div>';

    echo '<div class="ae_marriage_info_div">';
    // Marital Status
    echo '<div class="ae_marital_status_div">';
    echo '<span>'.get_lang("maritalstatus").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['maritalstatusid'];
    } else {
        $text = 1;
    }
    $form->onsubmit('showhidespouse(this.value);');
    $form->add_select_db_autoecho('maritalstatusid','SELECT * FROM maritalstatus','maritalstatusid',$text,'maritalstatus',$dbc);
    echo '</span></div>';

    // Spouses Name
    echo '<div class="ae_spouses_name_div" id="showhidespousename">';
    echo '<span>'.get_lang("spousesname").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['spousesname'];
    } else {
        $text = '';
    }
    $form->add_text('spousesname',$text);
    echo '</span></div>';

    echo "\n</div><!-- END ae_marriage_info_div -->\n";

    // Childrens Names
    echo '<div class="ae_childrens_names_div">';
    echo '<span>'.get_lang("childrensnames").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['childrensnames'];
    } else {
        $text = '';
    }
    $form->add_textarea('childrensnames',$text,60,3);
    echo '</span></div>';

    // Contacts Interests
    echo '<div class="ae_contacts_interests_div">';
    echo '<span>'.get_lang("contactsinterests").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['contactsinterests'];
    } else {
        $text = '';
    }
    $form->add_textarea('contactsinterests',$text,60,3);
    echo '</span></div>';

    // Notes
    echo '<div class="ae_notes_div">';
    echo '<span>'.get_lang("notes").':</span>';
    echo '<span>';
    if ($dowhat == 'editcontact') {
        $text = $contactdata['notes'];
    } else {
        $text = '';
    }
    $form->add_textarea('notespl',$text,65,3);
    echo '</span></div>';

    echo "\n</div><!-- END pl_group_two_div -->\n";

    echo '<div class="ae_switch_group_button_div">';
    $form->add_button_generic('pl_addressphonebutton',get_lang('showaddressphone'),'showGroup(1);');
    $form->add_button_generic('pl_moreinfobutton',get_lang('moreinfo'),'showGroup(2);');
    echo '</div>';

echo '
<script type="text/javascript">
    $(document).ready(function(){
        showGroup(1);
        showhidespouse(document.getElementById("maritalstatusid").value);
    });
</script>';

    echo '<!-- End Personal Layout -->';

}
// END function AePersonalLayout()
/////

/************************
 * View contact layouts *
 ************************/

function VcBusinessLayout($contactdata) {

    global $dbc;

    echo '<!-- Begin Business Layout (View contact) -->';

    $table = new HtmlTable();
    $grouptable = new HtmlTable();
    $innertables = new HtmlTable();
    $form = new HtmlForm();

    // Name div
    echo '<div class="vc_name_div">';
    echo '<span class="bold">'.$contactdata['companyname'].'</span>';
    echo '</div>';

    // Website
    if (($contactdata['website'] != '') && (!is_null($contactdata['website']))) {
        $website = '<a href="'.$contactdata['website'].'" target="_blank">'.get_lang('website').'</a>';
    } else {
        $website = '';
    }
    echo '<div class="vc_website_div">'.$website.'</div>';

    // Email Address
    echo '<div class="vc_email_div">'.($contactdata['emailaddress'] != '' ? $contactdata['emailaddress'] : get_lang('noemailonfile')).'</div>';

    // industry
    echo '<div class="vc_industry_bl_div">';
    echo '<span class="title">'.get_lang('industry').':</span><span class="industry">'.($contactdata['industry'] != '' ? '<span class="bold">'.$contactdata['industry'].'</span>' : '&nbsp;&nbsp;').'</span>';
    echo '</div>';

    // Mailing Address
    // TODO: 2017-07-15 - On screen formatting assumes USA address style.
    //       Need to research how to properly format for other countries.
    $address = ($contactdata['addressline1'] != '' ? $contactdata['addressline1'].'<br>' : '');
    $address .= ($contactdata['addressline2'] != '' ? $contactdata['addressline2'].'<br>' : '');
    $address .= ($contactdata['city'] != '' ? $contactdata['city'].', ' : '');
    $address .= ($contactdata['stateorprovince'] != '' ? $contactdata['stateorprovince'].' ' : '');
    $address .= ($contactdata['postalcode'] != '' ? $contactdata['postalcode'].' ' : '');
    $address .= ($contactdata['country'] != '' ? $contactdata['country'] : '');
    echo '<div class="vc_mailing_address_div"'.$googlemaps.'>';
    echo $address;
    echo '</div>';

    // Google maps URL
    GenMapUrl($contactdata);

    /////
    //Phone Numbers
    echo '<div class="vc_phone_numbers_div">';

    // Office Phone
    list($tf,$pn) = kgFormatPhoneNumber($contactdata['workphone']);
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("OfficePhone").':</span>';
    echo '<span class="fieldvalue_span">'.($pn != 'x' ? $pn : '').'</span>';
    echo '</div>';

    // Fax Number
    list($tf,$pn) = kgFormatPhoneNumber($contactdata['faxnumber']);
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("faxnumber").':</span>';
    echo '<span class="fieldvalue_span">'.($pn != 'x' ? $pn : '').'</span>';
    echo '</div>';

    echo "\n</div><!-- END vc_phone_numbers_div -->\n";
    // END Phone Numbers
    /////

    echo '<div style="clear:both;"></div>';

    // Last Meeting Date
    echo '<div class="vc_fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("lastmeetingdate").':</span>';
    echo '<span class="fieldvalue_span">'.($contactdata['lastmeetingdate'] != '0000-00-00' ? $contactdata['lastmeetingdate'] : get_lang('unknown')).'</span>';
    echo '</div>';

    // Referred By
    echo '<div class="vc_fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("referredby").':</span>';
    echo '<span class="fieldvalue_span">'.($contactdata['referredby'] != '' ? $contactdata['referredby'] : '&nbsp;&nbsp;').'</span>';
    echo '</div>';

    // notes
    echo '<div class="vc_notes_div">';
    echo '<table><tr><td class="toptext">'.get_lang("notes").':</td><td width="10px">&nbsp;</td>';
    echo '<td><div class="vc_notes_scroll_div">'.$contactdata['notes'].'</div></td></tr></table>';
    echo '</div>';

    echo '<!-- End Business Layout (View contact) -->';
}
// END function VcBusinessLayout()
/////

function VcPersonalLayout($contactdata) {
    // Personal contact layout (View contact)

    global $dbc;

    $form = new HtmlForm();

    echo '<!-- Begin Personal Layout (View contact) -->';

    // Name div
    // TODO: 2017-07-15 - Missing $contactdata['dear']
    echo '<div class="vc_name_div">';
    echo '<span class="bold">'.$contactdata['lastname'].', '.$contactdata['firstname'].($contactdata['middleinitial'] != '' ? ' '.$contactdata['middleinitial'] : '').'</span>';
    if ($contactdata['preferedname'] != '') {
        echo ' ('.$contactdata['preferedname'].')';
    }
    echo "</div>\n";

    // Email Address
    echo '<div class="vc_email_div">'.($contactdata['emailaddress'] != '' ? $contactdata['emailaddress'] : get_lang('noemailonfile')).'</div>';

    // Mailing Address
    // TODO: 2017-07-15 - On screen formatting assumes USA address style.
    //       Need to research how to properly format for other countries.
    //
    // Format for display
    $address = ($contactdata['addressline1'] != '' ? $contactdata['addressline1'].'<br>' : '');
    $address .= ($contactdata['addressline2'] != '' ? $contactdata['addressline2'].'<br>' : '');
    $address .= ($contactdata['city'] != '' ? $contactdata['city'].', ' : '');
    $address .= ($contactdata['stateorprovince'] != '' ? $contactdata['stateorprovince'].' ' : '');
    $address .= ($contactdata['postalcode'] != '' ? $contactdata['postalcode'].' ' : '');
    $address .= ($contactdata['country'] != '' ? $contactdata['country'] : '');
    echo '<div class="vc_mailing_address_div">';
    echo $address;
    echo '</div>';

    // Google maps URL
    GenMapUrl($contactdata);

    /////
    //Phone Numbers
    echo '<div class="vc_phone_numbers_div">';

    // Cell Phone
    list($tf,$pn) = kgFormatPhoneNumber($contactdata['cellphone']);
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("cellphone").':</span>';
    echo '<span class="fieldvalue_span">'.($pn != 'x' ? $pn : '').'</span>';
    echo '</div>';

    // Home Phone
    list($tf,$pn) = kgFormatPhoneNumber($contactdata['homephone']);
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("homephone").':</span>';
    echo '<span class="fieldvalue_span">'.($pn != 'x' ? $pn : '').'</span>';
    echo '</div>';

    // workphone and extension
    list($tf, $pn) = kgFormatPhoneNumber($contactdata['workphone']);
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("workphone").':</span>';
    echo '<span class="fieldvalue_span">'.($pn != 'x' ? $pn : '').($contactdata['workextension'] != '' ? ' (x'.$contactdata['workextension'].')' : '').'</span>';
    echo '</div>';

    // Fax Number
    list($tf,$pn) = kgFormatPhoneNumber($contactdata['faxnumber']);
    echo '<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("faxnumber").':</span>';
    echo '<span class="fieldvalue_span">'.($pn != 'x' ? $pn : '').'</span>';
    echo '</div>';

    echo "\n</div><!-- END vc_phone_numbers_div -->\n";
    // END Phone Numbers
    /////

    echo '<div class="vc_work_info_div">';
    // Works At
    echo get_lang("vc_worksat").' <span class="bold">'.($contactdata['companyname'] != '' ? $contactdata['companyname'] : get_lang('unknown')).'</span>'.($contactdata['industry'] != '' ? ' ['.$contactdata['industry'].']' : '' );
    // title
    echo ($contactdata['title'] != '' ? ' '.get_lang("asa").' <span class="bold">'.$contactdata['title'].'</span>' : '');
    echo "\n</div><!-- END vc_work_info_div -->\n";

    // notes
    $newnote = NamesInnotes($contactdata['notes']);
    echo '<div class="vc_notes_div">';
    echo '<table><tr><td class="toptext">'.get_lang('notes').':</td><td width="10px">&nbsp;</td>';
    echo '<td><div class="vc_notes_scroll_div">'.$newnote.'</div></td></tr></table>';
    echo '</div>';

    echo '<div class="vc_row">';

    // Birthday
    echo '<div class="vc_fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("birthdate").':</span>';
    echo '<span class="fieldvalue_span">'.($contactdata['birthdate'] != '0000-00-00' ? $contactdata['birthdate'] : get_lang('unknown')).'</span>';
    echo '</div>';

    // Hometown
    if ($contactdata['hometown'] != '') {
        if (strpos($contactdata['hometown'], ',') !== false) {
            // Assumes hometown was entered as 'city, state'
            $hometown = " onclick=\"window.open('http://maps.google.com/maps?q=".$contactdata['hometown']."')\" title=\"".get_lang('clicktoviewmap')."\" style=\"cursor:pointer;\"";
        } else {
            $hometown = '';
        }
    }
    echo '<div class="vc_fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("hometown").':</span>';
    echo '<span class="fieldvalue_span"'.$hometown.'>'.($contactdata['hometown'] != '' ? $contactdata['hometown'] : '&nbsp;&nbsp;' ).'</span>';
    echo '</div>';

    // Referred By
    if ($contactdata['referredby'] != '') {
        $referredby = ReferredByToLink($contactdata['referredby']);
    } else {
        $referredby = '&nbsp;&nbsp;';
    }
    echo '<div class="vc_fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("referredby").':</span>';
    echo '<span class="fieldvalue_span">'.$referredby.'</span>';
    echo '</div>';

    // Marital Status
    if ($contactdata['maritalstatusid'] == MARRIED) {
        $spousename = ' (';
        $query = $dbc->select('contacts', array('firstname' => $contactdata['spousesname'], 'lastname' => $contactdata['lastname']), 'contactid');
        if ($dbc->numRows($query) == 1) {
            $data = $dbc->fieldValue($query);
            $spousename .= kgCreateLink($contactdata['spousesname'], array('ACTON' => 'viewcontact', 'contactid' => $data));
        } else {
            $spousename .= $contactdata['spousesname'];
        }
        $spousename .= ')';
    } else {
        $spousename = '';
    }
    echo '<div class="vc_fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("maritalstatus").':</span>';
    echo '<span class="fieldvalue_span">'.$dbc->fieldValue($dbc->select('maritalstatus', 'maritalstatusid = '.$contactdata['maritalstatusid'], 'maritalstatus')).$spousename.'</span>';
    echo '</div>';

    // Childrens Names
    if ($contactdata['childrensnames'] != '') {
        $names = ChildrensNamesLinks($contactdata['childrensnames'], $contactdata['lastname']);
    } else {
        $names = '&nbsp;&nbsp;';
    }
    echo '<div class="vc_fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("childrensnames").':</span>';
    echo '<span class="fieldvalue_span">'.$names.'</span>';
    echo '</div>';

    echo "\n</div><!-- END vc_row -->\n";

    echo '<div style="clear:both;"></div>';

    // Last Meeting Date
    echo '<div class="vc_fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang("lastmeetingdate").':</span>';
    echo '<span class="fieldvalue_span">'.($contactdata['lastmeetingdate'] != '0000-00-00' ? $contactdata['lastmeetingdate'] : get_lang('unknown')).'</span>';
    echo '</div>';

    echo '<div style="clear:both;"></div>';

    // Contacts Interests
    echo '<div class="vc_fieldpair_div" style="margin-top:5px;">';
    echo '<span class="fieldname_span">'.get_lang("contactsinterests").':</span>';
    echo '<span class="fieldvalue_span">'.($contactdata['contactsinterests'] != '' ? $contactdata['contactsinterests'] : '&nbsp;&nbsp;').'</span>';
    echo '</div>';

    echo '<!-- End Personal Layout -->';

}
// END function VcPersonalLayout()
?>
