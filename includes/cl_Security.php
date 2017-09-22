<?php
/**
 * File: cl_security.php
 *
 * Added: 2017-06-07 by Nathan Weiler (ncweiler2@hotmail.com)
 * Modified: 2017-06-12 by Nathan Weiler (ncweiler2@hotmail.com)
 *
 * Checks if the user has permission to do something
 *
 * The possible permissions are 'add', 'edit', and 'delete'.
 *
**/

class Security {

    private $_checkuserid;

    function __construct() {

        // Set default values
        $this->reset_security();
    }

    public function checkUserId($id) {
        /**
         * Tells $this->haspermission() to compare $id with $userinfo['UserID']
         *
         * Added: 22017-06-07
         * Modified: 2017-07-04
         *
         * @param Required integer $id The user ID number listed as the owner of
         *                             the item being accessed
         *                             This is not $userinfo['UserID']. That is
         *                             the ID number of the person who logged in.
         *
         * @return Nothing
        **/

        if (is_numeric($this->_checkuserid)) {
            $this->_checkuserid = $id;
        } else {
            $this->_checkuserid = false;
        }
    }

    public function hasPermission($action) {
        /**
         * Checks if the user has permission to do the requested action
         *
         * Reminder: All messages will be visible unless you call $this->hideMsgs()
         *           before calling this function.
         *
         * Added: 2017-06-07
         * Modified: 2017-06-12
         *
         * @param Required $action The action to be performed. See list at top of file.
         *
         * @return boolean
         *                 True: User has permission to do the requested action
         *                 False: User does not have permission to do the requested action
         *                        Also prints a message saying why permission was denied
        **/

        global $KG_DBC, $userinfo, $KG_MODULE_NAME;

        if (!$this->isLoggedIn()) {
            // User is not logged in so don't bother doing anything else
            if (!$this->_hidemsgs) echo '<center>'.get_lang('notloggedin').'<br/>'.get_lang('nopermissionpage').'</center>';
            return false;
        }

        // Convert to lower case
        $action = strtolower($action);

        if ($action != 'add' && $action != 'edit' && $action != 'delete') {
            // An invalid action was given so permission denied
            return false;
        }

        // Check if the logged in user is the owner/creator of the item being accessed
        // The lowest possible user ID is 1
        if (is_numeric($this->_checkuserid) && $this->_checkuserid > 0) {
            if ($this->_checkuserid != $userinfo['UserID']) {
                // User doesn't own this item
                if ($this->_hidemsgs === false) echo '<center>'.get_lang('notyours').'</center>';
                return false;
            }
        } else {
            // Invalid User ID number given
            $this->_checkuserid = false;
        }

        $query = $KG_DBC->select('`'.$KG_DBC->getMainDatabase().'`.`UserPermissions`', array('UserID' => $userinfo['UserID'], 'Action' => $action, 'Module' => $KG_MODULE_NAME));

        if ($KG_DBC->numRows($query) > 0) {
            // Permission granted
            return true;
        } else {
            // Permission denied
            if ($this->_hidemsgs === false) echo '<center>'.get_lang('nopermissionpage').'</center>';
            return false;
        }

        // Resets $this->_checkuserid and $this->_hidemsgs so they are ready for the next call
        $this->reset_security();
    }
    // END function hasPermission()

    public function hideMsgs() {
        /**
         * Hides the messages from $this->hasPermission()
         *
         * Reminder: All messages will remain hidden until you call $this->showMsgs()
         *
         * Added: 2017-06-07
         * Modified: 2017-06-07
         *
         * @param None Calling this function changes a variable from (bool)False to (bool)True
         *
         * @return Nothing
        **/

        $this->_hidemsgs = true;

    }

    public function isAdmin() {
        /**
         * Checks if the user is a member of the admins group
         *
         * Added: 2017-06-07
         * Modified: 2017-06-07
         *
         * @param None
         *
         * @return boolean(True) or boolean(False)
        **/

        global $KG_DBC, $userinfo;

        // Retrieve group name
        $group = $KG_DBC->fieldValue($KG_DBC->select('`'.$KG_DBC->getMainDatabase().'`.`UserGroups`', 'GroupID = '.$userinfo['GroupID'], 'GroupName'));

        // The triple equals (===) is intentional
        if ($group === 'Admins') {
            return true;
        }

        return false;
    }

    public function isLoggedIn() {
        /**
         * Checks if the user is logged in
         *
         * Added: 2017-06-07
         * Modified: 2017-06-18
         *
         * @param None
         *
         * @return boolean(True) or boolean(False)
        **/

        if (array_key_exists('user', $_SESSION) && is_array($_SESSION['user'])) {
            // Logged in
            return true;
        }

        // Logged out
        return false;
    }
    // END function isLoggedIn()

    public function isOwner($id=0) {
        /**
         * Check if the logged in user is the owner/creator of the item being accessed
         *
         * Added: 2017-06-07
         * Modified: 2017-07-04
         *
         * @param Optional integer $id User ID to compare with $userinfo['UserID']
         *
         * @return boolean
        **/

        global $userinfo;

        if (!is_numeric($id)) {
            return false;
        }

        // The lowest possible user ID is 1
        if ($id < 1) {
            $id = $this->_checkuserid;
        }        
        
        // Triple equals intentional (compare value and type)
        if ($id === $userinfo['UserID']) {
            // User is owner of item
            return true;
        } else {
            // User does not own this item
            return false;
        }

        return false;
    }
    // END function isOwner()

    public function showMsgs() {
        /**
         * Shows the messages from $this->hasPermission()
         *
         * Reminder: All messages will remain visible until you call $this->hideMsgs()
         *
         * Added: 2017-06-07
         * Modified: 2017-06-07
         *
         * @param None
         *
         * @return Nothing
        **/

        $this->_hidemsgs = false;

    }

    private function reset_security() {
        /**
         * Resets $this->_checkuserid and $this->_hidemsgs so they are ready for the next call
         *
         * Added: 2017-06-07
         * Modified: 2017-06-07
        **/

        $this->_checkuserid = false;
        $this->_hidemsgs = false;

    }
}
?>
