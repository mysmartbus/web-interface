<?php
/**
 * Fuctions to use when creating an HTML form
 *
 * Added: 2017-06-08
 * Updated: 2017-07-29
*/
class HtmlForm {

    // These variables will have default values assigned by $this->reset_form();
    private $_escapestring;
    private $_fielddisabled;
    private $_fieldtitle;
    private $_hiddenfields;
    private $_inlinestyle;
    private $_newid;
    private $_noecho;
    private $_onblur;
    private $_onclick;
    private $_onfocus;
    private $_onsubmit;

    function __construct() {
        // Set default values
        $this->reset_form();
    }

    public function add_button_generic($name='submit',$value='Submit',$onclick='',$css='') {
        /**
         * A generic button
         * -Useful for redirecting someone to another page without having to toss
         *      the button outside all of the forms and messing up the page layout
         *   or
         *      for "forms" inside forms
         *
         *  Examples:
         *
         *      Change page location
         *      $form->add_button_generic('ACTON',get_lang('Cancel'),'location.href="/contacts.php?contactid='.$prevcontactid.'";');
         *
         *      Run javascript function
         *      $form->add_button_generic('submit',get_lang('ClearFrontCover'),'clearRB(document.frmeditalbum.frontcoverradio)');
         *
         * Added: 2017-06-08
         * Modified: 2017-07-29
         *
         * @param Optional string $name The name of the button. This is not the buttons label.
         * @param Optional string $value The text to display on the button. This is the buttons label.
         * @param Optional string $onclick Javascript code or function to run when this button is clicked.
         * @param Optional string $css The CSS class to apply to the button
        **/

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        if ($this->_inlinestyle != '') {
            $style = $this->_inlinestyle;
            $this->_inlinestyle = '';
        } else {
            $style = '';
        }

        if ($css != '') {
            $css = ' class="'.$css.'"';
        }

        if ($onclick != '') {
            $onclick = ' onclick="'.$onclick.'"';
        }

        if ($this->_fielddisabled === true) {
            $disabled = ' disabled';
            $this->_fielddisabled = false;
        } else {
            $disabled = '';
        }
        
        $returnstring = '<button type="button" name="'.$name.'" id="'.$id.'"'.$css.$style.$disabled.$onclick.'>'.$value.'</button>';

        if ($this->_noecho) {
            if ($this->_escapestring) {
                $returnstring = addslashes($returnstring);
            }
            return $returnstring;
        } else {
            echo $returnstring;
        }
    }

    public function add_button_reset($name='',$value='Reset',$css='') {
        // Form reset button

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($value);
        }

        if ($this->_inlinestyle != '') {
            $style = $this->_inlinestyle;
            $this->_inlinestyle = '';
        } else {
            $style = '';
        }

        if ($name == '') {
            $name = get_lang('Reset');
        }

        if ($css != '') {
            $css = ' class="'.$css.'"';
        }

        if ($this->_fielddisabled === true) {
            $disabled = ' disabled';
            $this->_fielddisabled = false;
        } else {
            $disabled = '';
        }

        echo '<button type="reset" name="'.$name.'" id="'.$id.'"'.$css.$style.$disabled.'>'.$value.'</button>';
    }

    public function add_button_submit($value='Submit',$name='submit',$css='') {
        /**
         * Creates a form submit button
         *
         * Added: 2017-06-08
         * Modified: 2017-06-08
         *
         * @param Optional $value Text to display on the button
         * @param Optional $name Name of button. Usefull if form has multiple submit buttons
         * @param Optional $css CSS class for formatting the button
         *
         * @return Nothing
        **/

        if ($name == '') {
            $name = 'submit';
        }

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        if ($this->_inlinestyle != '') {
            $style = ' style="'.$this->_inlinestyle.'"';
            $this->_inlinestyle = '';
        } else {
            $style = '';
        }

        if ($css != '') {
            $css = ' class="'.$css.'"';
        }

        if ($this->_fielddisabled === true) {
            $disabled = ' disabled';
            $this->_fielddisabled = false;
        } else {
            $disabled = '';
        }

        echo '<button type="submit" name="'.$name.'" id="'.$id.'"'.$css.$style.$disabled.'>'.$value.'</button>';
    }

    public function add_checkbox($name,$value,$label='',$checked=false,$onclick='') {
        /**
         * Create a checkbox
         *
         * Added: 2017-06-08
         * Modified: 2017-06-23
         *
         * @param Required $name    Unique name to give this form field.
         *                          This is also the fields id="" value
         *                          unless overridden by $this->_newid
         * @param Required $value   The value to return upon form submission
         * @param Optional $label   The text that tells the user what the checkbox is for
         * @param Optional $checked Set to true to have the checkbox checked when the page loads
         * @param Optional $onclick Javascript/jquery code to run when the checkbox is clicked
         *                 DEPRECIATED in favor of using $this->set_onclick(). This is to make the code
         *                             easier to read and edit.
         *
         * @return An HTML select list
        **/

        $opts = '';

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        if ($this->_fieldtitle != '') {
            $opts .= ' title="'.$this->_fieldtitle.'"';
            $this->_fieldtitle = '';
        }

        if ($checked === true) {
            $opts .= ' checked';
        }

        if ($this->_onclick != '') {
            $opts .= ' onclick="'.$this->_onclick.'"';
            $this->_onclick = '';
        } elseif ($onclick != '') {
            $opts .= ' onclick="'.$onclick.'"';
        }

        $returnstring = '<input type="checkbox" name="'.$name.'" id="'.$id.'" value="'.$value.'"'.$opts.'><label for="'.$id.'"> '.$label.'</label>';

        if ($this->_noecho) {
            if ($this->_escapestring) {
                $returnstring = addslashes($returnstring);
            }
            return $returnstring;
        } else {
            echo $returnstring;
        }
    }

    public function add_contact_list($name,$contactid=0) {
        /**
         * -Lists <Last_Name, First_Name> or <CompanyName> of all entries in the Contacts database
         * -Uses ContactTypeID to determine which data to display
         * -This is a customized version of $this->add_select_remote_db()
         *
         * Added: 2017-06-08
         * Modified: 2017-06-08
         *
         * @param Required $name Unique name to give this form field. This is also the fields id="" value
         * @param Optional $contactid ID number of the contact to select.
         *
         * @return An HTML select list
        **/

        global $KG_DBC;

        $opts = '';

        if ($name == '') {
            $name = 'contact';
        }

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        if ($this->_onclick != '') {
            $opts .= ' onchange="'.$this->_onclick.'"';
            $this->_onclick = '';
        }

        //Connect to the contacts database
        $KG_DBC->connectDatabase('contacts');

        // Default to false (No match found)
        $matched = false;

        /*
            An array that will contain either the full name
            if a personal contact or company name if business
            contact of every contact in the Contacts database.
        */
        $list = array();

        // Get the list
        $query = 'SELECT contactid, contacttypeid, concat_ws(\', \', lastname, firstname) AS fullname, companyname FROM contacts';
        $data = $KG_DBC->query($query);

        // Populate the array
        while($row = $KG_DBC->fetchAssoc($data)) {
            if ($row['contacttypeid'] == 1) {
                // Personal Contact
                $list[$row['contactid']] = $row['fullname'];
            } elseif ($row['contacttypeid'] == 2) {
                // Business Contact
                $list[$row['contactid']] = $row['companyname'];
            }
        }

        // Sort $list by contact name
        asort($list);
        
        ?>
        <select name="<?php echo $name; ?>" id="<?php echo $id; ?>" <?php echo $opts; ?>>
        <?php
            foreach ($list as $cid => $name) {
                // Set $matched to true if a match is found
                $matched = ($cid == $contactid ? true : false);
                ?>
                <option value="<?php echo $cid; ?>"<?php echo ($matched === true ? " selected" : ''); ?>><?php echo $name; ?></option><?php echo "\n"; ?>
                <?php
            }
        echo "</select>";

        $KG_DBC->connectPreviousDatabase();
    }
    // END function add_contact_list();

    public function add_contact_list_companies($name='',$contactid=0) {
        // Generates a select list containing only names of business contacts from the Contacts database

        if ($name == '') {
            $name = 'contact';
        }

        // Get the list
        $query = 'SELECT ContactID, CompanyName FROM Contacts WHERE ContactTypeID = 1 ORDER BY CompanyName';
        $this->add_select_remote_db($name,'Contacts',$query,'ContactID',$contactid,'CompanyName');
    }
    // END function add_contact_list_companies();

    public function add_contact_list_person($name='',$contactid=0) {
        // Generates a select list containing only names of personal contacts from the Contacts database

        if ($name == '') {
            $name = 'contact';
        }

        // Get the list
        $query = 'SELECT ContactID, concat_ws(\', \', LastName, FirstName) AS FullName FROM Contacts WHERE ContactTypeID = 1 ORDER BY FullName';
        $this->add_select_remote_db($name,'Contacts',$query,'ContactID',$contactid,'FullName');
    }
    // END function add_contact_list_person();

    public function add_file($name) {
        // Form field/button to allow for file selection/upload

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        ?>
        <input type="file" name="<?php echo $name; ?>" id="<?php echo $id; ?>">
        <?php
    }

    public function add_hidden($array) {
        /**
         * Name/value pairs that will be added to the HTML form
         * Should be called before calling start_form*
         *
         * If called after start_form*, you must call $this->show_hidden() to
         * add the fields to the form.
         *
         * Added: 2017-06-08
         * Modified: 2017-06-08
         *
         * @param Required array $array The array of name/value pairs
         *
         * @return Nothing
        **/

        if (is_array($array) === true) {
            foreach ($array as $name => $value) {
                $this->_hiddenfields[$name] = $value;
            }
        }
    }

    public function add_password($name,$value='',$maxlength=0,$size=0,$css='') {
        // Password field

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        if (!is_numeric($maxlength) || $maxlength < 0) {
            $maxlength == 0;
        }
        if (!is_numeric($size) || $size < 0) {
            $size == 0;
        }
        ?>
        <input type="password" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo $value; ?>"<?php echo ($maxlength > 0 ? ' maxlength="'.$maxlength.'"' : ''); echo ($size > 0 ? ' size="'.$size.'"' : ''); echo $css; ?>>
        <?php
    }

    public function add_radio($name,$value,$label='',$checked=false,$onclick='') {
        // Radio button

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        ?>
        <input type="radio" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="<?php echo $value; ?>"<?php echo ($checked === true ? ' checked' : ''); echo ($onclick != '' ? ' onclick="'.$onclick.'"' : ''); ?>><?php echo ($label != '' ? ' '.$label : ''); ?>
        <?php
    }

    public function add_radio_yesno($name,$yesno=false) {
        /**
         * Yes/no radio button group
         *
         * Added: 2017-06-08
         * Modified: 2017-06-08
         *
         *  $yesno values
         *      (bool)True = 'Yes' button selected
         *      (bool)False = 'No' button selected
         *
         * Returned value is fixed as either a '1' for yes or '0' for no
        **/

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        ?>
        <input type="radio" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="1"<?php echo ($yesno===true ? ' checked' : ''); ?>> Yes <input type="radio" name="<?php echo $name; ?>" id="<?php echo $id; ?>" value="0"<?php echo ($yesno===false ? ' checked' : ''); ?>> No
        <?php
    }

    public function add_select_date($name='',$sme='m',$showblank=true,$yearspan=30,$selectblanks=false,$year=-1,$month=-1,$day=-1) {
        // -Generates 3 select lists to select a year, month and day
        // -All dates supplied must be split into year, month and day parts

        // If $name = '', field names will be 'Year', 'Month' and 'Day'

        // -Valid values for $sme are
        //  > s - Year given will be at the top/start of the list
        //  > m - Year given will in the middle of the list (default)
        //  > e - Year given will be at the bottom/end of the list

        // If $year equals -2, then $year will be current year minus 1
        // If $year equals -1, then $year will be current year

        // If $month equals -2, then $month will be current month minus 1
        //    If $month < 1 then $month will be set to 12
        //    If $month > 12 then $month will be set to 1
        // If $day equals -2, then $day will be current day minus 1
        //    If $day < 1 then $day will be set to 31
        //    If $day > 31 then $day will be set to 1

        // Convert to lower case for easier matching
        if ($sme == '') $sme = 'm';
        $sme = strtolower($sme);

        /////
        // Make sure date is valid
        //
        // Year (Four Digit)
        if ($year == -1) {
            // Year was not given, use current year
            $year = date("Y");

        } elseif ($year == -2) {
            // Year was not given, use current year - 1
            $year = date("Y") - 1;

        }
        // Month
        if ($month == -1) {
            // Month was not given, use current month
            $month = date("m");

        } elseif ($month == -2) {
            // Month was not given, use current month - 1
            $month = date("m") - 1;

        }
        // Day
        if ($day == -1) {
            // Day was not given, use current day
            $day = date("d");

        } elseif ($day == -2) {
            // Day was not given, use current day - 1
            $day = date("d") - 1;

        }
        // Make sure date is valid
        /////

        // Set year range
        if ($sme == 's') {
            $yearstart = $year;
            $yearend = $year + $yearspan;
        } elseif ($sme == 'm') {
            $yearstart = $year - ceil($yearspan/2);
            $yearend = $year + ceil($yearspan/2);
        } elseif ($sme == 'e') {
            $yearstart = $year - $yearspan;
            $yearend = $year;
        }
        $yearrange = range($yearstart,$yearend);

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        // Create select list
        echo '<select name="'.$name.'Year" id="'.$id.'Year">'."\n";
        if ($showblank) {
            echo '<option value=""'.($selectblanks ? ' selected' : '').'></option>'."\n";
        }
        foreach ($yearrange as $key => $value) {
            echo '<option value="'.$value.'"'.(($year == $value) && !$selectblanks ? ' selected' : '').'>'.$value.'</option>'."\n";
        }
        echo '</select>'."\n - ";
        // End year

        // Set month range
        $monthrange = range(1,12);

        // Create select list
        echo '<select name="'.$name.'Month" id="'.$id.'Month">'."\n";
        if ($showblank) {
            echo '<option value=""'.($selectblanks ? ' selected' : '').'></option>'."\n";
        }
        foreach ($monthrange as $key => $value) {
            echo '<option value="'.sprintf("%02d", $value).'"'.(($month == $value) && !$selectblanks ? ' selected' : '').'>'.sprintf("%02d", $value).'</option>'."\n";
        }
        echo '</select>'."\n - ";
        // End month

        // Set day range
        $dayrange = range(1,31);

        echo '<select name="'.$name.'Day" id="'.$id.'Day">'."\n";
        if ($showblank) {
            echo '<option value=""'.($selectblanks ? ' selected' : '').'></option>'."\n";
        }
        foreach ($dayrange as $key => $value) {
            echo '<option value="'.sprintf("%02d", $value).'"'.(($day == $value) && !$selectblanks ? ' selected' : '').'>'.sprintf("%02d", $value).'</option>'."\n";
        }
        echo '</select>'."\n";
        // End day

    }
    // function add_select_date()
    //////////

    public function add_select_db($name,$query,$matchfield,$matchvalue,$displayvalue,$dbc) {
        /**
         * Generates a <select></select> list populated from database
         *
         * Added: 2017-06-08
         * Modified: 2017-06-16
         *
         * @param Required string $name The name to give this field
         * @param Required string $query The SQL query used to retreive the data
         * @param Required string $matchfield The field to look for $matchvalue in
         * @param Required string $matchvalue The value (string or integer) to look for
         * @param Required string|array $displayvalue The name or names of the fields to show in the select list
         * @param Required Mysql  $dbc The MySQL object from the calling module.
         *                             This is needed so $query can be run against the correct database
         *
         * @return string
        **/

        $rs = '';

        $matched = false;
        $data = $dbc->query($query);

        if ($this->_fielddisabled === true) {
            $disabled = ' disabled';
            $this->_fielddisabled = false;
        } else {
            $disabled = '';
        }

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        // Should anything be done when the user changes the selected option?
        if ($this->_onsubmit != '') {
            $onchange = $this->_onsubmit;
            $this->_onsubmit = '';
        } else {
            $onchange = '';
        }

        $rs .=' <select name="'.$name.'" id="'.$id.'"'.$disabled.($onchange != '' ? ' onchange="'.$onchange.'"' : '').'>';
            while($row = $dbc->fetchAssoc($data)) {
                // Set $matched to true if a match is found
                $matched = ($row[$matchfield] == $matchvalue ? true : $matched);

                // Format $displayvalue
                if (is_array($displayvalue)) {
                    $dpvalue = $row[$displayvalue[0]].' - '.$row[$displayvalue[1]];
                } else {
                    $dpvalue = $row[$displayvalue];
                }
                $rs .= '<option value="'.$row[$matchfield].'"'.($row[$matchfield]==$matchvalue ? " selected" : '').'>'.$dpvalue.'</option>';
            }
        $rs .= "</select>";

        return $rs;
    }
    // function add_select_db()
    //////////

    public function add_select_db_autoecho($name,$query,$matchfield,$matchvalue,$displayvalue,$dbc) {
        echo $this->add_select_db($name,$query,$matchfield,$matchvalue,$displayvalue,$dbc);
    }

    public function add_select_generic($name,$list,$matchme='',$selectvia='',$notinarray='',$note='',$shownomatch=true) {
        // Use add_select_match_key() or add_select_match_value() instead.
        // This function exists for backwards compatibility
        //
        // Select List Generic

        // -Valid values for $selectvia are
        //  > v - Matches value. Displays value.
        //  > k - Matches key. Displays key.
        //  > [blank] - Matches value. Displays key.
        $matched = 0;
        $selectvia = strtolower($selectvia);

        // Should anything be done when the user changes the selected option?
        $onchange = '';
        if ($this->_onsubmit != '') {
            $onchange = $this->_onsubmit;
        }

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        ?>
        <select name="<?php echo $name; ?>" id="<?php echo $id; ?>"<?php echo ($onchange != '' ? ' onchange="'.$onchange.'"' : ''); ?>>
        <?php
        foreach ($list as $key => $value) {

            if ($selectvia == 'v') {
                // Match value. Display value.
                $matched = ($value == $matchme ? 1 : $matched);
                ?>
                <option value="<?php echo $value; ?>"<?php echo ($value == $matchme ? " selected" : ''); ?>><?php echo $value; ?></option><?php echo "\n"; ?>
                <?php
            } elseif ($selectvia == 'k') {
                // Match key. Display key.
                $matched = ($key == $matchme ? 1 : $matched);
                ?>
                <option value="<?php echo $key; ?>"<?php echo ($key == $matchme ? " selected" : ''); ?>><?php echo $key; ?></option><?php echo "\n"; ?>
                <?php
            } else {
                // Match value. Display key.
                $matched = ($value == $matchme ? 1 : $matched);
                ?>
                <option value="<?php echo $value; ?>"<?php echo ($value == $matchme ? " selected" : ''); ?>><?php echo $key; ?></option><?php echo "\n"; ?>
                <?php
            }
        }
        echo "</select>";

        if (!$matched && $shownomatch) {
            // Printed to the right of above select list
            if ($notinarray != '') {
                echo $notinarray;
            } else {
                echo " - No match found";
            }
        }

        if ($note != '') {
            // Display note
            echo ' <span class="tinytext">'.$note.'</span>';
        }
    }

    public function add_select_match_key($name,$list,$matchme='',$css='') {
        // Match via array key

        // $list must be an array

        if ($this->_onsubmit != '') {
            $onchange = $this->_onsubmit;

            $this->_onsubmit = '';
        } else {
            $onchange = '';
        }

        if ($this->_fielddisabled === true) {
            $disabled = ' disabled';
            $this->_fielddisabled = false;
        } else {
            $disabled = '';
        }

        if ($css != '') {
            $css = ' class="'.$css.'"';
        }

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        echo '<select name="'.$name.'" id="'.$id.'"'.($onchange != '' ? ' onchange="'.$onchange.'"' : '').$css.$disabled.'>';

        foreach ($list as $key => $value) {

            echo '<option value="'.$key.'"'.($key == $matchme ? " selected" : '').'>'.$value.'</option>'."\n";

        }
        echo "</select>\n";
    }

    public function add_select_match_value($name,$list,$matchme='',$css='') {
        // Match via array value

        // $list must be an array
        if (is_array($list) === false) {
            return;
        }

        if ($this->_onsubmit != '') {
            $onchange = ' onchange="'.$this->_onsubmit.'"';

            $this->_onsubmit = '';
        } else {
            $onchange = '';
        }

        if ($this->_fielddisabled === true) {
            $disabled = ' disabled';
            $this->_fielddisabled = false;
        } else {
            $disabled = '';
        }

        if ($css != '') {
            $css = ' class="'.$css.'"';
        }

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        ?>
        <select name="<?php echo $name; ?>" id="<?php echo $id; ?>"<?php echo $css.$onchange.$disabled; ?>>
        <?php
        foreach ($list as $key => $value) {

            echo '<option value="'.$key.'"'.($value == $matchme ? " selected" : '').'>'.$value.'</option>'."\n";

        }
        echo "</select>";
    }

    public function add_select_remote_db($name,$remotedb,$query,$matchfield,$matchvalue,$displayvalue,$shownomatch=false) {
        // Select list populated from remote database

        global $KG_DBC;

        //Database Connection Setup
        $KG_DBC->connectDatabase($remotedb);

        $matched = false;
        $data = $KG_DBC->query($query);

        if ($this->_fielddisabled === true) {
            $disabled = ' disabled';
            $this->_fielddisabled = false;
        } else {
            $disabled = '';
        }

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        ?>
        <select name="<?php echo $name; ?>" id="<?php echo $id; ?>" <?php echo $disabled; ?>>
        <?php
            while($row = $KG_DBC->fetchAssoc($data)) {
                // Set $matched to 1 if a match is found
                $matched = ($row[$matchfield] == $matchvalue ? true : $matched);

                // Format $displayvalue
                if (is_array($displayvalue)) {
                    $dpvalue = $row[$displayvalue[0]].' - '.$row[$displayvalue[1]];
                } else {
                    $dpvalue = $row[$displayvalue];
                }
                ?>
                <option value="<?php echo $row[$matchfield]; ?>"<?php echo ($row[$matchfield]==$matchvalue ? " selected" : ''); ?>><?php echo $dpvalue; ?></option><?php echo "\n"; ?>
                <?php
            }
        echo "</select>";

        if ($shownomatch) {
            // Printed to the right of above select list
            if (!$matched) {
                echo " - No match found";
            }
        }
    }
    // function add_select_remote_db()
    //////////

    public function add_select_time_12hr($name,$matchhour=-1,$matchminute=-1) {

        /**
         *
         * Added: 2017-06-08
         * Modified: 2017-06-08

            Displays time using the 12 hour clock

            $name => name used to identify this group of select boxes.
            $matchhour => Event hour (optional)
            $matchminute => Event minute (optional)
        **/

        if ($matchhour <= -1) {
            // Hour not supplied, use current hour

            // 24-hour format of an hour without leading zeros (0 through 23)
            $matchhour = date('G');

        }

        if ($matchminute <= -1) {
            // Minute not supplied, use current minute

            // Minutes with leading zeros (00 to 59)
            $matchminute = date('i');

        }

        if ($matchhour > 12) {
            // Convert time from 24-hour clock to 12-hour clock
            $matchhour = $matchhour - 12;
            $ampm = 'PM';
        } else {
            $ampm = 'AM';
        }

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        // Hour select list
        ?>
        <select name="<?php echo $name.'hour'; ?>" id="<?php echo $id.'hour'; ?>">
        <?php

        foreach (range(1, 12) as $i) {
            ?>
            <option value="<?php echo $i; ?>"<?php echo ($i == $matchhour ? " selected" : ''); ?>><?php echo $i; ?></option><?php echo "\n"; ?>
            <?php

        }

        echo "</select>";

        // Minute select list
        ?>
        <select name="<?php echo $name.'minute'; ?>" id="<?php echo $id.'minute'; ?>">
        <?php

        foreach (range(0, 59) as $i) {
            $minute = sprintf("%02d",$i);

            ?>
            <option value="<?php echo $minute; ?>"<?php echo ($minute == $matchminute ? " selected" : ''); ?>><?php echo $minute; ?></option><?php echo "\n"; ?>
            <?php
        }

        echo "</select>";

        // AM/PM select list
        ?>
        <select name="<?php echo $name.'ampm'; ?>" id="<?php echo $id.'ampm'; ?>">
            <option value="am"<?php echo ($ampm == 'AM' ? " selected" : ''); ?>>AM</option><?php echo "\n"; ?>
            <option value="pm"<?php echo ($ampm == 'PM' ? " selected" : ''); ?>>PM</option><?php echo "\n"; ?>
        </select>
        <?php

    }

    public function add_select_time_24hr($name, $matchhour=-1, $matchminute=-1, $minutestep = 1) {
        /**
         * Displays time using the 24 hour clock
         *
         * My 24 hour clock goes from 00:00 (midnight) to 23:59 (11:59PM)
         * so incoming time will be formatted to match
         *
         * Added: 2014-04-30
         * Updated: 2015-12-31
         *
         * @param Required string $name name used to identify this group of select boxes.
         * @param Optional integer $matchhour Event hour
         * @param Optional integer $matchminute Event minute
         * @param Optional integer $minutestep Only display the numbers evenly divisible by this number
         *                              If equal to 5, will only list "00, 05, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55"
         *                              If equal to 8, will only list "00, 08, 16, 24, 32, 40, 48, 56"
         *                              $matchminute will only be matched if it is in the list generated by $minutestep
        **/

        if ($matchhour <= -1) {
            // Hour not supplied, use current hour

            // 24-hour format of an hour without leading zeros (0 through 23)
            $matchhour = date('G');

        }

        if ($matchminute <= -1) {
            // Minute not supplied, use current minute

            // Minutes with leading zeros (00 to 59)
            $matchminute = date('i');

        }

        if (($matchhour >= 24) && ($matchminute == 0)) {
            // convert 2400 to 0000
            $matchhour = 0;
        }

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        // Create the field names
        if (strpos($name, '[]') !== false) {

            $name = str_replace("[]","",$name);

            // Allows for multiple fields with the same name
            $hourname = $name.'hour[]';
            $minutename = $name.'minute[]';
        } else {
            $hourname = $name.'hour';
            $minutename = $name.'minute';
        }

        // Hour select list
        $returnstring = '<select name="'.$hourname.'" id="'.$id.'hour">';

        foreach (range(0, 23) as $i) {
            $returnstring .= '<option value="'.$i.'"'.($i == $matchhour ? ' selected="selected"' : '').'>'.sprintf("%02d",$i).'</option>';

        }

        $returnstring .= "</select>";

        // Minute select list
        $returnstring .= '<select name="'.$minutename.'" id="'.$id.'minute">';

        for($i = 0; $i < 60; $i += $minutestep) {
            // Add leading zero to numbers below 10
            $minute = sprintf("%02d",$i);

            $returnstring .= '<option value="'.$minute.'"'.($minute == $matchminute ? ' selected="selected"' : '').'>'.$minute.'</option>';
        }

        $returnstring .= "</select>";

        if ($this->_noecho) {
            if ($this->_escapestring) {
                $returnstring = addslashes($returnstring);
            }
            return $returnstring;
        } else {
            echo $returnstring;
        }

    }

    public function add_select_time_hour($name, $matchme) {

        $hrarray = array(12,1,2,3,4,5,6,7,8,9,10,11);

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        ?>
        <select name="<?php echo $name; ?>" id="<?php echo $id; ?>">
        <?php

        foreach (array('am', 'pm') as $ampm) {

            foreach ($hrarray as $hr) {

                ?>
                <option value="<?php echo $hr; ?>"<?php echo ($hr == $matchme ? " selected" : ''); ?>><?php echo $hr.$ampm; ?></option><?php echo "\n"; ?>
                <?php

            }
        }

        echo "</select>";

    }

    public function add_select_time_minute($name,$matchme) {

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        ?>
        <select name="<?php echo $name; ?>" id="<?php echo $id; ?>">
        <?php

        for ($i = 0; $i < 60; $i++) {
            $minute = sprintf("%02d",$i);

            ?>
            <option value="<?php echo $minute; ?>"<?php echo ($minute == $matchme ? " selected" : ''); ?>><?php echo $minute; ?></option><?php echo "\n"; ?>
            <?php
        }

        echo "</select>";
    }

    public function add_text($name,$value='',$size=15,$css='') {
        /**
         * Text field
         *
         * Added: 201?-?-?
         * Updated: 2017-03-11
         *
         * @param Required string $name Name used to identify this text box
         * @param Optional string $value Text to display inside the text box
         * @param Optional integer $size Number of characters visible in the text box
         * @param Optional string $css The CSS class to apply to the text box
         *
         * @return string or HTML code
        **/

        $opts = '';

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        if ($this->_fielddisabled === true) {
            $opts .= ' disabled';
            $this->_fielddisabled = false;
        }

        if ($this->_fieldtitle != '') {
            $opts .= ' title="'.$this->_fieldtitle.'"';
            $this->_fieldtitle = '';
        }

        if (!is_numeric($size) || $size < 1) {
            $size = 15;
        }

        if ($size > 0) {
            $opts .= ' size="'.$size.'"';
        }

        if ($this->_onblur != '') {
            $opts .= ' onblur="'.$this->_onblur.'"';
            $this->_onblur = '';
        }

        if ($this->_onfocus != '') {
            $opts .= ' onfocus="'.$this->_onfocus.'"';
            $this->_onfocus = '';
        }

        if ($css != '') {
            $opts .= ' class="'.$css.'"';
        }

        $returnstring = '<input type="text" name="'.$name.'" id="'.$id.'" value="'.$value.'"'.$opts.'>';

        if ($this->_noecho) {
            if ($this->_escapestring) {
                $returnstring = addslashes($returnstring);
            }
            return $returnstring;
        } else {
            echo $returnstring;
        }
    }

    public function add_textarea($name,$value='',$cols=40,$rows=5,$css='') {
        /**
         * HTML Textarea box
         *
         * Added: 201?-??-??
         * Updated: 2017-03-11
         *
         * @param Required string $name Name used to identify this textarea
         * @param Optional string $value Text to display inside the textarea
         * @param Optional integer $cols Number of characters to display between
         *                               the left and right sides of the textarea
         * @param Optional integer $rows Number of characters to display between
         *                               the top and bottom sides of the textarea
         * @param Optional string $css The CSS class to apply to the textarea

         *
         * @return string or HTML code
        **/

        $opts = '';

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($name);
        }

        if ($cols > 0) {
            $opts .= ' cols="'.$cols.'"';
        }

        if ($rows > 0) {
            $opts .= ' rows="'.$rows.'"';
        }

        if ($css != '') {
            $opts .= ' class="'.$css.'"';
        }

        $returnstring = '<textarea name="'.$name.'" id="'.$id.'"'.$opts.'>'.$value.'</textarea>';

        if ($this->_noecho) {
            if ($this->_escapestring) {
                $returnstring = addslashes($returnstring);
            }
            return $returnstring;
        } else {
            echo $returnstring;
        }
    }

    public function buttononlyform($formaction,$method='post',$frmname='',$buttonvalue='',$buttonname='',$css='') {
        // A single button form with no input boxes (text, check, radio)
        $this->start_form($formaction,$method,$frmname);
        $this->add_button_submit($buttonvalue,$buttonname,$css);
        $this->end_form();
    }

    public function disablefield() {
        // Call this function to disable a form field
        $this->_fielddisabled = true;
    }

    public function echo_off() {
        /**
         * Return the form field as a string of text
         *
         * Added: 2017-03-06
         * Modified: 2017-03-06
         *
         * @param None
         *
         * @return Nothing
        **/
        $this->_noecho = true;
    }

    public function echo_on() {
        /**
         * Display the form field
         *
         * Added: 2017-03-06
         * Modified: 2017-03-06
         *
         * @param None
         *
         * @return Nothing
        **/
        $this->_noecho = false;
    }

    public function end_form() {
        ?></form><?php
        $this->reset_form();
    }

    public function escape_on() {
        /**
         * Add slashes to the string returned by a function.
         * Requires $this->echo_off() to be called first.
         *
         * Initially added to allow the functions in this file
         * to be used in ajax calls.
         *
         * Added: 2017-03-10
         * Modified: 2017-03-10
         *
         * @param None
         *
         * @return Nothing
        **/
        $this->_escapestring = true;
    }

    public function escape_off() {
        /**
         * Stop adding slashes to the string returned by a function.
         * Can be called before or after $this->echo_on().
         *
         * Initially added to allow the functions in this file
         * to be used in ajax calls.
         *
         * Added: 2017-03-10
         * Modified: 2017-03-10
         *
         * @param None
         *
         * @return Nothing
        **/
        $this->_escapestring = false;
    }

    private function format_fieldid($id) {
        /**
         * Cleans up the value used by an HTML tags id="" attribute.
         *
         * Example in: This i_s a t0est[]
         * Example out: Thisisat0est
         * 
         * Added: 2015-0?-??
         * Update: 2017-07-22
         *
         * @param Required string $id The string to be formatted for use in the id=""
         *                            attribute of a form field
         *
         * @return string
        **/

        // Remove everything except a-z, A-Z, 0-9
        $id = preg_replace("/[^a-zA-Z0-9_]/", '', $id);

        return $id;
    }

    public function onsubmit($action) {
        /**
         * Set an action (usually javascript call) to take when form is submitted
        **/
        $this->_onsubmit = $action;
    }

    public function set_id($id) {
        /**
         * Normally, the HTML tag id="" attribute is set to the field name.
         * Use this function to override that value.
         * 
         * Added: 201?-??-?
         * Update: 2017-03-08
         *
         * @param Required string $id The string to be formatted for use in the id=""
         *                            attribute of a form field
         *
         * @return string $id
        **/
        $this->_newid = $this->format_fieldid($id);
    }

    public function set_fieldtitle($text) {
        // The text to be used inside a fields title="" attribute
        $this->_fieldtitle = $text;
    }

    public function set_inlinestyle($style) {
        // Call this function if you need to override some CSS code
        // This is the same as calling styles="" in the HTML tag
        $this->_inlinestyle = $style;
    }

    public function set_onblur($action) {
        /**
         * Specify javascript to run when a form field loses focus
         *
         * Added: 2017-03-05
         * Updated: 2017-03-05
         *
         * @param Required string $action Javascript code to run
        **/

        $this->_onblur = $action;
    }

    public function set_onclick($data='') {
        /**
         * Specify javascript to run when a form field is clicked on
         *
         * Added: 201?-??-??
         * Updated: 2017-03-08
         *
         * @param Required string $action Javascript code to run
        **/

        if ($data != '') {
            $this->_onclick = $data;
        }
    }

    public function set_onfocus($action='') {
        /**
         * Specify javascript to run when a form field gets focus
         *
         * Added: 2017-03-05
         * Updated: 2017-03-05
        **/
        if ($action != '') {
            $this->_onfocus = $action;
        }
    }

    private function show_hidden() {
        /**
         * Makes the hidden fields available to the form.
         *
         * Must be called before $this->start_form*
         *
         * Added: 2017-0?-??
         * Modified: 2017-07-28
         *
         * @param None
         *
         * @return Nothing
        **/

        if (!empty($this->_hiddenfields)) {

            foreach ($this->_hiddenfields as $name => $value) {
                ?>
                <input type="hidden" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo $value; ?>">
                <?php
            }

            // Clear the array
            $this->_hiddenfields = array();

        }
    }

    public function simple_form_select($fldname,$list,$desc='',$matchme='',$formaction='', $method='', $frmname='',$buttonvalue='',$buttonname='Submit',$css='') {
        /**
         * A single button form with a select list.
         *
         * Useful for creating a "go to page x" list.
         *
         * Added: 2017-05-13
         * Modified: 2017-05-13
         *
         * @param Required string   $fldname    Identifies this field in $_POST and $_GET calls
         * @param Required array    $list       Array keys will be the value submitted with the form
         *                                      Array values will be the text displayed in the list
         * @param Optional string   $desc       Text to display to the left of the select list
         * @param Optional string   $matchme    Automatically select this value from the list
         *                                      Must be one of the $list array keys to work
         * @param Optional string   $formaction Where to send the data when the form is submitted
         * @param Optional string   $method     Must be 'post', 'get', or '' (blank)
         * @param Optional string   $frmname    Identifies this form in $_POST and $_GET calls and in javascript/ajax calls
         * @param Optional string   $buttonvalue    Text to display on the submit button
         * @param Optional string   $buttonname Identifies this button in $_POST and $_GET calls
         * @param Optional string   $css        CSS class to use to set the look and feel of this button
         *
         * @return Nothing
        **/

        $this->start_form($formaction,$method,$frmname);       

        // Display description
        echo ($desc != '' ? $desc : '' );

        // Display input item
        $this->add_select_match_key($fldname,$list,$matchme);

        // Display submit button
        $this->add_button_submit($buttonvalue,$buttonname,$css);

        $this->end_form();
    }

    public function simple_form_text($fldname, $fldvalue='', $fldsize=5, $formaction='', $method='', $frmname='', $desc='', $buttonvalue='', $buttonname='Submit', $css='') {
        // Single button form with only 1 input field

        $this->start_form($formaction,$method,$frmname);       

        // Display description
        echo ($desc != '' ? $desc.': ' : '' );

        // Display input item
        $this->add_text($fldname,$fldvalue,$fldsize);

        // Display submit button
        $this->add_button_submit($buttonvalue,$buttonname,$css);

        $this->end_form();
    }

	public function start_form($formaction='', $method='', $frmname='') {
        /**
         * Start a generic form
         *
         * Added: 201?-??-??
         * Modified: 2015-12-15
         *
         * @param Optional $formaction The PHP script or html page to load when form is submitted
         * @param Optional $method Must be 'post' or 'get'
         * @param Optional $frmname A unique name for the form to identify it in javascript/jQuery functions
         *
         * @return Nothing
        **/

        global $KG_MODULE_NAME;

        if ($method == '') {
            $method = 'post';
        }
        if ($frmname == '') {
            $frmname = 'form1';
        }
        if ($formaction == '') {
            $formaction = kgGetScriptName();
        }
        if ($this->_onsubmit != '') {
            $onsubmit = $this->_onsubmit;
            $this->_onsubmit = '';
        } else {
            $onsubmit = '';
        }

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($frmname);
        }

        ?>
        <form action="<?php echo $formaction; ?>" method="<?php echo $method; ?>" name="<?php echo $frmname; ?>" id="<?php echo $id; ?>"<?php echo ($onsubmit != '' ? ' onsubmit="'.$onsubmit.'"' : '');?>>
        <?php
        // Goto the module that contains this form
        $this->add_hidden(array('KG_MODULE_NAME' => $KG_MODULE_NAME));

        $this->show_hidden();
	}

	public function start_form_upload($formaction='', $method='', $frmname='') {
        /**
         * Start a form to upload something
         *
         * Added: 201?-??-??
         * Modified: 2015-12-15
         *
         * @param Optional $formaction The PHP script or html page to load when form is submitted
         * @param Optional $method Must be 'post' or 'get'
         * @param Optional $frmname A unique name for the form to identify it in javascript/jQuery functions
         *
         * @return Nothing
        **/

        global $KG_MODULE_NAME;

        if ($method == '') {
            $method = 'post';
        }
        if ($frmname == '') {
            $frmname = 'form1upload';
        }
        if ($formaction == '') {
            $formaction = kgGetScriptName();
        }
        if ($this->_onsubmit != '') {
            $onsubmit = $this->_onsubmit;
            $this->_onsubmit = '';
        } else {
            $onsubmit = '';
        }

        if ($this->_newid != '') {
            $id = $this->_newid;
            $this->_newid = "";
        } else {
            $id = $this->format_fieldid($frmname);
        }

        ?>
        <form action="<?php echo $formaction; ?>" method="<?php echo $method; ?>" name="<?php echo $id; ?>" <?php echo ($onsubmit != '' ? ' onsubmit="'.$onsubmit.'"' : '');?> id="<?php echo $frmname; ?>" enctype="multipart/form-data">
        <?php
        // Goto the module that contains this form
        $this->add_hidden(array('KG_MODULE_NAME' => $KG_MODULE_NAME));

        $this->show_hidden();
	}

/*
  //
    These functions contain fixed lists so keep them seperate from the functions above
  //
*/

    public function states_select($state='') {
        /**
         * Creates a select list of states.
         * Uses the two letter abbreviation and is sorted alphabetcally
         * Hard coded since it probably won't change in the near future
         *
         * Added: 201?-??-??
         * Updated: 201?-??-??
        **/
        $state = strtoupper($state);
        ?>
        <select name="state">
            <option value="AL"<?php echo ($state == "AL" ? ' selected' : ''); ?>>Alabama</option>
            <option value="AK"<?php echo ($state == "AK" ? ' selected' : ''); ?>>Alaska</option>
            <option value="AZ"<?php echo ($state == "AZ" ? ' selected' : ''); ?>>Arizona</option>
            <option value="AR"<?php echo ($state == "AR" ? ' selected' : ''); ?>>Arkansas</option>
            <option value="CA"<?php echo ($state == "CA" ? ' selected' : ''); ?>>California</option>
            <option value="CO"<?php echo ($state == "CO" ? ' selected' : ''); ?>>Colorado</option>
            <option value="CT"<?php echo ($state == "CT" ? ' selected' : ''); ?>>Connecticut</option>
            <option value="DE"<?php echo ($state == "DE" ? ' selected' : ''); ?>>Delaware</option>
            <option value="FL"<?php echo ($state == "FL" ? ' selected' : ''); ?>>Florida</option>
            <option value="GA"<?php echo ($state == "GA" ? ' selected' : ''); ?>>Georgia</option>
            <option value="HI"<?php echo ($state == "HI" ? ' selected' : ''); ?>>Hawaii</option>
            <option value="ID"<?php echo ($state == "ID" ? ' selected' : ''); ?>>Idaho</option>
            <option value="IL"<?php echo ($state == "IL" ? ' selected' : ''); ?>>Illinois</option>
            <option value="IN"<?php echo ($state == "IN" ? ' selected' : ''); ?>>Indiana</option>
            <option value="IA"<?php echo ($state == "IA" ? ' selected' : ''); ?>>Iowa</option>
            <option value="KS"<?php echo ($state == "KS" ? ' selected' : ''); ?>>Kansas</option>
            <option value="KY"<?php echo ($state == "KY" ? ' selected' : ''); ?>>Kentucky</option>
            <option value="LA"<?php echo ($state == "LA" ? ' selected' : ''); ?>>Louisiana</option>
            <option value="ME"<?php echo ($state == "ME" ? ' selected' : ''); ?>>Maine</option>
            <option value="MD"<?php echo ($state == "MD" ? ' selected' : ''); ?>>Maryland</option>
            <option value="MA"<?php echo ($state == "MA" ? ' selected' : ''); ?>>Massachusetts</option>
            <option value="MI"<?php echo ($state == "MI" ? ' selected' : ''); ?>>Michigan</option>
            <option value="MN"<?php echo ($state == "MN" ? ' selected' : ''); ?>>Minnesota</option>
            <option value="MS"<?php echo ($state == "MS" ? ' selected' : ''); ?>>Mississippi</option>
            <option value="MO"<?php echo ($state == "MO" ? ' selected' : ''); ?>>Missouri</option>
            <option value="MT"<?php echo ($state == "MT" ? ' selected' : ''); ?>>Montana</option>
            <option value="NE"<?php echo ($state == "NE" ? ' selected' : ''); ?>>Nebraska</option>
            <option value="NV"<?php echo ($state == "NV" ? ' selected' : ''); ?>>Nevada</option>
            <option value="NH"<?php echo ($state == "NH" ? ' selected' : ''); ?>>New Hampshire</option>
            <option value="NJ"<?php echo ($state == "NJ" ? ' selected' : ''); ?>>New Jersey</option>
            <option value="NM"<?php echo ($state == "NM" ? ' selected' : ''); ?>>New Mexico</option>
            <option value="NY"<?php echo ($state == "NY" ? ' selected' : ''); ?>>New York</option>
            <option value="NC"<?php echo ($state == "NC" ? ' selected' : ''); ?>>North Carolina</option>
            <option value="ND"<?php echo ($state == "ND" ? ' selected' : ''); ?>>North Dakota</option>
            <option value="OH"<?php echo ($state == "OH" ? ' selected' : ''); ?>>Ohio</option>
            <option value="OK"<?php echo ($state == "OK" ? ' selected' : ''); ?>>Oklahoma</option>
            <option value="OR"<?php echo ($state == "OR" ? ' selected' : ''); ?>>Oregon</option>
            <option value="PA"<?php echo ($state == "PA" ? ' selected' : ''); ?>>Pennsylvania</option>
            <option value="RI"<?php echo ($state == "RI" ? ' selected' : ''); ?>>Rhode Island</option>
            <option value="SC"<?php echo ($state == "SC" ? ' selected' : ''); ?>>South Carolina</option>
            <option value="SD"<?php echo ($state == "SD" ? ' selected' : ''); ?>>South Dakota</option>
            <option value="TN"<?php echo ($state == "TN" ? ' selected' : ''); ?>>Tennessee</option>
            <option value="TX"<?php echo ($state == "TX" ? ' selected' : ''); ?>>Texas</option>
            <option value="UT"<?php echo ($state == "UT" ? ' selected' : ''); ?>>Utah</option>
            <option value="VT"<?php echo ($state == "VT" ? ' selected' : ''); ?>>Vermont</option>
            <option value="VA"<?php echo ($state == "VA" ? ' selected' : ''); ?>>Virginia</option>
            <option value="WA"<?php echo ($state == "WA" ? ' selected' : ''); ?>>Washington</option>
            <option value="WV"<?php echo ($state == "WV" ? ' selected' : ''); ?>>West Virginia</option>
            <option value="WI"<?php echo ($state == "WI" ? ' selected' : ''); ?>>Wisconsin</option>
            <option value="WY"<?php echo ($state == "WY" ? ' selected' : ''); ?>>Wyoming</option>
        </select>
        <?php
    }

    private function reset_form() {
        // Set default values for next form
        $this->_escapestring = false;
        $this->_fielddisabled = false;
        $this->_fieldtitle = '';
        $this->_hiddenfields = array();
        $this->_inlinestyle = '';
        $this->_newid = '';
        $this->_noecho = false;
        $this->_onblur = '';
        $this->_onclick = '';
        $this->_onfocus = '';
        $this->_onsubmit = '';

    }
}

/**
 * Change log:
 *
 * 2017-07-28:
 *      -Changed visibility of function show_hidden() from public to private
 *
 * 2017-06-23:
 *      -Function add_checkbox(): made label clickable
**/
?>
