<?php
/**
 * The login screen
 *
 * Added: 2017-06-11
 * Modified: 2017-07-08
**/

function displayform () {
    /**
     * Display the login form.
     *
     * Added: 2017-06-11
     * Modified: 2017-07-04
     *
     * @param None
     *
     * @return Nothing
    **/

    global $valid, $RP;

    $form = new HtmlForm();
    $table = new HtmlTable();

    ?>
    <script language="javascript" type="text/javascript">
        function doLogin() { 

            /////
            // Check user name
            var username = document.frmlogin.username.value;
            var usernamelen = username.length;

            if (username == "") {
                alert('<?php echo get_lang('usernamerequired'); ?>');
                return false;
            }

            // Check length
            if (usernamelen < 5 || usernamelen > 20) {
                alert('<?php echo get_lang('usernamelength'); ?>');
                return false;
            }

            // Only [a-z][A-Z][0-9] allowed
            username = username.replace(/[^a-zA-Z0-9]+/g,'');
            if (usernamelen != username.length) {
                // Invalid characters found
                alert('<?php echo get_lang('usernamevalidchars'); ?>');
                return false;
            }

            /////
            // Check password
            var password = document.frmlogin.password.value;
            var passwordlen = password.length;

            if (password == "") {
                alert('<?php echo get_lang('passwordrequired'); ?>');
                return false;
            }

            // Check length
            if (passwordlen < 5 || passwordlen > 20) {
                alert('<?php echo get_lang('passwordlength'); ?>');
                return false;
            }

            // Only [a-z][A-Z][0-9] allowed
            password = password.replace(/[^a-zA-Z0-9]+/g,'');
            if (passwordlen != password.length) {
                // Invalid characters found
                alert('<?php echo get_lang('passwordvalidchars'); ?>');
                return false;
            }

            return true;
        } 
    </script>
    <center>
    <?php

    $valid->displayErrors();

    $form->add_hidden(array(
        'validatedata' => 'yes'
    ));
    $form->onsubmit('return doLogin();');
    $form->start_form(kgGetScriptName(),'post','frmlogin');

    $table->new_table();

    $table->new_row();
    $table->new_cell('centertext');
    echo get_lang('logintablename');

    $table->blank_row(1);

    // Username
    $table->new_row();
    $table->new_cell();
    echo get_lang('username');

    $table->new_row();
    $table->new_cell();
    $form->add_text('username','',20);

    // Password
    $table->new_row();
    $table->new_cell();
    echo get_lang('password');

    $table->new_row();
    $table->new_cell();
    $form->add_password('password','',34);

    $table->blank_row(1);

    $table->new_row();
    $table->new_cell();
    echo '<div style="width:100%;"><div style="float:left;">';
    $form->add_button_submit(get_lang('log_in'));
    echo '</div><div style="float:right;">';
    $form->add_button_generic('cancel',get_lang('cancel'),'location.href="'.$RP.'/";');
    echo '</div></div>';

    $table->end_table();
    $form->end_form();

    ?>
    </center>
    <script type="text/javascript" language="JavaScript">
        document.forms['frmlogin'].elements['username'].focus();
    </script>
    <?php

}
// END function displayform()
/////

function validatedata() {
    /**
     * Server side validation of login data.
     *
     * Added: 2017-06-11
     * Modified: 2017-07-08
     *
     * @param None
     *
     * @return boolean(true) or boolean(false)
    **/

    global $valid, $KG_SESSION;

    $dbc = new genesis();
    $db = $dbc->connectServer();
    $dbc->connectDatabase('Kraven', 0.66);

    $username = $valid->get_value('username');
    $password = $valid->get_value('password');

    if ($username == '') {
        $valid->addError(get_lang('usernamerequired'));
        return false;
    }

    if ($password == '') {
        $valid->addError(get_lang('passwordrequired'));
        return false;
    }

    // Returns the users password if there is a user named $username
    $query = $dbc->select('Users', 'UserName = "'.$username.'"', 'Password');

    // $dbc->numRows($query) should only return 0 or 1 because usernames must be unique.
    // Any other value requires attention from an admin.
    if ($dbc->numRows($query) == 1) {

        $db_password = $dbc->fieldValue($query);

        if (!password_verify($password, $db_password)) {
            $valid->addError(get_lang('invaliduserpasswd'));
            return false;
        }

        // Get info for the user to validate login
        $row = $dbc->fetchAssoc($dbc->select('Users', 'UserName = "'.$username.'" AND Password = "'.$db_password.'"', array('Disabled', 'loggedin', 'UserID')));

        if ($dbc->numRows($query) != 1) {
            // Username and password combination not in database
            $valid->addError(get_lang('invaliduserpasswd'));
            return false;
        }

        if ($row['Disabled'] == 1) {
            // Account is disabled/locked. Login is not allowed

            $valid->addError(get_lang('invaliduserpasswd'));
            return false;
        }

        // Disabled 2017-07-09
        // TODO: Need to implement a custom PHP session handler that can
        // set this value to 0 automatically when the session expires.
//        if ($row['loggedin'] == 1) {
//            // Already logged in on some other device
//
//            $valid->addError(get_lang('alreadyloggedin'));
//            return false;
//        }

        // Generate session ID
        mt_srand((double)microtime() * 1000000);
        $sessionid = md5(uniqid(mt_rand(),1));

        // Update users last login date and time, session id and loggedin flag
        $data = array(
            'LastLoginDate' => date("Y-m-d"),
            'LastLoginTime' => date("H:i:s"),
            'SesID' => $sessionid,
            'loggedin' => 1
        );
        if (!$dbc->update('Users', 'UserID = '.$row['UserID'], $data)) {
            $valid->addError(get_lang('loginfaileddb'));
            return false;
        }

        // Login succeeded

        // Set some session variables
        $KG_SESSION->SetVar('userid',$row['UserID']);
        $KG_SESSION->SetVar('sessionid',$sessionid);

        return true;

    } else {

        $valid->addError(get_lang('invaliduserpasswd'));
        return false;

    }
    // END if ($dbc->numRows($query)) == 1)

}
// END function validatedata()
/////

if($KG_SESSION->sessionStarted()) {
    // A session has already been started
    // Return user to whatever module they had loaded when they tried to login

    if ($KG_MODULE_NAME == 'login') {
        $KG_MODULE_NAME = 'menu';
    }

    echo '<meta http-equiv="Refresh" content="0; url='.$RP.'/index.php?KG_MODULE_NAME='.$KG_MODULE_NAME.'">';

} else {
    // The user is not logged in

    $validatedata = $valid->get_value('validatedata');

    if ($validatedata == 'yes') {
        // Validate log in info

        // To avoid loops
        $valid->setValue(array('validatedata' => ''));

        $good = validatedata();
        if ($good) {
            // Reload index.php so we can return to where we logged in from

            echo '<meta http-equiv="Refresh" content="0; url='.$RP.'/index.php?KG_MODULE_NAME='.$KG_MODULE_NAME.'">';
        } else {
            // Display login form
            displayform();
        }

    } else {
        // Display login form
        displayform();
    }
    // END if ($validatedata == 'yes')
}
// END if($KG_SESSION->sessionStarted() === false)
?>
