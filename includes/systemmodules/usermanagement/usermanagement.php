<?php
// Last Updated: 2017-07-09

// Uses servers main database
$dbc = new genesis();
$db = $dbc->connectServer();
$dbc->connectDatabase('Kraven', 0.66);

function ChangePasswordForm() {

    global $valid;

    $table = new HtmlTable();
    $form = new HtmlForm();

    // Display errors if any
    $valid->displayErrors();

        echo '
<script language="javascript" type="text/javascript" src="includes/js/_md5.js"></script>
<script type="text/javascript">
    function clearform() {
        document.getElementById("password").value = "";
        document.getElementById("confirmpassword").value = "";
    }

    function validate() {

        var yp = document.getElementById("yourpassword");

        // The person changing the other users password must provide their own password
        // 32 is the number of characters in the MD5 hash
        // This check is being done incase the "password" or "confirmpassword" fields failed validation
        if (yp.value != "" && yp.value.length < 32) {
            yp.value = calcMD5(yp.value);
        } else {
            yp.focus();
            alert("'.get_lang('YourPasswordRequired').'");
            return false;
        }

        // Make sure they match
        if (document.getElementById("password").value != document.getElementById("confirmpassword").value) {
            document.getElementById("password").focus();
            alert("'.get_lang('PasswordMismatch').'");
            return false;
        }

        if (document.getElementById("password").value != "") {
            if (document.getElementById("confirmpassword").value == "") {
                document.getElementById("confirmpassword").focus();
                alert("'.get_lang('PasswordRequired').'");
                return false;
            }
            document.getElementById("password").value = calcMD5(document.getElementById("password").value);
        } else {
            document.getElementById("password").focus();
            alert("'.get_lang('PasswordRequired').'");
            return false;
        }

        // All tests passed
        return true;
    }
</script>
';

    echo '<center><h2>'.get_lang('ChangePassword').'</h2></center><br/><br/>';

    $selecteduserid = $valid->get_value_numeric('userid', -1);
    $selectedusername = $valid->get_value('username');

    // Start the form
    $form->add_hidden(array(
        'ACTON' => 'savechangespassword',
        'userid' => $selecteduserid,
        'username' => $selectedusername
    ));
    $form->onsubmit('return validate();');
    $form->start_form(kgGetScriptName(),'post','frmchangepassword');

    // Start the table
    $table->new_table();

    $table->new_row();
    $table->new_cell();
    echo get_lang('YourPassword');
    $table->new_cell();
    $form->add_password('yourpassword');

    $table->blank_row();

    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell('center');
    echo get_lang('UsersNewPassword');

    $table->new_row();
    $table->new_cell();
    echo get_lang('Password');
    $table->new_cell();
    $form->add_password('password');

    $table->new_row();
    $table->new_cell();
    echo get_lang('ConfirmPassword');
    $table->new_cell();
    $form->add_password('confirmpassword');

    $table->blank_row();

    $table->new_row();
    $table->new_cell();
    $form->add_button_submit(get_lang('save'));
    $table->new_cell();
    $form->add_button_generic('reset',get_lang('reset'),'clearform();');

    $table->end_table();

    $form->end_form();
}
// END function ChangePasswordForm()
/////

function ListUsers($showaddbutton=true) {

    global $dbc, $valid, $userinfo;

    $table = new HtmlTable();
    $form = new HtmlForm();

    // Display errors if any
    $valid->displayErrors();

    $selecteduserid = $valid->get_value_numeric('userid', -1);
    if ($selecteduserid > 0) {
        $matchme = $selecteduserid;
    } else {
        $matchme = -1;
    }

    // Select all registered users except UserID 2 which is Kraven.
    $query = $dbc->select('Users', 'UserID != 2', array('UserID', 'UserName'), array('ORDER BY' => 'UserName'));

    if ($dbc->numRows($query) < 1) {
        echo '<center>'.get_lang('NoOtherUsersToManage').'</center>';
    } else {

        // List of actions to take
        $list = array(
            'SelectAction' => get_lang('selectaction'),
            'EditPrefs' => get_lang('editpreferences'),
            'ModulePermissions' => get_lang('modulepermissions'),
            'ChangePassword' => get_lang('changepassword'),
            'DeleteUser' => get_lang('deleteuser')
        );

        while ($row = $dbc->fetchAssoc($query)) {
            $userlist[$row['UserID']] = $row['UserName'];
        }

        echo '<center>';
        $form->add_hidden(array('ACTON' => 'edituser', 'username' => ''));
        $form->start_form(kgGetScriptName(),'post','frmedituser');
        $form->onsubmit('EnableActionList();');

        // List of users
        $form->add_select_match_key('userid', $userlist, $matchme);

        // List of possible actions
        $form->add_select_match_value('dowhat', $list);
        $form->end_form();
        echo '</center>';

        // Javascript to submit the form when an action is selected
        echo '
<script type="text/javascript">
    function EnableActionList() {
        var ui = document.getElementById("userid");
        if (ui.value != "SelectUser") {
            document.getElementById("dowhat").disabled = false;
            document.getElementById("username").value = ui.options[ui.selectedIndex].text;
        } else {
            document.getElementById("dowhat").disabled = true;
            document.getElementById("username").value = ""
        }
    }

    document.getElementById(\'dowhat\').onchange = function() {

        if (this.value == "SelectAction") {
            // This is not a valid action
            return false;
        }
    
        // All of the elements in the form
        var formlist = document.getElementById("frmedituser").elements;

        // Store form data
        var getstring = "?";

        // Process and format form data
        for(i=0; i<formlist.length; i++){
            if (formlist[i].name != "submit") {
                getstring = getstring.concat(formlist[i].name,"=",formlist[i].value,"&");
            }
        }

        // Remove trailing "&"
        getstring = getstring.substr(0,getstring.length-1);

        // Reload the page
        window.location = "'.kgGetScriptName().'"+getstring;
    };

    // Adds the "Select User" option to the Username list
    var ui = document.getElementById("userid");
    ui.options.add(new Option("'.get_lang('SelectUser').'", "SelectUser"), ui.options[0]);

    // Figure out which option to select
    var selectme = 0;
    for(var i = 0;i < ui.length;i++){
        if(ui.options[i].value == '.$matchme.' ){
            selectme = i;
        }
    }
    //
    // Select that option
    ui.selectedIndex = selectme;

    // Enable/Disable the action list
    EnableActionList();
</script>
';
    }

    echo '<hr width="80%"><br/>';

    // Add user button
    if ($showaddbutton === true) {
        echo '<div class="centertext"><br/>';
        $form->add_hidden(array('ACTON' => 'adduser'));
        $form->buttononlyform(kgGetScriptName(),'post','frmadduser',get_lang('AddUser'));
        echo '</div>';
    }
}
// END function ListUsers()
/////

function ModulePermissionsForm() {

    global $dbc, $valid, $IP;

    $table = new HtmlTable();
    $form = new HtmlForm();

    // Display errors if any
    $valid->displayErrors();

    // Get the users ID number
    $selecteduserid = $valid->get_value_numeric('userid', -1);

    if ($selecteduserid < 1) {
        // User ID numbers must be positive integers greater than 0
        echo get_lang('invaliduser');
        return;
    }

    // Create a list of all of the user modules
    $usermoduledir = $IP.'/modules/';
    $usermodulelist = kgGetDirlist($usermoduledir);
    $usermodulecount = count($usermodulelist);
    asort($usermodulelist);  // Sort by module name A-Z

    // Create a list of all of the system modules
    $systemmoduledir = $IP.'/includes/systemmodules/';
    $systemmodulelist = kgGetDirlist($systemmoduledir);
    $systemmodulecount = count($systemmodulelist);
    asort($systemmodulelist);  // Sort by module name A-Z

    // Fill an array with all of the users permissions
    $userpermissions = array();
    $query = $dbc->select('UserPermissions', 'UserID = '.$selecteduserid);
    while ($row = $dbc->fetchAssoc($query)) {
        $userpermissions[$row['Module']][] = $row['Action'];
    }

    echo '<center><b>'.get_lang('ModulePermissions').'</b></center>';

    // Start the form
    $form->add_hidden(array(
        'userid' => $selecteduserid,
        'ACTON' => 'savepermissions'
    ));
    $form->start_form(kgGetScriptName(),'post','frmsetmodulepermissions');

    // Start the table
    $table->set_width(450,'px');
    $table->new_table();

    /////
    // User modules
    if ($usermodulecount > 0) {

        $table->new_row();
        $table->set_colspan(4);
        $table->new_cell('centertext');
        echo '<b>'.get_lang('UserModules').'</b>';

        $table->new_row();
        $table->set_width(150,'px');
        $table->new_cell();
        echo '<u>'.get_lang('ModuleName').'</u>';
        $table->set_colspan(3);
        $table->new_cell('center');
        echo '<u>'.get_lang('Permissions').'</u>';

        $altcolor = true;

        foreach ($usermodulelist as $key => $modulename) {

            $infofile = $usermoduledir.$modulename.'/'.$modulename.'.info.php';

            // Only list the module if its info file exists
            if (is_readable($infofile)) {

                // Load the info file so I can display $moduledata['modulename']
                require $infofile;

                // These variables selecte/deselect the checkboxes
                //   False = no check mark
                //   True = place check mark
                if (array_key_exists($modulename, $userpermissions)) {
                    if (in_array('add', $userpermissions[$modulename])) {
                        $add = true;
                    } else {
                        $add = false;
                    }
                    if (in_array('edit', $userpermissions[$modulename])) {
                        $edit = true;
                    } else {
                        $edit = false;
                    }
                    if (in_array('delete', $userpermissions[$modulename])) {
                        $delete = true;
                    } else {
                        $delete = false;
                    }
                } else {
                    // Module not in the $userpermissions array means user does not have permission
                    $add = false;
                    $edit = false;
                    $delete = false;
                }

                // One row per module
                $table->new_row(($altcolor === true ? 'cellalternate' : ''));
                $altcolor = !$altcolor;
                $table->set_height(22,'px');
                $table->new_cell();
                echo $moduledata['modulename'];
                $table->set_height(22,'px');
                $table->new_cell();
                $form->add_checkbox('add['.$modulename.']',1,get_lang('add'),$add);
                $table->set_height(22,'px');
                $table->new_cell();
                $form->add_checkbox('edit['.$modulename.']',1,get_lang('edit'),$edit);
                $table->set_height(22,'px');
                $table->new_cell();
                $form->add_checkbox('delete['.$modulename.']',1,get_lang('delete'),$delete);
            }
        }

        $table->end_table();
    }

    /////
    // System modules
    if ($systemmodulecount > 0) {
        if ($usermodulecount > 0) {
            // Add some blank lines between the module groups
            echo '<br/><br/>';
        }

        // Start the table
        $table->set_width(450,'px');
        $table->new_table();

        $table->new_row();
        $table->set_colspan(4);
        $table->new_cell('centertext');
        echo '<b>'.get_lang('SystemModules').'</b>';

        $table->new_row();
        $table->set_width(155,'px');
        $table->new_cell();
        echo '<u>'.get_lang('ModuleName').'</u>';
        $table->set_colspan(3);
        $table->new_cell('center');
        echo '<u>'.get_lang('Permissions').'</u>';

        $altcolor = true;

        // Do not display these modules because all users
        // must have access to them.
        $systemmodules = array(
            'login',
            'logout',
            'menu'
        );

        foreach ($systemmodulelist as $key => $modulename) {

            if (in_array($modulename, $systemmodules)) {
                // Not all system modules need permissions
                continue;
            }

            $infofile = $systemmoduledir.$modulename.'/'.$modulename.'.info.php';

            // Only list the module if its info file exists
            if (is_readable($infofile)) {

                // Load the info file so I can display $moduledata['modulename']
                require $infofile;

                // These variables selecte/deselect the checkboxes
                //   False = no check mark
                //   True = place check mark
                if (array_key_exists($modulename, $userpermissions) === true) {
                    if (in_array('add', $userpermissions[$modulename]) === true) {
                        $add = true;
                    } else {
                        $add = false;
                    }
                    if (in_array('edit', $userpermissions[$modulename]) === true) {
                        $edit = true;
                    } else {
                        $edit = false;
                    }
                    if (in_array('delete', $userpermissions[$modulename]) === true) {
                        $delete = true;
                    } else {
                        $delete = false;
                    }
                } else {
                    // Module not in the $userpermissions array means user does not have permission
                    $add = false;
                    $edit = false;
                    $delete = false;
                }

                // One row per module
                $table->new_row(($altcolor === true ? 'cellalternate' : ''));
                $altcolor = !$altcolor;
                $table->set_height(22,'px');
                $table->new_cell();
                echo $moduledata['modulename'];
                $table->set_height(22,'px');
                $table->new_cell();
                $form->add_checkbox('add['.$modulename.']',0,get_lang('add'),$add);
                $table->set_height(22,'px');
                $table->new_cell();
                $form->add_checkbox('edit['.$modulename.']',0,get_lang('edit'),$edit);
                $table->set_height(22,'px');
                $table->new_cell();
                $form->add_checkbox('delete['.$modulename.']',0,get_lang('delete'),$delete);
            }
        }
        // END foreach()
    }

    if (($systemmodulecount < 1) && ($usermodulecount < 1)) {
        // No modules where found.
        // But if your reading the error message then a module was found.
        $table->new_row();
        $table->set_colspan(4);
        $table->new_cell('center');
        echo get_lang('nomodulesfound');
    }

    $table->blank_row(4);

    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell('center');
    $form->add_button_submit(get_lang('save'));
    $table->set_colspan(2);
    $table->new_cell('center');
    $form->add_button_reset(get_lang('reset'));

    $table->end_table();

    $form->end_form();
}
// END function ModulePermissionsForm()
/////

function SaveChangesInfo() {
    // Save changes to the info of an existing user

    global $dbc, $valid;

    // Make sure we have a valid user ID
    $userid = $valid->get_value_numeric('userid',0);
    if ($userid < 1) {
        $valid->add_error(get_lang('InvalidUserID'));
        return false;
    }

    // Validate email address
    if ($valid->is_email($email,get_lang('InvalidEmailAddress')) === false) {
        return false;
    }

    $updateinfoquery = array(
        'FirstName' => $valid->get_value('firstname'),
        'LastName' => $valid->get_value('lastname'),
        'Email' => $valid->get_value('email'),
        'Language' => $valid->get_value('language'),
        'DateFormat' => $valid->get_value('dateformat'),
        'Skin' => $valid->get_value('skin'),
        'GroupID' => $valid->get_value_numeric('usergroup',0)
    );

    // Update user info
    if (!$dbc->update('Users', 'UserID = '.$userid, $updateinfoquery)) {
        $valid->add_error(get_lang('UnableToSaveChanges').$dbc->errorString());
        return false;
    }

}
// END function SaveChangesInfo()
/////

function SaveChangesPassword() {

    global $dbc, $valid, $userinfo;

    $yourpassword = $valid->get_value('yourpassword');
    $newpassword = $valid->get_value('password');
    $userid = $valid->get_value_numeric('userid',-1);
    $username = $valid->get_value('username');

    // Check ID number
    if ($userid < 1) {
        // Invalid ID number
        $valid->add_error(get_lang('InvalidUserID'));
        return false;
    } else {
        // ID seems to be valid.
        // Check if its in the database

        $query = $dbc->select('Users', array('UserID' => $userid, 'UserName' => $username), 'UserName');

        if ($dbc->numRows($query) != 1) {
            // User not found
            $valid->add_error(get_lang('UnableToSaveChanges').'. '.get_lang('InvalidUserPasswd'));
            return false;
        }
    }

    // Verify password of person changing the other users password
    $query = $dbc->select('Users', array('UserName' => $userinfo['UserName'], 'Password' => $yourpassword));
    if ($dbc->numRows($query) != 1) {
        $valid->add_error(get_lang('invalidcurrentpassword'));
        return false;
    }

    // Update the other users account with new password
    $updatearray = array(
        'Password' => $newpassword
    );
    if (!$dbc->update('Users', 'UserID = '.$userid, $updatearray)) {
        $valid->add_error(get_lang('UnableToSaveChangesReason').$dbc->errorString());
        return false;
    }
}
// END function SaveChangesPassword()
/////

function SaveNewUser() {
    // Add a new user to the database

    global $dbc, $valid;

    // Check if username is available
    $query = $dbc->select('Users', 'UserName = "'.$valid->get_value('username').'"', 'UserID');
    if ($dbc->numRows($query) > 0) {
        $valid->add_error(get_lang('UserNameTaken'));
    }

    // Validate email address
    $email = $valid->get_value('email');
    if ($valid->is_email($email,get_lang('InvalidEmailAddress')) === false) {
        return false;
    }

    $insertquery = array(
        'UserName' => $valid->get_value('username'),
        'Password' => $valid->get_value('password'),
        'FirstName' => $valid->get_value('firstname'),
        'LastName' => $valid->get_value('lastname'),
        'Email' => $email,
        'Language' => $valid->get_value('language'),
        'DateFormat' => $valid->get_value('dateformat'),
        'Skin' => $valid->get_value('skin'),
        'GroupID' => $valid->get_value_numeric('usergroup',0),
        'DateRegistered' => $valid->get_value('DateRegistered')
    );

    if ($valid->is_error() === false) {
        // Update user info
        if (!$dbc->insert('Users',$insertquery)) {
            $valid->add_error(get_lang('UnableToSaveChanges').$dbc->errorString());
            return false;
        }
    }

}
// END function SaveNewUser()
/////

function SavePermissions() {
    // Save user permissions

    global $dbc, $valid;

    // Get user ID
    $userid = $valid->get_value_numeric('userid', -1);

    // Is it valid?
    // User IDs start at 1 and go up
    if ($userid < 1) {
        // Not valid
        echo get_lang('invaliduser');
        return;
    }

    // Get the values of all the checkboxes
    $addperms = $valid->get_value('add');
    $editperms = $valid->get_value('edit');
    $deleteperms = $valid->get_value('delete');

    $query = $dbc->select('UserPermissions','UserID = '.$userid, array('Module', 'Action'));
    if ($dbc->numRows($query) > 0) {
        // Save existing permissions so they can hopefully be restored
        // if the new permissions could not be saved to the database
        $existingpermissions = array(
            'fields' => array('UserID', 'Module', 'Action'),
            'values' => array()
        );

        while ($row = $dbc->fetchAssoc($query)) {
            $existingpermissions['values'][] = array($userid, $row['Module'], $row['Action']);
        }

    } else {
        // No existing permissions found
        $existingpermissions = '';
    }

    $insertarray = array(
        'fields' => array('UserID', 'Module', 'Action'),
        'values' => array()
    );

    // Set the add permissions
    foreach ($addperms as $module => $v) {
        $insertarray['values'][] = array($userid, $module, 'add');
    }

    // Set the edit permissions
    foreach ($editperms as $module => $v) {
        $insertarray['values'][] = array($userid, $module, 'edit');
    }

    // Set the delete permissions
    foreach ($deleteperms as $module => $v) {
        $insertarray['values'][] = array($userid, $module, 'delete');
    }

    // Delete all existing permissions for the user
    // Does not return an error if $userid is not found
    $dbc->delete('UserPermissions', 'UserId = '.$userid);

    if (!$dbc->insertMulti('UserPermissions',$insertarray)) {

        // Unable to update permissions
        $valid->add_error(get_lang('unabletosetpermissions').'<br />'.get_lang('reason').': '.$dbc->errorString());

        // Try to restore previous permissions
        if (is_array($existingpermissions)) {
            if (!$dbc->insertMulti('UserPermissions', $existingpermissions)) {
                // Unable to restore previous permissions.
                // This user is now limited to read-only access until the problem is corrected
                $valid->add_error(get_lang('unabletorestorepermissions').'<br />'.get_lang('reason').': '.$dbc->errorString());
            }
        }
    }
}
// END function SavePermissions()
/////

function UserPrefsForm($dowhat='') {

    global $dbc, $valid;

    $table = new HtmlTable();
    $form = new HtmlForm();

    // Get the default values
    $defaultvalues = $dbc->fetchAssoc($dbc->select('DefaultValues'));

    if ($dowhat == 'edituser') {
        $edituserid = $valid->get_value_numeric('userid',0);
        if ($edituserid > 0) {
            $edituserdata = $dbc->fetchAssoc($dbc->select('Users', 'UserID = '.$edituserid));
        } else {
            $valid->add_error(get_lang('InvalidUserID'));
        }
    } else {
        // Creates an associative array with the field names as keys
        $edituserdata = $dbc->list_fields('Users');

        // Clear the value of each key
        foreach ($edituserdata as $key => $value) {
            $edituserdata[$key] = '';
        }

        // Assign to users group
        $edituserdata['GroupID'] = 1;
    }

    // Check for Warnings
    if ($valid->is_warning() === true) {
        $valid->warnings_table();
    }
    // Check for errors
    if ($valid->is_error() === true) {
        $valid->errors_table();

        // Don't display the form until the errors have been taken care of. 
        return;
    }

    echo '
    <script language="javascript" type="text/javascript" src="includes/js/_md5.js"></script>
    <script type="text/javascript">
    function UpdateDateFormat() {

        document.getElementById("dateformat").value = document.getElementById("dateformatselect").value

    }

    function ResetDateFormat() {

        document.getElementById("dateformat").value = "'.$defaultvalues['DateFormat'].'";

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
            // Consider it valid
            return true;
        } else {
            document.getElementById("email").focus();
            alert("'.get_lang('InvalidEmailAddress').'");
            return false;
        }

        return false;
    }
    
    function validate() {
        // Some client side validation of the form data

        if (val_email() === false) {
            return false;
        }

        if (document.getElementById("dateformat").value == "") {
            document.getElementById("dateformat").focus();
            alert("'.get_lang('DateFormatRequired').'");
            return false;
        }

        var username = document.getElementById("username").value;

        // Only [a-z][A-Z][0-9] allowed
        username = username.replace(/[^a-z0-9]/gi, "")

        if (username == "") {
            alert("'.get_lang('UserNameRequired').'");
            return false;
        }

        // Make sure they match
        if (document.getElementById("password").value != document.getElementById("confirmpassword").value) {
            document.getElementById("password").focus();
            alert("'.get_lang('PasswordMismatch').'");
            return false;
        }

        if (document.getElementById("password").value != "") {
            if (document.getElementById("confirmpassword").value == "") {
                document.getElementById("confirmpassword").focus();
                alert("'.get_lang('PasswordRequired').'");
                return false;
            }
            document.getElementById("password").value = calcMD5(document.getElementById("password").value);
        } else {
            document.getElementById("password").focus();
            alert("'.get_lang('PasswordRequired').'");
            return false;
        }

        // All tests passed
        return true;
    }
    </script>
';

    // Set the hidden values for the form
    if ($dowhat == 'adduser') {
        $form->add_hidden(array(
            'ACTON' => 'savenewuser',
            'DateRegistered' => date("Y-m-d")
        ));
    } elseif ($dowhat == 'edituser') {
        $form->add_hidden(array(
            'ACTON' => 'savechanges',
            'userid' => $edituserid
        ));
    }

    // Start the form
    $form->onsubmit('return validate();');
    $form->start_form(kgGetScriptName(),'post','frmuserinfo');

    // Start the table
    $table->new_table('centered');

    // Header row
    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell('center');
    if ($dowhat == 'adduser') {
        echo '<b>'.get_lang('AddUser').'</b>';
    } elseif ($dowhat == 'edituser') {
        echo '<b>'.get_lang('EditUser').'</b>';
    }

    // User name
    $table->new_row();
    $table->set_width(165,'px');
    $table->new_cell();
    echo get_lang('UserName');
    if ($dowhat == 'adduser') {
        echo ' <span class="requiredinfogreenish" title="'.get_lang('RequiredInfo').'">*</span>';
    }
    $table->set_width(155,'px');
    $table->new_cell();
    if ($dowhat == 'edituser') {
        echo $edituserdata['UserName'];
    } else {
        $form->add_text('username');
    }

    if ($dowhat == 'adduser') {
        // Password
        $table->new_row();
        $table->new_cell();
        echo get_lang('Password').' <span class="requiredinfogreenish" title="'.get_lang('RequiredInfo').'">*</span>';
        $table->new_cell();
        $form->add_password('password');

        // Confirm Password
        $table->new_row();
        $table->new_cell();
        echo get_lang('PasswordConfirm').' <span class="requiredinfogreenish" title="'.get_lang('RequiredInfo').'">*</span>';
        $table->new_cell();
        $form->add_password('confirmpassword');
    }

    // First name
    $table->new_row();
    $table->new_cell();
    echo get_lang('FirstName');
    $table->new_cell();
    $form->add_text('firstname',$edituserdata['FirstName']);

    // Last name
    $table->new_row();
    $table->new_cell();
    echo get_lang('LastName');
    $table->new_cell();
    $form->add_text('lastname',$edituserdata['LastName']);

    // Email
    $table->new_row();
    $table->new_cell();
    echo get_lang('Email').' <span class="requiredinfogreenish" title="'.get_lang('RequiredInfo').'">*</span>';
    $table->new_cell();
    $form->add_text('email',$edituserdata['Email'],20);

    // Date registered
    $table->new_row();
    $table->new_cell();
    echo get_lang('DateRegistered');
    $table->new_cell();
    echo date("Y-m-d");

    // User group
    $table->new_row();
    $table->new_cell();
    echo get_lang('UserGroup');
    $table->new_cell();
    $form->add_select_db_autoecho('usergroup','SELECT * FROM UserGroups','GroupID',$edituserdata['GroupID'],'GroupName',false);

    $table->blank_row();

    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell('center');
    echo '<b>'.get_lang('Preferences').'</b>';

    // Language
    $table->new_row();
    $table->new_cell();
    echo get_lang('Language');
    $table->new_cell();
    if ($dowhat == 'edituser') {
        $lang = $edituserdata['Language'];
    } else {
        $lang = $defaultvalues['Language'];
    }
    $form->add_select_match_key('language',kgGetLangList(),$lang);

    // Skins
    $table->new_row();
    $table->new_cell();
    echo get_lang('Skin');
    $table->new_cell();
    if ($dowhat == 'edituser') {
        $skin = $edituserdata['Skin'];
    } else {
        $skin = $defaultvalues['Skin'];
    }
    $form->add_select_match_key('skin',kgGetSkinList(),$skin);

    /////
    // Date format
    $table->new_row();
    $table->new_cell('','top');
    echo get_lang('DateFormat').' <span class="requiredinfogreenish" title="'.get_lang('RequiredInfo').'">*</span>';
    $table->new_cell();
    // Text box
    if ($dowhat == 'edituser') {
        $df = $edituserdata['DateFormat'];
    } else {
        $df = $defaultvalues['DateFormat'];
    }
    $form->add_text('dateformat',$df);
    $form->add_button_generic('reset',get_lang('Reset'),'ResetDateFormat();');
    $table->new_row();
    $table->blank_cell();
    $table->new_cell();
    echo '<span class="smallfont">'.get_lang('DateFormatNote').'</span>'    ;
    $table->new_row();
    $table->blank_cell();
    $table->new_cell();
    // Select list
    $form->onsubmit('UpdateDateFormat();');
    $form->add_select_match_key('dateformatselect',kgGetDateFormatList(),$df);
    // Date format
    /////

    $table->blank_row();

    // Submit and reset buttons
    $table->new_row();
    $table->new_cell('center');
    $form->add_button_submit(get_lang('AddUser'));
    $table->new_cell('center');
    $form->add_button_reset(get_lang('Reset'));

    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell('center');
    echo '<span class="requiredinfogreenish" title="'.get_lang('RequiredInfo').'">* '.get_lang('RequiredInfo').'</span>';

    $table->blank_row();

    $table->end_table();

    $form->end_form();

}
// END function UserPrefsFormForm()
/////

if ($dbc->isConnectedDB() === true && $KG_SECURITY->isLoggedIn() === true) {

    $ACTON = $valid->get_value('ACTON');

    if ($ACTON == 'adduser') {
        // Display form to add a new user

        ListUsers(false);

        UserPrefsForm($ACTON);

    } elseif ($ACTON == 'edituser') {
        // Edit info about an existing user

        $dowhat = $valid->get_value('dowhat');

        ListUsers(false);

        if ($dowhat == 'EditPrefs') {
            UserPrefsForm($ACTON);
        } elseif ($dowhat == 'ModulePermissions') {
            ModulePermissionsForm();
        } elseif ($dowhat == 'ChangePassword') {
            ChangePasswordForm();
        } elseif ($dowhat == 'DeleteUser') {
            DeleteUserForm();
        }

    } elseif ($ACTON == 'savechanges') {
        // Save changes

        SaveChangesInfo();

        if ($valid->is_error() === true) {
            ListUsers(false);
            UserPrefsForm('edituser');
        } else {
            ListUsers();
        }

    } elseif ($ACTON == 'savechangespassword') {
        // Update users password

        SaveChangesPassword();

        if ($valid->is_error() === true) {
            ListUsers(false);
            ChangePasswordForm();
        } else {
            ListUsers();
        }

    } elseif ($ACTON == 'savepermissions') {
        // Save the updated user permissions to the database

        SavePermissions();

        ListUsers(false);
        ModulePermissionsForm();

    } elseif ($ACTON == 'savenewuser') {
        // Add a new user to the database

        SaveNewUser();

        if ($valid->is_error() === true) {
            ListUsers(false);
            UserPrefsForm('adduser');
        } else {
            ListUsers();
        }

    } else {
        // List all registered users

        ListUsers();

    }
} // if ($dbc->isConnectedDB() === true && $KG_SECURITY->isLoggedIn() === true)
?>
