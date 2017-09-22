<?php
/**
 * Logs out the user by clearing the session ID from the database, clearing $userinfo and ending the PHP session
 *
 * Last Updated: 2017-07-08
**/

// User login info is stored in the main database
$dbc = new genesis();
$db = $dbc->connectServer();
$dbc->connectDatabase('Kraven', 0.66);

// Logout the user by clearing the session info and setting the loggedin flag to 0
if ($dbc->update('Users', 'UserID='.$KG_SESSION->getVar('userid'), array('SesID' => '', 'loggedin' => 0))) {

    // Clear the $userinfo array
    $userinfo = array(
        'GroupID' => GRP_GUEST,
        'UserID' => 0
    );

    // End the session
    $KG_SESSION->sessionEnd();

    // Remove the cookie
    setcookie('inuse', 'yes', time() - 3600, '/', 'rewrite.kraven.srg');

} else {

    $valid->addError(get_lang('LogOutFailed'));

}

if ($valid->hasErrors()) {
    // Error(s) occured during the logout process
    $valid->displayErrors();
} else {
    // Display a message incase the redirect fails
    echo get_lang('LogOutSuccess').'<br/><br/>';

    // Reload index.php
    echo '<meta http-equiv="Refresh" content="0; url='.$RP.'index.php">';
}
?>
