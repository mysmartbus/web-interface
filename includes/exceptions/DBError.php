<?php
/**
 * Database error handling
 *
 * Added: 2015-05-14 by Nathan Weiler (ncweiler2@hotmail.com)
 * Updated: 2015-05-14 by Nathan Weiler (ncweiler2@hotmail.com)
**/

class DBError extends Exception {

    private $_message = '';

    public function __construct($message = '') {
        /**
         * @param Optional string $message A description of the problem that you want
         *                            the user to see.
        **/
        $this->_message = $message;
    }

    private function dbMissingMysqli() {
    }
    // END private function dbMissingMysqli()
    /////

    private function dbUnexpectedException() {
    }
    // END private function dbUnexpectedException()
    /////

    public function handler($e, $code, $sql = '', $mysqlerrnum = 0, $mysqlerrstr = '') {
        /**
         * Uses $code to determine which functions to call and
         * what info to display
         *
         * @param Required Exception $e
         * @param Required integer $code From /includes/exceptions/Defines_Exceptions.php
         * @param Optional string $sql The SQL statement from $dbc->getQueryString()
        **/

        global $kgShowSqlInException, $kgShowBackTraceInException;

        // Log the exception in the SystemMessages database
        $this->logException($e->getTrace());

        echo 'Message: '.$this->_message;
        echo '<br/>Error Code: '.$code;

        if ($kgShowSqlInException === true && $sql != '') {
            echo '<br/><br/>Query Received<br/>'.$sql;
        }

        if ($kgShowBackTraceInException === true) {
            echo '<br/><br/>Backtrace<br/>'.nl2br($this->getRedactedTraceAsString($e));
        }

        switch ($code) {
            case DB_DATABASE_MISSING:
                $this->dbDatabaseMissing();
                break;
            case DB_BAD_QUERY:
                $this->dbBadQuery();
                break;
            case DB_MISSING_MYSQLI:
                $this->dbMissingMysqli();
                break;
            default:
                $this->dbUnexpectedException();
        }

        echo '<br/>$mysqlerrnum: '.$mysqlerrnum;
        echo '<br/>$mysqlerrstr: '.$mysqlerrstr;

        exit(1);
    }
    // END public function handler()
    /////

    private function getRedactedTrace(Exception $e) {
        return array_map(function ($frame) {
            if (isset($frame['args'])) {
                $frame['args'] = array_map(function ($arg) {
                    return is_object($arg) ? get_class($arg) : gettype($arg);
                }, $frame['args']);
            }
            return $frame;
        }, $e->getTrace());
    }
    // END private function getRedactedTrace()
    /////

    private function getRedactedTraceAsString(Exception $e) {
        $text = '';

        foreach ($this->getRedactedTrace($e) as $level => $frame) {
            if (isset($frame['file']) && isset($frame['line'])) {
                $text .= "#{$level} {$frame['file']}({$frame['line']}): ";
            } else {
                // 'file' and 'line' are unset for calls via call_user_func (bug 55634)
                // This matches behaviour of Exception::getTraceAsString to instead
                // display "[internal function]".
                $text .= "#{$level} [internal function]: ";
            }

            if (isset($frame['class'])) {
                $text .= $frame['class'] . $frame['type'] . $frame['function'];
            } else {
                $text .= $frame['function'];
            }

            if (isset($frame['args'])) {
                $text .= '(' . implode(', ', $frame['args']) . ")\n";
            } else {
                $text .= "()\n";
            }
        }

        $level = $level + 1;
        $text .= "#{$level} {main}";

        return $text;
    }
    // END private function getRedactedTraceAsString()
    /////

    private function logException($eGetTrace) {
        /**
         * Save info about the exception to a database for
         * easier debugging
         *
         * Added: 2015-05-26
         * Updated: 2015-05-26
         *
         * @param Required array $eGetTrace The output of Exception->getTrace()
         *
         * @return Nothing
        **/

        // Retreive and format info
        $message = $this->_message;
        $method = $eGetTrace[0]['class'].'::'.$eGetTrace[0]['function'];
        $file = $eGetTrace[0]['file'];
        $line = $eGetTrace[0]['line'];

        // Call the message logging system and save to database
        $sm = new SystemMessages();
        $sm->AddEntry($message,$method,$file,__CLASS__,$line);
    }
    // END private function logException()
    /////
}

class DBQueryError extends DBError {
    /**
     * Handles query errors from select(), insert(), update(), myslqi_fetch*, etc
     *
     * Added 2015-04-16 by Nathan Weiler (ncweiler2@hotmail.com)
     * Updated: 2015-05-16 by Nathan Weiler (ncweiler2@hotmail.com)
    **/

    protected function dbBadQuery() {
        echo '<br/><br/>bad query in some function';
    }
    // END private function dbBadQuery
    /////
}

class DBConnectionError extends DBError {
    /**
     * Handles errors that occur when attempting to connect to the
     * database or server or if the connection is unexpectedly interrupted.
     *
     * Added 2015-04-16 by Nathan Weiler (ncweiler2@hotmail.com)
     * Updated: 2015-05-16 by Nathan Weiler (ncweiler2@hotmail.com)
    **/

    protected function dbDatabaseMissing() {
        echo '<br/>dbDatabaseMissing()';
    }
    // END private function dbDatabaseMissing()
    /////
}
?>
