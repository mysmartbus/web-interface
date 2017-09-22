<?php
/**
 * File: cl_validation.php
 *
 * Does some sanity checks on $_POST and $_GET variables.
 *
 * Added: 2007-06-04 by Nathan Weiler (ncweiler2@hotmail.com)
 * Modified: 2017-06-11 by Nathan Weiler (ncweiler2@hotmail.com)
**/
class validate {

    private $errors = array();

    function __construct() {
        /**
         * Intializes the class
         *
         * Added: 2017-06-11
         * Modified: 2017-06-11
         *
         * @param None
         *
         * @return None
        **/

        $this->clearErrors();
    }

    public function addError($msg) {
        /**
         * Adds a message to the error message storage array
         *
         * Added: 2017-06-11
         * Modified: 2017-06-11
         *
         * @param Required string $msg Error message to store
         *
         * @return None
        **/

        $this->errors[] = $msg;
    }

    public function clearErrors() {
        /**
         * Clears the error message storage array
         *
         * Added: 2017-06-11
         * Modified: 2017-06-11
         *
         * @param None
         *
         * @return None
        **/

        $this->errors = array();
    }

    public function displayErrors() {
        /**
         * Displays any error messages in the storage array
         *
         * Added: 2017-06-11
         * Modified: 2017-06-11
         *
         * @param None
         *
         * @return None
        **/

        if (count($this->errors) > 0) {
            echo '<table>';
            foreach ($this->errors as $key => $value) {
                echo '<tr><td style="border: 1px solid #FF0000;">'.$value.'</td></tr>';
            }
            echo '</table>';
        }
    }

    private function clean_tags($field) {
        /**
         * Sanitizes a $_POST or $_GET field to minimize code injection attacks
         *
         * Added: 2017-06-04
         * Modified: 2017-06-04
         *
         * @param Required string $field Name of field to retreive
         *
         * @return String
        **/

        // Get the field value
        $rv = (array_key_exists($field,$_POST) ? $_POST[$field] : '');
        if ($rv == '') {
            $rv = (array_key_exists($field,$_GET) ? $_GET[$field] : '');
        }

        // Sanatize it
        $rv = str_replace('<','&lt;',$rv);
        $rv = str_replace('>','&gt;',$rv);

        return $rv;
    }
    
    public function get_value($field, $default='') {
        /**
         * Get a value from $_POST or $_GET
         *
         * Added: 2007-06-04
         * Modified: 2017-06-04
         *
         * @param Required string $field Name of field to retreive
         * @param Optional string $default Value to return if $_POST[$field]
         *                        and $_GET[$field] are empty.
         *
         * @return String
        **/

        $value = $this->clean_tags($field);

        if ($value != '') {
            // Field value
            $rv = $value;
        } elseif ($default != '') {
            // Use user supplied value
            $rv = $default;
        } else {
            // Field value is blank or code logic above is wrong
            $rv = '';
        }

        return $rv;
    }

    public function get_value_numeric($field, $default=-1) {
        /**
         * Forces the value being returned to be a number
         *
         * Added: 2007-06-04
         * Modified: 2017-06-04
         *
         * @param Required string $field Name of field to retreive
         * @param Optional integer $default The number to return if $field does
         *                                  not return a number
         *
         * @return Integer
        **/

        // Make sure $default is a number
        if (!is_numeric($default)) {
            $default = -1;
        }

        // Get field value
        $data = $this->get_value($field);

        // Make sure $data is a number
        if (!is_numeric($data)) {
            $data = $default;
        }

        return $data;
    }

    public function hasErrors() {
        /**
         * if count($this->errors) > 0, returns true, otherwise returns false
         *
         * Added: 2007-06-12
         * Modified: 2017-06-12
         *
         * @param None
         *
         * @return boolean(True) or boolean(False)
        **/

        if (count($this->errors) > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    public function isEmail($email, $msg) {
        /**
         *  Validates whether $email is a valid e-mail address.
         *
         * In general, this validates e-mail addresses against the syntax in RFC 822, with the exceptions that
         * comments and whitespace folding and dotless domain names are not supported.
         *
         * Added: 2017-06-24
         * Modified: 2017-06-24
         *
         * @param Required string $email The email address to validate
         * @param Required string $msg   Error message to be displayed if validation fails
         *
         * @return boolean(True) or boolean(False)
        **/

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            $this->addError($msg);
            return false;
        }
    }

    public function setValue($data) {
        /**
         * Sets a $_POST field. Field will be created if it does not exist.
         *
         * Added: 2007-06-11
         * Modified: 2017-06-28
         *
         * @param Required array $data
         *                       Array keys are the field names
         *                       Array values will be the field values
         *
         * @return Nothing
        **/

        if (is_array($data)) {
            foreach ($data as $field => $value) {
                $_POST[$field] = $value;    
            }
        }
    }

    public function setValues($data) {
        /**
         * The 's' at the end of the function name makes everything better.
         *
         * If I'm adding an array of items, shouldn't I be calling setValues() instead of setValue()
         *
         * Added: 2007-07-01
         * Modified: 2017-07-01
         *
         * @param Required array $data
         *                       Array keys are the field names
         *                       Array values will be the field values
         *
         * @return Nothing
        **/

        $this->setValue($data);
    }
}

/**
 * Change log:
 *
 * 2017-07-01:
 *      -Added function setValues()
 *
 * 2017-06-28:
 *      -Function setValue() now expects an array to be given
 *      -Changed function clearErrors() from private to public
 *
 * 2017-06-25:
 *      -Renamed function set_value() to setValue()
**/
?>
