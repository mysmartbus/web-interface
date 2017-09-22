<?php
// Last Updated: 2017-07-27
// DB Version: 0.32
$dbc = new genesis();
$db = $dbc->connectServer();
$dbc->connectDatabase('calendar', 0.32);

//////////
// Begin function list

function AddEditEvent($dowhat, $matchdate) {
    /**
     * Display a form to add or edit an event
     *
     * Added: 2017-07-03
     * Modified: 2017-07-26
     *
     * @param Required string $dowhat Must be 'addevent' or 'editevent'
     * @param Required string $matchdate The date of the event being added or edited
     *
     * @return Nothing
    **/

    global $dbc, $KG_SECURITY, $valid;

    // Return the user to the view (daily, weekly, monthly) they
    // came from when finished
    $view = $valid->get_value('view');
    if ($view == '') {
        $view = 'dailyview';
    }
    $valid->setValue(array('view' => $view));

    // Initial date to add the event to
    $date = $valid->get_value('date');
    list($year, $month, $day) = explode('-',$date);

    $form = new HtmlForm();
    $table = new HtmlTable();

    // Make sure user has permission to add or edit an event
    if (
        (($dowhat == 'addevent') && ($KG_SECURITY->hasPermission('add') === false))
        || (($dowhat == 'editevent') && ($KG_SECURITY->hasPermission('edit') === false))
    ) {
        // Permission denied
        $form->add_hidden(array(
            'ACTON' => $view,
            'date' => $date
        ));
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(), 'post', 'frmgoback', get_lang('goback'));
        echo '</center>';
        return false;
    }

    /////
    // BEGIN javascript functions
?>
    <script type="text/javascript">

        // Keep track of the days added
        var datesadded = [];

        // Dynamically add a date and time row to the "datestimestable" table
        function addDateRow(day) {

            // Remove the "No dates selected" message
            $("table#datestimestable tr#nodaysselectedrow").remove();

            var index = datesadded.indexOf(day);
            if (index == -1) {

                // Keep track of the days added
                datesadded.push(day);
            }

            // Get and format event start time
            var starttime = document.getElementById('starttime').value;
            var sepcount = starttime.split(":").length - 1;
            if (sepcount == 0) {
                var pos = -2
                if (starttime < 1200) {
                    starttime = starttime +'a';
                    pos = -3
                }
                starttime = [starttime.slice(0, pos), ':', starttime.slice(pos)].join('');
            }

            // Get and format event end time
            var endtime = document.getElementById('endtime').value;
            if (endtime != '') {
                var sepcount = endtime.split(":").length - 1;
                if (sepcount == 0) {
                    var pos = -2
                    if (endtime < 1200) {
                        endtime = endtime +'a';
                        pos = -3
                    }
                    endtime = [endtime.slice(0, pos), ':', endtime.slice(pos)].join('');
                }
            }

            var dataString = "ajaxacton=geneventrow&eventreminderday=" + day + "&starttime=" + starttime + "&endtime=" + endtime;

            $.ajax({
                type: "POST",
                url: "modules/calendar/calendar_ajax.php",
                data: dataString,
                cache: false,
            })
            .fail(function (xhr, ajaxOptions, thrownError) {
                  // On error, we alert user
                  alert("oops -- " + thrownError);
                }
            )
            .done(function(data) {
                data = $.parseJSON(data);
                if (data !== "~:~") {
                    // Split the code for the table row over multiple lines for easier reading/searching
                    var pone = '<tr id="daterow'+day+'">';
                    var ptwo = '<td class="dttcol1">'+jsOrdinal(day)+'</td>';

                    // Put it all together
                    $("#datestimestable").append(pone+ptwo+data);

                    validate();
                }
            });
        };

        // Dynamically remove a date and time row from the "datestimestable" table
        function removeDateRow(day) {
            $("table#datestimestable tr#daterow"+day).remove();

            // Remove day from array
            var index = datesadded.indexOf(day);
            if (index > -1) {
                datesadded.splice(index, 1);
            }

            if ($('#datestimestable tr').length < 1) {
                // Add the "No dates selected" message
                var rowdata = <?php echo "'<tr id=\"nodaysselectedrow\"><td colspan=\"3\">".get_lang("nodatesselected")."</td></tr>'"; ?>;
                row = $(rowdata);
                row.appendTo("#datestimestable");
            }

            validate();
        };
        // END function removeDateRow()

        function mydatetoadd() {
            // Add a date to the event

            var thedate = document.getElementById('datetoadd').value;
            var res = thedate.split("-");

            if (res.length == 3) {
                var day = res[2];
                if (isNaN(day) === false) {

                    // Add the table row
                    addDateRow(day);

                }
            }

            return false;
        };

        function validate() {
            // Some client side validation of the form data
            //
            // This function is also called from three locations: [js] removeDateRow(), [js] addreminder(), and
            // [php] form submit.

            // Enable/Disable form submission
            // If .length is greater than 0, disable form submission
            var formStatus = ["eventname", "location", "selectdate"];

            if (document.getElementById("eventname").value == "") {
                // Event name required
                document.getElementById("eventnamespan").className = "";
            } else {
                document.getElementById("eventnamespan").className = "hidden";

                // Remove the element from the array
                var index = formStatus.indexOf("eventname");
                if (index > -1) {
                    formStatus.splice(index, 1);
                }
            }

            var id = parseInt(document.getElementById('locationid').value, 10);
            if (isNaN(id) || id < 1) {
                // Location required
                document.getElementById("locationspan").className = "";
            } else {
                document.getElementById("locationspan").className = "hidden";

                // Remove the element from the array
                var index = formStatus.indexOf("location");
                if (index > -1) {
                    formStatus.splice(index, 1);
                }
            }

            if (datesadded.length < 1) {
                // There needs to be at least 1 date selected to save event

                // Set CSS class
                document.getElementById("selectdatesspan").className="";

            } else {
                document.getElementById("selectdatesspan").className = "hidden";

                // Remove the element from the array
                var index = formStatus.indexOf("selectdate");
                if (index > -1) {
                    formStatus.splice(index, 1);
                }
            }

            // Makes the selected dates available to $_POST
            document.getElementById("selecteddates").value = datesadded;

            /////
            // BEGIN Validate dates, times and reminders
            var starttimes = {};
            var endtimes = {};

            var sttext = 'starttime';
            var ettext = 'endtime';

            var stlen = sttext.length;
            var etlen = ettext.length;

            // Get field names and values
            $('form#frmaddeditevent').each(function () {
                for (var i = 0; i < $(this).find(':input').length; i++) {
                    var name = $(this).find(':input')[i].name;
                    var value = $(this).find(':input')[i].value

                    if((name.length > stlen) && (name.indexOf(sttext) == 0)) {
                        var day = name.substring(stlen + 1, stlen + 3);
                        if ((value.length < 8) && (value != '')) {
                            starttimes[day] = value;
                        }

                    } else if((name.length > etlen) && (name.indexOf(ettext) == 0)) {
                        var day = name.substring(etlen + 1, etlen + 3);
                        if ((value.length < 8) && (value != '')) {
                            endtimes[day] = value;
                        }

                    }
                }
            });
            // END $('form#frmaddeditevent').each()

            // Use datesadded to loop through [starttimes] and [endtimes]
            var missingcnt = 0;
            for (var i in datesadded) {
                day = datesadded[i];

                if (starttimes.hasOwnProperty(day) === false) {
                    // Validation failed
                    missingcnt++;
                    break;
                }

                if (endtimes.hasOwnProperty(day) === false) {
                    // Validation failed
                    missingcnt++;
                    break;
                }
            }
            // END for (var i in datesadded)

            if (missingcnt > 0) {
                // Invalid start/end time found
                document.getElementById('invalidtimesdiv').className = "";
            } else {
                document.getElementById('invalidtimesdiv').className = "hidden";
            }
// TODO: Add check for events that have an end time the comes before the start time

            // END Validate dates, times and reminders
            /////

            if ((missingcnt == 0) && (formStatus.length == 0)) {
                // Allow form submission

                // Enable the save button
                document.getElementById("submit").disabled = false;
                document.getElementById("submit").className = "formcolors";

                // Change background color to green
                document.getElementById("form_status_div").className = "ae_form_status_green_div";

                document.getElementById('submitdataallowed').className = '';

            } else {
                // Prevent form submission

                // Disable the save button
                document.getElementById("submit").disabled = true;
                document.getElementById("submit").className = "hidden";

                // Change background color to red
                document.getElementById("form_status_div").className = "ae_form_status_red_div";

                document.getElementById('submitdataallowed').className = 'hidden';

            }
        }
        // END javascript function validate()

        function closefunction() {

            // Hide the popup
            $('#setlocation').css({"display":"none"});
            $("#lean_overlay").fadeOut(200);

            // Get contact/location ID and name from <select></select> list
            var id = $('#locationlist').find(":selected").val();
            var name = $('#locationlist').find(":selected").text();

            // Display contact/location name
            document.getElementById('locationname').firstChild.nodeValue = name;
            $('#locationname').attr("title", name);

            // Store ID in hidden form field
            document.getElementById('locationid').value = id;

            validate();

        };

        $(document).ready(function(){
            $('#setlocationid').leanModal({ closeButton: ".modal_close" });
            $('#savebutton').click(function(){ closefunction(); });
        });

    </script>

<?php

    /////
    // BEGIN leanModal form
    echo '<div id="setlocation">
        <div id="setlocation-ct">
            <div id="setlocation-header">
                <span class="header_text">'.get_lang('setlocation').'</span>
                <a class="modal_close" href="#"></a>
            </div>
            <form name="frmsetlocation" id="frmsetlocation" class="addsetlocationform">
            ';
            $form->add_contact_list('locationlist');
    echo '            <div class="btn-fld">
                    <button type="button" name="savebutton" id="savebutton" class="savebutton">'.get_lang('save').'</button>
                </div>
            </form>
        </div>
    </div>';
    // END leanModal form
    /////

    if ($dowhat == 'addevent') {
        // Adding a new event

        $addevent = true;
        $pagetitle = get_lang('addevent');
        $starttime = $valid->get_value('starttime');
        $form->add_hidden(array(
            'ACTON' => 'savenewevent',
            'locationid' => -1
        ));

    } else {
        // Editing an existing event

        $addevent = false;
        $pagetitle = get_lang('editevent');
        $eventid = $valid->get_value_numeric('eventid');
        $eventinfo = geteventdata($eventid);
        $starttime = '';
        $form->add_hidden(array(
            'ACTON' => 'saveeventchanges',
            'eventid' => $eventid,
            'locationid' => $eventinfo['data']['contactid']
        ));
    }

    // Used when adding or editing an event
    $form->add_hidden(array(
        'starttime' => $starttime,  // Makes the event start time available to javascript
        'endtime' => '',  // Makes the event end time available to javascript
        'selecteddates' => ''
    ));

    // Start form
    $form->start_form(kgGetScriptName(), 'post', 'frmaddeditevent');

    echo '<div class="page_title_div">'.$pagetitle.'</div>';

    // Event name
    echo '<div class="ae_event_name_div">';
    if ($addevent) {
        $value = '';
    } else {
        $value = $eventinfo['data']['name'];
    }
    echo '<span>'.get_lang('eventname').'</span>';
    echo '<span>';
    $form->set_onblur('validate();');
    $form->add_text('eventname', $value);
    echo '</span></div>';

    // Description
    echo '<div class="ae_description_div">';
    if ($addevent) {
        $value = '';
    } else {
        $value = $eventinfo['data']['description'];
    }
    echo '<span class="toptext">'.get_lang('description').'</span>';
    echo '<span>';
    $form->add_textarea('description', $value);
    echo '</span></div>';

    // Location
    echo '<div class="ae_location_div">';
    if ($addevent) {
        $value = '&nbsp;';
    } else {
        $value = $eventinfo['data']['contactid'];
    }
    echo '<span>'.get_lang('location').'</span>';
    echo '<span id="locationname" class="wordwrapoff" title="'.$value.'">'.$value.'</span>';
    echo '<span><a id="setlocationid" rel="leanModal" name="setlocation" href="#setlocation">'.get_lang('setlocation').'</a></span>';
    echo '</div>';

    // Mark public
    echo '<div class="ae_markpublic_div">';
    if ($addevent) {
        $value = false;
    } else {
        $value = $eventinfo['data']['public'];
        if ($value == 1) {
            $value = true;
        } else {
            $value = false;
        }
    }
    echo '<span>';
    $form->add_checkbox('public', 1, get_lang('publicevent'), $value);
    echo '</span>';
    echo '<span class="note toptext">'.get_lang('publiceventcheckboxnote').'</span>';
    echo '</div>';

    echo '<div class="ae_dates_form_status_div">';

    // Dates and times table
    echo '<div class="ae_dates_times_table_div">';

    $table->new_table();
    $table->new_row();
    $table->new_cell('dttcol1');
    echo 'Date';
    $table->new_cell('dttcol2');
    echo 'Start Time';
    $table->new_cell('dttcol3');
    echo 'End Time';
    $table->new_cell('dttcol4');
    echo '&nbsp;';
    $table->end_table();

    echo '<div class="ae_dtt_list_table_div">';
    $table->set_id('datestimestable');
    $table->new_table();
    $table->set_id('nodaysselectedrow');
    $table->new_row();
    $table->set_colspan(3);
    $table->new_cell();
    echo get_lang('nodatesselected');
    $table->end_table();
    echo '</div>';

    echo "\n</div><!-- END ae_dates_times_table_div -->\n";

    // Validation status form
    echo '<div class="ae_form_status_red_div" id="form_status_div">';
    echo '<div id="submitdataallowed" class="hidden">'.get_lang('datapassedvalidation').'</div>';
    echo '<div id="eventnamespan" class="">'.get_lang('MissingEventName').'</div>';
    echo '<div id="locationspan" class="">'.get_lang('selectalocation').'</div>';
    echo '<div id="selectdatesspan" class="">'.get_lang('nodatesselected').'</div>';
    echo '<div id="invalidtimesdiv" class="">'.get_lang('invalidstartendtime').'</div>';
    echo "\n</div><!-- END ae_form_status_div -->\n";

    echo "\n</div><!-- END ae_dates_form_status_div -->\n";

    echo '<div class="ae_button_row_div">';

    // Save and cancel buttons
    echo '<div class="ae_save_cancel_buttons_div">';
    if ($addevent) {
        $value = get_lang('addevent');
    } else {
        $value = get_lang('savechanges');
    }
    $form->add_button_submit($value);
    $form->end_form();

    $form->add_button_generic('cancelbutton', get_lang('cancel'),"location.href='".kgCreateLink('',array('ACTON' => 'dailyview', 'date' => $date, 'NO_TAG' => 'NO_TAG'))."';");
    echo "\n</div><!-- END ae_save_cancel_buttons_div -->\n";

    // Add another date to the event
    echo '<div class="ae_datetoadd_div">';

echo '<script>
    $(function() {
        $( "#datetoadd" ).datepicker({dateFormat: "yy-mm-dd",  changeMonth: true, changeYear: true});
    });
</script>';

    echo get_lang('addanotherdate').'<br>';
    $form->onsubmit('return mydatetoadd();');
    $form->start_form(kgGetScriptName(),'post','frmdatetoadd');
    $form->add_text('datetoadd', get_lang('clicktosetdate'), 12);
    $form->add_button_submit(get_lang('add'));
    $form->end_form();
    echo "\n</div><!-- END ae_datetoadd_div -->\n";

    echo "\n</div><!-- END ae_button_row_div -->\n";

    echo '<script type="text/javascript">$(document).ready(function(){addDateRow('.$day.');});</script>';

}
// END function AddEditEvent()
/////

function MonthView($year, $month, $day, $minicalendar = false) {
    /**
     * This function is based on PHP Calendar (version 2.3), written by Keith Devens
     * http://keithdevens.com/software/php_calendar (2017-03-26: Site no longer exists)
     * See example at http://keithdevens.com/weblog
     * License: http://keithdevens.com/software/license
     *
     * Added: 201?-??-??
     * Modified: 2017-07-04
     *
     * @param Required integer $year 4 digit year
     * @param Required integer $month
     * @param Required integer $day
     * @param Optional boolean $minicalendar
     *
     * @return Nothing
    **/

    global $dbc, $valid, $userinfo, $KG_SECURITY, $RP;

    $form = new HtmlForm();
    $calendartbl = new HtmlTable();

    // Get list of U.S. federal holidays for $year
    require_once $RP.'modules/calendar/usfederalholidays.php';
    $holidays = new USFederalHolidays();
    $holidaylist = $holidays->get_list();

    /**
     * $holidaylist is an array of arrays which in_array() and array_search()
     * cannot search through.
     *
     * This foreach loop creates an array that only contains the info I need
     * so that in_array() and array_search() will work.
    **/
    foreach ($holidaylist as $key => $value) {
        $holidaysymd[$value['name']] = $value['timestamp'];
    }

    // How many letters of the day name to display at
    // the top of the calendar
    $day_name_length = 3;

    // Which day do you consider to be the first day of the week?
    // For Sunday set value to 0 (zero).
    // For Saturday set value to 6 (six).
    $first_day = 0;

    // Show the previouse/next month links
    $pnlinks = true;

    // mktime will automatically correct if invalid dates are entered
    //   for instance, mktime(0,0,0,12,32,1997) will be the date for Jan 1, 1998
    //   this provides a built in "rounding" feature to MonthView()
    $first_of_month = gmmktime(0,0,0,$month,1,$year);

    // Full date of today (YYYY-MM-DD)
    $todaydateepoch = strtotime(date('Y-m-d'));

    // Date user clicked on
    $selecteddate = "$year-$month-$day";
    $selecteddateepoch = strtotime($selecteddate);

    // $month_name will be the full month name, based on the locale
    // weekday is the numeric representation of the day of the week, 0 for Sunday through 6 for Saturday
    list($month_name, $weekday) = explode(',',gmstrftime('%B,%w',$first_of_month));

    // Adjust for $first_day
    $weekday = ($weekday + 7 - $first_day) % 7;

    // Note that some locales don't capitalize month and day names
    $title = htmlentities(ucfirst($month_name)).'&nbsp;'.$year;

    echo "\n".'<div class="calendar_grid_div">';

    // Previous and next links
    // -Only show them on the full size calendar
    if ($minicalendar === false) {
        if ($pnlinks === true) {

            // Full name of last month
            $pnmonth = $month - 1;
            if ($pnmonth < 1) {
                // Last month is December of last year
                $lmnum = 12;
                $pyear = $year - 1;
            } else {
                $lmnum = $pnmonth;
                $pyear = $year;
            }
            $timestamp = mktime(0, 0, 0, $lmnum, 1, $pyear);
            $lastmonth = date("F", $timestamp);

            //Full name of next month
            $pnmonth = $month + 1;
            if ($pnmonth > 12) {
                // Next month is January of next year
                $nmnum = 1;
                $nyear = $year + 1;
            } else {
                $nmnum = $pnmonth;
                $nyear = $year;
            }
            $timestamp = mktime(0, 0, 0, $nmnum, 1, $nyear);
            $nextmonth = date("F", $timestamp);

            // Previous month link
            $p = '<span title="'.$lastmonth.'">'.kgCreateLink('<<',array('year' => $pyear, 'month' => $lmnum)).'</span>';

            // Next month link
            $n = '<span title="'.$nextmonth.'">'.kgCreateLink('>>',array('year' => $nyear, 'month' => $nmnum)).'</span>';
        } else {
            $p = "";
            $n = "";
        }
        // END if ($pnlinks === true)

        // Previous/Next month links, Month Name
        echo '<div class="mv_pnlinks_div">';
        // Previous Month
        echo $p;
        echo '<span>'.$title.'</span>';
        // Next Month
        echo $n;
        echo '</div>';
    } else {
        // Month Name and year only
        echo '<div class="mv_mini_cal_title_div">';
        echo $title;
        echo '</div>';
    }
    // END if ($minicalendar === false)

    // Start table
    if ($minicalendar === true) {
        // A roughly 1/4 sized version of the calendar
        $css = 'mv_mini';
    } else {
        // The full size grid
        $css = 'mv_grid';
    }

    // Generate all the day names according to the current locale
    $dayNames = array();
    for ($n=0,$t=($first_day+3)*86400; $n<7; $n++,$t+=86400) {
        // %A means full textual day name
        $dayNames[$n] = ucfirst(gmstrftime('%A',$t));
    }

    // Show at least the first 2 letters of the day name
    if($day_name_length < 2){
        $day_name_length = 2;
    }

    // Format day names
    foreach($dayNames as $d) {
        // If $day_name_length is >= 4, the full name of the day will be printed
        $hdrrowdata[] = htmlentities($day_name_length < 4 ? substr($d,0,$day_name_length) : $d);
    }

    // Start the month veiw grid/table
    $calendartbl->new_table('mv_grid');
    $calendartbl->new_row();

    // Add the day name row to table
    foreach ($hdrrowdata as $key => $value) {
        $calendartbl->new_cell('dayname');
        echo $value;
    }

    $calendartbl->new_row();

    // Contains info to display in a table row below each day
    // Max 14 characters per array value
    $inforow = array();

    // Initial 'empty' days
    if($weekday > 0) {
        $calendartbl->blank_cell($weekday);
        for($i=1;$i<=$weekday;$i++) {
            $inforow[] = '';
        }
    }

    if ($minicalendar === true) {
        // Add [js] toggleSelection()
        ?>
        <script type="text/javascript">
            function toggleSelection(id) {

                var css = document.getElementById(id).className;
                var res = css.split(" ");

                // Note: browser support for indexOf is limited, it is not supported in IE7-8.
                var index = res.indexOf("addedit-selected");

                if (index > -1) {
                    res.splice(index, 1);
                    var str = res.join(" ");

                    // Remove table cell ID from array
                    var index = inputVals.indexOf(id);
                    if (index > -1) {
                        inputVals.splice(index, 1);
                    }

                    if (typeof removeDateRow == "function") {
                        // This function will only exist when adding or editing an event
                        removeDateRow(id);
                    }

                } else {
                    var str = res.join(" ")+" addedit-selected";

                    // Add table cell ID to array
                    inputVals.push(id);

                    if (typeof addDateRow == "function") {
                        // This function will only exist when adding or editing an event
                        addDateRow(id);
                    }

                }

                // Set CSS class
                document.getElementById(id).className=str;
            }
        </script>
        <?php
    }
    // END if ($minicalendar === false)

    for($dayloop=1,$days_in_month=gmdate('t',$first_of_month); $dayloop<=$days_in_month; $dayloop++,$weekday++){

        // Date used to search the database
        $searchdate = $year.'-'.sprintf("%02d",$month).'-'.sprintf("%02d",$dayloop);
        $searchdateepoch = strtotime($searchdate);

        // Is $searchdate in the past?
        if ($searchdateepoch < $todaydateepoch) {
            // Yes
            $pastdate = true;
        } else {
            // No
            $pastdate = false;
        }

        if ($weekday == 7) {
            // Start a new week
            $weekday = 0;

            // Display info row only if calendar is displayed at full size and not in touchmode
            //
            // There is only enough space to display 14 characters per table cell
            // This foreach() loop is also on line 1434 and 2225.
            if ($minicalendar === false) {
                $calendartbl->new_row('hidden_small_screen');
                foreach ($inforow as $key => $value) {
                    if ($value == '') {
                        $calendartbl->blank_cell();
                    } else {
                        $calendartbl->new_cell();
                        echo '<span class="tinytext">'.substr($value,0,14).'</span>';
                    }
                }
                // Reset the array for the next row
                $inforow = array();

            }
            // END if ($minicalendar === false)

            $calendartbl->new_row();
        }
        // END if ($weekday == 7)

        // Set css class used by form button or table cell
        if ($minicalendar === true) {
            $class = 'daybutton_mini';
        } else {
            $class = 'daybutton';
        }

        $conditions['eventdate'] = $searchdate;

        if ($KG_SECURITY->isLoggedIn() === true) {
            // Select events created by the logged in user
            $conditions['eventfor'] = $userinfo['UserID'];

            if ($valid->get_value_numeric('chkshowpublic',0) == 1) {
                // Also show public events
                $conditions['public'] = 1;

                // Creates an SQL fragment
                $conditions[] = $dbc->makeList($conditions, LIST_OR);

                // Need to unset these keys or we'll get incorrect results
                unset($conditions['public']);
                unset($conditions['eventfor']);
            }
        } else {
            // Show public events created by any user
            $conditions['public'] = 1;
        }

        // Count number of events scheduled for $searchdate
        $numrows = $dbc->numRows($dbc->select('eventdates', $conditions, "eventid", "starttime ASC"));

        if ($numrows > 0) {
            // There is at least 1 event scheduled for $searchdate

            $hasevents = true;
        } else {
            // There are no events scheduled for $searchdate

            $hasevents = false;
        }

        // Is $searchdate in the past?
        $eventpast = false;

        if (($weekday == 0) || ($weekday == 6)) {
            // $searchdate is a week end
            $weekend = true;
        } else {
            // $searchdate is a week day
            $weekend = false;
        }

        if ($searchdateepoch < $todaydateepoch) {
            // $searchdate is in the past

            if ($hasevents === true) {
                if ($weekend === true) {
                    $class .= ' weekendpast_hasevents';
                } else {
                    $class .= ' hasevents_past';
                }
            } else {
                if ($weekend === true) {
                    $class .= ' weekend_noevents';
                } else {
                    $class .= ' no_events';
                }
            }

            $eventpast = true;
        } elseif ($searchdateepoch > $todaydateepoch) {
            // $searchdate is in the future

            if ($hasevents === true) {
                if ($weekend === true) {
                    $class .= ' weekend_hasevents';
                } else {
                    $class .= ' hasevents_future';
                }
            } else {
                if ($weekend === true) {
                    $class .= ' weekend_noevents';
                } else {
                    $class .= ' no_events';
                }
            }
        } else {
            // $searchdate is today
            // -No checks to see if $searchdate falls on a weekend
            //  since that would hide the highlighting of today

            if ($hasevents === true) {
                $class .= ' today_hasevents';
            } else {
                $class .= ' today_noevents';
            }

        }
        // END if ($searchdateepoch < $todaydateepoch)

        // Check if this is a holiday
        if (in_array($searchdate, $holidaysymd)) {
            // Add holiday name to info row
            $inforow[] = array_search($searchdate, $holidaysymd);
        } else {
            $inforow[] = '';
        }

        // The button for each day
        $calendartbl->set_celltitle($numrows.' Event(s)');
        if ($minicalendar === true) {
            $calendartbl->set_onclick('toggleSelection('.$dayloop.')');
            $calendartbl->set_id($dayloop);
            $calendartbl->new_cell($class);
            echo $dayloop;
        } else {

            $calendartbl->new_cell();
            $form->add_hidden(array(
                'ACTON' => 'dailyview',
                'date' => $searchdate,
                'view' => $valid->get_value('view')
            ));
            $form->start_form(kgGetScriptName(),'post','frmloadschedule');
            $form->add_button_submit($dayloop,'',$class);
            $form->end_form();
        }

        if ($selecteddate == $searchdate && $minicalendar === true) {
            echo '<script type="text/javascript">$(document).ready(function(){toggleSelection('.$dayloop.');});</script>';
        }

    }
    // END for($dayloop=1,$days_in_month=gmdate(...
    /////

    // Remaining 'empty' days
    // TODO: Eventually this will list days for the next month
    if($weekday != 7) {
        $calendartbl->blank_cell(7-$weekday);
    }

    // Display info row
    // There is only enough space to display 14 characters per table cell
    // Doing this again so any info for last row of dates is displayed
    // This foreach() loop is also on line 1276 and 2225.
    // -Only show row if calendar is displayed at full size
    if ($minicalendar === false) {
        $calendartbl->new_row('hidden_small_screen');
        foreach ($inforow as $key => $value) {
            if ($value == '') {
                $calendartbl->blank_cell();
            } else {
                $calendartbl->new_cell();
                echo '<span class="tinytext">'.substr($value,0,14).'</span>';
            }
        }
    }
    $calendartbl->end_table();

    echo "\n</div><!-- END calendar_grid_div -->";

    // Only show on full size calendar
    if ($minicalendar === false) {
        echo '<div class="goto_view_div">';
        $calendartbl->new_table('centered');
        $calendartbl->new_row();
        $calendartbl->new_cell('centertext');
        // Link to jump to today
        echo kgCreateLink(get_lang('GoToToday'), array('ACTON' => 'dailyview', 'date' => date('Y-m-d')));

        $calendartbl->blank_row(1);

        // Go to a specific date without having to click through the months
        $calendartbl->new_row();
        $calendartbl->new_cell('centertext');
echo '<script>
    $(function() {
        $( "#GoToDate" ).datepicker({dateFormat: "yy-mm-dd",  changeMonth: true, changeYear: true});
    });
</script>';
        $form->add_hidden(array(
            'ACTON' => 'dailyview',
            'view' => $valid->get_value('view'),
        ));
        $form->start_form(kgGetScriptName(),'post','frmloadschedule');
        $form->add_text('GoToDate',get_lang('ClickToSelectDate'));
        $form->add_button_submit(get_lang('GoThere'));
        $form->end_form();

        $calendartbl->blank_row(1);

        $calendartbl->end_table();

        // Week view button
        monthweekviewbuttons(array('weekly', 'daily'), date('Y-m-d'));

        echo "\n</div><!-- END goto_view_div -->";

    }
    // END if ($minicalendar === false)

}
// END function MonthView()
/////

function WeeklyView($year, $month, $day, $pnlinks = true) {
    /**
     * This function is based on PHP Calendar (version 2.3), written by Keith Devens
     * http://keithdevens.com/software/php_calendar (2017-03-26: Site no longer exists)
     * See example at http://keithdevens.com/weblog
     * License: http://keithdevens.com/software/license
     *
     * Added: 2014-??-??
     * Modified: 2017-07-06
     *
     * @param Required integer $year
     * @param Required integer $month
     * @param Required integer $day
     * @param Optional boolean $pnlinks
     *
     * @return Nothing
    **/

    global $dbc, $valid, $userinfo, $KG_SECURITY, $RP;

    $form = new HtmlForm();
    $calendartbl = new HtmlTable();

    require_once $RP.'modules/calendar/usfederalholidays.php';
    $holidays = new USFederalHolidays();
    $holidaylist = $holidays->get_list();

    foreach ($holidaylist as $key => $value) {
        $holidaysymd[$value['name']] = date("Y-m-d", $value['timestamp']);
    }

    // How many letters of the day name to display at
    // the top of the calendar
    $day_name_length = 3;

    // Which day do you consider to be the first day of the week?
    // For Sunday set value to 0 (zero).
    // For Saturday set value to 6 (six).
    $first_day = 0;

    // Full date of today (YYYY-MM-DD)
    $todaysdate = date('Y-m-d');
    $todaydateepoch = strtotime($todaysdate);
    list($dayyear,$daymonth,$daynum) = explode('-',$todaysdate);

    // Date from previous/next week links
    $selecteddate = "$year-$month-$day";
    $selecteddateepoch = strtotime($selecteddate);

    // 3 letter abbreviation of day name
    $dayname = date('D', $selecteddateepoch);

    // Creates a date object using PHP's builting DateTime class
    $datesun = new DateTime($selecteddate);

    // Note that some locales don't capitalize month and day names
    $title = htmlentities(ucfirst($datesun->format('F')));

    // Get date for Sunday of the week that contains $selecteddate
    switch ($dayname) {
        case 'Sun':
            // Selected date is a Sunday
            $weekstartdate = $selecteddate;
            break;
        case 'Mon':
            // Selected date is a Monday
            $datesun->sub(new DateInterval('P1D'));
            $weekstartdate = $datesun->format('Y-m-d');
            break;
        case 'Tue':
            // Selected date is a Tuesday
            $datesun->sub(new DateInterval('P2D'));
            $weekstartdate = $datesun->format('Y-m-d');
            break;
        case 'Wed':
            // Selected date is a Wednesday
            $datesun->sub(new DateInterval('P3D'));
            $weekstartdate = $datesun->format('Y-m-d');
            break;
        case 'Thu':
            // Selected date is a Thursday
            $datesun->sub(new DateInterval('P4D'));
            $weekstartdate = $datesun->format('Y-m-d');
            break;
        case 'Fri':
            // Selected date is a Friday
            $datesun->sub(new DateInterval('P5D'));
            $weekstartdate = $datesun->format('Y-m-d');
            break;
        case 'Sat':
            // Selected date is a Saturday
            $datesun->sub(new DateInterval('P6D'));
            $weekstartdate = $datesun->format('Y-m-d');
            break;
    }

    // Get date for Saturday of the week that contains $selecteddate
    $datesat = new DateTime($selecteddate);
    switch ($dayname) {
        case 'Sun':
            // Selected date is a Sunday
            $datesat->add(new DateInterval('P6D'));
            $weekenddate = $datesat->format('Y-m-d');
            break;
        case 'Mon':
            // Selected date is a Monday
            $datesat->add(new DateInterval('P5D'));
            $weekenddate = $datesat->format('Y-m-d');
            break;
        case 'Tue':
            // Selected date is a Tuesday
            $datesat->add(new DateInterval('P4D'));
            $weekenddate = $datesat->format('Y-m-d');
            break;
        case 'Wed':
            // Selected date is a Wednesday
            $datesat->add(new DateInterval('P3D'));
            $weekenddate = $datesat->format('Y-m-d');
            break;
        case 'Thu':
            // Selected date is a Thursday
            $datesat->add(new DateInterval('P2D'));
            $weekenddate = $datesat->format('Y-m-d');
            break;
        case 'Fri':
            // Selected date is a Friday
            $datesat->add(new DateInterval('P1D'));
            $weekenddate = $datesat->format('Y-m-d');
            break;
        case 'Sat':
            // Selected date is a Saturday
            $weekenddate = $selecteddate;
            break;
    }

    // Get all dates between and including $weekstartdate and $weekenddate
    $weekdatearray = createDateRangeArray($weekstartdate,$weekenddate);

    // Check for holidays
    $inforow = array();
    foreach ($weekdatearray as $key => $date) {
        if (in_array($date, $holidaysymd)) {
            // Add holiday name to info row
            $inforow[] = array_search($date, $holidaysymd);
        } else {
            $inforow[] = '';
        }
    }

    // First table cell of row is blank
    $hdrrowdaynames[0] = array('Data' => '&nbsp;', 'css' => 'wv_hrcell');

    // Generate all the day names according to the current locale
    $dayNames = array();
    for ($n=0,$t=($first_day+3)*86400; $n<7; $n++,$t+=86400) {
        // %A means full textual day name
        $dayNames[$n] = ucfirst(gmstrftime('%A',$t));

        // If $day_name_length is >= 4, the full name of the day will be printed
        $dn = htmlentities($day_name_length < 4 ? substr($dayNames[$n],0,$day_name_length) : $dayNames[$n]);

        // Set css class to use
        if (($n == 0) || ($n == 6)) {
            $css = 'wv_dayname wv_column_width weekend';
        } else {
            $css = 'wv_dayname wv_column_width';
        }

        // Populate the array
        $hdrrowdaynames[$n + 1] = array('Data' => $dn,'css' => $css);
    }

    /////
    // Format day numbers
    //
    // Blank cell upper left corner
    $hdrrowdaynums[0] = array('Data' => '&nbsp;','css' => 'wv_hrcell');
    //
    // Day numbers for current week
    foreach ($weekdatearray as $key => $date) {
        list($wyear, $wmonth, $wday) = explode('-', $date);

        $css = 'wv_daynum';

        // Highlight the weekends
        if (($key == 0) || ($key == 6)) {
            $css .= ' weekend';
        }

        if ($wday == $daynum) {
            // Highlight today
            $css .= ' today';
        }

        $hdrrowdaynums[$wday] = array('Data' => $wday,'css' => $css);
    }
    // Format day numbers
    /////

    // Hour array
    $hrarray = array(12,1,2,3,4,5,6,7,8,9,10,11);

    // Previous and next links
    if($pnlinks === true) {

        // Reset the date
        $date = new DateTime($weekstartdate);

        // Date for Sunday of last week
        $date->modify('last Sunday');
        $datelastsunday = $date->format('Y-m-d');
        list($pyear,$pmonth,$pday) = explode('-',$datelastsunday);

        // Reset the date
        $date = new DateTime($weekstartdate);

        // Date for Sunday of next week
        $date->modify('next Sunday');
        $datenextsunday = $date->format('Y-m-d');
        list($nyear,$nmonth,$nday) = explode('-',$datenextsunday);

        // Previous week link
        $p = kgCreateLink('<<', array('view' => 'weeklyview', 'year' => $pyear, 'month' => $pmonth, 'day' => $pday));

        // Next week link
        $n = kgCreateLink('>>', array('view' => 'weeklyview', 'year' => $nyear, 'month' => $nmonth, 'day' => $nday));
    } else {
        $p = "";
        $n = "";
    }

    // Set css class and data for hour cells
    $hours = array();
    for ($x = 0; $x < 24; $x++) {
        if ($x > 11) {
            $hrx = $x - 12;
            $ampm = 'PM';
        } else {
            $hrx = $x;
            $ampm = 'AM';
        }
        $hr = $hrarray[$hrx];
        $hours[$x] = array('HR' => $hr.$ampm,'css' => 'wv_hrcell');
    }

    // The next 4 variables are used to set the height of the buttons
    // One unit is 24 pixels tall
    // One unit equals one hour
    $oneunitpixels = 24;
    $threequarterunit = $oneunitpixels * 0.75;
    $halfunit = $oneunitpixels / 2;
    $quarterunit = $oneunitpixels / 4;

    // Number of seconds per hour and parts of an hour
    $secondshr = 3600;
    $secondsthreequarterhr = 2700;
    $secondshalfhour = 1800;
    $secondsquarterhour = 900;

    // Value comes from 24 hours in a day times $oneunitpixels
    $maxcolumnheight = 576;

// TODO: Look into setting these colors in the skins colors.php file
    // CSS selectors to use to highlight the events
    $lightcolors = array(
        'wv_colorone_light',
        'wv_colortwo_light',
        'wv_colorthree_light',
        'wv_colorfour_light'
    );
    $darkcolors = array(
        'wv_colorone_dark',
        'wv_colortwo_dark',
        'wv_colorthree_dark',
        'wv_colorfour_dark'
    );

    // Keeps track of which color to use next
    $usecolor = 0;

    // SQL query to get data
    $where = array(
        array('buildBetween' => array('eventdate', $weekstartdate, $weekenddate))
    );
    if ($KG_SECURITY->isLoggedIn() === true) {
        // Select only the events created by the logged in user
        $where['eventfor'] = $userinfo['UserID'];
    } else {
        // Show public events created by any user
        $where['public'] = 1;
    }

    // Run the query
    $eventdatesquery = $dbc->select('eventdates', $where);

    $numrows = $dbc->numRows($eventdatesquery);
    if ($numrows > 0) {
        // There is at least 1 event scheduled for the week of $weekstartdate to $weekenddate

        // Get current time
        // "%T" equals "%H:%M:%S"
        $curtimeepoch = strtotime(strftime("%T"));

        // Get details about each event that occures on $matchdate
        while($row = $dbc->fetchAssoc($eventdatesquery)) {

            // Name of event
            $name = $dbc->fieldValue($dbc->select('eventinfo', 'eventid = '.$row['eventid'], 'name'));

            // Event date converted to Unix epoch
            $eventdateepoch = strtotime($row['eventdate']);

            // Event start time converted to Unix epoch
            $eventstartepoch = strtotime($row['starttime']);

            if ($usecolor > 3) {
                // Reset to 0 so the colors continue being used
                $usecolor = 0;
            }

            if ($eventdateepoch < $todaydateepoch) {
                // $eventdateepoch is in the past

                $class = $darkcolors[$usecolor];
                $usecolor++;

            } elseif ($eventdateepoch > $todaydateepoch) {
                // $eventdateepoch is in the future

                $class = $lightcolors[$usecolor];
                $usecolor++;
            } else {
                // $eventdateepoch is today


                if ($eventstartepoch > $curtimeepoch) {
                    // Event is in the future
                    $class = $lightcolors[$usecolor];
                    $usecolor++;

                } elseif ($eventstartepoch < $curtimeepoch) {
                    // Event is in the past
                    $class = $darkcolors[$usecolor];
                    $usecolor++;

                } else {
                    // Event is now
                    $class = $lightcolors[$usecolor];
                    $usecolor++;
                }

            }
            // END if ($searchdateepoch < $todaydateepoch)

            // Sort the data by event date
            $events[$row['eventdate']][] = array(
                'starttime' => $row['starttime'],
                'endtime' => $row['endtime'],
                'name' => $name,
                'alldayevent' => $row['alldayevent'],
                'eventid' => $row['eventid'],
                'css' => $class
            );

        }

    } else {
        // Create a blank array because there is nothing scheduled
        // for the week of $weekstartdate to $weekenddate
        $events = array();
    }
    // END if ($numrows > 0)

/**
 * Start the tables
**/

    echo "\n".'<div class="wv_encompassing_div"><div class="wv_column_header_table_div">';
    // Column header table
    $calendartbl->new_table();

    $calendartbl->new_row();
    $calendartbl->blank_cell(1, 'wv_hrcell');
    $calendartbl->new_cell();
    // Previous Month
    echo $p;
    $calendartbl->set_colspan(5);
    $calendartbl->new_cell('wv_title_cell');
    // Month name
    echo $title;
    $calendartbl->new_cell('righttext');
    // Next Month
    echo $n;

    // Add the day name row to table
    $calendartbl->new_row();
    foreach ($hdrrowdaynames as $key => $data) {
        $calendartbl->new_cell($data['css']);
        echo $data['Data'];
    }

    // Add the day numbers row to table
    $calendartbl->new_row();
    foreach ($hdrrowdaynums as $day => $data) {
        $calendartbl->new_cell($data['css']);
        echo $data['Data'];
    }

    $calendartbl->end_table();
    echo "\n</div><!-- END wv_column_header_table_div -->\n";

    // Event buttons table
    echo '<div class="wv_buttons_table_div">';
    $calendartbl->new_table();
    $calendartbl->new_row();
    $calendartbl->new_cell($hours[0]['css']); // 12AM
    echo $hours[0]['HR'];

    // Generate the buttons for one day/column at a time
    foreach ($weekdatearray as $x => $date) {

        if (array_key_exists($date, $events) && is_array($events[$date])) {

            // Clear the array for the next loop
            $gridarray = array();

            foreach ($events[$date] as $k => $v) {

                // Gets the button height, start position, and number of units used
                $unitcount = wvdv_getUnitCount($v['starttime'], $v['endtime'], $oneunitpixels);

                // Add the data to the array
                $gridarray[$unitcount['StartPixel']]['StartPixel'] = $unitcount['StartPixel'];
                $gridarray[$unitcount['StartPixel']]['Size'] = $unitcount['Size'];
                $gridarray[$unitcount['StartPixel']]['name'] = $v['name'];
                $gridarray[$unitcount['StartPixel']]['eventid'] = $v['eventid'];
                $gridarray[$unitcount['StartPixel']]['css'] = $v['css']; // Background color only
                $gridarray[$unitcount['StartPixel']]['Date'] = $date;
                $gridarray[$unitcount['StartPixel']]['starttime'] = $v['starttime'];
                    
            }
            // END foreach ($events as $key => $data)

            ksort($gridarray);

            // The start position for the next event button/block
            $curstart = 0;

            // How tall (in pixels) is the column?
            $curheight = 0;

            // Used to create the start time for the empty blocks
            $starttimehours = 0;
            $starttimeminutes = 0;

            // Now fill in the gaps
            foreach ($gridarray as $key => $data) {

                if ($curheight == 0 && $data['StartPixel'] > 0) {
                    // Fill in the space between 12:00AM (midnight) and the start of the days first event

                    // Number of blocks required
                    $numblocks = $data['StartPixel'] / $oneunitpixels;
                    if (strpos($numblocks, '.') !== false) {
                        list($wholeblocks, $partialblocks) = explode('.', $numblocks);
                        $partialblocks = $partialblocks / 10;
                    } else {
                        $wholeblocks = $numblocks;
                        $partialblocks = 0;
                    }

                    // Create the whole blocks
                    if ($wholeblocks > 0) {
                        for ($startpixel = $curheight, $i = 0; $i < $wholeblocks; $startpixel += $oneunitpixels, $i++) {
                            $temparray[$startpixel] = array('StartPixel' => $startpixel, 'Size' => $oneunitpixels, 'name' => '', 'eventid' => 0, 'css' => '', 'Date' => $date, 'starttime' => sprintf("%02d", $starttimehours).':00:00');
                            $curheight += $oneunitpixels; // Increase the columns height
                            $starttimehours++; // Needs to be after $temparray because midnight on the 24 hour clock is 00:00
                        }

                        if ($starttimehours > 0) {
// TODO: Why is this subtraction needed?
                            // Subtract 1 hour to keep the time accurate.
                            // If we don't, the rest of the calculations
                            // for the day will be off by 1 hour.
                            $starttimehours--;
                        }
                    }

                    // Create the partial block
                    if ($partialblocks > 0) {
                        $size = $partialblocks * $oneunitpixels;
                        $starttimeminutes = $partialblocks * 60;
                        $temparray[$startpixel] = array('StartPixel' => $startpixel, 'Size' => $size, 'name' => '', 'eventid' => 0, 'css' => '', 'Date' => $date, 'starttime' => sprintf("%02d", $starttimehours).':'.sprintf("%02d", $starttimeminutes).':00');
                        $curheight += $size; // Increase the columns height
                    }

                    // Increase the columns height
                    $curheight += $data['Size'];

                    // Increase $starttimehours by number of hours of event
                    $numblocks = $data['Size'] / $oneunitpixels;
                    if (strpos($numblocks, '.') !== false) {
                        list($wholeblocks, $partialblocks) = explode('.', $numblocks);
                    } else {
                        $wholeblocks = $numblocks;
                        $partialblocks = 0;
                    }
                    $starttimehours += $wholeblocks;

                    if ($partialblocks > 0) {
                        // The event ends after the top of the hour
                        // I.E: 
                        $starttimehours++;
                    }

                } elseif ($curheight > 0 && $curheight < $data['StartPixel']) {
                    // Fill in the gap between events

                    // Number of blocks required
                    $gap = $data['StartPixel'] - $curheight;
                    $numblocks = $gap / $oneunitpixels;
                    if (strpos($numblocks, '.') !== false) {
                        list($wholeblocks, $partialblocksstart) = explode('.', $numblocks);
                        $partialblocksstart = $partialblocksstart / 10;
                    } else {
                        $wholeblocks = $numblocks;
                        $partialblocksstart = 0;
                    }
                    $startpixel = 0;

                    // Create the whole blocks
                    if ($wholeblocks > 0) {
                        for ($startpixel = $curheight, $i = 0; $i < $wholeblocks; $startpixel += $oneunitpixels, $i++) {
                            $starttimehours++;
                            $temparray[$startpixel] = array('StartPixel' => $startpixel, 'Size' => $oneunitpixels, 'name' => '', 'eventid' => 0, 'css' => '', 'Date' => $date, 'starttime' => sprintf("%02d", $starttimehours).':00:00');
                            $curheight += $oneunitpixels; // Increase the columns height
                        }
                    }

                    // Create the partial block
                    if ($partialblocksstart > 0) {
                        if ($wholeblocks > 0) {
                            $starttimehours++;
                            $starttimeminutes = 0;
                        } else {
                            $starttimeminutes = $partialblocksstart * 60;
                        }
                        $blocksize = $partialblocksstart * $oneunitpixels;
                        $sp = ($startpixel == 0) ? $curheight : $startpixel;
                        $temparray[$sp] = array('StartPixel' => $sp, 'Size' => $blocksize, 'name' => '', 'eventid' => 0, 'css' => '', 'Date' => $date, 'starttime' => sprintf("%02d", $starttimehours).':'.sprintf("%02d", $starttimeminutes).':00');
                        $curheight += $blocksize; // Increase the columns height
                    }

                    // Increase the columns height
                    $curheight += $data['Size'];
                    // Increase $starttimehours by number of hours of event
                    $numblocks = $data['Size'] / $oneunitpixels;
                    if (strpos($numblocks, '.') !== false) {
                        list($wholeblocks, $partialblocksend) = explode('.', $numblocks);
                    } else {
                        $wholeblocks = $numblocks;
                        $partialblocksend = 0;
                    }
                    $starttimehours += $wholeblocks;

                    if ($partialblocksend > 0) {
                        $starttimehours++;
                    }

                } elseif ($curheight == $data['StartPixel']) {
                    // No gap between events

                    // Increase the columns height
                    $curheight = $curheight + $data['Size'];

                    // Increase $starttimehours by number of hours of event
                    $numblocks = $data['Size'] / $oneunitpixels;

                    if (strpos($numblocks, '.') !== false) {
                        list($wholeblocks, $partialblocks) = explode('.', $numblocks);
                    } else {
                        $wholeblocks = $numblocks;
                        $partialblocks = 0;
                    }

                    $starttimehours += $wholeblocks;

                    if ($partialblocks > 0) {
                        $starttimehours++;
                    }

                }
                // END if ($curheight == 0 && $data['StartPixel'] > 0)

                // Save these values for use when filling in the space between
                // the end of the days last event and 12:00AM (midnight)
                $laststarttime = $data['starttime'];
                $lastsize = $data['Size'];

            }
            // END foreach ($gridarray as $key => $data)

            if ($curheight < $maxcolumnheight) {
                // Fill in the space between the end of the days last event and 12:00AM (midnight)

                /**
                 * Get number of hour blocks to create
                **/

                // Number of blocks required
                $gap = $maxcolumnheight - $curheight;
                $numblocks = $gap / $oneunitpixels;
                if (strpos($numblocks, '.') !== false) {
                    list($wholeblocks, $partialblocks) = explode('.', $numblocks);
                    $partialblocks = $partialblocks / 10;
                } else {
                    $wholeblocks = $numblocks;
                    $partialblocks = 0;
                }

                if (($curheight % $oneunitpixels) == 0) {
                    // Only need to create whole blocks because
                    // $curheight is evenly divisible by $oneunitpixels

                    if ($wholeblocks > 0) {

                        // Use $curheight to set $starttimehours
                        $c = $curheight / $oneunitpixels;
                        if (strpos($c, '.') !== false) {
                            list($d, $dummy) = explode('.', $c);
                        } else {
                            $d = $c;
                        }
                        $starttimehours = $d;

                        for ($startpixel = $curheight, $i = 0; $i < $wholeblocks; $startpixel += $oneunitpixels, $i++) {
                            $temparray[$startpixel] = array('StartPixel' => $startpixel, 'Size' => $oneunitpixels, 'name' => '', 'eventid' => 0, 'css' => '', 'Date' => $date, 'starttime' => sprintf("%02d", $starttimehours).':00:00');
                            $curheight += $oneunitpixels;
                            $starttimehours++;
                        }
                    }
                } else {
                    // $curheight is NOT evenly divisible by $oneunitpixels which
                    // means that the last event of the day did not end at the top
                    // of the hour (I.E: 1:00PM, 2:00PM, etc)

                    // Create the partial block first
                    if ($partialblocks > 0) {

                        $size = $partialblocks * $oneunitpixels;
                        $starttimeminutes = $partialblocks * 60;
                        $temparray[$curheight] = array('StartPixel' => $curheight, 'Size' => $size, 'name' => '', 'eventid' => 0, 'css' => '', 'Date' => $date, 'starttime' => sprintf("%02d", $starttimehours).':'.sprintf("%02d", $starttimeminutes).':00');
                        $curheight += $size;
                    }

                    // Now create the whole blocks
                    if ($wholeblocks > 0) {
                        for ($startpixel = $curheight, $i = 0; $i < $wholeblocks; $startpixel += $oneunitpixels, $i++) {
                            $starttimehours++;
                            $temparray[$startpixel] = array('StartPixel' => $startpixel, 'Size' => $oneunitpixels, 'name' => '', 'eventid' => 0, 'css' => '', 'Date' => $date, 'starttime' => sprintf("%02d", $starttimehours).':00:00');
                            $curheight += $oneunitpixels;
                        }
                    }

                }
                // END if (($curheight % $oneunitpixels) == 0)
            }
            // END if ($curheight < $maxcolumnheight)

            $gridarray = $gridarray + $temparray;
            $temparray = array();
        } else {
            // No events scheduled for $date so create a completely blank grid

            // Start with an empty array
            $gridarray = array();

            // Now fill it in
            for ($starttimehours = 0, $i = 0; $i < $maxcolumnheight; $starttimehours++, $i += $oneunitpixels) {
                $gridarray[$i] = array('StartPixel' => $i, 'Size' => $oneunitpixels, 'name' => '', 'eventid' => 0, 'css' => '', 'Date' => $date, 'starttime' => sprintf("%02d", $starttimehours).':00:00');
            }

        }
        // END if (array_key_exists($date, $events) && is_array($events[$date])) {

        // Ensures that the blocks will display in the correct order
        ksort($gridarray);

/**
 * All of the data for this day has been gathered and formatted. Time to display it.
**/

        // Start the next column
        $calendartbl->set_rowspan(24);
        $calendartbl->new_cell();

        // Create the linear graph
        foreach ($gridarray as $key => $data) {

            // Set the hidden fields for the button
            if ($data['name'] == '') {
                $form->add_hidden(array(
                    'ACTON' => 'addevent',
                    'starttime' => $data['starttime']
                ));
            } else {
                $form->add_hidden(array(
                    'ACTON' => 'viewevent',
                    'eventid' => $data['eventid']
                ));
            }
            $form->add_hidden(array(
                'view' => 'weeklyview',
                'date' => $date
             ));

            // Display the button
            $form->set_inlinestyle('height:'.$data['Size'].'px;');
            $form->buttononlyform(kgGetScriptName(),'post','frmbutton',$data['name'],'','wv_block '.$data['css'].' wordwrap');
        }
        // END foreach ($gridarray as $key => $data)

    }
    // END foreach ($weekdatearray as $key => $date)
    echo '</div>';

    // Add the rest of the hours to the left side of the table
    for ($x = 1; $x < 24; $x++) {
        echo "\n";
        $calendartbl->new_row();

        // Display the hour for each row
        $calendartbl->new_cell($hours[$x]['css']);
        echo $hours[$x]['HR'];
    }

    $calendartbl->end_table();

    echo "\n</div><!-- END wv_buttons_table_div -->\n</div><!-- END wv_encompassing_div -->\n";

    $calendartbl->new_table('wv_nav_table');
    $calendartbl->new_row();
    $calendartbl->new_cell('toptext');
    // Link to jump to current week
    echo kgCreateLink(get_lang('GoToThisWeek'), array('view' => 'weeklyview')).'<br /><br />';

    $calendartbl->set_width(30, 'px');
    $calendartbl->blank_cell();

    // Go to a specific date without having to click through the weeks
    $calendartbl->new_cell('toptext');
echo '<script>
    $(function() {
        $( "#GoToDate" ).datepicker({dateFormat: "yy-mm-dd"});
    });

    function checkGoThereDate() {
        if (document.getElementById("GoToDate").value == \'\') {
            alert("'.get_lang('clicktextboxtoselectdate').'");
            return false;
        }

        return true;
    }
</script>';
    $form->add_hidden(array(
        'ACTON' => 'dailyview',
        'view' => $valid->get_value('view'),
    ));
    $form->onsubmit('return checkGoThereDate();');
    $form->start_form(kgGetScriptName(),'post','frmloadschedule');
    $form->add_text('GoToDate',get_lang('ClickToSelectDate'));
    $form->add_button_submit(get_lang('GoThere'));
    $form->end_form();

    $calendartbl->set_width(30, 'px');
    $calendartbl->blank_cell();

    $calendartbl->new_cell('toptext');
    // Monthly/Weekly view buttons
    monthweekviewbuttons(array('monthly'), $selecteddate);

    $calendartbl->end_table();

}
// END function WeeklyView()
/////

function createDateRangeArray($startdate,$enddate) {
    // -Takes two dates formatted as YYYY-MM-DD and creates an
    //  array of the dates between and including the from and to dates.

    $datesarray=array();
    list($startyear,$startmonth,$startday) = explode('-',$startdate);
    list($endyear,$endmonth,$endday) = explode('-',$enddate);

    $datefrom=mktime(1,0,0,$startmonth,$startday,$startyear);
    $dateto=mktime(1,0,0,$endmonth,$endday,$endyear);

    if ($dateto >= $datefrom) {
        // first entry
        array_push($datesarray,date('Y-m-d',$datefrom));

        while ($datefrom<$dateto) {
            // add 24 hours
            $datefrom+=86400;
            array_push($datesarray,date('Y-m-d',$datefrom));
        }
    }
    return $datesarray;
}
// END function createDateRangeArray()
/////

function DailyView($matchdate) {
    /**
     * Display schedule for selected date
     *
     * Added: 2017-07-03
     * Modified: 2017-07-06
     *
     * @param Required string $matchdate The date that we want to view the schedule for.
     *                                   Must be in the PHP format date("Y-m-d").
     *                                      I.E: 2015-12-24, 2016-01-01
     *
     * @return Nothing
    **/

    global $dbc, $valid, $userinfo, $KG_SECURITY;

    // Display errors if any
    $valid->displayErrors();

    $form = new HtmlForm();
    $table = new HtmlTable();

    // Full date of today (YYYY-MM-DD) converted to Unix epoch
    // Used to to set CSS class to show if event is in the past, present or future
    $todaysdateepoch = strtotime(date('Y-m-d'));

    // Get current time in Unix epoch form
    // Used to to set CSS class to show if event is in the past, present or future
    // "%T" equals "%H:%M:%S"
    $curtimeepoch = strtotime(strftime("%T"));

    // Convert $matchdate to Unix epoch
    $matchdateepoch = strtotime($matchdate);

    // If $matchdate is 2015-03-22, $textdate will be 'Sunday, March 22, 2015'
    $textdate = date("l, F d, Y", $matchdateepoch);

    // Set data and css class for hour cells
    $hourarray = array();
    for ($x = 0; $x < 24; $x++) {
        $hourarray[] = array('HR' => sprintf("%02d",$x).':00','css' => 'hrcell');
    }

    /**
     * This sets the width of the buttons in pixels
     * One unit equals one hour
     *
     * NOTE:
     *      This needs to match the width set in
     *      .dv_*_row_div .hrcell{} and .one_unit_div {}
     *      in module_main.css
    **/
    $oneunitpixels = 90;

    // Must be equal to the right margin set by
    // .one_unit_div, .three_quarter_unit_div, .half_unit_div, .quarter_unit_div {}
    $marginrightpixels = 2;

    echo '<h3 class="centertext">'.kgCreateLink($textdate,array('ACTON' => 'dailyview', 'date' => $matchdate), get_lang('ClickToRefresh')).'</h3><br /><br />';

    // Get info about each event scheduled for $matchdate
// TODO: What if the user wants to view their events and public events at the same time?
    if ($KG_SECURITY->isLoggedIn() === true) {
        // Select only the events created by the logged in user
        $extrafield = 'eventfor = '.$userinfo['UserID'];
    } else {
        // Show public events created by any user
        $extrafield = 'public = 1';
    }

    // Get all events for today
    $eventdatesquery = $dbc->select('eventdates', array('eventdate = "'.$matchdate.'"', $extrafield), '', array('ORDER BY' => 'starttime ASC'));

    $numrows = $dbc->numRows($eventdatesquery);

    // Save as string since there are two places in the code that display these lines
    $middle_row_hours = "\n".'<div class="dv_middle_row_div">'."\n".'<div class="cal_clearfix">'."\n";
    for ($x = 8; $x < 16; $x++) {
        $middle_row_hours .= '<div class="'.$hourarray[$x]['css'].'">'.$hourarray[$x]['HR'].'</div>'."\n";
    }
    $middle_row_hours .= "</div>\n";
    ///
    $bottom_row_hours = "\n".'<div class="dv_bottom_row_div">'."\n".'<div class="cal_clearfix">'."\n";
    for ($x = 16; $x < 24; $x++) {
        $bottom_row_hours .= '<div class="'.$hourarray[$x]['css'].'">'.$hourarray[$x]['HR'].'</div>'."\n";
    }
    $bottom_row_hours .= "</div>\n";

    echo "\n\n".'<div class="dv_row_encompassing_div">';

    // No need to save this row as a string since it will always
    // be displayed first.
    echo "\n".'<div class="dv_top_row_div">'."\n".'<div class="cal_clearfix">'."\n";
    for ($x = 0; $x < 8; $x++) {
        echo '<div class="'.$hourarray[$x]['css'].'">'.$hourarray[$x]['HR'].'</div>'."\n";
    }
    echo "</div>\n";

    if ($numrows > 0) {
        // There is at least 1 event scheduled for $matchdate

        // Used to keep track of unscheduled times between events
        // and to generate the button(s) for those time slots.
        $curepoch = strtotime("00:00:00");

        // Lets the script know when to start the next row
        $lasthour = 8;

// TODO: This needs to be tested. Also remove the unnecessary \n's
        echo "\n".'<div class="cal_clearfix">'."\n";

        // Get details about each event that occurs on $matchdate
        while($row = $dbc->fetchAssoc($eventdatesquery)) {

            // Start time in Unix epoch
            $eventstartepoch = strtotime($row['starttime']);

            if ($curepoch < $eventstartepoch) {
                // Create <div>s as needed to fill in the unused time slots

                $secdiff = $eventstartepoch - $curepoch;

                $hour = (strftime("%H", $curepoch) + 0);

                // Generate the onclick data
                $configarray = array(
                    'ACTON' => 'addevent',
                    'starttime' => '',
                    'date' => $matchdate,
                    'view' => 'dailyview',
                    'NO_TAG' => 'NO_TAG'
                );

                while ($secdiff >= 3600) {
                    // Generate the div(s)

                    $secdiff = $secdiff - 3600; // 3600 seconds equals 1 hour equals one unit

                    if ($hour < 10) {
                        $h = '0'.$hour;
                    } else {
                        $h = $hour;
                    }
                    $configarray['starttime'] = $h.':00:00';
                    $onclick = 'window.location=\''.kgCreateLink('', $configarray).'\'';

                    echo '<div class="one_unit_div no_events" onclick="'.$onclick.'">&nbsp;</div>';

                    $hour += 1;
                }

                if ($secdiff > 0) {
                    // Create partial unit div

                    if ($hour < 10) {
                        $h = '0'.$hour;
                    } else {
                        $h = $hour;
                    }
                    $configarray['starttime'] = $h.':00:00';
                    $onclick = 'window.location=\''.kgCreateLink('', $configarray).'\'';

                    $width = ($secdiff / 3600) * $oneunitpixels;
                    echo '<div class="no_events" style="width:'.$width.'px;" onclick="'.$onclick.'">&nbsp;</div>';
                }

            }

            // Name of event
            $name = $dbc->fieldValue($dbc->select('eventinfo', 'eventid = '.$row['eventid'], 'name'));

// TODO: Figure out how to properly handle $name being blank.
//       Usually caused by the eventinfo table not having any events with eventid
            if ($name == '') {
                $name = get_lang('eventnamemissing');
            }

            // Figure out which color to make the block
            if ($matchdateepoch > $todaysdateepoch) {
                // Event is in the future
                $class = 'hasevents_future';
            } elseif ($matchdateepoch < $todaysdateepoch) {
                // Event is in the past
                $class = 'hasevents_past';
            } else {
                // Event occurs today

                if ($eventtimeepoch > $curtimeepoch) {
                    // Event is in the future
                    $class = 'hasevents_future';
                } elseif ($eventtimeepoch < $curtimeepoch) {
                    // Event is in the past
                    $class = 'hasevents_past';
                } else {
                    // Event is now
                    $class = '';
                }
            }

            $unitdata = wvdv_getUnitCount($row['starttime'], $row['endtime'], $oneunitpixels);

            // Split event start/end times into hour, minute, second
            list($hs, $ms, $ss) = explode(':', $row['starttime']);
            list($he, $me, $se) = explode(':', $row['endtime']);

            if (($hs < $lasthour) && ($he > $lasthour)) {
                // The event spans two rows

// TODO: Add code to determine number of time slots left till end of row
//       For now I'm assuming only 1 time slot is left for the event
                $numslots = 1;
                $unitdata['numunits'] -= $numslots;
                $unitdata['Size'] -= $numslots * $oneunitpixels;

                // Display the first part of the button
                $configarray = array(
                    'ACTON' => 'viewevent',
                    'eventid' => $row['eventid'],
                    'starttime' => $row['starttime'],
                    'date' => $matchdate,
                    'view' => 'dailyview',
                    'NO_TAG' => 'NO_TAG'
                );
                $onclick = 'window.location=\''.kgCreateLink('', $configarray).'\'';
                echo '<div class="one_unit_div '.$class.'" style="width:'.($numslots * $oneunitpixels).'px;" title="'.$name.'" onclick="'.$onclick.'">'.$name.'</div>';

                // Set $hs equal to $he so the next row will be displayed
                $hs = $he;
            }

            if ($hs >= $lasthour) {
                $lasthour += 8;

                if ($lasthour == 16) {
                    echo "</div>\n";
                    echo $middle_row_hours;
                    echo '<div class="cal_clearfix">';
                } else {
                    echo "</div>\n";
                    echo $bottom_row_hours;
                    echo '<div class="cal_clearfix">';
                }
            }

            // Display the (rest of the) button
            $configarray = array(
                'ACTON' => 'viewevent',
                'eventid' => $row['eventid'],
                'starttime' => $row['starttime'],
                'date' => $matchdate,
                'view' => 'dailyview',
                'NO_TAG' => 'NO_TAG'
            );
            $onclick = 'window.location=\''.kgCreateLink('', $configarray).'\'';
            $width = $unitdata['Size'] + ($marginrightpixels * ($unitdata['numunits'] - 1));
            echo '<div class="one_unit_div '.$class.'" style="width:'.$width.'px;" title="'.$name.'" onclick="'.$onclick.'">'.$name.'</div>';

            $curepoch = strtotime($row['endtime']);
        }
        // END while($row = $dbc->fetchAssoc($eventdatesquery))
        /////

        // Fill in the remaining time slots if any
        $endepoch = strtotime('24:00:00');

        if ($curepoch < $endepoch) {

            $secdiff = $endepoch - $curepoch;

            while ($secdiff >= 3600) {
                // Generate button(s)

                $secdiff = $secdiff - 3600; // 3600 seconds in 1 hour

                echo '<div class="one_unit_div no_events">&nbsp;</div>';
            }

            if ($secdiff > 0) {
                // Create partial unit div

                $width = ($secdiff / 3600) * $oneunitpixels;
                echo '<div class="no_events" style="width:'.$width.'px;">&nbsp;</div>';
            }

        }

        echo "</div>\n";
    } else {
        // No events for $matchdate
        $configarray = array(
            'ACTON' => 'addevent',
            'date' => $matchdate,
            'view' => 'dailyview',
            'NO_TAG' => 'NO_TAG'
        );

        echo "\n".'<div class="cal_clearfix">'."\n";
        for ($x = 0; $x < 8; $x++) {
            if ($x < 10) {
                $h = '0'.$x;
            } else {
                $h = $x;
            }
            $configarray['starttime'] = $h.':00:00';
            $onclick = 'window.location=\''.kgCreateLink('', $configarray).'\'';
            echo '<div class="one_unit_div no_events" onclick="'.$onclick.'"></div>'."\n";
        }
        echo "</div>\n</div><!-- END dv_top_row_div -->\n";

        echo "\n".$middle_row_hours;

        echo "\n".'<div class="cal_clearfix">'."\n";
        for ($x = 8; $x < 16; $x++) {
            if ($x < 10) {
                $h = '0'.$x;
            } else {
                $h = $x;
            }
            $configarray['starttime'] = $h.':00:00';
            $onclick = 'window.location=\''.kgCreateLink('', $configarray).'\'';
            echo '<div class="one_unit_div no_events" onclick="'.$onclick.'"></div>'."\n";
        }
        echo "</div>\n</div><!-- END dv_middle_row_div -->\n";

        echo "\n".$bottom_row_hours;

        echo "\n".'<div class="cal_clearfix">'."\n";
        for ($x = 16; $x < 24; $x++) {
            if ($x < 10) {
                $h = '0'.$x;
            } else {
                $h = $x;
            }
            $configarray['starttime'] = $h.':00:00';
            $onclick = 'window.location=\''.kgCreateLink('', $configarray).'\'';
            echo '<div class="one_unit_div no_events" onclick="'.$onclick.'"></div>'."\n";
        }
        echo "</div>\n</div><!-- END dv_bottom_row_div -->\n";
    }
    // END if ($numrows > 0)

    echo "\n</div><!-- END dv_row_encompassing_div -->\n";

    monthweekviewbuttons(array('weekly', 'monthly'), $matchdate);

}
// END function DailyView()
/////

function monthweekviewbuttons($view, $date) {
    /**
     * Add buttons to the page to allow the user to goto/return to a certain view
     *
     * Added: 201?-??-??
     * Modified: 2017-07-06
     *
     * @param Required array $view The view(s) to allow the user to switch too
     *                             Valid views are:
     *                                daily
     *                                weekly
     *                                monthly
     * @param Required string $date The date that the view will focus on
     *
     * @return Nothing
    **/

    if ((is_array($view) === true) && (count($view) > 0)) {
        // $view is an array and contains at least one value

        // Convert all values to lower case
        $view = array_map('strtolower', $view);

        $table = new HtmlTable();
        $form = new HtmlForm();

        $table->new_table('monthweekviewbuttons_table');

        $table->new_row();
        if (array_search('weekly',$view) !== false) {
            $table->new_cell();
            // Form button to switch to week view
            $form->add_hidden(array('view' => 'weeklyview','date' => $date));
            $form->buttononlyform(kgGetScriptName(),'post','frmweekview',get_lang('WeeklyView'));
        }
        if (array_search('monthly',$view) !== false) {
            $table->new_cell();
            // Form button to switch to month view
            $form->add_hidden(array('view' => 'monthlyview','date' => $date));
            $form->buttononlyform(kgGetScriptName(),'post','frmmonthview',get_lang('MonthlyView'));
        }
        if (array_search('daily',$view) !== false) {
            $table->new_cell();
            // Form button to switch to dialy view
            $form->add_hidden(array('view' => 'dailyview','date' => $date));
            $form->buttononlyform(kgGetScriptName(),'post','frmdailyview',get_lang('dailyview'));
        }

        $table->end_table();
    } // else { Display nothing }
}
// END monthweekviewbuttons()
/////

function geteventdata($eventid) {
    /**
     * Get data for an existing event.
     *
     * If an invalid event ID is received, an empty array will be returned
     *
     * Added: 2013-01-19
     * Modified: 2017-07-27
     *
     * @param Required integer $eventid The ID number of the event
     *
     * @return array
    **/

    global $dbc, $valid;

    $eventinfo = array();

    // Make sure $eventid is numeric and is greater than 0
    // -The lowest possible event ID is 1 (one)
    if (is_numeric($eventid) && $eventid > 0) {

        // Name, description, Location
        $eventinfo['data'] = $dbc->fetchAssoc($dbc->select('eventinfo', 'eventid = '.$eventid));

        // Dates and times
        // $eventinfo['eventdates'] is a nested array
        $query = $dbc->select('eventdates', 'eventid = '.$eventid);
        while ($row = $dbc->fetchAssoc($query)) {
            $key = $row['eventdate'];
            unset($row['eventdate']);   // Avoid duplicating data
            $eventinfo['eventdates'][$key] = $row;
        }

        // Reminders
        // $eventinfo['reminders'] is a nested array
        $query = $dbc->select('reminders', 'eventid = '.$eventid);
        while ($row = $dbc->fetchAssoc($query)) {
            $key = $dbc->fieldValue($dbc->select('eventdates', 'dateid = '.$row['dateid'], 'eventdate'));
            $eventinfo['reminders'][$key] = $row;
        }
    }

    return $eventinfo;
}
// END function geteventdata()
/////

function deleteevent() {
    /**
     * Deletes an event from the database.
     *
     * Added: 201?-??-??
     * Modified: 2015-12-14
     *
     * @param None
     *
     * @return Nothing
    **/

    global $dbc, $valid;

    $form = new HtmlForm();
    $table = new HtmlTable();

    $eventid = $valid->get_value('eventid');
    $eventdate = $valid->get_value('eventdate');

    // Clear all existing errors and warnings
    $valid->reset_errors();
    $valid->reset_warnings();

    if ($eventdate == '') {
        // Delete all info for the event
        $where = 'eventid = '.$eventid;
    } else {
        // Only delete info for a specific day of the event
        $where = 'eventid = '.$eventid.' AND eventdate = "'.$eventdate.'"';
    }

    if (!$dbc->delete('eventdates', $where)) {
        // Unable to delete date(s) for event $eventid
        $valid->add_warning(get_lang('eventdateDeletionFailed').$eventid.'<br />'.get_lang('Reason').': '.$dbc->errorString());
    }

    if($dbc->numRows($dbc->select('eventdates', 'eventid = '.$eventid)) <= 0) { 
        // No more days for the event so delete the event info

        if (!$dbc->delete('eventinfo', $where)) {
            // Unable to delete info for event $eventid
            // If the event ID is reused, this will cause invalid event dates/info to be shown
            $valid->add_warning(get_lang('EventInfoDeletionFailed').$eventid.'<br />'.get_lang('Reason').': '.$dbc->errorString());
        }
    }

    if ($valid->is_warning()) {
        // Unable to delete event
        $valid->warnings_table();

        echo '<center>';
        $form->add_hidden(array('ACTON' => 'viewevent', 'eventid' => $eventid, 'view' => $valid->get_value('view')));
        $form->buttononlyform(kgGetScriptName(),'post','frmcontinue',get_lang('continue'));
        echo '</center>';
    }
} // END function deleteevent

function SaveEventChanges() {
    /**
     * Saves changes to the data for an existing event to the database
     * after final processing and validation of event dates and reminders
     *
     * Added: 2017-03-26
     * Modified: 2017-03-26
     *
     * @param None
     *
     * @return Nothing
    **/

    global $valid, $userinfo, $dbc;

    // Make sure there are no errors/warnings stored
    $valid->reset_errors();
    $valid->reset_warnings();

    /////
    // Get event data and sort it into arrays

    $eventid = $valid->get_value_numeric('eventid');

    if ($eventid < 1) {
        // Can't save changes unless there is a valid event ID
        $valid->addError(get_lang('eventupdatefailed').'.<br />'.get_lang('invalideventid'));
        return;
    }

    $initialdate = $valid->get_value('date');
    list($year,$month,$day) = explode('-', $initialdate);
    $selecteddates = explode(',', $valid->get_value('selecteddates')); // Convert string to array
    $starttimes = $valid->get_value('starttime');
    $endtimes = $valid->get_value('endtime');
    $chkreminders = $valid->get_value('chkreminder');
    $reminderdates = $valid->get_value('reminderdate');
    $remindertimes = $valid->get_value('remindertime');

    // $chkreminders must be an array
    if (!is_array($chkreminders)) {
        $chkreminders = array($chkreminders);
    }

    $reminders = array();

    // The event info/details
    $eventinfoarray = array(
        'name' => $valid->get_value('eventname'),
        'contactid' => $valid->get_value_numeric('location',0),
        'description' => $valid->get_value('description'),
        'addedby' => 3, // Kravens webserver user ID
        'eventfor' => $userinfo['UserID'],
        'public' => 0, // <-- TODO: Need to add check box for this
    );

    // The date(s) and time(s) the event is scheduled
    foreach ($selecteddates as $key => $day) {
        $date = $year."-".sprintf("%02d",$month)."-".sprintf("%02d",$day);
        $hasreminder = (array_key_exists($day, $chkreminders) === true ? 1 : 0);
        $eventdatesarray[$day] = array(
            'eventdate' => $date,
            // 'alldayevent' => $alldayevent, // <-- TODO: Need to add check box for this
            'starttime' => to24hour($starttimes[$day]),
            'endtime' => to24hour($endtimes[$day]),
            'addedby' => 3, // Kravens webserver user ID
            'eventfor' => $userinfo['UserID'],
            'hasreminder' => $hasreminder
        );

        if ($hasreminder == 1) {
            // Get the date and time to set the reminder for
            $reminders[$day] = array(
                'reminderdate' => $reminderdates[$day],
                'remindertime' => to24hour($remindertimes[$day])
            );
        }
    }

/*
    echo '<pre>$eventinfoarray = ';
    print_r($eventinfoarray);
    echo '</pre>';
    echo '<pre>$eventdatesarray = ';
    print_r($eventdatesarray);
    echo '</pre>';
    echo '<pre>$reminders = ';
    print_r($reminders);
    echo '</pre>';

    return;
*/

    /////
    // Update event data in database

    if ($dbc->insert('eventinfo', $eventinfoarray)) {
        // Event info added

        // Retrieve event ID for a new event
        $eventid = $dbc->GetInsertID();

        // Add the event dates
        foreach ($eventdatesarray as $day => $data) {
            $data['eventid'] = $eventid;

            if ($dbc->insert('eventdates', $data)) {
                if (array_key_exists($day, $reminders)) {
                    // Keep track of which date the reminder is for
                    $reminders[$day]['dateid'] = $dbc->GetInsertID();
                }
            } else {
                // event date insert failed
                $valid->addError(get_lang('UnableToSaveEventDates').'<br />'.get_lang('Reason').' :<br />'.$dbc->errorString());
            }
        }

        // Skip the reminders array if none were set
        if (count($reminders) > 0) {
            foreach ($reminders as $key => $data) {

                // Add event ID to each reminder
                $data['eventid'] = $eventid;

                // Add the reminder to the database
                if (!$dbc->insert('reminders', $data)) {
                    // Reminder insert failed
                    $valid->addError(get_lang('reminderinsertfailed').'<br />'.get_lang('Reason').' :<br />'.$dbc->errorString());
                }
            }
        }

    } else {
        // Insert query failed
        $valid->addError(get_lang('EventInsertFailed').'<br />'.get_lang('Reason').' :<br />'.$dbc->errorString());
    }

    $valid->setValue(array('view' => $valid->get_value('view')));

}
// END function SaveEventChanges()

function SaveNewEvent() {
    /**
     * Saves data for a new event to the database after final
     * processing and validation of event dates and reminders
     *
     * Added: 2017-03-11
     * Modified: 2017-03-11
     *
     * @param None
     *
     * @return Nothing
    **/

    global $valid, $userinfo, $dbc;

    // Make sure there are no errors/warnings stored
    $valid->reset_errors();
    $valid->reset_warnings();

    /////
    // Get event data and sort it into arrays

    $initialdate = $valid->get_value('date');
    list($year,$month,$day) = explode('-', $initialdate);
    $selecteddates = explode(',', $valid->get_value('selecteddates')); // Convert string to array
    $starttimes = $valid->get_value('starttime');
    $endtimes = $valid->get_value('endtime');
    $chkreminders = $valid->get_value('chkreminder');
    $reminderdates = $valid->get_value('reminderdate');
    $remindertimes = $valid->get_value('remindertime');

    // $chkreminders must be an array
    if (!is_array($chkreminders)) {
        $chkreminders = array($chkreminders);
    }

    $reminders = array();

    // The event info/details
    $eventinfoarray = array(
        'name' => $valid->get_value('eventname'),
        'contactid' => $valid->get_value_numeric('location',0),
        'description' => $valid->get_value('description'),
        'addedby' => 3, // Kravens webserver user ID
        'eventfor' => $userinfo['UserID'],
        'public' => 0, // <-- TODO: Need to add check box for this
    );

    // The date(s) and time(s) the event is scheduled
    foreach ($selecteddates as $key => $day) {
        $date = $year."-".sprintf("%02d",$month)."-".sprintf("%02d",$day);
        $hasreminder = (array_key_exists($day, $chkreminders) === true ? 1 : 0);
        $eventdatesarray[$day] = array(
            'eventdate' => $date,
            // 'alldayevent' => $alldayevent, // <-- TODO: Need to add check box for this
            'starttime' => to24hour($starttimes[$day]),
            'endtime' => to24hour($endtimes[$day]),
            'addedby' => 3, // Kravens webserver user ID
            'eventfor' => $userinfo['UserID'],
            'hasreminder' => $hasreminder
        );

        if ($hasreminder == 1) {
            // Get the date and time to set the reminder for
            $reminders[$day] = array(
                'reminderdate' => $reminderdates[$day],
                'remindertime' => to24hour($remindertimes[$day])
            );
        }
    }

/*
    echo '<pre>$eventinfoarray = ';
    print_r($eventinfoarray);
    echo '</pre>';
    echo '<pre>$eventdatesarray = ';
    print_r($eventdatesarray);
    echo '</pre>';
    echo '<pre>$reminders = ';
    print_r($reminders);
    echo '</pre>';

    return;
*/

    /////
    // Insert event data into database

    if ($dbc->insert('eventinfo', $eventinfoarray)) {
        // Event info added

        // Retrieve event ID for a new event
        $eventid = $dbc->GetInsertID();

        // Add the event dates
        foreach ($eventdatesarray as $day => $data) {
            $data['eventid'] = $eventid;

            if ($dbc->insert('eventdates', $data)) {
                if (array_key_exists($day, $reminders)) {
                    // Keep track of which date the reminder is for
                    $reminders[$day]['dateid'] = $dbc->GetInsertID();
                }
            } else {
                // event date insert failed
                $valid->addError(get_lang('UnableToSaveEventDates').'<br />'.get_lang('Reason').' :<br />'.$dbc->errorString());
            }
        }

        // Skip the reminders array if none were set
        if (count($reminders) > 0) {
            foreach ($reminders as $key => $data) {

                // Add event ID to each reminder
                $data['eventid'] = $eventid;

                // Add the reminder to the database
                if (!$dbc->insert('reminders', $data)) {
                    // Reminder insert failed
                    $valid->addError(get_lang('reminderinsertfailed').'<br />'.get_lang('Reason').' :<br />'.$dbc->errorString());
                }
            }
        }

    } else {
        // Insert query failed
        $valid->addError(get_lang('EventInsertFailed').'<br />'.get_lang('Reason').' :<br />'.$dbc->errorString());
    }

    $valid->setValue(array('view' => $valid->get_value('view')));
}
// END function SaveNewEvent()
/////

function to24hour($time) {
    /**
     * Converts the given time for use on a 24-hour clock.
     *
     * Adds leading zero if needed.
     *
     * Examples:
     *    2:00am    --> 02:00
     *    02:00     --> 02:00
     *    2:00      --> 02:00
     *    14:00     --> 14:00
     *    2:00pm    --> 14:00
     *    03:15PM   --> 15:15
     *    11:00pm   --> 23:00
     *
     * Added: 2017-03-20
     * Modified: 2017-03-20
    **/

    $time = strtolower($time);

    if (substr($time,-2) == 'am') {
        $time = explode(':', substr($time, 0, -2));
        if (strlen($time[0]) == 1) {
            $time[0] = '0'.$time[0];
        }
    } elseif (substr($time,-2) == 'pm') {
        $time = explode(':', substr($time, 0, -2));
        if ($time[0] < 13) {
            $time[0] += 12;
        }
    } else {
        // 
        $time = explode(':', $time);
        if (strlen($time[0]) == 1) {
            $time[0] = '0'.$time[0];
        }
    }

    $time = join(':', $time);

    return $time;
}
// END function to24hour()
/////

function ViewEvent($matchdate) {
    /**
     * Display info and all dates for an event.
     *
     * Added: 2017-03-19
     * Modified: 2017-07-04
    **/

    global $dbc, $valid, $KG_SECURITY, $userinfo, $kgSkin;

    $form = new HtmlForm();

    // Get event ID
    $eventid = $valid->get_value_numeric('eventid');
    if ($eventid < 1) {
        // Invalid event ID
        echo '<center>'.get_lang('invalideventid').'<br /><br />';
        $form->buttononlyform(kgGetScriptName(),'post','frmcontinue',get_lang('continue'));
        echo '</center>';
        return;
    }

    // Make sure user is allowed to view this event
    $veiwonlypermission = false;
    $eventownerid = $dbc->fieldValue($dbc->select('eventinfo', 'eventid = '.$eventid, 'eventfor'));
    if (!$KG_SECURITY->isOwner($eventownerid)) {
        echo '<center>'.get_lang('notyours').'<br /><br />';
        $form->add_hidden(array(
            'view' => 'dailyview',
            'date' => $matchdate
        ));
        $form->buttononlyform(kgGetScriptName(),'post','frmcontinue',get_lang('continue'));
        echo '</center>';
        return;
    }

    // Return user to the view (daily, weekly, monthly) they came from
    $view = $valid->get_value('view');
    if ($view == '') {
        $view = 'dailyview';
    }
    $valid->setValue(array('view' => $view));

    // Get event data
    $eventinfo = geteventdata($eventid);
    if (empty($eventinfo)) {
        // Unable to load event data
        echo '<center>'.get_lang('invalideventid').'<br /><br />'.get_lang('unabletoloadeventinfo');
        $form->add_hidden(array(
            'view' => $view,
            'date' => $matchdate
        ));
        $form->buttononlyform(kgGetScriptName(),'post','frmcontinue',get_lang('continue'));
        echo '</center>';
        return;
    }

    $table = new HtmlTable();
    $infotable = new HtmlTable();
    $listtable = new HtmlTable();

    // Get date for selected date
    $selectedday = $valid->get_value('date');
    list($year,$month,$day) = explode('-',$selectedday);

    // Start time
    list($starthour,$startminute,$startseconds) = explode(':',$eventinfo['eventdates'][$selectedday]['starttime']);

    // End time
    list($endhour,$endminute,$endseconds) = explode(':',$eventinfo['eventdates'][$selectedday]['endtime']);

    // Start table
    $table->new_table('centered');

    // Table/Page title
    $table->new_row();
    $table->new_cell('title_cell');
    echo get_lang('ViewEvent');

    $table->blank_row(1);

    $table->new_row();

    // Event data cell
    $table->new_cell();

        $infotable->new_table('tableborder');

        // Event name
        // $table and $infotable width is set in this row
        $infotable->new_row();
        $infotable->set_width(150,'px');
        $infotable->new_cell();
        echo get_lang('eventname').':';
        $infotable->set_width(500,'px');
        $infotable->new_cell();
        echo $eventinfo['name'];

        // Event location
        $infotable->new_row();
        $infotable->new_cell();
        echo get_lang('location').':';
        $infotable->new_cell();
        //Connect to the Contacts database
        $dbc->connectDatabase('Contacts');

        // Get info to display the event location
        $concat = array('buildConcatWS' => array(', ', array('LastName','FirstName'), 'FullName'));
        $query = $dbc->select('Contacts', 'ContactID = '.$eventinfo['contactid'], array('ContactTypeID', $concat, 'CompanyName'));
        $data = $dbc->fetchAssoc($query);
        if ($data['ContactTypeID'] == 1) {
            $location = $data['FullName'];
        } elseif ($data['ContactTypeID'] == 2) {
            $location = $data['CompanyName'];
        }
        echo $location;

        // Event description
        $infotable->new_row();
        $infotable->new_cell();
        echo get_lang('description').':';
        $infotable->new_cell();
        echo $eventinfo['description'];

        $infotable->blank_row();

        $infotable->new_row();
        $infotable->set_colspan(2);
        $infotable->new_cell();

            // Dates list header table
            $listtable->new_table('centered bordercollapse');

            $listtable->new_row();
            $listtable->set_colspan(4);
            $listtable->set_height(25,'px');
            $listtable->new_cell('centertext');
            echo '<b>'.get_lang('dateslist').'</b>';

            $listtable->new_row();
            $listtable->set_width(125,'px');
            $listtable->new_cell('centertext');
            echo get_lang('date');
            $listtable->set_width(110,'px');
            $listtable->new_cell('centertext');
            echo get_lang('starttime');
            $listtable->set_width(110,'px');
            $listtable->new_cell('centertext');
            echo get_lang('endtime');
            $listtable->set_width(85,'px');
            $listtable->new_cell('centertext');
            echo get_lang('reminder');

            $listtable->end_table();

        // Dates list
        foreach ($eventinfo['eventdates'] as $date => $data) {
            $infotable->new_row();
            $infotable->set_colspan(2);
            $infotable->new_cell();

                $listtable->new_table('centered bordercollapse');

                // Highlight the date selected in monthview or weeklyview
                if ($date == $matchdate) {
                    $css = 'highlightrow';
                } else {
                    $css = '';
                }
                $listtable->new_row($css);
                $listtable->set_width(125,'px');
                $listtable->new_cell('centertext');
                echo $date;
                $listtable->set_width(110,'px');
                $listtable->new_cell('centertext');
                echo $data['starttime'];
                $listtable->set_width(110,'px');
                $listtable->new_cell('centertext');
                echo $data['endtime'];
                $listtable->set_width(85,'px');
                $listtable->new_cell('centertext');
                if ($data['hasreminder'] == 1) {
                    echo '<img src="'.$kgSkin.'/images/green_checkmark_22.svg" alt="Yes" title="Yes">';
                } else {
                    echo '<img src="'.$kgSkin.'/images/gray_x_22.svg" alt="No" title="No">';
                }

                $listtable->end_table();
        }

        $infotable->blank_row();

        $infotable->new_row();
        $infotable->set_colspan(2);
        $infotable->new_cell();

            // Reminders list header table
            $listtable->new_table('centered bordercollapse');

            $listtable->new_row();
            $listtable->set_colspan(4);
            $listtable->set_height(25,'px');
            $listtable->new_cell('centertext');
            echo '<b>'.get_lang('reminderslist').'</b>';

            $listtable->new_row();
            $listtable->set_width(150,'px');
            $listtable->new_cell('centertext');
            echo get_lang('reminderdate');
            $listtable->set_width(150,'px');
            $listtable->new_cell('centertext');
            echo get_lang('remindertime');
            $listtable->set_width(125,'px');
            $listtable->new_cell('centertext');
            echo get_lang('eventdate');

            $listtable->end_table();

        // Reminders list
        foreach ($eventinfo['reminders'] as $date => $data) {
            $infotable->new_row();
            $infotable->set_colspan(2);
            $infotable->new_cell();

                $listtable->new_table('centered bordercollapse');

                // Highlight the reminder for the date selected in monthview or
                // weeklyview if one was set
                if ($date == $matchdate) {
                    $css = 'highlightrow';
                } else {
                    $css = '';
                }

                $listtable->new_row($css);
                $listtable->set_width(150,'px');
                $listtable->new_cell('centertext');
                echo $data['reminderdate'];
                $listtable->set_width(150,'px');
                $listtable->new_cell('centertext');
                echo $data['remindertime'];
                $listtable->set_width(125,'px');
                $listtable->new_cell('centertext');
                echo $date;

                $listtable->end_table();
        }

    $table->end_table();

    if ($KG_SECURITY->isOwner($eventownerid) && $KG_SECURITY->isLoggedIn()) {
        // Add the edit button only if this event is for the current user

        $table->new_table('centered');

        $table->blank_row(1);

        $table->new_row();
        $table->new_cell();
        $form->add_hidden(array(
            'ACTON' => 'editevent',
            'eventid' => $eventid,
            'date' => $matchdate
        ));
        $form->buttononlyform(kgGetScriptName(),'post','frmeditevent',get_lang('editevent'));

        $table->blank_row(1);

        $table->end_table();

    }

    monthweekviewbuttons(array('daily', 'weekly', 'monthly'), $matchdate);

}
// END function ViewEvent()
/////

function wvdv_getUnitCount($starttime, $endtime, $oneunitpixels) {
    /**
     * Finds the number of hours between the start and end times of an
     * event and converts that into a fractional number (the units) instead of hours and minutes.
     *
     * Example:
     *      An event starts at 8:00AM and ends at 10:30AM.
     *      This event is 2 hours 30 minutes long.
     *      When converted into a fractional number it becomes 2.5
     *
     * Added: 2015-12-20
     * Modified: 2016-01-17
     *
     * @param Required string $starttime     The start time of the event
     *                                       Must be in the format H:i:s
     * @param Required string $endtime       The end time of the event
     *                                       Must be in the format H:i:s
     * @param Required integer $oneunitpixels The number of pixels each unit block uses
     *
     * @return array
    **/

    /**
     * Get units of time required for this event
    **/
    $stime = strtotime("1980-01-01 $starttime");
    $etime = strtotime("1980-01-01 $endtime");
    
    if ($etime < $stime) {
        // The event starts on one day and ends the following day
        $etime += 86400;
    }
    
    // Number of hours, minutes and seconds between start and end times
    $timelen = date("H:i:s", strtotime("1980-01-01 00:00:00") + ($etime - $stime));

    list($hours, $minutes, $seconds) = explode(':', $timelen);

    // Converts $minutes to a number and removes any leading zeros (0)
    $minutes = $minutes + 0;

    if ($minutes > 0) {
        $minutes = round($minutes / 60, 2);
        if ($minutes < 1) {
            $minutes = $minutes * 10;
        }
    }

    $timelen = $hours.'.'.$minutes;
    $timelen = $timelen + 0;
    $size = $timelen * $oneunitpixels;

    /**
     * Get start pixel for this event
    **/
    list($hours, $minutes, $seconds) = explode(':', $starttime);

    // Converts $minutes to a number and removes any leading zeros (0)
    $minutes = $minutes + 0;

    if ($minutes > 0) {
        $minutes = round($minutes / 60, 2);
        if ($minutes < 1) {
            $minutes = $minutes * 10;
        }
    }

    $startpixel = $hours.'.'.$minutes;
    $startpixel = $startpixel + 0;
    $startpixel = $startpixel * $oneunitpixels;    
    
    return array('Size' => $size, 'StartPixel' => $startpixel, 'numunits' => round($size / $oneunitpixels, 2));
}
// END function wvdv_getUnitCount()

if ($dbc->isConnectedDB() === true) {

    // Date to work with
    $matchdate = $valid->get_value('date');

    if ($matchdate == '') {
        // Was date given via jQuery's datepicker?
        $matchdate = $valid->get_value('GoToDate');
    }

    if ($matchdate == '') {
        // Check a 3rd "location" for the date as seperate values

        $year = $valid->get_value('year');
        $month = $valid->get_value('month');
        $day = $valid->get_value('day');

        if ($year == '') {
            // No year was given so use current year
            $year = date("Y"); // 4 digit year
        }

        if ($month == '') {
            // No year was given so use current month
            $month = date("m"); // 2 digit month with leading zero
        }

        if ($day == '') {
            // No day was given so use todays date
            $day = date("d"); // 2 digit day with leading zero
        }

    } else {
        // Full date was given so use it
        list($year,$month,$day) = explode('-',$matchdate);
    }

    $matchdate = $year."-".sprintf("%02d",$month)."-".sprintf("%02d",$day);

    // Get view mode
    $view = $valid->get_value('view');

    if ($view == '') {
        // View mode not set, default to month
        $view = 'month';
        $valid->setValue(array('view' => $view));
    }

    // What to do? What to do?
    $ACTON = $valid->get_value('ACTON');
    if (($ACTON == 'addevent') || ($ACTON == 'editevent')) {

        AddEditEvent($ACTON, $matchdate);

    } elseif ($ACTON == 'dailyview') {

        DailyView($matchdate);

    } elseif ($ACTON == 'deleteevent') {

        deleteevent($valid->get_value_numeric('eventid',0));

        if ($view == 'dailyview') {

            DailyView($matchdate);

        } elseif ($view == 'weeklyview') {

            WeeklyView($year, $month, $day);

        } else {

            MonthView($year, $month, $day);

        }

    } elseif ($ACTON == 'saveeventchanges') {

        SaveEventChanges();

        if ($view == 'dailyview') {

            DailyView($matchdate);

        } elseif ($view == 'weeklyview') {

            WeeklyView($year, $month, $day);

        } else {

            MonthView($year, $month, $day);

        }

    } elseif ($ACTON == 'savenewevent') {

        SaveNewEvent();

        if ($view == 'dailyview') {

            DailyView($matchdate);

        } elseif ($view == 'weeklyview') {

            WeeklyView($year, $month, $day);

        } else {

            MonthView($year, $month, $day);

        }

    } elseif ($ACTON == 'viewevent') {

        ViewEvent($matchdate);

    } else {

        if ($view == 'dailyview') {

            DailyView($matchdate);

        } elseif ($view == 'weeklyview') {

            WeeklyView($year, $month, $day);

        } else {

            MonthView($year, $month, $day);

        }

    }
} // END if ($dbc->isConnectedDB() === true)
?>
