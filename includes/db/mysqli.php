<?php
/**
 * MySQLI specific database access code
 *
 * This class uses mysqli's procedural interface/style
 *
 * Added: 2015-12-12
 * Modified: 2017-01-08
 *
 * History:
 *      2017-05-11: Corrected variable usage in connectDatabase()
 *      2017-05-10: Removed unused code from __construct()
 *      2017-02-12: Removed unused variables '$databaseName' and '$serverName'
 *                  Added function 'getCurrentServer()'
 *      2017-01-11: Function makeList - Added comments to make it easier to see the
 *                  difference between the the 'between' and 'not between' checks
 *      2017-01-08: Function 'insertMulti()' correctly returns result
 *      2017-01-07: Added function 'createTable()'
 *                  Changed function 'tableExists()' from private to public
 *
 * The following functions are from MediaWiki 1.24.2 and were modified for use with Kraven
 *      makeGroupByWithHaving()
 *      makeList()
 *      makeSelectOptions()
 *      makeOrderBy()
 *
 * NOTE:
 *    Use "find . -type f -name "*.php" -exec sed -i 's/<oldname>/<newname>/g' {} +" to update
 *    the websites php files as needed
**/

// Simplifies creation of SQL queries.
define('LIST_COMMA', 0);
define('LIST_AND', 1);
define('LIST_SET', 2);
define('LIST_NAMES', 3);
define('LIST_OR', 4);

class genesis {

    private $databaseVersion = 0 ;      // Version of the database needed by the PHP script
    private $link;                      // The object which represents the connection to a MySQL Server
    private $debug = false;             // Debug level to use. False disables debugging
    private $connectedServer = false;   // Are we connected to the server?
    private $connectedDatabase = false; // Are we connected to the database?
    private $userName = '';             // Name of user to connect to server as
    private $userPassword = '';         // Password to use when connecting to server
    private $databaseName = '';         // Database we are connected to
    private $maindbname = '';           // The main database for this connection (usernames and passwords, common settings/values, etc.)
    private $queryCount = 0;            // Number of queries made
    private $queryString = '';          // The last SQL statement executed
    private $prevDatabaseName = '';     // Name of the database that was connected before calling $this->connectDatabase()
    private $mysqlnd_switch = '';       // Tells the mysqlnd library which server to send the select/update/insert query to

    //Get'rs
    public function getCurrentDatabaseName()    { return $this->databaseName; }
    public function getCurrentDatabaseVersion() { return $this->databaseVersion; }
    public function getMainDatabase()           { return $this->maindbname; }
    public function getQueryString()            { return $this->queryString; } // Returns the most recently used SQL query string
    public function isConnectedDB()             { return $this->connectedDatabase; }
    public function isConnectedSrvr()           { return $this->connectedServer; }
    public function getCurrentServer()          { return $this->serverName; }

    function __construct() {
        // Added: 2015-12-12
        // Updated: 2017-05-10

        // Fail now
        // Otherwise we get a suppressed fatal error, which is very hard to track down
        try {
            if (!function_exists('mysqli_init')) {
                // PHP does not have support mysqli enabled
                throw new DBConnectionError(get_lang(DB_MISSING_MYSQLI));
            }
        } catch (DBConnectionError $e) {
            $e->handler($e, DB_MISSING_MYSQLI);
        }
    }

    private function buildBetween($field, $min, $max, $addnot = false) {
        /**
         * Finds fields with a value that is equal to or greater than $min and
         * equal to or less than $max.
         *
         * Added: 2015-05-13
         * Modified: 2015-05-28
         *
         * @param Required string $field Name of field to search
         * @param Required string|int $min Minimum value to search for
         * @param Required string|int $max Maximum value to search for
         * @param Optional boolean $addnot Default is false
         *                      True: Use NOT BETWEEN()
         *                      False: Use BETWEEN()
         *
         * @return string
        **/

        if ($this->debug >= 3) {
            $data = array(
                'Field' => $field,
                'Min' => $min,
                'Max' => $max
            );
            $msg = serialize($data);
            $sm = new SystemMessages();
            $sm->AddEntry(__FUNCTION__.' '.$msg, __METHOD__, __FILE__, 'DBDebug');
        }

        return '('.$field.($addnot === false ? '' : ' NOT').' BETWEEN '.$this->quote($min).' AND '. $this->quote($max).')';
    }

    private function buildConcatWS($sep, $strings, $alias) {
        /**
         * Combines strings together to form a single string with separator
         *
         * Added: 2015-05-13
         * Modified: 2015-05-28
         *
         * @param Required string $sep The seperator between the strings
         * @param Required array $strings The strings be be combined
         * @param Required string $alias Name the combined fields will be referenced by
         *
         * @return string
        **/

        if ($this->debug >= 3) {
            $data = array(
                'Sep' => $field,
                'Strings' => $strings,
                'Alias' => $alias
            );
            $msg = serialize($data);
            $sm = new SystemMessages();
            $sm->AddEntry(__FUNCTION__.' '.$msg, __METHOD__, __FILE__, 'DBDebug');
        }

        return 'CONCAT_WS("'.$sep.'", '.implode( ',', $strings ) . ') AS '.$alias;
    }

    private function buildMatchAgainst($fields, $expression, $modifier = '', $alias = '') {
        /**
         * Creates a Match..Against condition
         *
         * Added: 2015-05-26
         * Modified: 2015-05-28
         *
         * @param Required string|array $fields Field names to search through.
         *                              List must match exactly the column list in a
         *                              FULLTEXT index definition for the table, unless
         *                              $modifier equals 'IN BOOLEAN MODE'. Boolean-mode
         *                              searches can be done on nonindexed columns,
         *                              although they are likely to be slow.
         * @param Required string $expression The word or phrase to search for
         * @param Optional string $modifier Must be one of the following:
         *                        IN NATURAL LANGUAGE MODE
         *                        IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION
         *                        IN BOOLEAN MODE
         *                        WITH QUERY EXPANSION
         * @param Optional string $alias Name resulting field will be referenced by
         *                        NOTE: This is a required parameter if called by self::selectSQLText()
         *
         * @return string
        **/

        if ($this->debug >= 3) {
            $data = array(
                'Fields' => $fields,
                'Expression' => $expression,
                'Modifier' => $modifier,
                'Alias' => $alias
            );
            $msg = serialize($data);
            $sm = new SystemMessages();
            $sm->AddEntry(__FUNCTION__.' '.$msg, __METHOD__, __FILE__, 'DBDebug');
        }

        if (is_array($fields)) {
            $fields = implode(',',$fields);
        }

        $ma = 'MATCH('.$fields.') AGAINST("'.$expression.'"';

        if ($modifier != '') {
            $ma .= ' '.strtoupper($modifier);
        }

        $ma .= ')';

        if ($alias != '') {
            $ma .= ' AS '.$alias;
        }

        return $ma;
    }

    public function connectDatabase($dbname='', $requiredDbVersion=0) {
        /**
         * Attempts to connect to the requested database
         *
         * Added: 2015-12-12
         * Modified: 2017-05-11
         *
         * @param Optional string $dbname Name of database to connect to
         * @param Optional double $requireddbversion Version of the database needed
         *
         * @return boolean
         *         (boolean)True: Database exists AND is the correct version
         *         (boolean)False: Database does not exist, is the wrong version
         *                         or no server is connected
        **/

        if ($this->maindbname == '') {
            $this->setMainDatabase($dbname);
        }

        // If not connected to a server, it will be
        // impossible to connect to a database
        if ($this->connectedServer !== true) {
            return false;
        }

        if ($this->databaseName == $dbname && $this->connectedDatabase === true) {
            // Do nothing since we are already connected to the requested database
            return true;
        }

        if ($this->databaseExist($dbname) === false) {
            // The requested database does not exist on the current server
            echo '<center>'.get_lang('DBNotFound').'<br>'.get_lang('DBExistPerms').'<br><br>'.get_lang('DBRequested').' '.$dbname.'</center>';

            if ($this->errorNumber() != 0) {
                echo get_lang('dberradditionalinfo').'<br>';
                echo get_lang('DBErrNum').$this->errorNumber().'<br>';
                echo get_lang('DBErrStr').$this->errorString().'<br><br>';
            }

            $this->connectedDatabase = false;
            return false;
        }

        // Now connect to the database
        if (mysqli_select_db($this->link, $dbname) !== true) {
            // Connection failed

            $this->connectedDatabase = false;
            $this->databaseName = '';
            $this->databaseVersion = 0;

            return false;

        } else {
            // Connection established

            $this->connectedDatabase = true;
            $this->prevDatabaseName = $this->databaseName;
            $this->prevDatabaseVersion = $this->databaseVersion;
            $this->databaseName = $dbname;

            // Get version number of installed database
            $this->databaseVersionInstalled();

            if ($requiredDbVersion > 0 && $requiredDbVersion != $this->databaseVersion) {
                // Database versions do not match

                // Wrong version of database installed
                echo '<center>'.get_lang('DBWrongVersionInstalled').'<br/><br/>'.get_lang('DBVersionInstalled').': '.$this->databaseVersion.'<br/>';
                echo get_lang('DBVersionRequired').': '.$requiredDbVersion.'</center>';

                if ($this->errorNumber() != 0) {
                    echo get_lang('dberradditionalinfo').'<br>';
                    echo get_lang('DBErrNum').$this->errorNumber().'<br>';
                    echo get_lang('DBErrStr').$this->errorString().'<br><br>';
                }

                $this->connectedDatabase = false;
                return false;
            }

            return true;
        }

    }

    public function connectPreviousDatabase() {
        /**
         * Reconnect to the database we were connected to before calling $this->connectDatabase()
         *
         * Added: 2015-12-12
         * Modified: 2015-12-12
         *
         * @param None
         *
         * @return Nothing
        **/

        if ($this->prevDatabaseName != '') {
            $this->connectDatabase($this->prevDatabaseName, $this->prevDatabaseVersion);
        }
    }

    public function connectServer($server = '', $user = '', $passwd = '') {
        /**
         * Attempt to connect to the requested database server
         *
         * Added: 2015-12-12
         * Modified: 2017-05-11
         *
         * @param Optional string $server Host name or IP address of server to connect to
         * @param Optional string $user Name of user you want to connect as
         * @param Optional string $passwd Password for $user
         *
         * @return Returns an object which represents the connection to the MySQL Server
        **/

        // See /includes/db/_config.php for values
        global $kgDatabase;

        if ($server == '') {
            $server = $kgDatabase['server'];
        }
        $this->serverName = $server;

        if ($user == '') {
            $user = $kgDatabase['user'];
        }
        $this->userName = $user;

        if ($passwd == '') {
            $passwd = $kgDatabase['passwd'];
        }
        $this->userPassword = $passwd;

        // Connect to server
        $this->link = @mysqli_connect($server, $user, $passwd);

        // Check for successful connection
        if (!$this->link) {
            die('Connect Error ('.mysqli_connect_errno().') '.mysqli_connect_error());
        } else {
            $this->connectedServer = true;
        }

        return $this->link;
    }

    public function createTable($table, $schema = '', $template = '') {
        /**
         * Creates a table in the current database if the table
         * does not already exist.
         *
         * Added: 2017-01-07
         * Modified: 2017-01-07
         *
         * @param Required string $table Name of the table to create
         *
         * @param array $schema Define the tables columns and keys
         *              Required IF $template is empty
         *              Not used IF $template is not empty
         *
         *      $schema is a nested array using named keys to sort the data used
         *      to create the table.
         *
         *      $schema['fields']: (array) The fields that make up the table
         *            `stock_id` int NOT NULL AUTO_INCREMENT,
         *            `stock_name` tinytext,
         *            `stock_dateadded` date DEFAULT '0000-00-00',
         *
         *      $schema['primarykey']: (string) The tables primary key. Each table
         *            can have only one primary key.
         *
         *      $schema['indexes']: (arry) Used by MySQL to speed up operations done on this table
         *
         *      $schema['engine']: (string) The storage engine used for this table
         *                  Available choices are: InnoDB, MyISAM, MEMORY, CSV, ARCHIVE, EXAMPLE, FEDERATED, HEAP, MERGE, NDBCLUSTER
         *
         *      A valid example of using $schema
         *
         * @param Optional string $template Copy the schema of this table into $table
         *
         * @return
         *     (boolean)True: table created or already exists
         *     (boolean)False: unable to create table
        **/

        if ($this->tableExists($table) === true) {
            // A table with that name already exists.
            // This does not mean that the table schema is correct.
            return true;
        }

        if ($template == '' && $schema == '') {
            // One of these must have data
            return false;
        }

        if (is_array($schema) === true && $template == '') {
            foreach ($schema as $k => $v) {
                $tableschema += $v.',';
            }

            // Remove trailing comma (,)
            $tableschema = substr($tableschema,0,strlen($tableschema)-1);
        }

        if ($template != '') {
            $query = 'CREATE TABLE '.$table.' LIKE '.$template;
        } else {
            $query = 'CREATE TABLE '.$table.' ('.$tableschema.')';
        }

        if ($this->query($query) === true) {
            // Table created
            return true;
        }

        // Something went wrong
        return false;
    }

    private function databaseExist($db) {
        /**
         * Checks if the database exists on the current server
         *
         * Added: 2017-01-06
         * Modified: 2017-01-06
         *
         * @param None
         *
         * @return
         *     (boolean)True: database found
         *     (boolean)False: database missing
        **/

        if ($db == '') {
            // Can't run the search unless we are given a database name
            return false;
        }

        $query = $this->query('SHOW DATABASES');
        while ($row = $this->fetchRow($query)) {
            if ($row[0] == $db) {
                return true;
            }
        }

        return false;
    }

    private function databaseVersionInstalled() {
        /**
         * Gets the version number of the installed database and saves it to $this->databaseVersion
         *
         * Added: 2015-12-12
         * Modified: 2017-03-02
         *
         * @param None
         *
         * @return Nothing
        **/

        // Old databases have a capitalized 'Settings', new/updated databases have a lower case 'settings'
        if ($this->tableExists('Settings') === true) {
            $this->databaseVersion = $this->fieldValue($this->select('`'.$this->databaseName.'`.`Settings`', '', 'VersionDB'));
        } elseif ($this->tableExists('settings') === true) {
            $this->databaseVersion = $this->fieldValue($this->select('`'.$this->databaseName.'`.`settings`', '', 'versiondb'));
        } else {
            $this->databaseVersion = 0;
        }
    }

    public function delete($table, $conditions) {
        /**
         * Delete a row of data from a table
         *
         * Added: 2015-12-13
         * Modified: 2015-12-13
         *
         * @param Required string       $table     Name of table to delete data from
         * @param Required string|array $condition The WHERE condition to use
         *                                   See $this->makeList() for format
         *
         * @return mixed
         *    FALSE on failure
         *    For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries mysqli_query() will return a mysqli_result object
         *    For other successful queries mysqli_query() will return TRUE
        **/

        $query = "DELETE FROM $table WHERE ";
        $query .= $this->makeList($conditions);

        return $this->query($query);
    }

    public function disconnectServer() {
        /**
         * Disconnect from the server
         *
         * Added: 2015-12-12
         * Modified: 2015-12-13
         *
         * @param None
         *
         * @return
         *     (boolean)True: successfully disconnected
         *     (boolean)False: something went wrong
        **/

        if ($this->connectedServer === true) {
            $this->connectedServer = mysqli_close($this->link);

            if ($this->connectedServer === false) {
                $this->databaseName = '';
                $this->connectedDatabase = false;
            }

            return $this->connectedServer;
        }
    }

    public function errorNumber() {
        /**
         * Get the error number returned by the database server
         *
         * Added: 2016-01-23
         * Modified: 2016-01-23
         *
         * @param None
         *
         * @return Integer
        **/

        return mysqli_errno($this->link);
    }

    public function errorString() {
        /**
         * Get the error string returned by the database server
         *
         * Added: 2016-01-23
         * Modified: 2016-01-23
         *
         * @param None
         *
         * @return string
        **/

        return mysqli_error($this->link);
    }

    private function fieldNameWithAlias($name, $alias = false) {
        /**
         * Get an aliased field name
         * e.g. fieldName AS newFieldName
         *
         * Added: 2015-12-12
         * Modified: 2015-12-12
         *
         * @param string $name Field name
         * @param string|bool $alias Alias (optional)
         * @return string SQL name for aliased field. Will not alias a field to its own name
        **/

        if (!$alias || (string)$alias === (string)$name) {
            return $name;
        } else {
            return $name.' AS '.$alias;
        }
    }

    private function fieldNamesWithAlias($fields) {
        /**
         * Gets an array of aliased field names
         *
         * Added: 2015-12-12
         * Modified: 2015-12-12
         *
         * @param array $fields Array( [alias] => field )
         * @return string[] See fieldNameWithAlias()
        **/
        $retval = array();
        foreach ($fields as $alias => $field) {
            if (is_numeric($alias)) {
                $alias = $field;
            }
            $retval[] = $this->fieldNameWithAlias($field, $alias);
        }

        return $retval;
    }

    public function fetchArray($result) {
        /**
         * Returns an array that corresponds to the
         * fetched row or NULL if there are no more rows
         *
         * Added: 2015-12-13
         * Modified: 2015-12-13
         *
         * @param Required object $result The output from $this->query()
         *
         * @return array
        **/

        $rv = mysqli_fetch_array($result);
        try {
            // $rv !== NULL is checking for no results found
            if ($rv !== NULL && is_array($rv) === false) {
                throw new DBQueryError(get_lang(DB_BAD_QUERY));
            }
        } catch (DBQueryError $e) {
            $e->handler($e, DB_BAD_QUERY, $this->getQueryString());
        }

        return $rv;
    }
    
    public function fetchAssoc($result) {
        /**
         * Returns an associative array that corresponds to
         * the fetched row or NULL if there are no more rows
         *
         * Added: 2015-012-13
         * Modified: 2015-012-13
         *
         * @param Required object $result Output of $this->query()
         *
         * @return array|null An associative array of strings representing
         *       the fetched row in the result set, where each key in
         *       the array is the name of one of the rows columns or
         *       NULL if there are no more rows available
        **/

        $rv = mysqli_fetch_assoc($result);
        try {
            // $rv !== NULL is checking for no results found
            if ($rv !== NULL && is_array($rv) === false) {
                throw new DBQueryError(get_lang(DB_BAD_QUERY));
            }
        } catch (DBQueryError $e) {
            $e->handler($e, DB_BAD_QUERY, $this->getQueryString());
        }

        return $rv;
    }

    private function fetchRow($result) {
        /**
         * Returns an array of strings that corresponds to
         * the fetched row or NULL if there are no more rows
         *
         * Added: 2015-12-12
         * Modified: 2015-12-12
         *
         * @param Required object $result The returned object from $this->query()
         *
         * @return array
        **/
        $rv = mysqli_fetch_row($result);
        try {
            // $rv !== NULL is checking for no results found
            if ($rv !== NULL && is_array($rv) === false) {
                throw new DBQueryError(get_lang(DB_BAD_QUERY));
            }
        } catch (DBQueryError $e) {
            $e->handler($e, DB_BAD_QUERY, $this->getQueryString());
        }

        return $rv;
    }

    public function fieldValue($result, $row = 0) {
        /**
         * Get the value of a single field/column
         *
         * Added: 2015-12-12
         * Modified: 2015-12-12
         *
         * @param Required object  $result The returned object from $this->query()
         * @param Optional integer $row    The row number to retrieve. Row numbering starts at 0.
         *
         * @return string
        **/

        $data = $this->fetchRow($result);
        return $data[$row];
    }
    
    public function GetInsertID() {
        /**
         * Returns the auto generated id used in the last query
         *
         * Added: 2015-12-13
         * Modified: 2015-12-13
         *
         * @param None
         *
         * @return integer|string
         *      Will return the number as a string if the number is greater
         *      than the servers max int value
        **/

        return mysqli_insert_id($this->link);
    }

    public function insert($table, $data) {
        /**
         * Adds a new row of data to a table
         *
         * Added: 2015-12-13
         * Modified: 2015-12-15
         *
         * @param Required string $table Name of table to add data to
         * @param Required array  $data  The data to add to the table
         *      Must be in the format of
         *      array(
         *          'field_name' => data
         *      )
         *      Capitalization of field names must match what is in the database
         *
         * @return mixed
         *    FALSE on failure
         *    For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries mysqli_query() will return a mysqli_result object
         *    For other successful queries mysqli_query() will return TRUE
        **/

        $data = $this->quoteVals($data);
        $fields = "(".implode(array_keys($data), ", ").")";
        $values = "(".implode(array_values($data), ", ").")";
        $query = "INSERT INTO $table $fields VALUES $values";
        return $this->query($query);
    }

    public function insertMulti($table, $data, $extended = true) {
        /**
         * Adds multiple rows of data to a table
         *
         * Added: 2015-12-15
         * Modified: 2016-08-06
         *
         * @param Required string $table Name of table to add data to
         * @param Required array  $data  The data to add to the table
         *
         *      Must be an array in the following format
         *      array(
         *          'Fields' => array('Field1', 'Field2', 'Field3', '...'),
         *          'Values' => array(
         *              array('value1','value2','value3','...'), // Row one
         *              array('value4','value5','value6','...'), // Row two
         *              array('value7','value8','value9','...')  // Row three
         *          )
         *      )
         *
         *  Capitalization of field names must match what is in the database.
         *  The number of values being inserted for each row must be the same as the
         *  number of fields in $data['FIELDS']
         *
         * @param Optional boolean $extended
         *      True: Insert all the rows in one call
         *      False: Insert one row at a time
         *
         * @return mixed
         *    FALSE on failure
         *    For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries mysqli_query() will return a mysqli_result object
         *    For other successful queries mysqli_query() will return TRUE
        **/

        global $valid;

        if (!is_array($data)) {
            return false;
        }

        $data = array_change_key_case($data, CASE_UPPER);

        $fields = $data['FIELDS'];
        $values = $data['VALUES'];

        if ($extended) {
            // All the rows at once
            // An extended insert
            $sql = 'INSERT INTO '.$table.' ('.implode(array_values($fields), ", ").') VALUES ';
        }

        foreach ($values as $k => $v) {
            if ($extended) {
                // All the rows at once
                // An extended insert
                $v = $this->quoteVals($v);
                $array[] = '('.implode(array_values($v), ", ").')';
            } else {
                // One row at a time
                $d = array_combine($fields, $v);
                if ($d !== false) {
                    if (!$this->insert($table,$d)) {
                        // Insert failed on one of the rows
                        return false;
                    }
                } else {
                    $valid->add_warning(get_lang('DBCountMismatch').'.<br/>'.get_lang('DBFieldList').': '.'('.implode(array_values($fields), ", ").')'.'<br/>'.get_lang('DBValueList').': '.'('.implode(array_values($v), ", ").')');
                }
            }
        }

        if ($extended) {
            // All the rows at once
            // An extended insert
            $sql .= implode(array_values($array), ", ");
            return $this->query($sql);
        }
    }
    // END function insertMulti()
    /////

    private function limitResult($sql, $limit, $offset = false) {
        /**
         * Construct a LIMIT query with optional offset. This is used for query
         * pages. The SQL should be adjusted so that only the first $limit rows
         * are returned. If $offset is provided as well, then the first $offset
         * rows should be discarded, and the next $limit rows should be returned.
         * If the result of the query is not ordered, then the rows to be returned
         * are theoretically arbitrary.
         *
         * $sql is expected to be a SELECT, if that makes a difference.
         *
         * Added: 2017-05-11
         * Modified: 2017-05-11
         *
         * @param string $sql SQL query we will append the limit too
         * @param int $limit The SQL limit
         * @param int|bool $offset The SQL offset (default false)
         *
         * @return string
        **/

        if (!is_numeric($limit ) ) {
            // Don't make any changes
            return $sql;
        }

        return "$sql LIMIT " . ((is_numeric($offset) && $offset != 0) ? "{$offset}," : "") . "{$limit} ";
    }

    function listFields($table=null) {
        /**
         * Returns an array containing default values for each field in the table.
         *
         * Be aware that some fields (auto_increment, CURRENT_TIMESTAMP) may need to be set on INSERT.
         * auto_increment fields will be set to 0.
         * CURRENT_TIMESTAMP fields will be set to the PHP current timestamp based on the time this
         * function was called
         *
         * Added: 2015-12-13
         * Modified: 2017-07-27
         *
         * Original version of this function was found at https://bizinfosys.com/php/php-mysql-default-values-array.html
         * on 2017-07-25.
         *
         * @param Required $table Name of table to get list of fields from
         *
         * @return array $list The field names and types if the table exists
         *                     or an empty array if table does not exist
        **/

        // If $table is NULL, then check for a table with the
        // same name as the database
        if ($table == NULL) {
            $table = $this->databaseName;
        }

        // Set up blank array:
        $aReturn_value = array();

        // Get the fields:
        $cSQL = 'SHOW COLUMNS FROM `'.$table.'`';
        $rResult = $this->query($cSQL);
        $nResult = $this->numRows($rResult);
        if (count($nResult)==0) return array();

        // Scan through each field and assign defaults:
        for($i=0;$i<$nResult;$i++) {
            $aVals = $this->fetchAssoc($rResult);

            if ($aVals['Default'] && $aVals['Default']=='CURRENT_TIMESTAMP') {
                $aReturn_value[$aVals['Field']] = date('Y-m-d H:i:s');      
            }
            if ($aVals['Extra'] && $aVals['Extra']=='auto_increment') {
                $aReturn_value[$aVals['Field']] = 0;      
            }
            if ($aVals['Default'] && $aVals['Default']!='CURRENT_TIMESTAMP') {
                $aReturn_value[$aVals['Field']] = $aVals['Default'];
            } else {
                if ($aVals['Null']=='YES') {
                    $aReturn_value[$aVals['Field']] = NULL;
                } else {
                    $cType = $aVals['Type'];
                    if (strpos($cType,'(')!==false) $cType = substr($cType,0,strpos($cType,'('));

                    if (in_array($cType,array('varchar','text','char','tinytext','mediumtext','longtext','set',
                                  'binary','varbinary','tinyblob','blob','mediumblob','longblob'))) {
                        $aReturn_value[$aVals['Field']] = '';
                    } elseif ($cType=='datetime') {
                        $aReturn_value[$aVals['Field']] = '0000-00-00 00:00:00';
                    } elseif ($cType=='date') {
                        $aReturn_value[$aVals['Field']] = '0000-00-00';
                    } elseif ($cType=='time') {
                        $aReturn_value[$aVals['Field']] = '00:00:00';
                    } elseif ($cType=='year') {
                        $aReturn_value[$aVals['Field']] = '0000';
                    } elseif ($cType=='timestamp') {
                        $aReturn_value[$aVals['Field']] = date('Y-m-d H:i:s');
                    } elseif ($cType=='enum') {
                        $aReturn_value[$aVals['Field']] = 1;
                    } else {  // Numeric:
                        $aReturn_value[$aVals['Field']] = 0;
                    }
                }  // end NOT NULL
            }  // end default check
        }  // end foreach loop

        return $aReturn_value;

    }
    // END function listFields()
    /////

    private function makeGroupByWithHaving($options) {
        /**
         * Returns an optional GROUP BY with an optional HAVING
         *
         * Added: 2015-12-12
         * Modified: 2015-12-12
         *
         * @param Required array $options associative array of options
         *
         * @return string
        **/

        if ($this->debug >= 3) {
            $msg = serialize($options);
            $sm = new SystemMessages();
            $sm->AddEntry(__FUNCTION__.' '.$msg, __METHOD__, __FILE__, 'DBDebug');
        }

        $sql = '';
        if (isset($options['GROUP BY'])) {
            $gb = is_array($options['GROUP BY'])
                ? implode(',', $options['GROUP BY'])
                : $options['GROUP BY'];
            $sql .= ' GROUP BY ' . $gb;
        }
        if (isset($options['HAVING'])) {
            $having = is_array($options['HAVING'])
                ? $this->makeList($options['HAVING'])
                : $options['HAVING'];
            $sql .= ' HAVING ' . $having;
        }
        return $sql;
    }

    public function makeList($a, $mode = LIST_AND) {
        /**
         * Makes an encoded list of strings from an array
         *
         * Taken from Mediawiki 1.24.2::/includes/db/Database.php::Line 2080
         *
         * Added: 2015-12-12
         * Modified: 2017-01-11
         *
         * @param array   $a    Containing the data
         * @param integer $mode Constant
         *    - LIST_COMMA: Comma separated, no field names
         *    - LIST_AND:   ANDed WHERE clause (without the WHERE). See the
         *      documentation for $conditions in $this->select().
         *    - LIST_OR:    ORed WHERE clause (without the WHERE)
         *    - LIST_SET:   Comma separated with field names, like a SET clause
         *    - LIST_NAMES: Comma separated field names
         *
         * @return string
        **/

        if (is_string($a) === true) {
            // Assume this is a raw SQL fragment
            return $a;
        }

        $first = true;
        $list = '';

        foreach ($a as $field => $value) {
            if ($first === false) {
                if ($mode == LIST_AND) {
                    $list .= ' AND ';
                } elseif ($mode == LIST_OR) {
                    $list .= ' OR ';
                } else {
                    $list .= ',';
                }
            } else {
                $first = false;
            }

            if (($mode == LIST_AND || $mode == LIST_OR) && is_numeric($field)) {
                if (is_array($value)) {
                    if (array_key_exists('buildBetween', $value) && is_array($value['buildBetween'])) {
                        // Check whether a value is within a range of values
                        $list = $this->buildBetween($value['buildBetween'][0],$value['buildBetween'][1],$value['buildBetween'][2]);
                    } elseif (array_key_exists('buildBetweenNot', $value) && is_array($value['buildBetweenNot'])) {
                        // Check whether a value outside a range of values 
                        $list = $this->buildBetween($value['buildBetweenNot'][0],$value['buildBetweenNot'][1],$value['buildBetweenNot'][2],true);
                    } else {
                        // User supplied comparison operator
                        $f = true;
                        foreach ($value as $field => $d) {
                            if ($f === false) {
                                if ($mode == LIST_AND) {
                                    $list .= ' AND ';
                                } elseif ($mode == LIST_OR) {
                                    $list .= ' OR ';
                                } else {
                                    $list .= ',';
                                }
                            } else {
                                $f = false;
                            }
                            $list .= "$field ".$d[0]." ".$this->quote($d[1]);
                        }
                    }
                    // END if (array_key_exists('buildBetween', $value) && is_array($value['buildBetween']))
                } else {
                    $list .= "($value)";
                }
            } elseif (($mode == LIST_SET) && is_numeric($field)) {
                $list .= "$value";
            } elseif (($mode == LIST_AND || $mode == LIST_OR) && is_array($value)) {
                if (count($value) == 0) {
                    // Empty input for field $field
                } elseif (count($value) == 1) {
                    // Special-case single values, as IN isn't terribly efficient
                    // Don't necessarily assume the single key is 0; we don't
                    // enforce linear numeric ordering on other arrays here.
                    $value = array_values($value);
                    $list .= $field . " = " . $this->quote($value[0]);
                } else {
                    $list .= $field . " IN (" . $this->makeList($value, LIST_COMMA) . ") ";
                }
            } elseif ($value === null) {
                if ($mode == LIST_AND || $mode == LIST_OR) {
                    $list .= "$field IS ";
                } elseif ($mode == LIST_SET) {
                    $list .= "$field = ";
                }
                $list .= 'NULL';
            } else {
                if ($mode == LIST_AND || $mode == LIST_OR || $mode == LIST_SET) {
                    $list .= "$field = ";
                }
                $list .= ($mode == LIST_NAMES) ? $value : $this->quote($value);
            }
        }

        return $list;
    }

    private function makeOrderBy($options) {
        /**
         * Adds an optional 'ORDER BY' to sort query results
         *
         * Added: 2015-12-12
         * Modified: 2015-12-12
         *
         * @param Required array $options See notes below
         *
         * @return string
         *
         *****
         * $options must be an associative array of field names to sort by
         *
         * Valid calls to this function
         *     makeOrderBy(array('ORDER BY' => 'StartTime ASC'))
         *     makeOrderBy(array('ORDER BY' => 'StartTime'))
         *     makeOrderBy(array('ORDER BY' => array('StartTime', 'EndTime DESC')))
         *     makeOrderBy(array('ORDER BY' => array('StartTime ASC', 'EndTime')))
         *
         * Invalid calls to this function
         *     makeOrderBy(array('StartTime ASC, EndTime DESC'))
         *     makeOrderBy('StartTime ASC, EndTime DESC')
         *     makeOrderBy(3)
        **/

        // Using isset() instead of array_key_exists() because isset() will return false for a NULL value
        if (isset($options['ORDER BY'])) {
            $ob = is_array($options['ORDER BY']) ? implode(',', $options['ORDER BY']) : $options['ORDER BY'];
            return ' ORDER BY ' . $ob;
        }
        return '';
    }

    private function makeSelectOptions($options) {
        /**
         * Returns an optional USE INDEX clause to go after the table, and a
         * string to go at the end of the query.
         *
         * Added: 2015-12-12
         * Modified: 2015-12-12
         *
         * @param Required string|array $options Array of query options. See the
         *      documentation for $options in $this->select().
         *
         * @return Array
        **/

        if ($this->debug >= 3) {
            $msg = serialize($options);
            $sm = new SystemMessages();
            $sm->AddEntry(__FUNCTION__.' '.$msg, __METHOD__, __FILE__, 'DBDebug');
        }

        $preLimitTail = '';
        $postLimitTail = '';
        $startOpts = '';

        $noKeyOptions = array();

        if (is_array($options) === true) {
            foreach ($options as $key => $option) {
                if (is_numeric($key)) {
                    $noKeyOptions[$option] = true;
                }
            }
        }

        $preLimitTail .= $this->makeGroupByWithHaving($options);

        $preLimitTail .= $this->makeOrderBy($options);

        if (isset($noKeyOptions['FOR UPDATE'])) {
            $postLimitTail .= ' FOR UPDATE';
        }

        if (isset($noKeyOptions['LOCK IN SHARE MODE'])) {
            $postLimitTail .= ' LOCK IN SHARE MODE';
        }

        if (isset($noKeyOptions['DISTINCT']) || isset($noKeyOptions['DISTINCTROW'])) {
            $startOpts .= 'DISTINCT';
        }

        # Various MySQL extensions
        if (isset($noKeyOptions['STRAIGHT_JOIN'])) {
            $startOpts .= ' /*! STRAIGHT_JOIN */';
        }

        if (isset($noKeyOptions['HIGH_PRIORITY'])) {
            $startOpts .= ' HIGH_PRIORITY';
        }

        if (isset($noKeyOptions['SQL_BIG_RESULT'])) {
            $startOpts .= ' SQL_BIG_RESULT';
        }

        if (isset($noKeyOptions['SQL_BUFFER_RESULT'])) {
            $startOpts .= ' SQL_BUFFER_RESULT';
        }

        if (isset($noKeyOptions['SQL_SMALL_RESULT'])) {
            $startOpts .= ' SQL_SMALL_RESULT';
        }

        if (isset($noKeyOptions['SQL_CALC_FOUND_ROWS'])) {
            $startOpts .= ' SQL_CALC_FOUND_ROWS';
        }

        if (isset($noKeyOptions['SQL_CACHE'])) {
            $startOpts .= ' SQL_CACHE';
        }

        if (isset($noKeyOptions['SQL_NO_CACHE'])) {
            $startOpts .= ' SQL_NO_CACHE';
        }

        if (isset($options['USE INDEX']) && is_string($options['USE INDEX'])) {
            $useIndex = $this->useIndexClause($options['USE INDEX']);
        } else {
            $useIndex = '';
        }

        return array($startOpts, $useIndex, $preLimitTail, $postLimitTail);
    }

    public function mysqlndSwitch($use='') {
        /**
         * Specify which server to use when using the mysqlnd library
         *
         * Added: 2017-02-18
         * Modified: 2017-02-19
         *
         * @param Optional object $use Must be set to one of the following.
         *                             'master' - Use MYSQLND_MS_MASTER_SWITCH for the next $this->select() call
         *                             'slave' - Use MYSQLND_MS_SLAVE_SWITCH for the next $this->select() call
         *                             'last' - Use MYSQLND_MS_LAST_USED_SWITCH for the next $this->select() call
         *                             '' - Let the mysqlnd library decide which server to use
         *
         * @return Nothing
        **/

        // Convert to lowercase for easier matching
        $use = strtolower($use);

        if ($use == 'master') {
            $this->mysqlnd_switch = MYSQLND_MS_MASTER_SWITCH;
        } elseif ($use == 'slave') {
            $this->mysqlnd_switch = MYSQLND_MS_SLAVE_SWITCH;
        } elseif ($use == 'last') {
            $this->mysqlnd_switch = MYSQLND_MS_LAST_USED_SWITCH;
        } else {
            $this->mysqlnd_switch = '';
        }
    }

    public function numRows($result) {
        /**
         * Count number of rows returned by the query
         *
         * Added: 2015-12-12
         * Modified: 2015-12-12
         *
         * @param Required object $result The returned object from $this->query()
         *
         * @return integer|string
         *          Will only return a string if the number of rows is greater than PHP_INT_MAX
        **/

        return mysqli_num_rows($result);
    }

    public function query($query) {
        /**
         * All SQL statements run through this function.
         *
         * In new code, the query wrappers select(), insert(), update(), delete(),
         * etc. should be used where possible, since they automatically quote and
         * validate user input in a variety of contexts.
         *
         * This function is generally only useful for queries which are unsupported
         * by the query wrappers, such as "CREATE TABLE" and "SET sql_mode"
         *
         * However, the query wrappers themselves should call this function.
         *
         * Added: 2015-12-12
         * Modified: 2015-12-12
         *
         * @param Required string The SQL text
         *
         * @return mixed
         *    FALSE on failure
         *    For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries mysqli_query() will return a mysqli_result object
         *    For other successful queries mysqli_query() will return TRUE
        **/

        $this->queryString = $query;
        ++$this->queryCount;

        // Run the query
        $rv = mysqli_query($this->link, $query);

        return $rv;
    }

    public function select($table, $conditions='', $fields='', $options='') {
        /**
         * Runs a MySQL SELECT() statement
         *
         * Added: 2015-12-13
         * Modified: 2015-12-13
         *
         * @param Required string       $table      Name of table to get data from
         * @param Optional string|array $conditions Field names and values for the WHERE clause
         *
         * $conditions may be either a string containing a single condition, or an array of
         * conditions. If an array is given, the conditions constructed from each
         * element are combined with AND.
         *
         * Array elements may take one of two forms:
         *
         *   - Elements with a numeric key are treated in one of two ways.
         *     - If the element is a string, it is interpreted as a raw SQL fragment
         *     - If the element is an array, it is assumed the user wants to use a
         *       comparison operator other than equals (=).
         *   - Elements with a string key are interpreted as equality conditions,
         *     where the key is the field name.
         *     - If the value of such an array element is a scalar (such as a
         *       string), it will be treated as data and thus quoted appropriately.
         *       If it is null, an IS NULL clause will be added.
         *     - If the value is an array, an IN(...) clause will be constructed,
         *       such that the field name may match any of the elements in the
         *       array. The elements of the array will be quoted.
         *
         *
         * @param Optional string|array $fields Name(s) of the field(s) to return
         *
         *
         * @param Optional string|array $options An associative array of options
         *
         * Boolean options are specified by including them in the array as a string
         * value with a numeric key, for example:
         *
         *    array('FOR UPDATE')
         *
         * The supported options are:
         *
         *   - OFFSET: Skip this many rows at the start of the result set. OFFSET
         *     with LIMIT can theoretically be used for paging through a result set,
         *     but this is discouraged in MediaWiki for performance reasons.
         *
         *   - LIMIT: Integer: return at most this many rows. The rows are sorted
         *     and then the first rows are taken until the limit is reached. LIMIT
         *     is applied to a result set after OFFSET.
         *
         *   - FOR UPDATE: Boolean: lock the returned rows so that they can't be
         *     changed until the next COMMIT.
         *
         *   - DISTINCT: Boolean: return only unique result rows.
         *
         *   - GROUP BY: May be either an SQL fragment string naming a field or
         *     expression to group by, or an array of such SQL fragments.
         *
         *   - HAVING: May be either an string containing a HAVING clause or an array of
         *     conditions building the HAVING clause. If an array is given, the conditions
         *     constructed from each element are combined with AND.
         *
         *   - ORDER BY: May be either an SQL fragment giving a field name or
         *     expression to order by, or an array of such SQL fragments.
         *
         *   - USE INDEX: This may be either a string giving the index name to use
         *     for the query, or an array. If it is an associative array, each key
         *     gives the table name (or alias), each value gives the index name to
         *     use for that table. All strings are SQL fragments and so should be
         *     validated by the caller.
         *
         *   - EXPLAIN: In MySQL, this causes an EXPLAIN SELECT query to be run,
         *     instead of SELECT.
         *
         * And also the following boolean MySQL extensions, see the MySQL manual
         * for documentation:
         *
         *    - LOCK IN SHARE MODE
         *    - STRAIGHT_JOIN
         *    - HIGH_PRIORITY
         *    - SQL_BIG_RESULT
         *    - SQL_BUFFER_RESULT
         *    - SQL_SMALL_RESULT
         *    - SQL_CALC_FOUND_ROWS
         *    - SQL_CACHE
         *    - SQL_NO_CACHE
         *
         * @return mixed
         *    FALSE on failure
         *    For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries mysqli_query() will return a mysqli_result object
         *    For other successful queries mysqli_query() will return TRUE
        **/

        $sql = $this->selectSQLText($table, $conditions, $fields, $options);
        return $this->query($sql);
    }

    public function selectSQLText($table, $conditions='', $fields='', $options='') {
        /**
         * Creates and runs a MySQL SELECT() statement
         *
         * The equivalent of self::select() except that the constructed SQL
         * is returned, instead of being immediately executed. This can be useful for
         * doing UNION queries, where the SQL text of each query is needed. In general,
         * however, callers outside of this class should just use select().
         *
         * Added: 2015-12-12
         * Modified: 2015-12-12
         *
         * @param Required string       $table      Name of table to get data from
         * @param Optional string|array $conditions Field names and values for the WHERE clause
         * @param Optional string|array $fields     Name(s) of the fields to look in
         * @param Optional array        $options    An associative array of options
         *
         * @return string An SQL statement
        **/

        if (is_array($fields)) {
            foreach ($fields as $key => $value) {
                // Using $key to keep the fields in order

                if (is_array($value)) {
                    if (array_key_exists('buildConcatWS', $value) && is_array($value['buildConcatWS'])) {
                        $fields[$key] = $this->buildConcatWS($value['buildConcatWS'][0],$value['buildConcatWS'][1],$value['buildConcatWS'][2]);
                    }

                    // Build a Match..Against condition
                    if (array_key_exists('buildMatchAgainst', $value) && is_array($value['buildMatchAgainst'])) {
                        $fields[$key] = $this->buildMatchAgainst($value['buildMatchAgainst'][0],$value['buildMatchAgainst'][1],$value['buildMatchAgainst'][2],$value['buildMatchAgainst'][3]);
                    }
                }
            }
            // END foreach ($fields as $key => $value)

            $fields = implode(',', $this->fieldNamesWithAlias($fields));
        }

        if ($fields == '') {
            // No fields specified so get them all
            $fields = '*';
        }

        list($startOpts, $useIndex, $preLimitTail, $postLimitTail) = $this->makeSelectOptions($options);

        if (!empty($conditions)) {
            $conditions = $this->makeList($conditions);
            $sql = "SELECT $startOpts $fields FROM $table $useIndex WHERE $conditions $preLimitTail $postLimitTail";
        } else {
            $sql = "SELECT $startOpts $fields FROM $table $useIndex $preLimitTail $postLimitTail";
        }

        if (isset($options['LIMIT'])) {
            $sql = $this->limitResult($sql, $options['LIMIT'],
                isset( $options['OFFSET'] ) ? $options['OFFSET'] : false );
        }
        $sql = "$sql $postLimitTail";

        // Specify the server to use
        if ($this->mysqlnd_switch != '') {
            $sql = '/*'.$this->mysqlnd_switch.'*/ '.$sql;
        }

        return $sql;
    }

    public function setMainDatabase($dbname) {
        /**
         * The main database for this connection
         * Database could contain usernames and passwords, common settings for
         * the applications hosted on this server, or frequently accessed data
         *
         * Added: 2017-05-11
         * Modified: 2017-05-11
         *
         * @param Required string $dbname The database to connect to
         *
         * @return Nothing
        **/

        $this->maindbname = $dbname;
    }

    public function tableExists($table) {
        /**
         * Searches the current database for the given table
         *
         * Table name is case sensitive.
         *
         * Changed to a public function on 2017-01-07.
         *
         * Added: 2015-12-12
         * Modified: 2017-01-07
         *
         * @param Required string $table The table name to look for
         *
         * @return
         *     (boolean)true: table exists
         *     (boolean)false: table not found
        **/

        if ($table == '') {
            // Can't run the search unless we are given a table name
            return false;
        }

        $query = $this->query('SHOW TABLES');
        while ($row = $this->fetchRow($query)) {
            if ($row[0] == $table) {
                return true;
            }
        }

        return false;
    }

    public function update($table, $condition, $data = array()) {
        /**
         * Update data in a table
         *
         * Added: 2015-12-13
         * Modified: 2016-01-23
         *
         * @param Required string $table Name of table to update
         * @param Required string|array $condition The WHERE condition to use
         *                                   See $this->makeList() for format
         * @param Required array  $data The data to add to the table
         *      Must be in the format of
         *      array(
         *          'field_name' => data
         *      )
         *      Capitalization of field names must match what is in the database
         *
         * @return mixed
        **/

        if (empty($data)) {
            // No data given to update
            return false;
        }

        $update_pairs=array();
        foreach ($data as $field => $val) {
            array_push($update_pairs, "$field = ".$this->quote($val));
        }
        $query = "UPDATE $table SET ";
        $query .= implode(", ", $update_pairs);
        $query .= ' WHERE '.$this->makeList($condition);

        return $this->query($query);
    }

    public function updateOrInsert($table, $condition, $data, $field, $value) {
        /**
         * If the data already exists, update it, otherwise insert it.
         *
         * Added: 2016-09-25
         * Modified: 2016-09-25
         *
         * @param Required string $table Name of table to update
         * @param Required string|array $condition The WHERE condition to use
         *                                   See $this->makeList() for format
         * @param Required array  $data The data to add to the table
         *      Must be in the format of
         *      array(
         *          'field_name' => data
         *      )
         *      Capitalization of field names must match what is in the database
         * @param Required string $field The field to use in the select() where clause
         * @param Required string $value The value for $field to use in the select() where clause
         *
         * $field and $value are used to determine if the data needs to be updated or inserted
         *
         * @return mixed
        **/

        # Check if this info is already in the database
        $count = $this->fieldValue($this->select($table, $field.' = '.$value, 'count(*)'));

        if ($count < 1) {
            # Add info to database
            $data[$field] = $value; // Add this info to the array so the data can be found next time
            return $this->insert($table, $data);
        } else {
            # Update existing info
            return $this->update($table, $condition, $data);
        }
    }

    private function useIndexClause($index) {
        /**
         * Add the USE INDEX clause
         *
         * Added: 2015-12-12
         * Modified: 2015-12-12
         *
         * @param Required string $index List of index(es) to use
         *
         * @return string
        **/
        return "FORCE INDEX (".$index.")";
    }

    public function quote($val) {
        // Needs to be public because it is used via /song_api.php.
        // Other wise this would be a private function
        if (is_numeric($val)) return $val;
        if (get_magic_quotes_gpc()==1) {
            return '"'.$val.'"';
        } else {
            return '"'.addslashes($val).'"';
        }
    }

    private function quoteVals($array) {
        foreach ($array as $key=>$val) {
            $ret[$key]=$this->quote($val);
        }
        return $ret;
    }
}
?>
