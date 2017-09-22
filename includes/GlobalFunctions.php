<?php
/**
 * File: GlobalFunctions.php
 *
 * Added: 2017-06-04 by Nathan Weiler (ncweiler2@hotmail.com)
 * Modified: 2017-06-29 by Nathan Weiler (ncweiler2@hotmail.com)
 *
 * These are functions that haven't been sorted into one of the other GlobalFunctions_* files
 *
 * The 'kg' at the beginning of the function names stands for "Kraven Global"
**/

if (!defined('KRAVEN')) {
	die('This file is part of Kraven. It is not a valid entry point.');
}

function kgBaseConvert($input, $sourceBase, $destBase, $pad = 1, $lowercase = true, $engine = 'auto') {
    /**
     * Convert an arbitrarily-long digit string from one numeric base
     * to another, optionally zero-padding to a minimum column width.
     *
     * Supports base 2 through 36; digit values 10-36 are represented
     * as lowercase letters a-z. Input is case-insensitive.
     *
     * Taken from 'mediawiki-1.22.1::/includes/GlobalFunctions.php::Line 3178'
     * Original function name was wfBaseConvert
     *
     * Added: 2014-09-29
     * Modified: 2014-09-29
     *
     * @param string $input Input number
     * @param int $sourceBase Base of the input number
     * @param int $destBase Desired base of the output
     * @param int $pad Minimum number of digits in the output (pad with zeroes)
     * @param bool $lowercase Whether to output in lowercase or uppercase
     * @param string $engine Either "gmp", "bcmath", or "php"
     * @return string|bool The output number as a string, or false on error
    **/

    $input = (string)$input;
    if (
        $sourceBase < 2 ||
        $sourceBase > 36 ||
        $destBase < 2 ||
        $destBase > 36 ||
        $sourceBase != (int)$sourceBase ||
        $destBase != (int)$destBase ||
        $pad != (int)$pad ||
        !preg_match( "/^[" . substr( '0123456789abcdefghijklmnopqrstuvwxyz', 0, $sourceBase ) . "]+$/i", $input )
    ) {
        return false;
    }

    static $baseChars = array(
        10 => 'a', 11 => 'b', 12 => 'c', 13 => 'd', 14 => 'e', 15 => 'f',
        16 => 'g', 17 => 'h', 18 => 'i', 19 => 'j', 20 => 'k', 21 => 'l',
        22 => 'm', 23 => 'n', 24 => 'o', 25 => 'p', 26 => 'q', 27 => 'r',
        28 => 's', 29 => 't', 30 => 'u', 31 => 'v', 32 => 'w', 33 => 'x',
        34 => 'y', 35 => 'z',

        '0' => 0,  '1' => 1,  '2' => 2,  '3' => 3,  '4' => 4,  '5' => 5,
        '6' => 6,  '7' => 7,  '8' => 8,  '9' => 9,  'a' => 10, 'b' => 11,
        'c' => 12, 'd' => 13, 'e' => 14, 'f' => 15, 'g' => 16, 'h' => 17,
        'i' => 18, 'j' => 19, 'k' => 20, 'l' => 21, 'm' => 22, 'n' => 23,
        'o' => 24, 'p' => 25, 'q' => 26, 'r' => 27, 's' => 28, 't' => 29,
        'u' => 30, 'v' => 31, 'w' => 32, 'x' => 33, 'y' => 34, 'z' => 35
    );

    if ( extension_loaded( 'gmp' ) && ( $engine == 'auto' || $engine == 'gmp' ) ) {
        $result = gmp_strval( gmp_init( $input, $sourceBase ), $destBase );
    } elseif ( extension_loaded( 'bcmath' ) && ( $engine == 'auto' || $engine == 'bcmath' ) ) {
        $decimal = '0';
        foreach ( str_split( strtolower( $input ) ) as $char ) {
            $decimal = bcmul( $decimal, $sourceBase );
            $decimal = bcadd( $decimal, $baseChars[$char] );
        }

        for ( $result = ''; bccomp( $decimal, 0 ); $decimal = bcdiv( $decimal, $destBase, 0 ) ) {
            $result .= $baseChars[bcmod( $decimal, $destBase )];
        }

        $result = strrev( $result );
    } else {
        $inDigits = array();
        foreach ( str_split( strtolower( $input ) ) as $char ) {
            $inDigits[] = $baseChars[$char];
        }

        // Iterate over the input, modulo-ing out an output digit
        // at a time until input is gone.
        $result = '';
        while ( $inDigits ) {
            $work = 0;
            $workDigits = array();

            // Long division...
            foreach ( $inDigits as $digit ) {
                $work *= $sourceBase;
                $work += $digit;

                if ( $workDigits || $work >= $destBase ) {
                    $workDigits[] = (int)( $work / $destBase );
                }
                $work %= $destBase;
            }

            // All that division leaves us with a remainder,
            // which is conveniently our next output digit.
            $result .= $baseChars[$work];

            // And we continue!
            $inDigits = $workDigits;
        }

        $result = strrev( $result );
    }

    if ( !$lowercase ) {
        $result = strtoupper( $result );
    }

    return str_pad( $result, $pad, '0', STR_PAD_LEFT );
}
// END function kgBaseConvert()

function kgCreateLink($name, $data='', $title='') {
    /**
     * Creates an <a href></a> link.
     *
     * This function was originally created so that the author of the modules did not
     * have to remember to include the 'module='.$KG_MODULE_NAME part of the link.
     *
     * Added: 2017-06-04
     * Modified: 2017-06-10
     *
     * @param Required string $name The text that goes between the <a></a> tags
     * @param Optional array|string $data
     *                              If array
     *                                Contents of array will be used to create an HTTP GET request in the format "&key=value"
     *                                Array keys will be the parameters and the value of the key will become the value of the parameter.
     *                                -Special Keys (Key names are case sensitive)
     *                                  'KG_MODULE_NAME'
     *                                      The value of this key will be used instead of the global $KG_MODULE_NAME.
     *                                      This allows the modules to "interacte" with each other.
     *                                  'NO_TAG' with value 'NO_TAG'
     *                                      Function will return the URL without the <a href></a> tag.
     *                                      Useful for generating onclick URLs
     *                                      $name will be ignored.
     *                                  'USE_CSS'
     *                                      The value of this key needs to be a valid CSS class selector from the modules
     *                                      CSS stylesheet
     *                              If string
     *                                Any valid URL string such as ['#tabs-'.$key]
     * @param Optional string $title An extra description to display when the user hovers their mouse over the link
     *
     * @return String An <a href></a> link
    **/

    global $KG_MODULE_NAME, $RP;

    if ($title != '') {
        $title = ' title="'.$title.'"';
    }

    // Assume user wants the <a href> part
    $urlonly = false;

    $link = '';
    $css = '';

    if (is_array($data)) {
        if (array_key_exists('KG_MODULE_NAME', $data)) {
            // Changes the module this link will go to
            $KG_MODULE_NAME = $data['KG_MODULE_NAME'];
            unset($data['KG_MODULE_NAME']);
        }

        if (array_key_exists('NO_TAG', $data) && $data['NO_TAG'] == 'NO_TAG') {
            // Only create the URL
            $urlonly = true;
            unset($data['NO_TAG']);
        }

        if (array_key_exists('USE_CSS', $data)) {
            // Only create the URL
            $css = ' class="'.$data['USE_CSS'].'"';
            unset($data['USE_CSS']);
        }

        // Create the HTTP GET request string
        foreach ($data as $key => $value) {
            $link .= '&'.$key.'='.$value;
        }

    } else {
        // $data appears to be a string
        $link = $data;
    }

    if ($urlonly) {
        $link = $RP.'/index.php?KG_MODULE_NAME='.$KG_MODULE_NAME.$link;
    } else {
        $link = '<a href="'.$RP.'/index.php?KG_MODULE_NAME='.$KG_MODULE_NAME.$link.'"'.$title.$css.'>'.$name.'</a>';
    }

    return $link;
}
// END function kgCreateLink()
/////

function kgFindIdNumber($table,$field,$database) {
    /**
     * Finds an available ID number to use
     *
     * Added: 2017-08-16
     * Modified: 2017-08-16
     *
     * @param Required string $table    Name of table that contains $field
     * @param Required string $field    Name of field to search
     * @param Required string $database Database handle used by the calling module
     *
     * @return integer
    **/

    // Returns ID numbers sorted low to high
    $query = $database->select($table, '', $field, array('ORDER BY' => $field.' ASC'));
    $numrows = $database->numRows($query);

    if ($numrows == 1) {
        // There is only 1 entry in the table

        // Get ID number in use
        $rv = $database->fieldValue($query);

        if ($rv > 1) {
            // ID number 1 is not in use so grab it
            $id = 1;
        } else {
            // Add 1 to $rv and that will be the ID number for this contact
            $id = $rv + 1;
        }
    } elseif ($numrows > 1) {
        // There 2 or more entries in the table

        $idnumbers = array();
        while ($row = $database->fetchAssoc($query)) {
            array_push($idnumbers, $row[$field]);
        }

        //Run through the array and find a number that is missing
        //E.G.: 12346
        //      5 is missing so use 5 as the new ID number

        /**
         * Count the number of elements in the array and
         * subtract 2 so the loop doesn't go past end of array.
         *
         * Why minus 2 instead of minus 1?
         * count() returns the number of elements in the array
         * PHP arrays are zero-based
         *
         * $array = Array (
         *     [0] => 1
         *     [1] => 2
         *     [2] => 3
         *     [3] => 4
         *     [4] => 5
         *     [5] => 6
         * )
         * count($array) == 6
         *
         * $i <= count($array)
         * $i = 0; $i + 1 = 1
         * $i = 1; $i + 1 = 1
         * $i = 2; $i + 1 = 1
         * $i = 3; $i + 1 = 1
         * $i = 4; $i + 1 = 1
         * $i = 5; $i + 1 = 1 <-- Error: no element at $array[$i + 1]
         * $i = 6; $i + 1 = 1
         *
         * $i <= count($array) - 1
         * $i = 0; $i + 1 = 1
         * $i = 1; $i + 1 = 1
         * $i = 2; $i + 1 = 1
         * $i = 3; $i + 1 = 1
         * $i = 4; $i + 1 = 1
         * $i = 5; $i + 1 = 1 <-- Error: no element at $array[$i + 1]
         *
         * $i <= count($array) - 2
         * $i = 0; $i + 1 = 1
         * $i = 1; $i + 1 = 1
         * $i = 2; $i + 1 = 1
         * $i = 3; $i + 1 = 1
         * $i = 4; $i + 1 = 1 <-- $array[$i + 1] does exist
        **/
        $nr = count($idnumbers) - 2;

        for ($i = 0; $i <= $nr; $i++) {
            $b = $idnumbers[$i];
            $c = $idnumbers[$i + 1];
            if (($c - $b) >= 2) {
                /*
                    C minus B is equal to or greater than 2.  Since B is the lower number
                    of the two, add 1 to B and store that number in $id.  This will be
                    the new ID number.
                */
                $id = $b + 1;

                // Exit for() loop
                break;
            }
        }

        // If $i greater than $nr then no missing number was found.
        // Set $id equal to the highest number in array and add 1 to it.
        if ($i > $nr) {
            $id = array_pop($idnumbers) + 1;
        }

    } else {
    
        //There are no enteries in the table
        $id = 1;

    } // END if ($numrows == 1)

    return $id;

}
// END function kgFindIdNumber()
/////

function kgFormatPhoneNumber($pnorig, $fordb=false) {
/**
 *
 *  NOTES:
 *      Currently only handles 7, 10 and 11 digit phone numbers.
 *      Will consider a phone number with a country calling code
 *          invalid due to length of phone number.
 *      See http://en.wikipedia.org/wiki/List_of_country_calling_codes to fix this
 *
 *      Also assumes that only 1-800 numbers will use letters
 *
 *      Cannot handle international phone numbers
 *
 *  Validation Examples:
 *      (234) 235 5678 is valid
 *      (234) 911 5678 is invalid, because the exchange code cannot be in the form N11.
 *      (123) 234 5678 is invalid, because NPA (Area Code) cannot begin with "0" or "1"
 *      (291) 234 5678 is invalid, because the second digit of an area code cannot be "9"
 *
 *      Sources:
 *          http://en.wikipedia.org/wiki/Telephone_numbers_in_the_United_States
 *          http://en.wikipedia.org/wiki/List_of_North_American_Numbering_Plan_area_codes
 *
 *  Formatting Examples:
 *                       Display
 *      Input:           Output:          TF:
 *      717 123-4567     (717) 123-4567   true
 *      7171234567       (717) 123-4567   true
 *      1234567          123-4567         true
 *      1-800-CALL-ME2   1-800-CAL-LME2   true
 *      1800CALLME2      1-800-CAL-LME2   true
 *      1800CALLME       1800CALLME       false
 *      123456           123456           false
 *
 *                       Database
 *      Input:           Output:          TF:
 *      (717) 123-4567   7171234567       true
 *      7171234567       7171234567       true
 *      1234567          1234567          true
 *      1-800-CALL-ME2   1800CALLME2      true
 *      1-800-CALL-ME    1800CALLME       false
 *      123456           123456           false
 *
 * Added: 2017-07-10
 * Updated: 2017-07-10
 *
 * @param Required string $pnorig The phone number to be validated & formatted
 * @param Optional boolean $fordb 
**/

    // Should the function return the original version or the modified version
    $returnorig = false;

    // Convert all letters (if any) to upper case
    $pnorig = strtoupper($pnorig);

    // Gets rid of everything but numbers, letters and the underscore character
    $pn = preg_replace('#\W#', '', $pnorig);

    // Remove underscore characters
    $pn = preg_replace('#\_#', '', $pn);

    // At this point $pn will consist of [0-9] and/or [A-Z]

    // Length of phone number
    $pnl = strlen($pn);

    $replacepairs = array(
        'A' => '2',
        'B' => '2',
        'C' => '2',
        'D' => '3',
        'E' => '3',
        'F' => '3',
        'G' => '4',
        'H' => '4',
        'I' => '4',
        'J' => '5',
        'K' => '5',
        'L' => '5',
        'M' => '6',
        'N' => '6',
        'O' => '6',
        'P' => '7',
        'Q' => '7',
        'R' => '7',
        'S' => '7',
        'T' => '8',
        'U' => '8',
        'V' => '8',
        'W' => '9',
        'X' => '9',
        'Y' => '9',
        'Z' => '9'
    );
    $alpha = array_keys($replacepairs);

    if ($pnl == 11) {
        /**
         *  Convert letters to numbers but only for 1-800 numbers
         *  Example: 1-800-EXAMPLE -> 1-800-392-6753
                           
        **/
        $dummy = str_replace($alpha, '', $pn, $count);
        $pn = strtr($pn, $replacepairs);

        if ($count > 0 ) {
            $returnorig = true;
        }

    }
    // END if ($pnl == 11)

    // Check length
    if (($pnl <> 7) && ($pnl <> 10) && ($pnl <> 11)) {
        // Invalid length
        $tf = false;
    } else {
        $tf = true;
    }

    if ($tf === true) {
        // Phone number length is valid

        // Create an array for easier validation
        $data = str_split($pn);

        // -Reverse the array to allow the tests that
        //  are common among all phone number formats
        //  to be done without having to duplicate the tests
        $data = array_reverse($data);

        //////////
        // Validation checks common to ALL US formatted phone numbers

        // Subscriber number (4 digits long)
        if (is_numeric(substr($pn,0,4))) {
            $tf = true;
        } else {
            $tf = false;
        }

        /////
        // Check the exchange code (3 digits long)

        // Exchange code cannot be in the form N11
        if ($tf === true) {
            if (($data[4] == 1) && ($data[5] == 1)) {
                $tf = false;
            } else {
                $tf = true;
            }
        }

        // Only allow [2–9] for first digit of exchange code
        if ($tf === true) {
            if ($data[6] < 2) {
                $tf = false;
            } else {
                $tf = true;
            }
        }

        // End exchange code check
        /////

        // END Validation checks common to ALL US formatted phone numbers
        //////////

        if ($tf === true) {
            // Phone number still valid

            if ($pnl == 10) {
                // Phone number with area code

                /////
                // Check NPA code (Digits 1-3)

                // Digit 1
                // Only allow [2–9]
                if ($data[9] < 2) {
                    $tf = false;
                } else {
                    $tf = true;
                }

                // Digit 2
                // Cannot be a 9
                // (X9X Reserved for potential North American Numbering Plan expansion)
                if ($tf === true) {
                    if ($data[8] == 9) {
                        $tf = false;
                    } else {
                        $tf = true;
                    }
                }

                // END NPA code check
                /////

            } elseif ($pnl == 11) {
                // 1-800 number

            }
        }

        if ($tf === true) {
            // Phone number still valid

            if ($fordb) {
                // Phone number already formatted for database
            } else {
                // Format phone number for display

                if ($pnl == 7) {
                    // Phone number without area code

                    $pn = substr($pn,0,3) . '-' . substr($pn,3,6);
                    $tf = true;
                } elseif ($pnl == 10) {
                    // Phone number with area code

                    $pn = '(' . substr($pn,0,3) . ') ' . substr($pn,3,3) . '-' . substr($pn,6,4);
                    $tf = true;
                } elseif ($pnl == 11) {
                    // 1-800 number

                    $pn = substr($pn,0,1) . '-' . substr($pn,1,3) . '-' . substr($pn,4,3) . '-' . substr($pn,7,4);
                    $tf = true;
                } else {
                    // Invalid phone number
                    $tf = false;
                }
            }
            // END if ($fordb)

        }
        // END if ($tf)

    }
    // END if ($tf)

    if (!$tf || $returnorig) {
        // Phone number is invalid or contains characters.
        // Return original version.
        $pn = $pnorig;
    }

    return array($tf,$pn);
}
// END function kgFormatPhoneNumber()
/////

function kgFormatZipCode($zipcodeorig, $fordb=false) {
    /**
     * Validates and formats US zipcodes and Canadian postal codes
     *
     * Added: 2017-08-16
     * Modifid: 2017-08-16
     *
     * Examples:
     *                 Display
     *      Input:     Output:     TF:
     *      17540      17540       true
     *      17540-1234 17540-1234  true
     *      175401234  17540-1234  true
     *      1740       1740        false
     *      17401234   17401234    false
     *
     *                  Database
     *      Input:      Output:     TF:
     *      17540       17540       true
     *      17540-1234  175401234  true
     *      175401234   175401234   true
     *      1740        1740        false
     *      1740-1234   17401234    false
     *
     *      Also validates Canadian Postal Codes
     *          Format is A0A 0A0
     *          Where A is a letter and 0 is a digit
     *          A space separates the third and fourth characters
     *
     * @param Required integer|string $zipcodeorig Zipcode/Postal code to be formatted
     * @param Optional boolean $fordb If true, format for storing data in database
     *                         If false, format for display (default)
     *
     * @return array(validated true||false, formatted data)
    **/

    // Work with a copy of the zip code
    $zipcode = $zipcodeorig;

    // Assume zip code is invalid
    $tf = false;

    if ((strlen($zipcode) == 5) || (strlen($zipcode) == 9)) {
        // USA zipcode

        // Gets rid of everything but numbers
        $zipcode = preg_replace('#\D#', '', $zipcode);

        if ($fordb) {
            // Format zipcode to be saved to database

            if (strlen($zipcode) <> 5 && strlen($zipcode) <> 9) {
                $tf = false;
            } else {
                $tf = true;
            }
        } else {
            // Format zipcode for display

            if (strlen($zipcode) == 5) {
                // 5 digit zipcode

                $tf = true;
            } elseif (strlen($zipcode) == 9) {
                // 9 digit zipcode

                // Insert the dash
                $zipcode = substr($zipcode,0,5) . '-' . substr($zipcode,5,4);
                $tf = true;
            } else {
                // Invalid zipcode
                $tf = false;
            }
        }

    } elseif ((strlen($zipcode) == 6) || (strlen($zipcode) == 7)) {
        // Canada Postal Codes

        // Convert all letters to uppercase
        $zipcode = strtoupper($zipcode);

        // Remove spaces and hyphens
        $zipcode = str_replace(" ", "", $zipcode);
        $zipcode = str_replace("-", "", $zipcode);

        if (strlen($zipcode) == 6) {
            // Postal Code length is valid

            // Make sure it is in the format A0A0A0, where A is a letter and 0 is a digit
            $data = str_split($zipcode);
            $cnt = count($data) - 1;

            for ($i = 0; $i <= $cnt; $i++) {
                if (($i % 2) == 0) {
                    // 1st, 3rd and 5th characters
                    // Supposed to be [A-Z]
                    if (is_numeric($data[$i])) {
                        $tf = false;
                        break;
                    } else {
                        $tf = true;
                    }
                } else {
                    // 2nd, 4th and 6th characters
                    // Supposed to be [0-9]
                    if (!is_numeric($data[$i])) {
                        $tf = false;
                        break;
                    } else {
                        $tf = true;
                    }
                }
    
            }
            // END for ($i = 0; $i <= $cnt; $i++)

            if ($fordb) {
                // Already formatted for database
            } else {
                // Format for display
                $zipcode = substr($zipcode,0,3).' '.substr($zipcode,3);
            }
        }
    }

    if (!$tf) {
        // Return original version because something is wrong with it
        $zipcode = $zipcodeorig;
    }

    return array($tf,$zipcode);
}
// END function kgFormatZipCode()
/////

function kgGetDateFormatList() {
    /**
     * A list of dates for the user to chose from when setting preferences.
     *
     * Added: 2017-06-24
     * Modified: 2017-06-24
     *
     * @param None
     *
     * @return Array
    **/

    $dates = array();

    $example = strtotime("now");

    $dates['h:i a - d M Y'] = date('h:i a - d M Y',$example);
    $dates['h:i a - D d M Y'] = date('h:i a - D d M Y',$example);
    $dates['h:i a - M d Y'] = date('h:i a - M d Y',$example);
    $dates['h:i a - D, M d Y'] = date('h:i a - D, M d Y',$example);
    $dates['h:i a - d M'] = date('h:i a - d M',$example);
    $dates['h:i a - D d M'] = date('h:i a - D d M',$example);
    $dates['h:i a - M d'] = date('h:i a - M d',$example);
    $dates['h:i a - D M d'] = date('h:i a - D M d',$example);
    $dates['H:i - M d Y'] = date('H:i - M d Y',$example);
    $dates['H:i - D, M d Y'] = date('H:i - D, M d Y',$example);
    $dates['H:i - d M Y'] = date('H:i - d M Y',$example);
    $dates['H:i - D d M Y'] = date('H:i - D d M Y',$example);
    $dates['H:i - d M'] = date('H:i - d M',$example);
    $dates['H:i - D d M'] = date('H:i - D d M',$example);
    $dates['H:i - M d'] = date('H:i - M d',$example);
    $dates['H:i - D M d'] = date('H:i - D M d',$example);
    $dates['Hi - D M d, Y'] = date('Hi - D, M d, Y',$example);

    return $dates;
}
// END function dateformatlist()

function kgGenShortName($str) {
    /**
     * Create the shortname from a given string for easier searching of the database
     *
     * Added: 2017-06-17
     * Modified: 2017-06-17
     *
     * @param Required string $str The string to be modified
     *
     * @return string
    **/

    // Removes all spaces, newlines and tabs
    $str = preg_replace('/\s+/', '', $str);

    // Some strings have non-alphanumeric characters in them
    // Any characters in this array will be preserved
    $find = array('$','!','#');

    $positions = array();
    $strlength = strlen($str);

    foreach ($find as $key => $findme) {

        // Set to 0 for each character in the $find array
        $offset = 0;

        // Make sure the character exists
        if (strpos($str, $findme, $offset) !== false) { 

            for($i = 0; $i<$strlength; $i++) {

                // Match found
                $pos = strpos($str, $findme, $offset);

                if ($pos == $offset) {
                        
                    // Save character position to array
                    $positions[$pos] = $findme;

                }

                // -Increment $offset so we don't find the same character
                //  multiple times.
                $offset++;

            }
            // END for($i = 0; $i<$strlength; $i++)

        }

    }
    // END foreach ($find as $key => $findme)

    // Sort the array by key, maintaining key[data] pairs
    ksort($positions);

    // Removes all non-alphnumeric characters
    $str = preg_replace("/[^A-Za-z0-9]/", '', $str);

    // Convert all characters to lower case
    $str = strtolower($str);

    // Reinsert the contents of the $positions array
    foreach ($positions as $pos => $char) {
        $str = substr_replace($str,$char,$pos,0);
    }

    return $str;
}
// END function kgGenShortName();
/////

function kgGetLangList() {
    /**
     * Fills an array with all of the supported languages
     *
     * Added: 2017-06-24
     * Modified: 2017-06-24
     *
     * @param None
     *
     * @return array
    **/

    global $IP;

    $languages = array();

    $temp = kgFilterFileList($IP.'/includes/lang', '.php', true);

    // Set the value as key
    foreach ($temp as $key => $value) {
        $value = str_replace('.php','',$value);

        if (strlen($value) == 2) {
            $languages[$value] = $value;
        }
    }

    return $languages;
}
// END function kgGetLangList()

function kgGetScriptName() {
    /**
     * Returns the script name (including file extension) and does a sanity check
     * Strips $_GET variables from returned value
     *
     * Added: 2017-06-07
     * Modified: 2017-06-07
     *
     * @param none
     *
     * @returns string $result The path to $_SERVER['PHP_SELF'] as viewed by the browser
     *                         Example: If $_SERVER['PHP_SELF'] = /a/b/c/d/codetest.php?example=true&testing=yes
     *                                  will return /a/b/c/d/codetest.php
    **/

    $php_self = utf8_decode($_SERVER['PHP_SELF']);

    // These two lines remove anything not in $regex
    // Items that will be kept: [a-z][A-Z][0-9][_=&/.-?+]
    $regex = '/[^a-zA-Z0-9_=&\/\.\-\?\+]/';
    $result = preg_replace_callback($regex,create_function('$matches','return \'\';') ,$php_self );

    // Keeps file extention but removes anything that follows it
    $result = substr($result,0,strrpos($result,".php")+4);

    return $result;
}
// END function kgGetScriptName()

function kgGetSkinList() {
    /**
     * Fills an array with all of the available skins (color schemes)
     *
     * Added: 2017-06-24
     * Added: 2017-06-24
     *
     * @param None
     *
     * @return array
    **/

    global $IP;

    $skins = array();

    // Get list of available skins
    $temp = kgGetDirlist($IP.'/skins');

    // Set the value as key
    foreach ($temp as $key => $value) {
        if ($value != 'css') {
            $skins[$value] = $value;
        }
    }

    return $skins;
}
// END function kgGetSkinList()
/////

function kgWrapString($str, $limit=82) {
    /**
     * Wraps a string at the space closest to the limit without going over.
     *
     * Paragraphs are preserved.
     *
     * Added: 2017-06-29
     * Modified: 2017-06-29
     *
     * Example:
     *     Limit: 82 characters
     *     Input:
     *         3 Doors Down is an American rock band from Escatawpa, Mississippi who formed in \
     *         1996. The band originally consisted of Brad Arnold (vocals/drums), Todd Harrell \
     *         (bass) and Matt Roberts (guitar).
     *     Returns:
     *         3 Doors Down is an American rock band from Escatawpa, Mississippi who formed in
     *         1996. The band originally consisted of Brad Arnold (vocals/drums), Todd Harrell
     *         (bass) and Matt Roberts (guitar).
     *     instead of (note split words at end of lines)
     *         3 Doors Down is an American rock band from Escatawpa, Mississippi who formed in 19
     *         96. The band originally consisted of Brad Arnold (vocals/drums), Todd Harrell (bas
     *         s) and Matt Roberts (guitar).
     *
     * @param Required string $str The string to be cut
     * @param Optional integer $limit The length that $str will be shortened to
     *
     * @return string $str The shortened string
    **/

    // Replace \r\n with \n
    $str = str_replace("\r\n","\n",$str);

    // Catch any stray \r
    $str = str_replace("\r","\n",$str);

    $nlsplits = array();

    // Splits $str at each paragraph break
    $nlsplits = explode("\n\n", $str);

    $str = '';

    // Replace all new line characters within each paragraph with a space.
    // The paragraph is now all on 1 line.
    foreach ($nlsplits as $key => $paragraph) {
        $nlsplits[$key] = str_replace("\n"," ",$paragraph);
    }

    // Recombine the paragraphs adding the line breaks between each paragraph
    foreach ($nlsplits as $key => $paragraph) {
        $str .= $paragraph."\n\n";
    }

    // Remove trailing \n\n from $str
    $str = rtrim($str);

    if (strlen($str) > $limit) {

        // Splits $str at each existing \n (new line) character to preserve paragraphs
        $nlsplits = preg_split("/[\n]+/", $str);

        // All line segments will be stored in this array
        $nlstr = array();

        foreach ($nlsplits as $key => $str) {

            $remainder = $str;

            while (strlen($remainder) > $limit) {

                // Find the space and split the string (Creates a segment)
                $nlstr[] = preg_replace('/\s+?(\S+)?$/', '', substr($remainder, 0, $limit));

                // Count number of characters in last segment of string
                // Subtract 1 from the count because php arrays are zero based
                $cnt = count($nlstr)-1;
                $cnt = strlen($nlstr[$cnt]);

                // Remove last segment of string from $remainder
                $remainder = substr($remainder, $cnt);

            }

            // Don't forget the part of the string not processed by the preg_replace() function
            $nlstr[] = $remainder;

            // Add newline character so there is a blank line between paragraphs
            $nlstr[] = "\n";

        }

        // Now rebuild the string adding line breaks at the end of each line segment
        // -Subtracting 2 from count($nlstr) because PHP arrays are zero based and
        //  $nlstr[count($nlstr)] already has a new line character
        $str = '';
        $cnt = count($nlstr)-2;
        foreach ($nlstr as $key => $value) {
            $str .= trim($value);

            // Add a newline character at the end of all lines except the last line
            if ($key < $cnt) {
                $str .= "\n";
            }
        }

    }

    return $str;

}
// END function kgWrapString()
////
?>
