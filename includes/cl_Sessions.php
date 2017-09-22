<?php
/**
 * Kravens session management class using PHP's $_SESSION global.
 *
 * See http://us3.php.net/manual/en/book.session.php for more info.
 *
 * Added: 2017-06-07
 * Modified: 2017-06-07
**/

class Sessions {

    public function sessionBegin() {
        /**
         * Start session management
         *
         * Checks if a session already exists. Creates a new session if none found
         *
         * Added: 2017-06-07
         * Modified: 2017-06-07
        **/

        if (array_key_exists('user', $_SESSION) && is_array($_SESSION['user'])) {
            // Session already created

            // Now what?
        } else {
            // Need to create a session

            $this->sessionCreate();
        }
    }
    // END public function sessionBegin()

    private function sessionCreate($user_id = false) {

        if ($user_id === false) {
            // Guest user
            $_SESSION['user'] = array();
        } else {
//            $query = $dbc->select('Users',
        }
    }
    // END private function sessionCreate()

    public function sessionEnd() {
        /**
         * End the session
         *
         * This function should only be called from logout.php.
         * Calling it from anywhere else will cause problems for the user.
         *
         * @param None
         *
         * @return Nothing
        **/

        global $KG_DBC;

        // Clear the logged in flag
        $KG_DBC->update('Users', 'UserID = '.$this->GetVar('userid'), array('loggedin' => 0));

        unset($_SESSION['user']);
    }

    public function GetVar($key) {
        /**
         * Gets the value of a key from $_SESSION['user']
         *
         * Added: 2017-06-07
         * Modified: 2017-06-07
         *
         * @param Required string $key The array key to get the data from
         *
         * @return mixed
        **/

        if (array_key_exists('user', $_SESSION) && is_array($_SESSION['user'])) {
            if (array_key_exists($key, $_SESSION['user'])) {
                // $key exists so return its data
                return $_SESSION['user'][$key];
            }
        }

        // $key was not found or $_SESSION['user'] does not exist
        return INVALID_DATA;
    }
    // END public function GetVar()

    public function SetVar($key, $value) {
        /**
         * Adds a key and value to $_SESSION['user']
         *
         * Added: 2017-06-07
         * Modified: 2017-06-07
         *
         * @param Required string $key   The array key that $value will be associated with
         * @param Required string $value The data to store
         *
         * @return Nothing
        **/

        $_SESSION['user'][$key] = $value;
    }
    // END public function SetVar()

    public function sessionStarted() {
        /**
         * Checks if a session has already been started
         *
         * Added: 2017-06-07
         * Modified: 2017-06-07
         *
         * @param None
         *
         * @return boolean
         *      (boolean)True: A session has been started meaning a user is logged in
         *      (boolean)False: No session started meaning the user has not logged in yet
        **/
        if (array_key_exists('user', $_SESSION) && is_array($_SESSION['user'])) {
            return true;
        } else {
            return false;
        }
    }
    // END public function sessionStarted()
}
?>
