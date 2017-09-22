<?php
// Last Updated: 2017-01-20
// Use servers main database
$dbc = new genesis();
$db = $dbc->connectServer();
$dbc->connectDatabase('Kraven', 0.66);

function ChangePassword() {
    /**
     * Allows the user to change their password
     *
     * Added: 2017-06-24
     * Modified: 2017-06-24
     *
     * @param None
     *
     * @return Nothing
    **/

    global $valid, $userinfo;

    // Display errors if any
    $valid->displayErrors();
?>
<script type="text/javascript">

    function val_newpassword() {

        if (document.getElementById("currentpassword").value == "") {
            document.getElementById("currentpassword").focus();
            alert('<?php echo get_lang('currentpasswordrequired'); ?>');
            return false;
        }

        /***********************/
        /* New password checks */

        var np = document.getElementById("newpassword");
        var newpassword = np.value;
        var newpasswordlen = newpassword.length;

        // Length
        if (newpasswordlen < 5 || newpasswordlen > 20) {
            document.getElementById("newpassword").focus();
            alert('<?php echo get_lang('passwordlength'); ?>');
            return false;
        }

        // Valid characters
        // Only [a-z][A-Z][0-9] allowed
        newpassword = newpassword.replace(/[^a-zA-Z0-9]+/g,'');
        if (newpasswordlen != newpassword.length) {
            // Invalid characters found
            document.getElementById("newpassword").focus();
            alert('<?php echo get_lang('passwordvalidchars'); ?>');
            return false;
        }

        /* END newpassword */

        /***************************/
        /* Confirm password checks */

        var cp = document.getElementById("confirmpassword");
        var confirmpassword = cp.value;
        var confirmpasswordlen = confirmpassword.length;

        // Length
        if (confirmpasswordlen < 5 || confirmpasswordlen > 20) {
            document.getElementById("confirmpassword").focus();
            alert('<?php echo get_lang('passwordlength'); ?>');
            return false;
        }

        // Valid characters
        // Only [a-z][A-Z][0-9] allowed
        confirmpassword = confirmpassword.replace(/[^a-zA-Z0-9]+/g,'');
        if (confirmpasswordlen != confirmpassword.length) {
            // Invalid characters found
            document.getElementById("confirmpassword").focus();
            alert('<?php echo get_lang('passwordvalidchars'); ?>');
            return false;
        }

        /* END confirmpassword */

        // Make sure the new and confirm passwords match
        if (newpassword != confirmpassword) {
            document.getElementById("newpassword").focus();
            alert('<?php echo get_lang('PasswordMismatch'); ?>');
            return false;
        }

        // All tests passed
        return true;
    }
</script>
<?php

    $table = new HtmlTable();
    $form = new HtmlForm();

    echo '<center><b>'.get_lang('changepassword').'</b></center><br/>';

    $form->add_hidden(array(
        'ACTON' => 'savenewpassword',
        'userid' => $userinfo['UserID']
    ));
    $form->onsubmit('return val_newpassword();');
    $form->start_form(kgGetScriptName(),'post','frmchangepassword');

    // Start the table
    $table->new_table();

    $table->new_row();
    $table->new_cell();
    echo get_lang('currentpassword');
    $table->new_cell();
    $form->add_password('currentpassword');

    $table->blank_row();

    $table->new_row();
    $table->new_cell();
    echo get_lang('newpassword');
    $table->new_cell();
    $form->add_password('newpassword');

    $table->new_row();
    $table->new_cell();
    echo get_lang('confirmpassword');
    $table->new_cell();
    $form->add_password('confirmpassword');

    $table->blank_row();

    // Submit, reset, and cancel buttons
    $table->new_row();
    $table->new_cell('center');
    $form->add_button_submit(get_lang('save'));
    $table->new_cell('center');
    echo '<div style="float:left;">';
    $form->add_button_reset(get_lang('reset'));
    echo '</div><div style="float:right;">';
    $form->add_button_generic('ACTON',get_lang('cancel'),'location.href="'.kgCreateLink('',array('NO_TAG' => 'NO_TAG')).'";');
    echo '</div>';

    $table->end_table();

    $form->end_form();

}
// END function ChangePassword()
/////

function SaveNewPassword() {
    /**
     * Save new password to database after validating user
     *
     * Added: 2017-06-24
     * Modified: 2017-06-24
     *
     * @param None
     *
     * @return boolean(True) or boolean(False)
    **/

    global $KG_DBC, $valid, $userinfo;

    $currentpassword = $valid->get_value('currentpassword');
    $newpassword = $valid->get_value('newpassword');
    $userid = $valid->get_value_numeric('userid', -1);

    // Check ID number
    if ($userid < 1) {
        // Invalid ID number
        $valid->addError(get_lang('invaliduserid'));
        return false;
    } else {
        // ID seems to be valid

        // Does it match the ID number of the user currently logged in?
        if ($userid != $userinfo['UserID']) {
            // Nope
            $valid->addError(get_lang('invaliduserid'));
            return false;
        }

        // What user name is associated with $userid?
        $query = $KG_DBC->select('Users', 'UserID = '.$userid, 'UserName');

        if ($KG_DBC->numRows($query) == 1) {
            // Do the usernames match?
            if ($KG_DBC->fieldValue($query) != $userinfo['UserName']) {
                // Nope
                $valid->addError(get_lang('invaliduserid'));
                return false;
            }
        } else {
            // $userid was not found or it was found multiple times in the database.
            // This should not happen since user ID's are supposed to be unique.
            $valid->addError(get_lang('invaliduserid'));
            return false;
        }
    }

    // Get users current password
    $query = $KG_DBC->select('Users', array('UserID = '.$userid, 'UserName' => $userinfo['UserName']), 'Password');
    if ($KG_DBC->numRows($query) != 1) {
        // The combination of $userid and $userinfo['UserName'] was not found or was found multiple times in the database.
        // This should not happen since user ID's and names are supposed to be unique.
        $valid->addError(get_lang('invaliduserid'));
        return false;
    } else {
        $dbpassword = $KG_DBC->fieldValue($query);
    }

    if (!password_verify($currentpassword, $dbpassword)) {
        // Current password from the form does not match the one in the database
        $valid->addError(get_lang('invalidcurrentpassword'));
        return false;
    }

    // Hash the password
    $newpassword = password_hash($newpassword, PASSWORD_DEFAULT);

    // Update the account with new password
    $updatearray = array(
        'Password' => $newpassword
    );
    if (!$KG_DBC->update('Users', 'UserID = '.$userid.' AND UserName = "'.$userinfo['UserName'].'"', $updatearray)) {
        $valid->addError(get_lang('unabletosavechangesreason').$KG_DBC->errorString());
        return false;
    }

    // Not an error but it lets the user know their password has been updated
    $valid->addError(get_lang('passwordchangesuccess'));
    return true;
}
// END function SaveNewPassword()
/////

function SavePreferences() {
    /**
     * Saves the users preferences to the database
     *
     * Added: 2017-06-24
     * Modified: 2017-06-24
     *
     * @param None
     *
     * @return boolean(True) or boolean(False)
    **/

    global $userinfo, $KG_DBC, $valid, $KG_SESSION;

    // Get form data
    $userid = $valid->get_value_numeric('userid',0);
    $firstname = $valid->get_value('firstname');
    $lastname = $valid->get_value('lastname');
    $email = $valid->get_value('email');
    $language = $valid->get_value('language');
    $dateformat = $valid->get_value('dateformat');
    $skin = $valid->get_value('skin');

    // Make sure we have a valid user ID
    if ($userid < 1) {
        $valid->addError(get_lang('InvalidUserID'));
        return false;
    }

    if ($valid->isEmail($email, get_lang('invalidemailaddress')) === false) {
        return false;
    }

    $updatearray = array(
        'FirstName' => $firstname,
        'LastName' => $lastname,
        'Email' => $email,
        'Language' => $language,
        'DateFormat' => $dateformat,
        'Skin' => $skin
    );

    // Update user info
    if (!$KG_DBC->update('Users', 'UserID = '.$userid, $updatearray)) {
        $valid->addError(get_lang('UnableToSaveChangesReason').$KG_DBC->errorString());
        return false;
    }

    // Limiting the fields to get info from for security
    // This list must match the one in /includes/_StartHere.php
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
    $userinfo = $KG_DBC->fetchAssoc($KG_DBC->select('Users', 'SesID != "" AND SesID = "'.$KG_SESSION->getVar('sessionid').'" AND UserID = '.$KG_SESSION->getVar('userid'), $fields));

    // Not an error but lets the user know their preferences have been saved
    $valid->addError(get_lang('preferencesupdated'));
    return true;

}
// END function SavePreferences()
/////

function SetPreferences() {

    global $KG_DBC, $valid, $KG_SESSION;

    // Display errors if any
    $valid->displayErrors();

    // Limiting the fields to get info from for security
    $fields = array(
        'UserID',
        'UserName',
        'FirstName',
        'LastName',
        'Language',
        'Email',
        'Skin',
        'DateRegistered',
        'DateFormat',
        'DefaultModule',
        'Disabled',
        'GroupID'
    );
    // This instance of $userinfo will not affect the global $userinfo array created in _StartHere.php
    $userinfo = $KG_DBC->fetchAssoc($KG_DBC->select('Users', 'SesID != "" AND SesID = "'.$KG_SESSION->getVar('sessionid').'" AND UserID = '.$KG_SESSION->getVar('userid'), $fields));

    echo '
<script type="text/javascript">
    function UpdateDateFormat() {

        document.getElementById("dateformat").value = document.getElementById("dateformatselect").value

    }

    function ResetDateFormat() {

        document.getElementById("dateformat").value = "'.$userinfo['DateFormat'].'";

    }

    function val_email() {
        // Quick and dirty email address validation

        var email = document.getElementById("email").value;
        var n = email.indexOf("@");

        if (n < 1) {
            document.getElementById("email").focus();
            alert("'.get_lang('InvalidEmailAddress').'");

            return false;
        }

        var part = email.substr(n);

        if (part.indexOf(".") > 1) {
            return true;
        } else {
            document.getElementById("email").focus();
            alert("'.get_lang('InvalidEmailAddress').'");
            return false;
        }

        return false;
    }
    
    function validate_prefs() {
        // Some client side validation of the form data

        if (val_email() === false) {
            return false;
        }

        if (document.getElementById("dateformat").value == "") {
            document.getElementById("dateformat").focus();
            alert("'.get_lang('DateFormatRequired').'");
            return false;
        }

        // All tests passed
        return true;
    }
</script>
';

    $table = new HtmlTable();
    $form = new HtmlForm();

    echo '<center><b>'.get_lang('usersettings').'</b></center><br/>';

    // Start the form
    $form->onsubmit('return validate_prefs();');
    $form->add_hidden(array(
        'userid' => $userinfo['UserID'],
        'ACTON' => 'savepreferences'
    ));
    $form->start_form(kgGetScriptName(),'post','frmuserinfo');

    // Start the table
    $table->new_table();

    // User name
    $table->new_row();
    $table->set_width(175,'px');
    $table->new_cell();
    echo get_lang('username');
    $table->new_cell();
    echo $userinfo['UserName'];

    // First name
    $table->new_row();
    $table->new_cell();
    echo get_lang('firstname');
    $table->new_cell();
    $form->add_text('firstname',$userinfo['FirstName']);

    // Last name
    $table->new_row();
    $table->new_cell();
    echo get_lang('lastname');
    $table->new_cell();
    $form->add_text('lastname',$userinfo['LastName']);

    // Email
    $table->new_row();
    $table->new_cell();
    echo get_lang('email').' <span class="requiredinfogreenish" title="'.get_lang('requiredinfo').'">*</span>';
    $table->new_cell();
    $form->add_text('email',$userinfo['Email'],20);

    // Date registered
    $table->new_row();
    $table->new_cell();
    echo get_lang('dateregistered');
    $table->new_cell();
    echo $userinfo['DateRegistered'];

    // User group
    $table->new_row();
    $table->new_cell();
    echo get_lang('usergroup');
    $table->new_cell();
    echo $KG_DBC->fieldValue($KG_DBC->select('UserGroups', 'GroupID = '.$userinfo['GroupID'], 'GroupName'));

    // Language
    $table->new_row();
    $table->new_cell();
    echo get_lang('language');
    $table->new_cell();
    $form->add_select_match_key('language', kgGetLangList(), $userinfo['Language']);

    // Skins
    $table->new_row();
    $table->new_cell();
    echo get_lang('skin');
    $table->new_cell();
    $form->add_select_match_key('skin', kgGetSkinList(), $userinfo['Skin']);

    /////
    // Date format
    $table->new_row();
    $table->new_cell('','top');
    echo get_lang('dateformat').' <span class="requiredinfogreenish" title="'.get_lang('requiredinfo').'">*</span>';
    $table->new_cell();
    // Text box
    $form->add_text('dateformat',$userinfo['DateFormat']);
    $form->add_button_generic('reset',get_lang('reset'),'ResetDateFormat();');
    $table->new_row();
    $table->blank_cell();
    $table->new_cell();
    echo '<span class="smallfont">'.get_lang('dateformatnote').'</span>'    ;
    $table->new_row();
    $table->blank_cell();
    $table->new_cell();
    // Select list
    $form->onsubmit('UpdateDateFormat();');
    $form->add_select_match_key('dateformatselect',kgGetDateFormatList(),$userinfo['DateFormat']);
    // Date format
    /////

    $table->blank_row();

    // Submit, reset, and change password buttons
    $table->new_row();
    $table->new_cell('center');
    $form->add_button_submit(get_lang('savepreferences'));
    $table->new_cell('center');
    echo '<div style="float:left;">';
    $form->add_button_reset(get_lang('Reset'));
    echo '</div><div style="float:right;">';
    $form->add_button_generic('changepwdbutton',get_lang('changepassword'),'location.href="'.kgCreateLink('',array('ACTON' => 'changepassword', 'NO_TAG' => 'NO_TAG')).'";');
    echo '</div>';

    $table->blank_row();

    $table->new_row();
    $table->new_cell();
    echo '<span class="requiredinfogreenish">* '.get_lang('requiredinfo').'</span>';
    $table->blank_cell();

    $table->end_table();

    $form->end_form();

}
// END function SetPreferences()
/////

if ($KG_DBC->isConnectedDB() === true && $KG_SECURITY->isLoggedIn() === true) {

    $ACTON = $valid->get_value('ACTON');

    if ($ACTON == 'changepassword') {
        // Change user password

        ChangePassword();

    } elseif ($ACTON == 'savepreferences') {
        // Save preferences

        SavePreferences();

        SetPreferences();

    } elseif ($ACTON == 'savenewpassword') {
        // User changed their password

        $rv = SaveNewPassword();

        if ($rv) {
            SetPreferences();
        } else {
            ChangePassword();
        }

    } else {

        SetPreferences();
    }

} // if ($KG_DBC->isConnectedDB() === true && $KG_SECURITY->isLoggedIn() === true)
?>
