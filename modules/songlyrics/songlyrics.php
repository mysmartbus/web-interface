<?php
// Last Updated: 2017-07-01
// DB Version: 0.12
$dbc = new genesis();
$db = $dbc->connectServer();
$dbc->connectDatabase('Songlyrics', 0.12);

function AddAlbum() {
    /**
     * Add a new album to the database
     *
     * Added: 2017-06-15
     * Modified: 2017-06-29
     *
     * @param None
     *
     * @return None
    **/

    global $dbc, $valid, $KG_SECURITY, $kgSiteLightBoxJava, $kgSiteLightBoxCss, $IP;

    $form = new HtmlForm();

    // Get artist ID
    // Will be greater than 0 only if this function was called from ViewArtist()
    $artistid = $valid->get_value_numeric('artistid',0);

    // Are they allowed to add a new album?
    if ($KG_SECURITY->haspermission('add') === false) {
        if ($artistID > 0) {
            $form->add_hidden(array(
                'ACTON' => 'viewartist',
                'artistid' => $artistid
            ));
        }
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('goback'));
        echo '</center>';
        return false;
    }

    // Display errors if any
    $valid->displayErrors();

    $table = new HtmlTable();
    $innertable = new HtmlTable();

    if ($artistid > 0) {

        // Get list of album art already uploaded to the server
        $filelist = kgListFolderContents($dbc->fieldValue($dbc->select('Artists', 'ArtistID = '.$artistid, 'ShortName')), true);
    }

    // Check if the server has write permissions to the files directory
    $rv = posix_getpwuid(fileowner($IP.'/files'));
    if ($rv{'name'} == 'www-data') {
        $allowuploads = true;
    } else {
        $allowuploads = false;
    }

    echo '<script src="'.$kgSiteLightBoxJava.'"></script>
<link rel="stylesheet" href="'.$kgSiteLightBoxCss.'">';

echo '<script type="text/javascript">
            function validateAddAlbum() {
            
            if (document.frmaddalbum.albumname.value != "") {
                return true;
            } else {
                alert("'.get_lang('missingalbumname').'");
            }

            return false;
        }
    </script>';

    // Start of form
    $form->add_hidden(array(
        'ACTON' => 'savenewalbum',
        'artistid' => $artistid
    ));
    $form->onsubmit('return validateAddAlbum();');
    $form->start_form_upload(kgGetScriptName(),'post','frmaddalbum');

    $table->new_table();

    $table->new_row();
    $table->set_colspan(3);
    $table->new_cell('page_title_cell');
    echo get_lang('addalbum');

    // Artist Name
    $table->new_row();
    $table->new_cell('left_column_width');
    echo get_lang('artistname');
    $table->new_cell();
    if ($artistid > 0) {
        echo $dbc->fieldValue($dbc->select('Artists', 'ArtistID = '.$artistid, 'Name'));
    } else {
        $form->add_select_db_autoecho('artistid','SELECT ArtistID, Name FROM Artists','ArtistID','','Name',$dbc);
    }

    $table->set_rowspan(2);
    if ($allowuploads) {
        $table->new_cell('centertext');
        // Upload front cover art button
        echo get_lang('uploadfrontcoverart');
        $form->add_file('CoverArtFront');
    } else {
        // Disable upload
        $table->blank_cell();
    }

    // Album Name
    $table->new_row();
    $table->new_cell();
    echo get_lang('albumname');
    $table->new_cell();
    $form->add_text('albumname','',40);

    // Release Date
    $table->new_row();
    $table->new_cell();
    echo get_lang('releasedate');
    $table->new_cell();
// Use jQuery's Datepicker
echo '<script>
    $(function() {
        $( "#releasedate" ).datepicker({dateFormat: "yy-mm-dd",  changeMonth: true, changeYear: true, yearRange: "c-60:c"})
    });
</script>';
    $form->add_text('releasedate',get_lang('ClickToSelectDate'));
    $table->blank_cell();

    // Release Date Unknown
    $table->new_row();
    $table->blank_cell();
    $table->new_cell();
    $form->add_checkbox('releasedateunknown',1,get_lang('Unknown').' '.get_lang('releasedate'));

    $table->set_rowspan(2);
    if ($allowuploads) {
        $table->new_cell('centertext');
        // Upload back cover art button
        echo get_lang('uploadbackcoverart');
        $form->add_file('CoverArtBack');
    } else {
        // Disable upload
        $table->blank_cell();
    }

    // Wikipedia Article
    $table->new_row();
    $table->new_cell();
    echo get_lang('wikipediaarticle');
    $table->new_cell();
    $form->add_text('wikipediaarticle','',40);

    $table->blank_row(3);

    // Form buttons
    $table->new_row();
    $table->blank_cell();
    $table->new_cell();

        $innertable->set_width(100,'%');
        $innertable->new_table();
        $innertable->new_row();
        $innertable->new_cell();
            // Save button
            $form->add_button_submit(get_lang('Save'));
            $form->end_form();
            $innertable->new_cell('centertext');
            // Cancel button
            if ($artistid > 0) {
                $form->add_button_generic('ACTON',get_lang('cancel'),'location.href="'.kgCreateLink('',array('ACTON' => 'viewartist', 'artistid' => $artistid, 'NO_TAG' => 'NO_TAG')).'";');
            } else {
                $form->add_button_generic('ACTON',get_lang('cancel'),'location.href="'.kgCreateLink('',array('NO_TAG' => 'NO_TAG')).'";');
            }
        $innertable->end_table();
    $table->blank_cell();

    if ($filelist['FileCount'] > 0) {
        // Found some files

        //$table->blank_row(3);
        $table->blank_row(3);

        $table->new_row();

        // Display cover art already on the server
        $table->new_row();
        $table->set_colspan(3);
        $table->new_cell('centertext');
        echo get_lang('orselectexistingart');
        $table->new_row();
        $table->set_colspan(3);

        $table->new_cell();

        $innertable->new_table();
        $innertable->new_row();

        $maxcols = 8;
        $cnt = 0;
        foreach ($filelist['Files'] as $shahash => $filename) {

            $cnt++;

            // One image per table cell
            $imginfo = kgGetImageLink($shahash);
            $innertable->new_cell();
            echo '<a href="'.$imginfo['URL'].'" data-lightbox="cover-art-set" data-title="'.$filename.'"><img src="'.$imginfo['URL'].'" width="100px" height="100px"></a><br /><span class="tinytext">'.$filename.'</span><br />';
            $form->add_radio('frontcoverradio',$filename,get_lang('FrontCover'));
            echo '<br />';
            $form->add_radio('backcoverradio',$filename,get_lang('BackCover'));
            echo '<br />';
            $form->add_button_generic('submit',get_lang('Delete'),'location.href="'.kgCreateLink('',array('ACTON' => 'deletealbumart', 'sha' => $shahash, 'artistid' => $artistid, 'dowhat' => 'addalbum', 'NO_TAG' => 'NO_TAG')).'";');
            echo '<br />';

            if ($cnt >= $maxcols) {
                // Start a new row of album art if $cnt >= $maxcols
                $innertable->new_row();
                $cnt = 0;
            } else {
                $innertable->blank_cell(2);
            }
        }

        $innertable->blank_row($maxcols + ($cnt * 2) - 1);
        $innertable->new_row();
        $innertable->new_cell();
        $form->add_button_generic('submit',get_lang('ClearFrontCover'),'clearRB(document.frmeditalbum.frontcoverradio)');
        $innertable->blank_cell(2);
        $innertable->new_cell();
        $form->add_button_generic('submit',get_lang('ClearBackCover'),'clearRB(document.frmeditalbum.backcoverradio)');

        $innertable->end_table();

    }
    // END if ($filelist['FileCount'] > 0))

    $table->end_table();

}
// END function AddAlbum()
/////

function AddArtist() {
    /**
     * Add a new artist to the database
     *
     * Added: 2017-06-13
     * Modified: 2017-07-01
     *
     * @param None
     *
     * @return None
    **/

    global $dbc, $valid, $KG_SECURITY;

    $form = new HtmlForm();

    // Are they allowed to add a new artist?
    if ($KG_SECURITY->haspermission('add') === false) {
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('returntoartistlist'));
        echo '</center>';
        return false;
    }

    // Display errors if any
    $valid->displayErrors();

echo '<script type="text/javascript">
            function validateAddArtist() {
            
            if (document.frmaddartist.artistname.value != "") {
                return true;
            } else {
                alert("'.get_lang('missingartistname').'");
            }

            return false;
        }
    </script>';

    $form->add_hidden(array('ACTON' => 'savenewartist'));
    $form->onsubmit('return validateAddArtist();');
    $form->start_form(kgGetScriptName(),'post','frmaddartist');

    echo '<div class="add_artist_centering_div">';
    echo '<div class="page_title_div">'.get_lang('addartist').'</div>';

    echo "\n".'<div class="add_artist_field_pair_div">';
    echo '<div class="artist_name_label_div">'.get_lang('artistname').':</div>';
    echo '<div class="artist_name_value_div">';
    $form->add_text('artistname','',20);
    echo '</div>';
    echo '</div>';

    echo "\n".'<div class="add_artist_field_pair_div">';
    echo '<div class="website_label_div">'.get_lang('websitelink').':</div>';
    echo '<div class="website_value_div">';
    $form->add_text('website','',40);
    echo '</div>';
    echo '</div>';

    echo "\n".'<div class="add_artist_field_pair_div">';
    echo '<div class="wikiarticle_label_div">'.get_lang('wikipediaarticle').':</div>';
    echo '<div class="wikiarticle_value_div">';
    $form->add_text('wikipediaarticle','',40);
    echo '</div>';
    echo '</div>';

    echo "\n".'<div class="add_artist_field_pair_div">';
    echo '<div class="artistbio_label_div">'.get_lang('artistbio').':</div>';
    echo '<div class="artistbio_label_div">';
    $form->add_textarea('artistbio');
    echo '</div>';
    echo '</div>';

    echo "\n".'<div class="button_group_div">';
    echo '<div>';
    $form->add_button_submit();
    echo '</div><div>';
    $form->add_button_generic('ACTON',get_lang('cancel'),'location.href="'.kgCreateLink('',array('NO_TAG' => 'NO_TAG')).'";');
    echo "</div>\n</div><!-- END button_group_div -->";

    echo '</div>';
    $form->end_form();

}
// END function AddArtist()
/////

function addSelectGenre($genreid=0) {
    /**
     * Long list of music genres retrieved from the database
     *
     * Added: 2017-06-22
     * Modified: 2017-06-22
     *
     * @param Optional integer $genreid ID number of the genre to select
     *
     * @return Nothing
    **/

    global $dbc;

    $query = $dbc->select('GenreIDs', '', '', array('ORDER BY' => 'Name ASC'));

    echo '<select name="genreid" id="genreid">'."\n";

    while ($row = $dbc->fetchAssoc($query)) {
        echo '<option value="'.$row['GenreID'].'"'.($row['GenreID'] == $genreid ? ' selected' : '').'>'.$row['Name'].'</option>'."\n";
    }

    echo '</select>';
}
// END function addSelectGenre()
/////

function AddEditSong($addsong=true) {
    /**
     * Add a new song to the database.
     * or
     * 
     * or
     * Edit an existing song.
     * 
     *
     * Added: 2017-06-27
     * Modified: 2017-07-01
     *
     * @param Optional boolean $addsong If true, display add song form
     *                                  if false, display edit song form
     *
     * @return Nothing
    **/

    global $dbc, $valid, $KG_SECURITY;

    $form = new HtmlForm();

    if ($addsong) {

        // Are they allowed to add a new song?
        if ($KG_SECURITY->haspermission('add') === false) {
            echo '<center>';
            $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('goback'));
            echo '</center>';
            return false;
        }

    } else {

        // Are they allowed to edit an existing song?
        if ($KG_SECURITY->haspermission('edit') === false) {
            $form->add_hidden(array(
                'ACTON' => 'viewsong',
                'songid' => $valid->get_value_numeric('songid', -1)
            ));
            echo '<center>';
            $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('goback'));
            echo '</center>';
            return false;
        }
    }

    // Display errors if any
    $valid->displayErrors();

    $table = new HtmlTable();
    $buttontable = new HtmlTable();

    if ($addsong) {
/******************
 * Add song data *
 ******************/

        /**
         * The value of $addvia determines which sql queries to use and which
         * form fields to display
         *
         * Possible values
         *   mainpage: User clicked on 'Add Song' button on main page
         *             The form will be completely blank when loaded
         *   artistpage: User clicked on 'Add Song' button an artist page
         *               Only artist name and associated albums will be loaded
         *   albumpage: User clicked on 'Add Song' button on an album page
         *              Artist name and album name loaded. Rest of form is blank.
        **/
        $addvia = $valid->get_value('addvia');

        // If $addvia equals 'mainpage', $artistid will be set to zero (0)
        $artistid = $valid->get_value_numeric('artistid',0);

        if ($addvia == 'artistpage') {
            // function called from an artist page

            // Artist Info
            $artistdata = $dbc->fetchAssoc($dbc->select('Artists', 'ArtistID = '.$artistid));

            // Albums associated with selected artist
            $numalbums = $dbc->numRows($dbc->select('Albums', 'ArtistID = '.$artistid, array('AlbumID', 'Name')));

        } elseif ($addvia == 'albumpage') {
            // function called from an album page

            $albumid = $valid->get_value_numeric('albumid',0);

            // Artist Info
            $artistdata = $dbc->fetchAssoc($dbc->select('Artists', 'ArtistID = '.$artistid));

            // Data for the selected album
            $albumdata = $dbc->fetchAssoc($dbc->select('Albums', 'AlbumID = '.$albumid));
        } elseif ($addvia = 'mainpage') {
            // function called from the main page

            $albumid = 0;

        }

        // Javascript code
        echo '<script type="text/javascript">
        function validate() {';

        // The form fields that get validated depend on how we got here
        if ($addvia == 'artistpage') {
            echo '
            if (document.frmaddsong.albumname.value == "" && document.frmaddsong.albumid.value < 1) {
                alert("'.get_lang('missingalbumname').'");
                document.frmaddsong.albumname.focus();
                return false;
            }';
        } else {

            echo '
            if (document.getElementById("newalbumchkbox").checked === true && document.getElementById("newalbumtxtbox").value == "") {
                alert("'.get_lang('missingalbumname').'");
                document.frmaddsong.newalbumtxtbox.focus();
                return false;
            }

            if (document.getElementById("newartistchkbox").checked === true && document.getElementById("newartisttxtbox").value == "") {
                alert("'.get_lang('missingartistname').'");
                document.frmaddsong.newartisttxtbox.focus();
                return false;
            }';
        }
        // END if ($addvia == 'artistpage')

        // Always validate these fields no matter what $addvia equals
        echo '
            if (document.frmaddsong.songname.value == "") {
                alert("'.get_lang('missingsongname').'");
                document.frmaddsong.songname.focus();
                return false;
            }

            if (document.frmaddsong.lyrics.value == "") {
                alert("'.get_lang('missinglyrics').'");
                document.frmaddsong.lyrics.focus();
                return false;
            }

            return true;
        }

        function toggleNewArtistTxtBox() {

            if (document.getElementById("newartistchkbox").checked === true) {
                document.getElementById("artistid").disabled = true;
                document.getElementById("newartisttxtbox").disabled = false;
                document.getElementById("newartisttxtbox").value = "";
            } else {
                document.getElementById("artistid").disabled = false;
                document.getElementById("newartisttxtbox").disabled = true;
                document.getElementById("newartisttxtbox").value = "<-- '.get_lang('clicktoenable').'";
            }

        }

        function toggleNewAlbumTxtBox() {

            var elemStatus = true;
            var elemValue = "<-- '.get_lang('clicktoenable').'";

            if (document.getElementById("newalbumchkbox").checked === true) {

                elemStatus = false;
                elemValue = "";
            }

            document.getElementById("albumid").disabled = !elemStatus;
            document.getElementById("newalbumtxtbox").disabled = elemStatus;
            document.getElementById("newalbumtxtbox").value = elemValue;
        }

    </script>';

        /////
        // Form starts here
        if ($addvia == 'artistpage') {
            $albumid = -1;
        }

        $form->add_hidden(array(
            'ACTON' => 'savenewsong',
            'addvia' => $addvia
        ));

        if ($addvia == 'albumpage') {
            // function called from an album page
            $form->add_hidden(array(
                'albumid' => $albumid,
                'artistid' => $artistid
            ));
        }

        $form->onsubmit('return validate();');
        $form->start_form(kgGetScriptName(),'post','frmaddnewsong');

        echo '<div class="page_title_div">'.get_lang('addsong').'</div>';
    } else {
/******************
 * Edit song data *
 ******************/

        $songid = $valid->get_value_numeric('songid', 0);

        if ($songid < 1) {
           // Invalid song ID, exit function
           echo get_lang('invalidsongid');
           return false;
        }

        // Song data
        $songdata = $dbc->fetchAssoc($dbc->select('Lyrics', 'SongID = '.$songid));

        // Artist name
        // $artistdata = $dbc->fetchAssoc($dbc->select('Artists', 'ArtistID = '.$songdata['ArtistID']));
        $artistname = $dbc->fieldValue($dbc->select('Artists', 'ArtistID = '.$songdata['ArtistID'], 'Name'));

        // Album data
        // $albumdata = $dbc->fetchAssoc($dbc->select('Albums', 'AlbumID = '.$songdata['AlbumID']));
        $albumname = $dbc->fieldValue($dbc->select('Albums', 'AlbumID = '.$songdata['AlbumID'], 'Name'));

        echo '<script type="text/javascript">
function toggleChangeArtistSelect() {
    var elemStatus = true;

    if (document.getElementById("changeartistchkbox").checked === true) {
        elemStatus = false;
    }

    document.getElementById("changeartistselect").disabled = elemStatus;
}

function toggleChangeAlbumSelect() {
    var elemStatus = true;

    if (document.getElementById("changealbumchkbox").checked === true) {
        elemStatus = false;
    }

    document.getElementById("changealbumselect").disabled = elemStatus;
}

function validate() {
    if (document.getElementById("songname").value == "") {
        alert("'.get_lang('missingsongname').'");
        document.frmeditsong.songname.focus();
        return false;
    }

    if (document.getElementById("lyrics").value == "") {
        alert("'.get_lang('missinglyrics').'");
        document.frmeditsong.lyrics.focus();
        return false;
    }

    return true;
}
</script>';

        // Start form
        $form->add_hidden(array(
            'ACTON' => 'savechangessong',
            'songid' => $songid,
            'artistid' => $songdata['ArtistID'],
            'albumid' => $songdata['AlbumID']
        ));
        $form->onsubmit('return validate();');
        $form->start_form(kgGetScriptName(),'post','frmeditsong');

        echo '<div class="page_title_div">'.get_lang('editsong').'</div>';
    }
    // END if ($addsong)

    // This javascript code is required no matter what $addsong is set to
?>
    <script type="text/javascript">
        function showGroup(type) {

            if (type == 1) {
                // Group one (details)
                document.getElementById("groupone").className = "";
                document.getElementById("grouptwo").className = "hidden";
                document.getElementById("editdetailsbutton").className = "hidden";
                document.getElementById("editlyricsbutton").className = "";

            } else if (type == 2) {
                // Group two (lyrics)
                document.getElementById("groupone").className = "hidden";
                document.getElementById("grouptwo").className = "";
                document.getElementById("editdetailsbutton").className = "";
                document.getElementById("editlyricsbutton").className = "hidden";

            }
        }
    </script>
<?php

    echo '<div class="group_one_div" id="groupone">'."\n";

    echo '<div class="page_title_div">'.get_lang('editsonginfo').'</div>';

    // Song Name
    echo "\n".'<div class="fieldpair_div clearfix" style="margin-bottom:10px;">';
    echo '<span class="fieldname_span">'.get_lang('songname').':</span>';
    echo '<span class="fieldvalue_span">';
    if ($addsong) {
        $form->add_text('songname','',40);
    } else {
        $form->add_text('songname',$songdata['Name'],40);
    }
    echo '</span></div>';

    // Artist Name
    echo "\n".'<div class="fieldpair_div clearfix" style="margin-bottom:10px;">';
    echo '<span class="fieldname_span">'.get_lang('artistname').'</span>';
    echo '<span class="fieldvalue_span">';
    if ($addsong) {
        if ($addvia == 'mainpage') {
            // Artist is unknown. Display list of available artist names.
            $form->add_select_db_autoecho('artistid','SELECT ArtistID,Name from Artists ORDER BY Name','ArtistID',0,'Name', $dbc);
            echo '</span></div>';

            // New Artist checkbox
            echo "\n".'<div class="fieldpair_div clearfix" style="margin-bottom:10px;">';
            echo '<span class="fieldname_span">';
            $form->add_checkbox('newartistchkbox',1,get_lang('newartistname'),false,'toggleNewArtistTxtBox();');
            echo '</span><span class="fieldvalue_span">';
            $form->add_text('newartisttxtbox','<-- '.get_lang('clicktoenable'),40);
            echo '</span>';

        } else {
            echo $artistdata['Name'].'</span>';
        }
    } else {
        echo $artistname.'</span>';
    }
    echo '</div>';

    // Album Name
    echo "\n".'<div class="fieldpair_div clearfix" style="margin-bottom:10px;">';
    echo '<span class="fieldname_span">'.get_lang('albumname').'</span>';
    echo '<span class="fieldvalue_span">';
    if ($addsong) {
        if ($addvia == 'mainpage') {
            // Artist is unknown. Display list of all albums in the database.
            $form->add_select_db_autoecho('albumid','SELECT AlbumID, Name from Albums ORDER BY Name','AlbumID',$albumid,'Name',$dbc);
        } elseif ($addvia == 'artistpage') {
            // Display list of albums for the selected artist
            if ($numalbums > 0) {
                // Display select list for existing albumsz
                $form->add_select_db_autoecho('albumid','SELECT AlbumID, Name from Albums WHERE ArtistID = '.$artistid.' ORDER BY Name','AlbumID',$albumid,'Name',$dbc);
            } else {
                // No albums for this artist found
                $form->add_text('albumname', '', 40);
            }
        } else {
            echo $albumdata['Name'];
        }
    } else {
        echo $albumname;
    }
    echo '</span></div>';

    if ($addsong) {
        if ($addvia == 'mainpage' || $addvia == 'artistpage') {
            // New album checkbox
            echo "\n".'<div class="fieldpair_div clearfix" style="margin-bottom:10px;">';
            echo '<span class="fieldname_span">';
            $form->disablefield();
            $form->add_checkbox('newalbumchkbox',1,get_lang('NewAlbumName'),false,'toggleNewAlbumTxtBox();');
            echo '</span><span class="fieldvalue_span">';
            $form->add_text('newalbumtxtbox','<-- '.get_lang('clicktoenable'),40);
            echo '</span></div>';
        }
    } else {
        echo '<div class="clearfix" style="float:left;margin-bottom:10px;">';
        $form->add_checkbox('changealbumchkbox',1,get_lang('changealbumname'),false,'toggleChangeAlbumSelect();');
        $form->disablefield();
        $form->add_select_db_autoecho('changealbumselect','SELECT AlbumID, Name from Albums WHERE ArtistID = '.$songdata['ArtistID'].' ORDER BY Name','AlbumID',$songdata['AlbumID'],'Name',$dbc);
        echo '</div>';
    }

    // Track Number
    echo "\n".'<div class="fieldpair_div clearfix" style="margin-bottom:10px;">';
    echo '<span class="fieldname_span">'.get_lang('tracknumber').'</span>';
    echo '<span class="fieldvalue_span">';
    if ($addsong) {
        $form->add_select_generic('tracknumber',range(0,20),0,'v','','',false);
    } else {
        $form->add_select_generic('tracknumber',range(0,20),$songdata['TrackNumber'],'v','','',false);
    }
    echo '</span></div>';

    // Track Time
    echo "\n".'<div class="fieldpair_div clearfix" style="margin-bottom:10px;">';
    echo '<span class="fieldname_span">'.get_lang('runtime').'</span>';
    echo '<span class="fieldvalue_span">';
    if ($addsong) {
        $form->add_select_generic('trackminutes',range(0,60),0,'v','','',false);
        echo ':';
        $form->add_select_generic('trackseconds',range(0,60),0,'v','','',false);
    } else {
        $form->add_select_generic('trackminutes',range(0,60),$songdata['TrackMinutes'],'v','','',false);
        echo ':';
        $form->add_select_generic('trackseconds',range(0,60),$songdata['TrackSeconds'],'v','','',false);
    }
    echo '<div style="float:right;margin-left:20px;" class="tinytext">'.get_lang('tracktimenote').'</div>';
    echo '</span></div>';

    // Genre
    echo "\n".'<div class="fieldpair_div clearfix" style="margin-bottom:10px;">';
    echo '<span class="fieldname_span">'.get_lang('genre').'</span>';
    echo '<span class="fieldvalue_span">';
    if ($addsong) {
        addSelectGenre();
    } else {
        addSelectGenre($songdata['GenreID']);
    }
    echo '</span></div>';

    // Volume
    echo "\n".'<div class="fieldpair_div clearfix" style="margin-bottom:10px;">';
    echo '<span class="fieldname_span">'.get_lang('volume').'</span>';
    echo '<span class="fieldvalue_span">';
    echo '<div style="float:left;">';
    if ($addsong) {
        $form->add_select_generic('volume',range(-1,100),-1,'v','','',false);
    } else {
        $form->add_select_generic('volume',range(-1,100),$songdata['volume'],'v','','',false);
    }
    echo '</div><div class="tinytext" style="float:left;margin-left:20px;">'.get_lang('setmpdvolume').'</div>';
    echo '</span></div>';

    echo "\n</div><!-- END group_one_div -->\n";

    echo '<div class="group_two_div" id="grouptwo">';

    // Song lyrics
    echo '<div class="page_title_div">'.get_lang('editlyrics').'</div><div style="text-align:center;">';
    if ($addsong) {
        $form->add_textarea('lyrics','',60,20);
    } else {
        $form->add_textarea('lyrics',$songdata['Lyrics'],60,20);
    }
    echo '</div>';

    echo "\n</div><!-- END group_two_div -->\n";

    echo '<div class="showgroup_button_div"><div>';
    $form->add_button_generic('editdetailsbutton',get_lang('editdetails'),'showGroup(1);');
    echo '</div><div>';
    $form->add_button_generic('editlyricsbutton',get_lang('editlyrics'),'showGroup(2);');
    echo "</div>\n</div><!-- END showgroup_button_div -->\n";

    echo '<div class="button_group_div">';

    // Save button
    echo '<div>';
    $form->add_button_submit(get_lang('save'));
    $form->end_form();
    echo '</div><div>';

    // Cancel button
    if ($addsong) {
        if ($addvia == 'albumpage') {
            // Go to album details
            $form->add_hidden(array(
                'ACTON' => 'viewalbum',
                'albumid' => $albumid
            ));
        } elseif ($addvia == 'artistpage') {
            // Go to artist details
            $form->add_hidden(array(
                'ACTON' => 'viewartist',
                'artistid' => $artistid
            ));
        } else {
            // Return to main page
            $form->add_hidden(array(
                'ACTON' => ''
            ));
        }
    } else {
        $form->add_hidden(array('ACTON'=>'viewsong','songid'=>$songid));
    }
    $form->buttononlyform(kgGetScriptName(),'post','frmcancel',get_lang('cancel'));
    echo "</div>\n</div><!-- END button_group_div -->";

echo '
<script type="text/javascript">
    $(document).ready(function(){showGroup(1);});
</script>';

/*
    To be added in later

    // Album Art
    $table->set_rowspan(8);
    if ($addvia == 'albumpage') {
        $table->new_cell('centertext');
        // Display album art if available
        if ($albumdata['CoverArtFront'] != '') {
            $imginfo = kgGetImageLink($albumdata['CoverArtFront']);
            if (array_key_exists('ImgTag', $imginfo) === true) {
                // Cover art exists
                echo $imginfo['ImgTagThumb'];
            } else {
                // Cover art not found
                echo $albumdata['Name'].'<br /><br />('.get_lang('NoCoverArt').')';
            }
        } else {
            // No cover art, show album name
            echo $albumdata['Name'];
        }
    } else {
        $table->blank_cell();
    }
*/

}
// END function AddSong()
/////

function AssignAlbum() {
    /**
     * Assigns a song to an album.
     *
     * Only called from function VeiwArtist().
     *
     * Added: 2017-06-25
     * Modified: 2017-06-25
     *
     * @param None
     *
     * @return Nothing
    **/

    global $dbc,$valid;

    // Get song ID
    $songid = $valid->get_value_numeric('songid',0);

    // Get album ID
    $albumid = $valid->get_value_numeric('albumid',0);

    if (($songid > 0) && ($albumid > 0)) {
        // Set album id for song

        if (!$dbc->update('Lyrics', 'SongID = '.$songid, array('AlbumID' => $albumid))) {
            // Update failed
            $valid->addError(get_lang('AlbumIDNotSet').'<br />'.get_lang('Reason').': '.$dbc->errorString());
        }

    } // else { do nothing }

}
// END function AssignAlbum()
/////

function DeleteAlbum() {
    /**
     * Delete and album
     *
     * Possibly delete all songs associated with album.
     * Album art does not get deleted
     *
     * Added: 2017-06-25
     * Modified: 2017-06-25
     *
     * @param None
     *
     * @return Nothing
    **/

    global $dbc,$valid, $KG_SECURITY;

    // Get album ID
    $albumid = $valid->get_value_numeric('albumid', 0);

    // Do they have permission to delete the album?
    if ($KG_SECURITY->haspermission('delete') === false) {
        $form = new HtmlForm();
        $form->add_hidden(array(
            'ACTON' => 'viewalbum',
            'albumid' => $valid->get_value_numeric('albumid', -1)
        ));
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('goback'));
        echo '</center>';
        return false;
    }

    $alsodeletesongs = $valid->get_value_numeric('alsodeletesongs', 0);

    if ($albumid > 0) {
        // Album ID appears to be valid

        // Get album name to use in error message(s)
        $albumname = $dbc->fieldValue($dbc->select('Albums', 'AlbumID = '.$albumid, 'Name'));

        // Set artist ID for use by next function
        $artistid = $dbc->fieldValue($dbc->select('Albums', 'AlbumID = '.$albumid, 'ArtistID'));
        $valid->setValue(array('artistid' => $artistid));

        // Assume album deletion failed
        $albumdeleted = false;

        // Delete album info
        if (!$dbc->delete('Albums','AlbumID = '.$albumid)) {
            // Failed
            $valid->addError(get_lang('UnableToDeleteAlbumInfo').' '.$albumname.'<br />'.$dbc->errorString());
        }

        if (($alsodeletesongs == 1) && (!$valid->hasErrors())) {
            /**
             * Delete songs related to album
             *  -If the album info was successfully deleted and some/all of the
             *    songs cannot be deleted, this will create orphaned songs. This
             *    really isn't a problem since the function ViewArtist() will
             *   list songs for that artist that are not assigned to an album.
             *  -If the album info was not deleted, the !$valid->hasErrors() check
             *   will prevent any of the associated song lyrics from being deleted
            **/
            if (!$dbc->delete('Lyrics', 'AlbumID = '.$albumid)) {
                // Failed
                $valid->addError(get_lang('UnableToDeleteAllSongs').' '.$albumname.'<br />'.$dbc->errorString());
            }
        }

        // Count number of songs still in database that have an album ID of $albumid
        $cnt = $dbc->numRows($dbc->select('Lyrics', 'AlbumID = '.$albumid, 'SongID'));

        if ($cnt > 0) {
            // Orphaned songs found. Set album ID to 0

            if (!$dbc->update('Lyrics', array('AlbumID' => $albumid, 'ArtistID' => $artistid), array('AlbumID' => 0))) {
                // Update failed

                /**
                 * What to do?
                 *  -Now I have orphaned songs with no easy way to identify them.
                 *  -To identify these songs I'll need a function that gets a list of
                 *   album IDs from the Lyrics table and a list of album IDs from the
                 *   albums table. It will then need to compare these lists to identify
                 *   invalid album IDs.
                **/
            }

        }
        // END if ($cnt > 0)
    }
    // END if ($albumid > 0)

}
// END function DeleteAlbum()
/////

function DeleteAlbumArt() {
    /**
     * Removes the album art from the servers hard drive
     *
     * TODO: Add deletion of thumbnails
     *       Add deletion of file name from Albums.CoverArt[Front||Back]
     *
     * Added: 2017-06-25
     * Modified: 2017-06-25
     *
     * @param None
     *
     * @return Nothing
    **/

    global $dbc, $valid, $IP, $kgSiteFiles;

    $sha = $valid->get_value('sha');
    $albumid = $valid->get_value_numeric('albumid');

    if ($sha != '') {

        // Get file name before the info is deleted
        $filename = $dbc->fieldValue($dbc->select('`Files`.`FileInfo`', 'FileSHA1 = "'.$sha.'"', 'FileName'));

        if ($dbc->delete('`Files`.`FileMultiFolder`', 'FileSHA1 = "'.$sha.'"')) {
            if ($dbc->delete('`Files`.`FileInfo`', 'FileSHA1 = "'.$sha.'"')) {
                // Delete the file from the servers harddrive

                // Original version
                $file = $IP.'/'.$kgSiteFiles.'/'.kgShaToDir($sha).'/'.$filename;
                unlink($file);

                // Thumbnail version
                $file = $IP.'/'.$kgSiteThumbnails.'/'.kgShaToDir($sha).'/'.$filename;
                unlink($file);

            } else {
                $valid->addError(get_lang('UnableToDeleteAlbumArt').'<br />'.get_lang('Reason').': '.$dbc->errorString());
            }
        } else {
            $valid->addError(get_lang('UnableToDeleteAlbumArt').'<br />'.get_lang('Reason').': '.$dbc->errorString());
        }
    }

    // Makes the albumID available for function EditAlbum()
    $valid->setValue(array('albumid' => $valid->get_value_numeric('albumid')));
}
// END function DeleteAlbumArt()
/////

function DeleteArtist() {
    /**
     * Deletes artist data including albums and songs from database
     *
     * Does not remove cover art from the servers hard drive.
     *
     * Added: 2017-06-25
     * Modified: 2017-07-01
     *
     * @param None
     *
     * @return Nothing
    **/

    global $dbc, $valid, $KG_SECURITY;

    $artistid = $valid->get_value_numeric('artistid');

    // Do they have permission to delete the artist?
    if ($KG_SECURITY->haspermission('delete') === false) {
        $form = new HtmlForm();
        $form->add_hidden(array(
            'ACTON' => 'viewartist',
            'artistid' => $valid->get_value_numeric('artistid', -1)
        ));
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('viewartist'));
        echo '</center>';
        return false;
    }

    if ($artistid > 0) {

        if ($valid->get_value_numeric('alsodeletecoverart') == 1) {
            // Remove all cover art for this artist from the servers hard drive

            $query = $dbc->select('Albums', 'ArtistID = '.$artisid, array('CoverArtFront', 'CoverArtBack'));
            if ($dbc->numRows($query) > 0) {

                while ($row = $dbc->fetchAssoc($query)) {

                    if (($row['CoverArtFront'] != '') && !is_null($row['CoverArtFront'])) {

                        // Get files SHA1 hash
                        $sha1 = $dbc->fieldValue($dbc->select('`Files`.`FileInfo`', 'FileName = "'.$row['CoverArtFront'].'"', 'FileSHA1'));

                        if ($dbc->delete('`Files`.`FileMultiFolder`', 'FileSHA1 = "'.$sha.'"')) {
                            if ($dbc->delete('`Files`.`FileInfo`', 'FileSHA1 = "'.$sha.'"')) {
                                // Delete the file from the servers harddrive
                                $file = $IP.'/'.$kgSiteFiles.'/'.kgShaToDir($sha).'/'.$row['CoverArtFront'];
                                unlink($file);
                            } else {
                                $valid->addError(get_lang('UnableToDeleteAlbumArt').'<br />'.get_lang('Reason').': '.$dbc->errorString());
                            }
                        } else {
                            $valid->addError(get_lang('UnableToDeleteAlbumArt').'<br />'.get_lang('Reason').': '.$dbc->errorString());
                        }
                    }
                    // END if (($row['CoverArtFront'] != '') && !is_null($row['CoverArtFront']))

                    if (($row['CoverArtBack'] != '') && !is_null($row['CoverArtBack'])) {

                        // Get files SHA1 hash
                        $sha1 = $dbc->fieldValue($dbc->select('`Files`.`FileInfo`', 'FileName = "'.$row['CoverArtBack'].'"', 'FileSHA1'));

                        if ($dbc->delete('`Files`.`FileMultiFolder`', 'FileSHA1 = "'.$sha.'"')) {
                            if ($dbc->delete('`Files`.`FileInfo`', 'FileSHA1 = "'.$sha.'"')) {
                                // Delete the file from the servers harddrive
                                $file = $IP.'/'.$kgSiteFiles.'/'.kgShaToDir($sha).'/'.$row['CoverArtBack'];
                                unlink($file);
                            } else {
                                $valid->addError(get_lang('UnableToDeleteAlbumArt').'<br />'.get_lang('Reason').': '.$dbc->errorString());
                            }
                        } else {
                            $valid->addError(get_lang('UnableToDeleteAlbumArt').'<br />'.get_lang('Reason').': '.$dbc->errorString());
                        }
                    }
                    // END if (($row['CoverArtBack'] != '') && !is_null($row['CoverArtBack']))

                }

            }
            // END if ($dbc->numRows($query) > 0)

        }

        // Delete all songs for artist
        if (!$dbc->delete('Lyrics', 'ArtistID = '.$artistid)) {
            // Not all songs deleted
            $valid->addError(get_lang('UnableToDeleteAllSongs').'<br />'.$dbc->errorString());
        }

        // Delete all albums for artist
        if (!$dbc->delete('Albums', 'ArtistID = '.$artistid)) {
            // Not all albums deleted
            $valid->addError(get_lang('UnableToDeleteAllAlbums').'<br />'.$dbc->errorString());
        }

        // Delete the artist info
        if (!$dbc->delete('Artists', 'ArtistID = '.$artistid)) {
            // Unable to delete artist info
            $valid->addError(get_lang('UnableToDeleteArtistInfo').'<br />'.$dbc->errorString());
        }

    }
    // END if ($artistid > 0)

    if (!$valid->hasErrors()) {
        // The next $dbc->select() needs to read from the master server while the slaves update their data
        $dbc->mysqlndSwitch('master');
    }

    return true;

}
// END function DeleteArtist()
/////

function DeleteGenre() {
    /**
     * Deletes the selected genre from the database
     *
     * Added 2017-06-25
     * Modified 2017-06-25
     *
     * @param None
     *
     * @return None
    **/

    global $dbc,$valid;

    $genreid = $valid->get_value_numeric('genreid', 0);

    if ($genreid > 0) {

        // Delete genre info
        if (!$dbc->delete('GenreIDs', 'GenreID = '.$genreid)) {
            // Delete failed
            $valid->addError(get_lang('UnableToDeleteGenre').'<br />'.$dbc->errorString());
        }

    } else {
        $valid->addError(get_lang('invalidgenre'));
    }
    // END if ($genreid > 0)
}
// END function DeleteGenre()
/////

function DeleteSong() {
    /**
     * Deletes all info for the selected song from the database
     *
     * Added: 2017-06-25
     * Modified: 2017-06-25
     *
     * @param None
     *
     * @return Nothing
    **/

    global $dbc,$valid, $KG_SECURITY;

    $songid = $valid->get_value_numeric('songid', 0);

    // Do they have permission to delete the song?
    if ($KG_SECURITY->haspermission('delete') === false) {
        $form = new HtmlForm();
        $form->add_hidden(array(
            'ACTON' => 'viewsong',
            'songid' => $valid->get_value_numeric('songid', -1)
        ));
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('goback'));
        echo '</center>';
        return false;
    }

    if ($songid > 0) {

        // Delete song info
        if (!$dbc->delete('Lyrics', 'SongID = '.$songid)) {
            // Song not deleted
            $valid->addError(get_lang('unabletodeletesong').'<br />'.$dbc->errorString());
        }

    } else {
        $valid->addError(get_lang('invalidsongid'));
    }
    // END if ($songid > 0)
}
// END function DeleteSong()
/////

function displayCoverArt($name, $albumid = '', $fb = 'front') {
    /**
     * Displays the cover art if found or a message if not found
     * Only used by editAlbum() and viewAlbum()
     *
     * Added: 2017-06-16
     * Modified: 2017-07-02
     *
     * @param Required string $name Name or SHA-1 hash of cover art file
     * @param Optional integer $albumid ID number of the album this coverart is for
     * @param Optional string $fb Value must be 'front' or 'back' if used
     *                            This tells the function which coverart it is displaying
     *
     * @return string Text to place above the cover art upload browser button
    **/

    // Make sure it is lowercase
    $fb = strtolower($fb);

    if ($name != '') {
        // File name or SHA-1 hash given

        // The text to be returned
        if ($fb == 'front') {
            // Front cover art
            $msg = get_lang('uploadfrontcoverart');
        } elseif ($fb == 'back') {
            // Back cover art
            $msg = get_lang('uploadbackcoverart');
        }

        $imginfo = kgGetImageLink($name);

        if (array_key_exists('ImgTag', $imginfo) === true) {
            // Cover art exists

            if ($albumid == '') {
                echo $imginfo['UrlTagThumb'];
            } else {
                echo kgCreateLink($imginfo['ImgTag'],array('ACTON' => 'viewalbum', 'albumid' => $albumid)).'<br />';
            }

            // Change the text
            $msg = get_lang('clickbrowsetochange');

        } elseif (array_key_exists('mm', $imginfo) === true) {
            // Multiple files with the same name were found

            echo get_lang('filenamemultimatch').'<br />'.$name.'<br />';

        } elseif (array_key_exists('missing', $imginfo) === true) {
            // Info found in database but file is not on server

            echo get_lang('filenotfoundonserver').'<br />'.$name.'<br />';

        } elseif (empty($imginfo)) {
            // No info in database

            echo get_lang('noinfoindbforfile').'<br />'.$name.'<br />';
        }
    } else {
        
        // The text to be returned
        if ($fb == 'front') {
            // Front cover art
            $msg = get_lang('uploadfrontcoverart');
        } elseif ($fb == 'back') {
            // Back cover art
            $msg = get_lang('uploadbackcoverart');
        }
    }

    return $msg;
}
// END function displayCoverArt()
/////

function DoUpload($file, $artistshortname) {
    /**
     * Process an uploaded album cover art image
     *
     * Added 2017-06-29
     * Modified 2017-06-29
     *
     * @param Required string $file Name of the '<input type="file"...' form field
     * @param Required string $artistshortname The shortened version of the artists name
     *                                         that this album belongs to
     *
     * @return string
    **/

    global $dbc, $valid, $kgSiteFiles, $kgSiteThumbnails, $userinfo, $IP;

    $upload = new FileUpload();

    if (is_array($_FILES[$file]) === false || $_FILES[$file]['error'] === UPLOAD_ERR_NO_FILE) {
        // Nothing to upload

        // Exit function
        return '';
    }

    // Upload the file
    list($result, $filename, $shainfo) = $upload->doUpload($_FILES[$file], '/CD Coverart/'.$artistshortname);

    if ($result === KG_UPLOAD_SUCCESS) {
        // File uploaded successfully

        // Create path to the image so Imagick can work on the uploaded file
        $imagepath = $IP.'/'.$kgSiteFiles.'/'.$shainfo['dirfull'].'/'.$filename;
        $imagethumbpath = $IP.'/'.$kgSiteThumbnails.'/'.$shainfo['dirfull'].'/'.$filename;

        // Creates the $kgSiteThumbnails.$shainfo['dirfull'] folders if needed
        kgMakeFolders($IP.'/'.$kgSiteThumbnails.'/'.$shainfo['dirfull']);

        // Use Image Magick to create a thumbnail image if needed
        try {

            // Load the imagick code
            $im = new Imagick();

            // Make sure it is an image file that imagick can handle
            $im->pingImage($imagepath);

            // Load image into memory
            $im->readImage($imagepath);

            // Get image width and height in pixels
            $width = $im->getImageWidth();
            $height = $im->getImageHeight();

            // Only resize the image if it is more than THUMBNAIL_HEIGHT_LARGE pixels wide
            if ($width > THUMBNAIL_HEIGHT_LARGE) {

                /*
                    -Resize image to {THUMBNAIL_HEIGHT_LARGE}px wide
                    -Using '0' for height maintains aspect ratio
                    -Using resizeImage() instead of thumbnailImage() to preserve EXIF data
                */
                $im->resizeImage(THUMBNAIL_HEIGHT_LARGE, 0, imagick::FILTER_CATROM, 1);

            }

            // Saves a copy to the thumbnail folder even if the image was not resized
            // This will overwrite an existing file with the same name
            $im->writeImage($imagethumbpath);

            // Free up memory and disk space used to process image
            $im->destroy();

        } catch(Exception $e) {
            // Display error message

            $valid->add_error($e->getMessage());
        }
        // END try..catch block

        return $filename;

    } elseif ($result === KG_UPLOAD_FILE_EXISTS) {

        // This is actually the file name
        return $filename;

    } else {
        // CONTINUE if ($result === KG_UPLOAD_SUCCESS)

        echo $filename.'<br />Status: '.$result.'<br />';
    }
    // END if ($result === true)

    return '';

}
// END function DoUpload()
/////

function EditAlbum() {
    /**
     * Edit/update info about an existing album
     *
     * Added: 2017-06-25
     * Modified: 2017-06-28
     *
     * @param None
     *
     * @return Nothing
    **/

    global $dbc, $valid, $KG_SECURITY, $kgSiteLightBoxJava, $kgSiteLightBoxCss, $IP;

    $form = new HtmlForm();

    // Get album ID
    $albumid = $valid->get_value_numeric('albumid', 0);

    // Are they allowed to edit album info?
    if ($KG_SECURITY->haspermission('edit') === false) {
        $form->add_hidden(array(
            'ACTON' => 'viewalbum',
            'albumid' => $albumid
        ));
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('goback'));
        echo '</center>';
        return false;
    }

    $table = new HtmlTable();
    $innertable = new HtmlTable();

    if ($albumid > 0) {

        // Get album info
        $albumdata = $dbc->fetchAssoc($dbc->select('Albums', 'AlbumID = '.$albumid));

        // Get artist info for the album
        $artistdata = $dbc->fetchAssoc($dbc->select('Artists', 'ArtistID = '.$albumdata['ArtistID'], array('Name', 'ShortName')));

        // Count number of songs for this album
        $songcnt = $dbc->numRows($dbc->select('Lyrics', 'AlbumID = '.$albumid, 'SongID'));

        // Get list of album art already uploaded to the server
        $filelist = kgListFolderContents($artistdata['ShortName'], true);

        // Check if the server has write permissions to the files directory
        $rv = posix_getpwuid(fileowner($IP.'/files'));
        if ($rv{'name'} == 'www-data') {
            $allowuploads = true;
        } else {
            $allowuploads = false;
        }

    // Javascript code
echo '
<script type="text/javascript">

    function clearRB(buttonGroup) {

        for (i=0; i < buttonGroup.length; i++) {
            buttonGroup[i].checked = false;
        }
    }

    function uncheckunknownartist() {
        document.getElementById("unknownartist").checked = false;
    }

    function showGroup(type) {

        if (type == 1) {
            // Group one (details)
            document.getElementById("groupone").className = "";
            document.getElementById("grouptwo").className = "hidden";
            document.getElementById("editdetailsbutton").className = "hidden";
            document.getElementById("changealbumartbutton").className = "";

        } else if (type == 2) {
            // Group two (albumart)
            document.getElementById("groupone").className = "hidden";
            document.getElementById("grouptwo").className = "";
            document.getElementById("editdetailsbutton").className = "";
            document.getElementById("changealbumartbutton").className = "hidden";

        }
    }

    function confirmDelete(name, link) {
        var msg = \''.get_lang('confirmcoverartdeletion').'\n\n + name\';

        var answer = confirm(msg);

        if (answer) {
            location.href=link;
        } else {
            return false;
        }
    }
</script>
<script src="'.$kgSiteLightBoxJava.'"></script>'."\n".'<link rel="stylesheet" href="'.$kgSiteLightBoxCss.'">';

        $form->add_hidden(array(
            'ACTON' => 'savechangesalbum',
            'albumid' => $albumid,
            'albumshortname' => $albumdata['ShortName']
        ));
        $form->start_form_upload(kgGetScriptName(),'post','frmeditalbum');

        echo '<div class="page_title_div">'.get_lang('editalbuminfo').'</div>';

        echo '<div class="group_one_div" id="groupone">';

        $table->new_table('fullwidth');

        $table->new_row();

        // Artist Name
        $table->set_width(160,'px');
        $table->new_cell();
        echo get_lang('artistname');
        $table->new_cell();
        if ($artistdata['Name'] != '') {
            echo $artistdata['Name'].' &nbsp; &nbsp; &nbsp; ';
            $form->add_checkbox('unknownartist',1,get_lang('checkifunknown'));
        } else {
            $form->add_checkbox('unknownartist',1,get_lang('unknown'),true);
            echo ' - '.get_lang('makeselectionbelow')."<br />\n";
            $form->onsubmit('uncheckunknownartist();');
            $form->add_select_db_autoecho('artistid','SELECT ArtistID, Name FROM Artists ORDER BY Name','ArtistID','','Name',false);
        }

        // Display current cover art if available
        $table->set_rowspan(8);
        $table->new_cell();

        // Front coverart
        $msg = displayCoverArt($albumdata['CoverArtFront'], $albumid);

        // Album Name
        $table->new_row();
        $table->new_cell();
        echo get_lang('albumname');
        $table->new_cell();
        $form->add_text('albumname',$albumdata['Name'],40);

        // Release Date
        $table->new_row();
        $table->new_cell();
        echo get_lang('releasedate');
        $table->new_cell();
// Use jQuery's Datepicker
echo '<script>
    $(function() {
        $( "#releasedate" ).datepicker({dateFormat: "yy-mm-dd",  changeMonth: true, changeYear: true, yearRange: "c-70:c"';

        if ($albumdata['ReleaseDate'] != '0000-00-00') {
            // Set date for jQuery's datepicker to use
            list($year,$month,$day) = explode('-',$albumdata['ReleaseDate']);
            echo '}).datepicker(\'setDate\', \''.$year.'-'.$month.'-'.$day.'\');';
        } else {
            // Date unknown
            echo '})';
        }
echo "\n".'    });
</script>';
        $form->add_text('releasedate',get_lang('clicktoselectdate'));

        // New album checkbox
        $table->new_row();
        $table->new_cell();
        echo get_lang('wikipediaarticle').':';
        $table->new_cell();
        $data = (array_key_exists('Wikipedia', $albumdata) === true ? $albumdata['Wikipedia'] : '');
        $form->add_text('wikipediaarticle',$data,40);

        $table->blank_row();
        $table->blank_row();
        $table->blank_row();
        $table->blank_row();
        $table->blank_row(3);

        // Save and cancel buttons
        $table->new_row();
        $table->set_colspan(3);
        $table->new_cell('centertext');

            $innertable->set_width(100,'%');
            $innertable->new_table();
            $innertable->new_row();

            // Save button
            $innertable->new_cell();
            $form->add_button_submit(get_lang('savechanges'));

            // Cancel button
            $innertable->new_cell();
            $form->add_button_generic('ACTON',get_lang('cancel'),'location.href="'.kgCreateLink('',array('ACTON' => 'viewalbum', 'albumid' => $albumid, 'NO_TAG' => 'NO_TAG')).'";');

            // Change album art button
            $innertable->new_cell();
            $form->add_button_generic('changealbumartbutton',get_lang('changealbumart'),'showGroup(2);');
            $innertable->end_table();

        $table->end_table();

        echo "\n</div><!-- END group_one_div -->\n";

        echo '<div class="group_two_div" id="grouptwo">';

        $table->new_table('fullwidth');

        $table->new_row();

        if ($allowuploads) {
            // Allow upload

            // Front cover art
            $table->new_cell();
            echo $msg.'<br />';
            $form->add_file('CoverArtFront');

            $table->blank_cell();

            // Back cover art
            $table->new_cell();
            $msg = displayCoverArt($albumdata['CoverArtBack'], $albumid, 'back');
            echo $msg.'<br />';
            $form->add_file('CoverArtBack');
        } else {
            // Disable upload
            $table->set_colspan(3);
            $table->new_cell();
            echo get_lang('servernouploadpermission');
        }

        if ($filelist['FileCount'] > 0) {
            // Found some files

            $table->blank_row(3);
            $table->blank_row(3);

            // Display cover art already on the server
            $table->new_row();
            $table->set_colspan(3);
            $table->new_cell('centertext');
            echo get_lang('orselectexistingart');

            $table->new_row();
            $table->set_colspan(3);
            $table->new_cell();

            $maxcols = 8;
            $cnt = 0;

            foreach ($filelist['Files'] as $shahash => $filename) {

                $cnt++;

                // One image per table cell
                $imginfo = kgGetImageLink($shahash);
                echo '<div class="coverart_editalbum_div">';
                echo '<a href="'.$imginfo['URL'].'" data-lightbox="cover-art-set" data-title="'.$filename.'"><img src="'.$imginfo['URL'].'" width="100px" height="100px"></a><br /><span class="tinytext">'.$filename.'</span><br />';
                $form->add_radio('frontcoverradio',$filename,get_lang('frontcover'));
                echo '<br />';
                $form->add_radio('backcoverradio',$filename,get_lang('backcover'));
                echo '<br /><br />';
                $form->add_button_generic('submit',get_lang('delete'),'confirmDelete("'.$filename.'", "'.kgCreateLink('',array('ACTON' => 'deletealbumart', 'sha' => $shahash, 'albumid' => $albumid, 'dowhat' => 'editalbum', 'NO_TAG' => 'NO_TAG')).'");');
                echo '</div>';
            }

            $table->new_row();
            $table->set_colspan(3);
            $table->new_cell();
            echo '<div style="float:left;">';
            $form->add_button_generic('submit',get_lang('ClearFrontCover'),'clearRB(document.frmeditalbum.frontcoverradio)');
            echo '</div><div style="float:left;margin-left:20px">';
            $form->add_button_generic('submit',get_lang('ClearBackCover'),'clearRB(document.frmeditalbum.backcoverradio)');
            echo '</div>';

        }
        // END if ($filelist['FileCount'] > 0))

        $table->blank_row(3);

        // Save, cancel, and edit details buttons
        $table->new_row();
        $table->set_colspan(3);
        $table->new_cell('centertext');

            $innertable->set_width(100,'%');
            $innertable->new_table();
            $innertable->new_row();

            // Save button
            $innertable->new_cell();
            $form->add_button_submit(get_lang('savechanges'));

            // Cancel button
            $innertable->new_cell();
            $form->add_button_generic('ACTON',get_lang('cancel'),'location.href="'.kgCreateLink('',array('ACTON' => 'viewalbum', 'albumid' => $albumid, 'NO_TAG' => 'NO_TAG')).'";');

            // Edit details button
            $innertable->new_cell();
            $form->add_button_generic('editdetailsbutton',get_lang('editalbuminfo'),'showGroup(1);');
            $innertable->end_table();

        $table->end_table();

        echo "\n</div><!-- END group_two_div -->\n";

        $form->end_form();

echo '
<script type="text/javascript">
    $(document).ready(function(){showGroup(1);});
</script>';

    } else {
        echo get_lang('InvalidAlbumID');
    }

}
// END function EditAlbum()
/////

function EditArtist() {
    /**
     * Edit/update info about an existing artist
     *
     * Added: 2017-06-25
     * Modified: 2017-06-25
     *
     * @param None
     *
     * @return Nothing
    **/

    global $dbc, $valid, $KG_SECURITY;

    $form = new HtmlForm();

    // Get artist ID
    $artistid = $valid->get_value_numeric('artistid',0);

    // Are they allowed to edit artist info?
    if ($KG_SECURITY->haspermission('edit') === false) {
        $form->add_hidden(array(
            'ACTON' => 'viewartist',
            'artistid' => $artistid
        ));
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('goback'));
        echo '</center>';
        return false;
    }

    if ($artistid < 1) {

        // Invalid artist ID, exit function
        echo '<center>'.get_lang('UnableToEditArtistInfo').'. '.get_lang('invalidartistid').'</center><br><br>';
        $form->add_hidden(array(
            'ACTON' => 'viewartist',
            'artistid' => $artistid
        ));
        $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('goback'));
        return;

    }

    $table = new HtmlTable();
    $buttontable = new HtmlTable();

    echo '<script language="Javascript">
        function confirmDelete() {
            var msg = \''.get_lang('WillAlsoDeleteAlbumsSongs').'\n\n\';

            if (document.getElementById("alsodeletecoverart").checked === true) {
                msg = msg + \''.get_lang('CoverArtWillBeDeleted').'\';
            } else {
                msg = msg + \''.get_lang('CoverArtWillNotBeDeleted').'\';
            }

            msg = msg + \'\n\n'.get_lang('ConfirmDeleteMsg').'\';

            var answer = confirm(msg);

            if (answer) {
                return true;
            } else {
                return false;
            }
        }
    </script>';

    // Artist data
    $artistdata = $dbc->fetchAssoc($dbc->select('Artists', 'ArtistID = '.$artistid));

    // Start of form
    $form->add_hidden(array(
        'ACTON' => 'savechangesartist',
        'artistid' => $artistid,
        'artistname' => $artistdata['Name']
    ));
    $form->start_form(kgGetScriptName(),'post','frmeditartist');

    echo '<div class="page_title_div">'.get_lang('editartistinfo').'</div>';

    $table->new_table('centered');

    $table->new_row();
    $table->new_cell();
    echo get_lang('artistname').': ';
    $table->new_cell();
    echo $artistdata['Name'];

    $table->new_row();
    $table->new_cell();
    echo get_lang('websitelink').': ';
    $table->new_cell();
    $form->add_text('website',$artistdata['Website'],50);

    $table->new_row();
    $table->new_cell();
    echo get_lang('wikipediaarticle').': ';
    $table->new_cell();
    $form->add_text('wikipediaarticle',$artistdata['Wikipedia'],50);

    $table->new_row();
    $table->new_cell('toptext');
    echo get_lang('artistbio').': ';
    $table->new_cell();
    $form->add_textarea('artistbio',$artistdata['Bio'],48,8);

    $table->blank_row();

    $table->new_row();
    $table->new_cell('right');
    $form->add_button_submit(get_lang('savechanges'));
    $form->end_form();
    $table->new_cell();

        $buttontable->set_width(100,'%');
        $buttontable->new_table();
        $buttontable->new_row();
        $buttontable->new_cell('centertext');
        $form->add_hidden(array('ACTON'=>'viewartist','artistid'=>$artistid));
        $form->buttononlyform(kgGetScriptName(),'post','frmcancel',get_lang('cancel'));
        $buttontable->new_cell('centertext');
        $form->add_hidden(array(
            'ACTON' => 'deleteartist',
            'artistid' => $artistid
        ));
        $form->onsubmit('return confirmDelete();');
        $form->start_form(kgGetScriptName(),'post','frmdeleteartist');
        $form->add_button_submit(get_lang('deleteartistinfo'));
        echo '<br />';
        $form->add_checkbox('alsodeletecoverart',1,get_lang('alsodeletecoverart'));
        $form->end_form();
        $buttontable->end_table();

    $table->end_table();

}
// END function EditArtist()
/////

function EditGenres() {
    /**
     * Edit the list of genres to choose from when adding or updating a song.
     *
     * Added: 2017-06-24
     * Modified 2017-06-24
     *
     * @param None
     *
     * @return Nothing
    **/

    global $dbc, $valid, $KG_SECURITY;

    // Are they allowed to edit the info?
    if ($KG_SECURITY->haspermission('edit') === false) {
        // Send them back to the artist list
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('goback'));
        echo '</center>';
        return false;
    }

    // Display errors if any
    $valid->displayErrors();

    $form = new HtmlForm();
    $table = new HtmlTable();
    $innertable = new HtmlTable();

    $table->set_width(80,'%');
    $table->new_table('centered');
    $table->new_row();

    // Refresh the list
    $table->new_cell();
    $form->add_hidden(array('ACTON' => 'editgenres'));
    $form->buttononlyform(kgGetScriptName(),'post','frmrefreshgenres',get_lang('refreshgenrelist'));

    // Add new genre form
    $table->new_cell('righttext');
    echo '<script type="text/javascript">
            function validateNewGenre() {
            
            if (document.frmsavegenre.newgenre.value != "") {
                return true;
            } else {
                alert("'.get_lang('missinggenrename').'");
            }

            return false;
        }
    </script>';
    $form->add_hidden(array('ACTON' => 'savegenre'));
    $form->onsubmit('return validateNewGenre();');
    $form->simple_form_text('newgenre','',15,'','POST', 'frmsavegenre',get_lang('newgenre'),get_lang('add'));

    // Spacer row
    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell();
    echo '<hr>';

    $table->end_table();

    // Retrieve the list
    $query = $dbc->select('GenreIDs', '', '', array('ORDER BY' => 'Name ASC'));
    $numgenres = $dbc->numRows($query);

    if ($numgenres > 0) {

        // Number of genres to display per column
        $percolumn = ceil($numgenres / 4);

        $count = 0;

        $table->set_width(100,'%');
        $table->new_table();
        $table->new_row();
        $table->new_cell('toptext');

        // Display the list
        while ($row = $dbc->fetchAssoc($query)) {
            $count++;

            // Number of songs assigned to this genre
            $songcnt = $dbc->select('Lyrics', 'GenreID = '.$row['GenreID']);

            echo "\n<!-- start -->\n".'<div style="background:#a1b2c3;margin:1px 0px 1px 0px;padding:1px 0px 1px 0px;"><div style="float:left;margin:1px;padding:1px;">';
            echo $row['Name'].' <span title="Number of songs assigned to this genre">('.$dbc->numRows($songcnt).')</span></div><div style="float:right;margin:1px;padding:1px;">';
            echo '<div style="float:left;">';
            $form->add_hidden(array('ACTON'=>'renamegenre','genreid'=>$row['GenreID']));
            $form->buttononlyform(kgGetScriptName(),'post','frmcancel',get_lang('Rename'));
            echo '</div>&nbsp; <div style="float:right;">';
            $form->add_hidden(array('ACTON'=>'deletegenre','genreid'=>$row['GenreID']));
            $form->buttononlyform(kgGetScriptName(),'post','frmcancel',get_lang('Delete'));
            echo '</div></div></div><br />'."\n<!-- end -->";

            if ($count >= $percolumn) {
                $table->set_width(12,'px');
                $table->blank_cell();
                $table->new_cell('toptext');
                $count = 0;
            }

        }

        $table->end_table();
    } else {
        echo get_lang('NoGenresFound');
    }
}
// END function EditGenres()
/////

function genreIdToName($genreid) {
    /**
     * Returns the name of the genre given an ID number.
     *
     * Added: 2017-06-20
     * Modified: 2017-06-20
     *
     * @param Required integer $genreid The genre ID number
     *              ID numbers start at 100 to allow for group of
     *              genre sub-types
     *
     * @return string Name of genre
    **/

    if (!is_numeric($genreid) or $genreid < 100) {
        return '';
    }

    global $dbc;

    return $dbc->fieldValue($dbc->select('GenreIDs', 'GenreID = '.$genreid, 'Name'));
}
// END function genreIdToName()
/////

function getArtistData() {
    /**
     * This function decreases the number of changes I need to make
     * to this file every time I change the database.
     *
     * Added 2017-06-29
     * Modified 2017-06-29
     *
     * @param none
     *
     * @return array
    **/

    global $valid, $userinfo;

    $artistdata = array(
        'Name' => $valid->get_value('artistname'),
        'ShortName' => kgGenShortName($valid->get_value('artistname')),
        'Website' => $valid->get_value('website'),
        'Wikipedia' => $valid->get_value('wikipediaarticle'),
        'Bio' => kgWrapString($valid->get_value('artistbio')),
        'AddedBy' => $userinfo['UserID']
    );

    return $artistdata;
}
// END function getArtistData()
/////

function getSongData() {
    /**
     * This function decreases the number of changes I need to make
     * to this file every time I change the database.
     *
     * Added: 2017-06-24
     * Modified 2017-06-24
     *
     * @param none
     *
     * @return array
    **/

    global $valid;

    $songdata = array(
        'Name' => $valid->get_value('songname'),
        'ShortName' => kgGenShortName($valid->get_value('songname')),
        'ArtistID' => $valid->get_value_numeric('artistid', 0),
        'AlbumID' => $valid->get_value_numeric('albumid', 0),
        'TrackNumber' => $valid->get_value_numeric('tracknumber', 0),
        'TrackMinutes' => $valid->get_value_numeric('trackminutes', 0),
        'TrackSeconds' => $valid->get_value_numeric('trackseconds', 0),
        'GenreID' => $valid->get_value_numeric('genreid', 0),
        'volume' => $valid->get_value_numeric('volume', -1),
        'Lyrics' => $valid->get_value('lyrics')
    );

    return $songdata;
}
// END function getSongData()
/////

function listArtists() {
    /**
     * The modules main page.
     *
     * Lists all artists in the database
     *
     * Added: 2017-06-24
     * Modified 2017-06-24
     *
     * @param none
     *
     * @return array
    **/

    global $dbc, $valid, $KG_SECURITY;

    // Display errors if any
    $valid->displayErrors();

    $form = new HtmlForm();
    $table = new HtmlTable();

    // -Selects all artists in the database and sorts
    //  them in alphabetical order
    $query = $dbc->select('Artists', '', array('ArtistID', 'Name'), array('ORDER BY' => 'Name'));

    // -Sort artist names into an array
    // -Group artists by first letter of name
    while ($row = $dbc->fetchAssoc($query)) {

        // Get the first letter of the artist name
        $fl = substr($row['Name'],0,1);
        $fl = strtoupper($fl);

        if (ctype_alpha($fl)) {
            // $fl matches [A-Za-z]
            $artistlist[$fl][$row['ArtistID']] = $row['Name'];
        } else {
            // $fl matches everything else
            $artistlist['Symbols'][$row['ArtistID']] = $row['Name'];
        }
    }

    // Let the mysqlnd library decide which server to use
    $dbc->mysqlndSwitch();

    // -Number of lines per column
    // -Includes blank lines
    $maxlines = 20;

    echo '<div class="tabslist_height_control_div"><div id="tabslist">';

    // -First run through the $artistlist array
    // -Creates the list that becomes the tabs
    echo '<ul>'."\n";
    foreach ($artistlist as $key => $value) {
        echo '<li>'.kgCreateLink($key,'#tabs-'.$key).'</li>'."\n";
    }
    echo '</ul>';

    // -Second run through the $artistlist array
    // -Creates the contents of the tabs
    foreach ($artistlist as $key => $value) {
        $linecnt = 0;

        echo '<div id="tabs-'.$key.'">';

        $table->new_table();
        $table->new_row();
        $table->new_cell('name_table_cell');

        foreach ($value as $id => $name) {

            echo '<div class="artist_link_div" onclick="window.location=\''.kgCreateLink($name, array('ACTON' => 'viewartist', 'artistid' => $id, 'NO_TAG' => 'NO_TAG')).'\'">'.$name.'</div>';
            $linecnt = $linecnt + 1;

            if ($linecnt >= $maxlines) {
                $table->new_cell('name_table_cell');

                $linecnt = 0;
            }
        }

        $table->end_table();
        echo "</div>\n";

    }
    // END foreach ($artistlist as $key => $value)

    echo "</div><!-- END tabslist -->\n</div><!-- END tabslist_height_control_div -->\n";

    echo '
<!-- jquery -->
<script>
    // This turns the divs and UL\'s into jQuery tabs
    $(function() {
        $( "#tabslist" ).tabs({
            heightStyle: "fill"
        });
    });

    // Fix the anchor of the tabs so they point to the correct div
    tabLinks = $(\'#tabslist li a\');
    numOfTabs = tabLinks.length;
    for (index = 0; index < numOfTabs; index ++) {
        oldAnchor = $(tabLinks[index]).attr(\'href\');
        hashPos = oldAnchor.indexOf(\'#\');
        newAnchor = oldAnchor.substr(hashPos);
        $(tabLinks[index]).attr(\'href\', newAnchor);
    }
</script>';

    echo "\n".'<div class="list_artists_button_row_div">'."\n";

    // Hides all messages that the following $KG_SECURITY checks might generate
    $KG_SECURITY->hideMsgs();

    // Add Artist button
    if ($KG_SECURITY->hasPermission('add') === true) {
        $form->add_hidden(array('ACTON' => 'addartist'));
        $form->buttononlyform(kgGetScriptName(),'post','frmaddartist',get_lang('addartist'));
    }

    // Add Album button
    if ($KG_SECURITY->hasPermission('add') === true) {
        $form->add_hidden(array('ACTON' => 'addalbum'));
        $form->buttononlyform(kgGetScriptName(),'post','frmaddalbum',get_lang('addalbum'));
    }

    // Add Song button
    if ($KG_SECURITY->hasPermission('add') === true) {
        $form->add_hidden(array('ACTON' => 'addsong','addvia' => 'mainpage'));
        $form->buttononlyform(kgGetScriptName(),'post','frmaddsong',get_lang('addsong'));
    }

    // Edit genre list
    if ($KG_SECURITY->hasPermission('edit') === true) {
        $form->add_hidden(array('ACTON' => 'editgenres'));
        $form->buttononlyform(kgGetScriptName(),'post','frmeditgenres',get_lang('genrelist'));
    }

    // Orphan check button
    $form->add_hidden(array('ACTON' => 'orphancheckalbums'));
    $form->buttononlyform(kgGetScriptName(),'post','frmorphancheckalbums',get_lang('orphanedalbums'));

    // Search form
    // Small screens (800px wide or less)
    echo "\n".'<div class="list_artists_search_button_div">';
    $form->add_hidden(array('ACTON' => 'searchform'));
    $form->buttononlyform(kgGetScriptName(),'post','frmsearch',get_lang('search'));
    echo '</div>';
    // Large screens (Over 800px)
    echo "\n".'<div class="list_artists_search_form_div">';
    $form->add_hidden(array('ACTON' => 'runsearch'));
    $form->start_form(kgGetScriptName(),'post','frmsearch');
    $searchscopes = array(
        'artistname' => get_lang('artistname'),
        'albumname' => get_lang('albumname'),
        'songname' => get_lang('songname'),
        'songlyrics' => get_lang('lyrics')
    );
    $form->add_select_match_key('searchscope',$searchscopes);
    $form->add_text('searchtext');
    $form->add_button_submit(get_lang('search'));
    $form->end_form();
    echo '</div>';

    echo "\n</div><!-- END list_artists_button_row_div -->\n";
}
// END function ListArtists()
/////

function OrphanCheckAlbums() {
    /**
     * Lists albums and songs not associated with an artist
     * Only called from function listartists()
     *
     * Orphaned songs are listed via function viewartist() if artist ID is greater than 0
     *
     * Added: 2017-06-17
     * Modified: 2017-06-17
     *
     * @param None
     *
     * @return None
    **/

    global $dbc, $valid;

    $table = new HtmlTable();
    $form = new HtmlForm();

    /**
     * Orphaned Albums
    **/

    // Number of albums to display per row
    $maxcols = 5;
    $cnt = 0;

    // Search for albums not associated with any artist
    $query = $dbc->select('Albums', 'ArtistID = 0', array('AlbumID', 'Name', 'CoverArtFront'));
    $numalbums = $dbc->numRows($query);

    if ($numalbums > 0) {
        // Display list of orphaned albums

        echo '<center><b>'.get_lang('orphanedalbums').'</b></center><br />';

        echo "\n".'<div class="orphaned_albums_div">';

        while ($row = $dbc->fetchAssoc($query)) {

            echo "\n".'<div class="coverart_div">';
            // Display cover art if available
            if ($row['CoverArtFront'] != '') {
                $imginfo = kgGetImageLink($row['CoverArtFront']);
                if (array_key_exists('ImgTagThumb',$imginfo) === true) {
                    // Cover art exists
                    echo kgCreateLink($imginfo['ImgTagThumb'],array('ACTON' => 'viewalbum', 'albumid' => $row['AlbumID']));
                    echo '<br />'.kgCreateLink($row['Name'], array('ACTON' => 'viewalbum', 'albumid' => $row['AlbumID']));
                } else {
                    // Cover art not found
                    echo get_lang('NoCoverArt').'<br />'.kgCreateLink($row['Name'], array('ACTON' => 'viewalbum', 'albumid' => $row['AlbumID']));
                }
            } else {
                // No cover art, show album name
                echo get_lang('NoCoverArt').'<br />'.kgCreateLink($row['Name'], array('ACTON' => 'viewalbum', 'albumid' => $row['AlbumID']));
            }

            echo '</div>';

        }
        // END while ($row = $dbc->fetchAssoc($query))

        echo "\n</div><!-- END album_art_row_div -->\n";

    } else {
        echo '<center><b>'.get_lang('NoOrphanedAlbums').'</b></center>';
    }

    /**
     * Orphaned Songs
    **/

    if ($numalbums > 0) {
        // Add some space between the listings
        echo '<br /><br />';
    }

    // Number of songs to display per row
    $maxcols = 8;
    $cnt = 0;

    // Search for songs not associated with any album and not associated with any artist
    $query = $dbc->select('Lyrics', array('ArtistID' => 0, 'AlbumID' => 0), array('SongID', 'Name'));
    $numsongs = $dbc->numRows($query);

    if ($numsongs > 0) {
        // Display list of orphaned songs

        echo '<center><b>'.get_lang('orphanedsongs').'</b></center><br />';

        $table->new_table('centered');
        $table->new_row();

        while ($row = $dbc->fetchAssoc($query)) {

            $cnt++;
            $table->set_width(220,'px');
            $table->set_height(220,'px');
            $table->new_cell('centertext');
            echo kgCreateLink($row['Name'], array('ACTON' => 'viewsong', 'songid' => $row['SongID']));

            if ($cnt >= $maxcols) {
                // Start a new row  if $cnt >= $maxcols
                $table->blank_row($maxcols);
                $table->new_row();
                $cnt = 0;
            }

        }
        // END while ($row = $dbc->fetchAssoc($query))

        $table->end_table();

    } else {
        echo '<center><b>'.get_lang('NoOrphanedSongs').'</b></center>';
    }

    echo '<br /><br /><center>';
    $form->buttononlyform(kgGetScriptName(),'post','frmgotomain',get_lang('returntoartistlist'));
    echo '</center>';

}
// END function OrphanCheckAlbums()
/////

function RenameGenre() {
    /**
     * Displays a form to change the name of a genre.
     *
     * Added: 2017-06-25
     * Modified: 2017-07-01
     *
     * @param None
     *
     * @return None
    **/

    global $dbc, $valid, $KG_SECURITY;

    $genreid = $valid->get_value_numeric('genreid');

    if ($KG_SECURITY->haspermission('edit') === false) {
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('returntoartistlist'));
        echo '</center>';
        return false;
    }

    $form = new HtmlForm();
    $table = new HtmlTable();

    if ($genreid < 1)  {
        echo '<div class="page_title_div">'.get_lang('invalidgenreid').'</div>';
        $form->add_hidden(array(
            'ACTON' => 'editgenres'
        ));
        $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('goback'));
        return;
    }

    $name = $dbc->fieldValue($dbc->select('GenreIDs', 'GenreID = '.$genreid, 'Name'));

    $table->set_width(450,'px');
    $table->new_table_css('centered');

    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell('centertext');
    echo '<b>'.get_lang('RenameGenre').'</b>';

    $table->blank_row(2);

    $table->new_row();
    $table->new_cell();
    echo get_lang('CurrentName').':';
    $table->new_cell();
    echo $name;

    $table->blank_row(2);

    $table->new_row();
    $table->new_cell();
    echo get_lang('NewName').':';
    $table->new_cell();
    $form->add_hidden(array(
        'ACTON' => 'savechangesgenre',
        'genreid' => $genreid
    ));
    $form->simple_form_text('name','',15,'','','frmrenamegenre','',get_lang('Save'));

    $table->blank_row(2);

    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell('centertext');
    $form->buttononlyform(kgGetScriptName(),'post','frmcancel',get_lang('Cancel'));        

    $table->end_table();
}
// END function RenameGenre()
/////

function RunSearch() {
    /**
     * Searches the database for the given string.
     *
     * Call this function AFTER the user fills out the search form.
     *
     * Added: 2017-02-23
     * Modified: 2017-02-23
     *
     * @param None
     *
     * @return Nothing
    **/

    global $dbc,$valid;

    $form = new HtmlForm();

    $table = new HtmlTable();

    // Get values to insert into search query
    $searchscope = $valid->get_value('searchscope');
    $searchtext = $valid->get_value('searchtext');

    $searchscopes = array(
        'artistname' => get_lang('ArtistName'),
        'albumname' => get_lang('AlbumName'),
        'songname' => get_lang('SongName'),
        'songlyrics' => get_lang('lyrics')
    );

    // Make sure search term is long enough
    if (strlen($searchtext) < 4) {
        echo '<center>'.get_lang('searchtermnotlongenough').'<br />'.get_lang('searchterm'). ' \''.$searchtext.'\'<br />'.get_lang('searchscope').': '.$searchscopes[$searchscope].'<br /><br />';
        $form->buttononlyform(kgGetScriptName(),'post','frmgotomain',get_lang('continue'));
        echo '</center>';
        return;
    }

    // Create search query
    if ($searchscope == 'artistname') {

        $shorttext = kgGenShortName($searchtext);
        $query = $dbc->select('Artists', 'ShortName LIKE "%'.$shorttext.'%"', array('ArtistID', 'Name'));
        $numresults = $dbc->numRows($query);
        $hdrwidth = array(array(175,'px'));
        $hdrrow = array(get_lang('artistname'));

    } elseif ($searchscope == 'albumname') {

        $shorttext = kgGenShortName($searchtext);
        $query = $dbc->select('Albums', 'ShortName LIKE "%'.$shorttext.'%"', array('AlbumID', 'ArtistID', 'Name'));
        $numresults = $dbc->numRows($query);
        $hdrwidth = array(array(250,'px'),array(15,'px'),array(150,'px'));
        $hdrrow = array(get_lang('albumname'),' ',get_lang('artistname'));

    } elseif ($searchscope == 'songname') {

        $shorttext = kgGenShortName($searchtext);
        $query = $dbc->select('Lyrics', 'ShortName LIKE "%'.$shorttext.'%"', array('SongID', 'ArtistID', 'Name'));
        $numresults = $dbc->numRows($query);
        $hdrwidth = array(array(250,'px'),array(15,'px'),array(150,'px'));
        $hdrrow = array(get_lang('songname'),' ',get_lang('artistname'));

    } elseif ($searchscope == 'songlyrics') {

        $ma = array('buildMatchAgainst' => array('Lyrics', $searchtext, 'IN NATURAL LANGUAGE MODE', 'score'));
        $query = $dbc->select('Lyrics', '', array('SongID','ArtistID','Name',$ma), array('ORDER BY' => 'score DESC'));
        $hdrwidth = array(array(250,'px'),array(15,'px'),array(150,'px'));
        $hdrrow = array(get_lang('songname'),' ',get_lang('artistname'));

        $matches = array();
        // Run the query
        while ($row = $dbc->fetchAssoc($query)) {
            if ($row['score'] > 0) {
                // Only want song lyrics that contain $searchtext
                $matches[] = $row;
            }
        }

        $numresults = count($matches);
    }
    // END if ($searchscope == 'artistname')

    if ($numresults > 0) {

        // Start search results table
        $table->new_table('search_results_table');

        $hdrcount = count($hdrrow);

        $table->new_row();
        for ($i = 0; $i < $hdrcount; $i++) {
            $table->set_width($hdrwidth[$i], 'px');
            $table->new_cell('search_result_header_cell');
            echo $hdrrow[$i];
        }

        if ($searchscope == 'artistname') {

            while ($row = $dbc->fetchAssoc($query)) {

                $table->new_row('searchtablerow');
                $table->new_cell();
                echo kgCreateLink($row['Name'],array('ACTON' => 'viewartist', 'artistid' => $row['ArtistID'], 'USE_CSS' => 'href_to_button'));
            }

        } elseif ($searchscope == 'albumname') {

            while ($row = $dbc->fetchAssoc($query)) {

                $table->new_row('searchtablerow');
                $table->new_cell();
                echo kgCreateLink($row['Name'],array('ACTON' => 'viewalbum', 'albumid' => $row['AlbumID'], 'USE_CSS' => 'href_to_button'));
                $table->blank_cell();
                $table->new_cell();
                $artistname = $dbc->fieldValue($dbc->select('Artists', 'ArtistID = '.$row['ArtistID'], 'Name'));
                echo kgCreateLink($artistname, array('ACTON' => 'viewartist', 'artistid' => $row['ArtistID']));
            }

        } elseif ($searchscope == 'songname') {

            while ($row = $dbc->fetchAssoc($query)) {

                $table->new_row('searchtablerow');
                $table->new_cell();
                echo kgCreateLink($row['Name'], array('ACTON' => 'viewsong', 'songid' => $row['SongID'], 'USE_CSS' => 'href_to_button'));
                $table->blank_cell();
                $table->new_cell();
                $artistname = $dbc->fieldValue($dbc->select('Artists', 'ArtistID = '.$row['ArtistID'], 'Name'));
                echo kgCreateLink($artistname, array('ACTON' => 'viewartist', 'artistid' => $row['ArtistID']));
            }

        } elseif ($searchscope == 'songlyrics') {

            foreach ($matches as $key => $value) {

                $table->new_row('searchtablerow');
                $table->new_cell();
                echo kgCreateLink($value['Name'], array('ACTON' => 'viewsong', 'songid' => $value['SongID'], 'USE_CSS' => 'href_to_button'));
                $table->blank_cell();
                $table->new_cell();
                $artistname = $dbc->fieldValue($dbc->select('Artists', 'ArtistID = '.$value['ArtistID'], 'Name'));
                echo kgCreateLink($artistname, array('ACTON' => 'viewartist', 'artistid' => $value['ArtistID']));
            }
        }
        // END if ($searchscope == 'artistname')

        $table->end_table();

    } else {
        // No matches found
        echo '<center>'.get_lang('NoMatchesFound'). ' \''.$searchtext.'\'<br />'.get_lang('searchscope').': '.$searchscopes[$searchscope].'<br /><br />';
        $form->buttononlyform(kgGetScriptName(),'post','frmgotomain',get_lang('continue'));
        echo '</center>';
    }

    echo '<br><br><center>';
    $form->add_hidden(array('ACTON' => 'searchform'));
    $form->buttononlyform(kgGetScriptName(),'post','frmsearch',get_lang('search'));
    echo '</center>';
}
// END function RunSearch()
/////

function SaveChangesAlbum() {
    /**
     * Validate and save changes made to info about an existing album.
     *
     * Added: 2017-06-24
     * Modified: 2017-06-28
     *
     * @param None
     *
     * @return boolean(true) or boolean(false)
    **/

    global $dbc, $valid, $KG_SECURITY;

    $albumid = $valid->get_value_numeric('albumid', 0);

    if ($KG_SECURITY->hasPermission('edit') === false) {
        $form->add_hidden(array(
            'ACTON' => 'editalbum',
            'albumid' => $albumid
        ));
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmeditalbum',get_lang('goback'));
        echo '</center>';
        return false;
    }

    // -Make sure there are no errors stored
    // -Some of the validation tests must pass for the data
    //  to be added to the database
    $valid->clearErrors();

    if ($albumid < 1) {
        // Invalid album ID
        $valid->add_error(get_lang('UnableToSaveChanges').'. '.get_lang('InvalidAlbumID'));
        return false;
    }

    // Get artist shortname
    $artistid = $dbc->fieldValue($dbc->select('Albums', 'AlbumID = '.$albumid, 'ArtistID'));
    $artistshortname = $dbc->fieldValue($dbc->select('Artists', 'ArtistID = '.$artistid, 'ShortName'));

    // The data in this array will be put in the datbase
    $albumupdatequery = array();

    // Get album's release date
    $albumupdatequery['ReleaseDate'] = $valid->get_value('releasedate');

    // Wikipedia article
    $albumupdatequery['Wikipedia'] = $valid->get_value('wikipediaarticle');

    // Album name
    $albumupdatequery['Name'] = $valid->get_value('albumname');
    $albumupdatequery['ShortName'] = kgGenShortName($albumupdatequery['Name']);

    $unknownartistchkbox = $valid->get_value_numeric('unknownartist');
    if ($unknownartistchkbox == 1) {
        // Artist is unknown. This will create an orphaned album.
        $albumupdatequery['ArtistID'] = 0;
    }

    // Get name of selected album coverart
    $frontcoverradio = $valid->get_value('frontcoverradio');
    $backcoverradio = $valid->get_value('backcoverradio');

    if ($frontcoverradio != '') {
        // Change name of file used for front cover art
        $albumupdatequery['CoverArtFront'] = $frontcoverradio;
    } else {
        $albumupdatequery['CoverArtFront'] = DoUpload('CoverArtFront', $artistshortname);
    }

    if ($albumupdatequery['CoverArtFront'] == '') {
        // Nothing was uploaded or something went wrong with the upload
        // Unset the array key so existing info is not overwritten
        unset($albumupdatequery['CoverArtFront']);
    }

    if ($backcoverradio != '') {
        // Change name of file used for back cover art
        $albumupdatequery['CoverArtBack'] = $backcoverradio;
    } else {
        $albumupdatequery['CoverArtBack'] = DoUpload('CoverArtBack', $artistshortname);
    }

    if ($albumupdatequery['CoverArtBack'] == '') {
        // Nothing was uploaded or something went wrong with the upload.
        // Unset the array key so existing info is not overwritten.
        unset($albumupdatequery['CoverArtBack']);
    }

    // Check if artist ID number has changed
    if ($valid->get_value_numeric('artistid') > 0) {
        $albumupdatequery['ArtistID'] = $valid->get_value_numeric('artistid');
    }

    if ($valid->get_value_numeric('unknownartist') == 1) {
// TODO     // Should this be set to something other than 0?
        // Setting this to 0 will result in an orphaned album.
        $albumupdatequery['ArtistID'] = 0;
    }

    if (!$dbc->update('Albums', 'AlbumID = '.$albumid, $albumupdatequery)) {
        // Update failed
        $valid->add_error(get_lang('UnableToSaveChanges').'. '.get_lang('UpdateFailed').'<br />'.$dbc->errorString());
        $valid->setValue($albumupdatequery);
        return false;
    }

    // Next $dbc->select() needs to read from the master server while the slaves update their data
    $dbc->mysqlndSwitch('master');

    return true;

}
// END function SaveChangesAlbum()
/////

function SaveChangesArtist() {
    // Validate and save changes made to info about an artist

    global $dbc, $valid, $KG_SECURITY;

    // Get artist ID
    $artistid = $valid->get_value_numeric('artistid', 0);

    if ($KG_SECURITY->hasPermission('edit') === false) {
        $form = new HtmlForm();
        $form->add_hidden(array(
            'ACTON' => 'editartist',
            'artistid' => $artistid
        ));
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmeditalbum',get_lang('editartistinfo'));
        echo '</center>';
        return false;
    }

    if ($artistid < 1) {
        // Invalid artist ID
        $valid->add_error(get_lang('UnableToSaveChanges').'. '.get_lang('invalidartistid'));
        return;
    }

    $artistdata = getArtistData();

    if (!$dbc->update('Artists', 'ArtistID = '.$artistid, $artistdata)) {
        // Update failed
        $valid->add_error(get_lang('UnableToSaveChanges').'. '.get_lang('UpdateFailed').'<br />'.$dbc->errorString());
    }

}
// END function SaveChangesArtist()
/////

function SaveChangesGenre() {
    // Save changes to an existing genre

    global $dbc, $valid, $KG_SECURITY;

    $genreid = $valid->get_value_numeric('genreid');
    $name = $valid->get_value('name');

    if ($KG_SECURITY->hasPermission('edit') === false) {
        $form = new HtmlForm();
        $form->add_hidden(array(
            'ACTON' => 'editgenres'
        ));
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmsavechangesgenre',get_lang('genrelist'));
        echo '</center>';
        return false;
    }

    if ($genreid > 0) {
        $updatearray = array(
            'Name' => $name
        );
        if (!$dbc->update('GenreIDs', 'GenreID = '.$genreid, $updatearray)) {
            $valid->add_error(get_lang('UnableToSaveChanges').'. '.get_lang('invalidgenreid'));
        }
    } else {
        $valid->add_error(get_lang('invalidgenre'));
    }
    
}
// END function SaveChangesGenre()
/////

function SaveChangesSong() {
    /**
     * Save changes to an existing song
     *
     * Added: 2017-06-24
     * Modified: 2017-07-01
     *
     * @param None
     *
     * @return boolean
    **/

    global $dbc, $valid, $KG_SECURITY;

    // Get song ID
    $songid = $valid->get_value_numeric('songid');

    if ($KG_SECURITY->hasPermission('edit') === false) {
        $form = new HtmlForm();
        $form->add_hidden(array(
            'ACTON' => 'editsong',
            'songid' => $songid
        ));
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmeditalbum',get_lang('editsong'));
        echo '</center>';
        return false;
    }

    if ($songid > 0) {

        $songdata = getSongData();

        $changealbumchkbox = $valid->get_value_numeric('changealbumchkbox');
        if ($changealbumchkbox == 1) {
            // Move song to a different album
            $songdata['AlbumID'] = $valid->get_value_numeric('changealbumselect');
        }

        $changeartistchkbox = $valid->get_value_numeric('changeartistchkbox');
        if ($changeartistchkbox == 1) {
            // Move song to a different artist
            $songdata['ArtistID'] = $valid->get_value_numeric('changeartistselect');
        }

        if (!$dbc->update('Lyrics', 'SongID = '.$songid, $songdata)) {
            // Update failed

            $link = kgCreateLink(get_lang('ReturnToSong'),array('ACTON' => 'viewsong', 'songid' => $songid));

            $valid->add_error(get_lang('UnableToSaveChanges').'. '.get_lang('UpdateFailed').'<br />'.$dbc->errorString().'.<br /><br />'.$link);

            return false;
        }

        // Next $dbc->select() needs to read from the master server while the slaves update their data
        $dbc->mysqlndSwitch('master');

    } else {
        // Invalid song ID
        $valid->add_error(get_lang('UnableToSaveChanges').'. '.get_lang('InvalidSongID'));
        return false;
    }

    return true;
}
// END function SaveChangesSong()
/////

function SaveNewAlbum() {
    /**
     * Validate and save new album info to database
     *
     * Added: 2017-06-16
     * Modified: 2017-07-01
     *
     * @param None
     *
     * @return Nothing
    **/

    global $dbc, $valid, $userinfo, $KG_SECURITY;

    if ($KG_SECURITY->hasPermission('add') === false) {
        $form = new HtmlForm();
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmgoback',get_lang('returntoartistlist'));
        echo '</center>';
        return false;
    }

    if ($valid->get_value('albumname') == '') {
        $valid->addError(get_lang('missingalbumname'));
        return false;
    }

    if ($valid->get_value_numeric('artistid', 0) < 1) {
        $valid->addError(get_lang('missingartistname'));
        return false;
    }

    // Info in this array will be added to the database
    $albumdata = array();

    // Get info to add to database
    $albumdata['Name'] = $valid->get_value('albumname');
    $albumdata['ShortName'] = kgGenShortName($albumdata['Name']);
    $albumdata['ArtistID'] = $valid->get_value_numeric('artistid', 0);
    $albumdata['Wikipedia'] = $valid->get_value('wikipediaarticle');
    $ablumdata['AddedBy'] = $userinfo['UserID'];
    $ablumdata['DateAdded'] = date("Y-m-d"); // 4 digit year, 2 digit month with leading zero, 2 digit day with leading zero

    if ($valid->get_value_numeric('releasedateunknown', 0) != 1) {
        // -Release date known or could actually be unknown but user didn't click the checkbox saying so

        if ($valid->get_value('releasedate') != '') {
            // Release date was not entered or is actually unknown
            $albumdata['ReleaseDate'] = $valid->get_value('releasedate');
        }
    }

    // Get artist shortname
    $artistshortname = $dbc->fieldValue($dbc->select('Artists', 'ArtistID = '.$albumdata['ArtistID'], 'ShortName'));

    if ($valid->get_value('frontcoverradio') != '') {
        // Use an already uploaded coverart image
        $albumdata['CoverArtFront'] = $valid->get_value('frontcoverradio');
    } else {
        // Upload new coverart if any
        $albumdata['CoverArtFront'] = DoUpload('CoverArtFront', $artistshortname);
    }

    if ($valid->get_value('backcoverradio') != '') {
        // Use an already uploaded coverart image
        $albumdata['CoverArtBack'] = $valid->get_value('backcoverradio');
    } else {
        // Upload new coverart if any
        $albumdata['CoverArtBack'] = DoUpload('CoverArtBack', $artistshortname);
    }

    // We're adding a new album so it shouldn't be in the database
    // but check anyways to avoid duplicating data
    $query = $dbc->select('Albums', array('ShortName' => $albumdata['ShortName'], 'ArtistID' => $albumdata['ArtistID']), 'AlbumID');
    $numalbums = $dbc->numRows($query);

    if ($numalbums == 0) {
        // Add album info to database

        if ($dbc->insert('Albums', $albumdata)) {
            // Insert succeeded

            // Make album ID available for the next function
            $valid->setValue(array('albumid' => $dbc->GetInsertID()));

            // Next $dbc->select() needs to read from the master server while the slaves update their data
            $dbc->mysqlndSwitch('master');

        } else {
            // Insert failed
            $valid->addError(get_lang('InsertFailed').'<br />'.$dbc->errorString());
        }

    } elseif ($numalbums == 1) {
        // Album already exists in database

        // Make album ID available for the next function
        $valid->setValue(array('albumid' => $dbc->fieldValue($query)));

    } elseif ($numalbums > 1) {
        // Multiple matches found. This shouldn't have happened.

        $msg = str_replace('~~name~~', $albumdata['Name'], get_lang('multipleaalbumssamename'));
        $valid->addError(get_lang('unabletosavenewalbuminfo').' '.$msg);

    }

}
// END function SaveNewAlbum()
/////

function SaveNewArtist() {
    /**
     * Add a new artist to the database
     *
     * Added: 2017-06-16
     * Modified: 2017-07-01
     *
     * @param None
     *
     * @return None
    **/

    global $dbc, $valid, $userinfo, $KG_SECURITY;

    // Info to add to database
    $artistdata = getArtistData();

    if ($KG_SECURITY->hasPermission('add') === false) {
        $valid->setValues($artistdata);
        $form = new HtmlForm();
        $form->add_hidden(array(
            'ACTON' => 'addartist'
        ));
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmeditalbum',get_lang('editartistinfo'));
        echo '</center>';
        return false;
    }

    $artistname = $valid->get_value('artistname');

    if ($artistname == '') {
        $valid->addError(get_lang('missingartistname'));
        return;
    }

    // Get short name/version of artist name
    $shortname = kgGenShortName($artistname);        

    // Check to see if artist already in database
    $query = $dbc->select('Artists', 'ShortName = "'.$shortname.'"', 'ArtistID');
    $numartists = $dbc->numRows($query);

    if ($numartists == 0) {
        // Shortname not found
        // Add artist info to database

        if ($dbc->insert('Artists', $artistdata)) {
            // Insert succeeded

            // Make artist ID available for the next function
            $valid->setValue(array('artistid' => $dbc->GetInsertID()));

            // Next $dbc->select() needs to read from the master server while the slaves update their data
            $dbc->mysqlndSwitch('master');

        } else {
            // Insert failed
            $valid->addError(get_lang('insertfailed').'<br />'.$dbc->errorString());
        }


    } elseif ($numartists == 1) {
        // Artist already exists in database

        // Make artist ID available for the next function
        $valid->setValue(array('artistid' => $dbc->fieldValue($query)));

    } elseif ($numartists > 1) {
        // Multiple matches found. This shouldn't have happened.
        // Artist info not saved

        $msg = str_replace('~~name~~', $artistname, get_lang('multipleartistssamename'));
        $valid->addError(get_lang('unabletosavenewartistinfo').' '.$msg);
    }
}
// END function SaveNewArtist()
/////

function SaveNewSong() {
    /**
     * Validates info for a new song and saves it to the database
     *
     * Saving a new song requires a few steps
     * 1) Check if we need to add a new artist
     * 2) Check if we need to add a new album
     * 3) Add the info for the new song
     *
     * Added: 2017-06-24
     * Modified: 2017-07-01
     *
     * @param None
     *
     * @return boolean(True) or boolean(False)
    **/

    global $dbc, $valid, $userinfo, $KG_SECURITY;

    // Get song data to insert into the database
    $songdata = getSongData();

    // Get artist data to insert into the database
    $artistdata = getArtistData();

    if ($KG_SECURITY->hasPermission('edit') === false) {
        $valid->setValues($songdata);
        $valid->setValues($artistdata);
        $form = new HtmlForm();
        $form->add_hidden(array(
            'ACTON' => 'addsong'
        ));
        echo '<center>';
        $form->buttononlyform(kgGetScriptName(),'post','frmeditalbum',get_lang('editsong'));
        echo '</center>';
        return false;
    }

    // -Make sure there are no errors stored
    // -Some of the validation tests must pass for the data
    //  to be added to the database
    $valid->clearErrors();

    // These variables determine if a new artist or album needs to be added
    $newartistchkbox = $valid->get_value_numeric('newartistchkbox');
    $newalbumchkbox = $valid->get_value_numeric('newalbumchkbox');

    /////
    // Add minimal artist data if needed
    if ($newartistchkbox == 1) {
        // Add a new artist

        if ($valid->get_value('newartisttxtbox') == '') {
            // They forgot the artist name
            $valid->addError(get_lang('missingnewartistname'));
            return false;
        }

        $artistname = $valid->get_value('newartisttxtbox');
        $artistshortname = kgGenShortName($artistname);
        $artistdata = array(
            'Name ' => $artistname,
            'ShortName ' => $artistshortname,
            'DateAdded' => date("Y-m-d"), // 4 digit year, 2 digit month with leading zero, 2 digit day with leading zero
            'addedby' => $userinfo['UserID']
        );

        if ($dbc->insert('Artists', $artistdata)) {
            // Insert succeeded

            // Get artist ID
            $artistid = $dbc->GetInsertID();

        } else {
            // Insert failed
            // -Insert must succeed to get a valid artist ID number
            $valid->addError(get_lang('unabletosavenewartistinfo').'<br />'.$dbc->errorString());
            return false;
        }

    } else {
        // Get ID number for selected artist
        $artistid = $valid->get_value_numeric('artistid');
    }
    // END Add minimal artist data if needed
    /////

    /////
    // Add minimal album data if needed
    if (($newalbumchkbox == 1) && (!$valid->is_error())) {
        // Add a new album

        if ($valid->get_value('newalbumtxtbox') == '') {
            // They forgot the album name
            $valid->addError(get_lang('missingnewalbumname'));
            return false;
        }

        $albumname = $valid->get_value('newalbumtxtbox');
        $albumshortname = kgGenShortName($albumname);
        $albumdata = array(
            'Name ' => $albumname,
            'ShortName ' => $albumshortname,
            'ArtistID' => $artistid,
            'DateAdded' => date("Y-m-d"), // 4 digit year, 2 digit month with leading zero, 2 digit day with leading zero
            'addedby' => $userinfo['UserID']
        );

        if ($dbc->insert('Albums', $albumdata)) {
            // Insert succeeded

            // Get album ID
            $albumid = $dbc->GetInsertID();

        } else {
            // -Insert failed
            // -Insert must succeed to get a valid album ID number
            $valid->addError(get_lang('unabletosavenewalbuminfo').'<br />'.$dbc->errorString());
            return false;
        }

    } else {
        // Get ID number for selected album
        $albumid = $valid->get_value_numeric('albumid',0);
    }
    // END Add minimal album data if needed
    /////

    if (!$valid->hasErrors()) {
        // All previous steps passed
        // Now save song data to database

        // Update song data with the new artist ID and album ID numbers
        $songdata['ArtistID'] = $artistid;
        $songdata['AlbumID'] = $albumid;
        $songdata['DateAdded'] = date("Y-m-d"); // 4 digit year, 2 digit month with leading zero, 2 digit day with leading zero
        $songdata['addedby'] = $userinfo['UserID'];

        if ($dbc->insert('Lyrics', $songdata)) {
            // Insert succeeded

            // Get song ID
            $songid = $dbc->GetInsertID();

            // Set songid so it can be accessed via the next function
            $valid->setValue(array('songid' => $songid));

            // Next $dbc->select() needs to read from the master server while the slaves update their data
            $dbc->mysqlndSwitch('master');

        } else {
            // Insert failed
            $valid->addError(get_lang('unabletosavesongdata').'<br />'.$dbc->errorString());
            return false;
        }
        // END if ($dbc->insert('Lyrics', $songdata))

    }
    // END if (!$valid->hasErrors())

    // Data saved successfully
    return true;

}
// END function SaveNewSong()
/////

function SearchForm() {
    /**
     * Display the search form
     *
     * Added: 2017-06-17
     * Modified: 2017-06-17
     *
     * @param None
     *
     * @return None
    **/

    $form = new HtmlForm();

    echo "\n".'<div class="page_title_div">'.get_lang('searchform').'</div><br>';

    echo "\n".'<div class="search_category_column_div">'.get_lang('category').'</div><div class="search_text_column_div">'.get_lang('searchfor').'</div>';

    echo "\n".'<div style="clear:both;"></div>';

    $form->add_hidden(array('ACTON' => 'runsearch'));
    $form->start_form(kgGetScriptName(),'post','frmsearch');

    echo "\n".'<div class="search_category_column_div">';
    $searchscopes = array(
        'artistname' => get_lang('artistname'),
        'albumname' => get_lang('albumname'),
        'songname' => get_lang('songname'),
        'songlyrics' => get_lang('lyrics')
    );
    $form->onsubmit('setFormFocus()');
    $form->add_select_match_key('searchscope',$searchscopes);
    echo '</div><div class="search_text_column_div">';
    $form->add_text('searchtext');
    echo '</div><div class="search_submit_button_div">';
    $form->add_button_submit(get_lang('search'));
    echo '</div>';
    $form->add_button_generic('ACTON',get_lang('cancel'),'location.href="'.kgCreateLink('',array('NO_TAG' => 'NO_TAG')).'";');
    $form->end_form();

    ?>
    <script type="text/javascript" language="JavaScript">
        function setFormFocus() {
            document.forms['frmsearch'].elements['searchtext'].focus();
        }

        setFormFocus();
    </script>
    <?php
}
// END function SearchForm()
/////

function ViewAlbum() {
    /**
     * Display available info about the selected album
     *
     * Added: 2017-06-16
     * Modified: 2017-06-16
     *
     * @param None
     *
     * @return None
    **/

    global $dbc, $valid, $KG_SECURITY, $userinfo;

    $form = new HtmlForm();
    $table = new HtmlTable();
    $innertable = new HtmlTable();

    // Display errors if any
    $valid->displayErrors();

    // Get album ID
    $albumid = $valid->get_value_numeric('albumid', 0);

    if ($albumid < 1) {
        echo get_lang('invalidalbumid');
        return;
    }

    echo '<script type="text/javascript">
        function confirmAlbumDelete() {
            var answer = confirm("'.get_lang('ConfirmDeleteMsg').'");

            if (answer) {
                return true;
            } else {
                return false;
            }
        }
    </script>';

    // Get album info
    $albumdata = $dbc->fetchAssoc($dbc->select('Albums', 'AlbumID = '.$albumid));

    // Get artist info for the album
    $artistdata = $dbc->fetchAssoc($dbc->select('Artists', 'ArtistID = '.$albumdata['ArtistID'], array('Name', 'ShortName')));

    // Count number of songs for this album
    $songcnt = $dbc->numRows($dbc->select('Lyrics', 'AlbumID = '.$albumid, 'SongID'));

    // Let the mysqlnd library decide which server to use
    $dbc->mysqlndSwitch();

    echo "\n".'<div class="page_title_div">'.get_lang('viewalbum').'</div>';

    // Start of table
    $table->new_table();

    // Artist Name
    $table->new_row();
    $table->new_cell('view_album_label_cell');
    echo get_lang('artistname');
    $table->new_cell('view_album_data_cell');
    echo kgCreateLink($artistdata['Name'], array('ACTON' => 'viewartist', 'artistid' => $albumdata['ArtistID']), get_lang('gotoartistinfo'));

    // Display cover art if available
    $table->set_rowspan(8);
    $table->new_cell('coverart_cell');
    
    // Front coverart
    // Not (echo)ing $msg since we don't need it
    $msg = displayCoverArt($albumdata['CoverArtFront']);

    // Back coverart
    // Not (echo)ing $msg since we don't need it
    $msg = displayCoverArt($albumdata['CoverArtBack'], '', 'back');

    $table->new_cell();
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.kgCreateLink(get_lang('returntoartistlist'));

    // Album Name
    $table->new_row();
    $table->new_cell();
    echo get_lang('albumname');
    $table->new_cell();
    echo kgCreateLink($albumdata['Name'], array('ACTON' => 'viewalbum', 'albumid' => $albumid), get_lang('reloadalbumdata'));
    $table->blank_cell();

    // Release Date
    $table->new_row();
    if ($albumdata['ReleaseDate'] != '0000-00-00') {
        $table->new_cell();
        echo get_lang('releasedate');
        $table->new_cell();
        echo $albumdata['ReleaseDate'];
        $table->blank_cell();
    } else {
        $table->blank_cell(3);
    }

    // Wikipedia link
    $table->new_row();
    if ($albumdata['Wikipedia'] != '') {
        // Add link to Wikipedia Article
        $table->new_cell();
        echo '<a href="'.$albumdata['Wikipedia'].'" target="_blank">'.get_lang('wikipediaarticle').'</a>';
    } else {
        $table->blank_cell();
    }

    $table->blank_cell(2);

    $table->blank_row(3);

    $table->new_row();

    $table->blank_cell(2);

    // Edit album button
    $KG_SECURITY->checkUserId($userinfo['UserID']);
    $KG_SECURITY->hideMsgs();
    if ($KG_SECURITY->hasPermission('edit') === true) {
        // Has permission
        $table->new_cell('righttext');
        $form->add_hidden(array(
            'ACTON' => 'editalbum',
            'albumid' => $albumid
        ));
        $form->buttononlyform(kgGetScriptName(),'post','frmeditalbum',get_lang('editalbuminfo'));
    } else {
        $table->blank_cell();
    }

    $table->blank_row(3);

    $table->new_row();
    $table->blank_cell(2);

    // Delete album button
    $KG_SECURITY->checkUserId($userinfo['UserID']);
    $KG_SECURITY->hideMsgs();
    if ($KG_SECURITY->hasPermission('delete') === true) {
        // Has permission
        $table->new_cell('righttext');
        $form->add_hidden(array(
            'ACTON' => 'deletealbum',
            'albumid' => $albumid
        ));
        $form->onsubmit('return confirmAlbumDelete();');
        $form->start_form(kgGetScriptName(),'post','frmdeletealbum');
        $form->add_button_submit(get_lang('deletealbum'));
        echo '<br />';
        $form->add_checkbox('alsodeletesongs',1,get_lang('alsodeletesongs'));
        $form->end_form();
    } else {
        $table->blank_cell();
    }

    $table->new_row();
    $table->new_cell();
    echo get_lang('SongList');
    // Add song button
    $KG_SECURITY->checkUserId($userinfo['UserID']);
    $KG_SECURITY->hideMsgs();
    if ($KG_SECURITY->hasPermission('add') === true) {
        // Has permission
        $table->new_cell();
        $form->add_hidden(array(
            'ACTON' => 'addsong',
            'addvia' => 'albumpage',
            'artistid' => $albumdata['ArtistID'],
            'albumid' => $albumid
        ));
        $form->buttononlyform(kgGetScriptName(),'post','frmaddsong',get_lang('addsong'));
    } else {
        $table->blank_cell();
    }
    $table->blank_cell(2);

    if ($songcnt > 0) {
        // List songs for this album

        // Get info about the songs for this album
        $songlist = $dbc->select('Lyrics', 'AlbumID = '.$albumid, array('SongID', 'Name', 'TrackNumber'), array('ORDER BY' => 'TrackNumber'));

        $table->new_row();
        $table->set_colspan(3);
        $table->new_cell();

            $innertable->new_table();
            $innertable->new_row();
            $innertable->set_width(75, 'px');
            $innertable->new_cell();
            echo get_lang('tracknumber');
            $innertable->new_cell();
            echo get_lang('songname');

            // Display song name and track number
            while ($row = $dbc->fetchAssoc($songlist)) {

                $innertable->new_row();
                $innertable->new_cell('centertext');
                echo $row['TrackNumber'];
                $innertable->new_cell();
                echo kgCreateLink($row['Name'],array('ACTON' => 'viewsong', 'songid' => $row['SongID']));

            }

            $innertable->end_table();

        $table->blank_cell();

    }

    $table->end_table();

}
// END function ViewAlbum()
/////

function ViewArtist() {
    /**
     * Shows info about the selected artist
     *
     * Added: 2017-06-08
     * Modified: 2017-06-10
     *
     * @param None
     *
     * @return None
    **/

    global $dbc, $valid, $KG_SECURITY;

    $valid->displayErrors();

    $form = new HtmlForm();

    // Get artist ID
    $artistid = $valid->get_value_numeric('artistid', 0);

    if ($artistid < 1) {
        echo '<center>'.get_lang('invalidartistid').'<br /><br />';
        $form->buttononlyform(kgGetScriptName(),'post','frmgotomain',get_lang('goback'));
        echo '</center>';

        return;
    }

    // Valid ID received

    // Load artist info
    $artistdata = $dbc->fetchAssoc($dbc->select('Artists', 'ArtistID = '.$artistid));

    // Count number of songs for this artist
    $songcnt = $dbc->numRows($dbc->select('Lyrics', 'ArtistID = '.$artistid, 'SongID'));

    // Count number of albums for this artist
    $albumcnt = $dbc->numRows($dbc->select('Albums', 'ArtistID = '.$artistid, 'AlbumID'));

    // Get artist names and ID numbers from the database ordered alphabetically
    $findnpquery = $dbc->select('Artists', '', array('ArtistID', 'Name'), array('ORDER BY' => 'Name ASC'));

    // Position in the $sortedartists array
    $curpos = 0;

    // Put the name and ID info into an array
    while ($row = $dbc->fetchAssoc($findnpquery)) {
        $sortedartists[] = array('Name' => $row['Name'],'ArtistID' => $row['ArtistID']);
        if ($row['ArtistID'] == $artistid) {
            // Position in the array of $artistid
            $curpos = count($sortedartists) - 1;
        }
    }

    // Let the mysqlnd library decide which server to use
    $dbc->mysqlndSwitch();

    // Count the number of artists found
    $arrcnt = count($sortedartists);

    // Create the next/previous artist links
    $shownplinks = false;
    if ($arrcnt > 1) {
        // There are at least 2 artists in the database
        $shownplinks = true;

        if ($curpos == 0) {
            // Start of array $sortedartists

            // Second artist in the array
            $idnext = $sortedartists[$curpos + 1]['ArtistID'];
            $namenext = $sortedartists[$curpos + 1]['Name'];

            // Last artist in the array
            $idprev = $sortedartists[$arrcnt - 1]['ArtistID'];
            $nameprev = $sortedartists[$arrcnt - 1]['Name'];

        } elseif ($curpos == ($arrcnt - 1)) {
            // End of array $sortedartists

            // First artist in the array
            $idnext = $sortedartists[0]['ArtistID'];
            $namenext = $sortedartists[0]['Name'];

            // Second to last artist in the array
            $idprev = $sortedartists[$curpos - 1]['ArtistID'];
            $nameprev = $sortedartists[$curpos - 1]['Name'];

        } else {
            // Somewhere in between

            $idnext = $sortedartists[$curpos + 1]['ArtistID'];
            $namenext = $sortedartists[$curpos + 1]['Name'];

            $idprev = $sortedartists[$curpos - 1]['ArtistID'];
            $nameprev = $sortedartists[$curpos - 1]['Name'];

        }

        $nextartist = kgCreateLink(get_lang('NextArtist').' >>',array('ACTON' => 'viewartist','artistid' => $idnext),$namenext);
        $prevartist = kgCreateLink('<< '.get_lang('PrevArtist'),array('ACTON' => 'viewartist','artistid' => $idprev),$nameprev);
    } else {
        $nextartist = '';
        $prevartist = '';
    }

    // Artist name
    echo "\n".'<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang('artistname').':</span>';
    echo '<span class="fieldvalue_span centertext">'.kgCreateLink($artistdata['Name'],array('ACTON' => 'viewartist', 'artistid' => $artistid),get_lang('ReloadArtistData')).'</span>';
    echo '</div>';

    // Number of albums in database
    echo "\n".'<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang('AlbumsInDB').':</span>';
    echo '<span class="fieldvalue_span item_count_width centertext">'.$albumcnt.'</span>';
    echo '</div>';

    // Number of songs in database
    echo "\n".'<div class="fieldpair_div">';
    echo '<span class="fieldname_span">'.get_lang('SongsInDB').':</span>';
    echo '<span class="fieldvalue_span item_count_width centertext">'.$songcnt.'</span>';
    echo '</div>';

    echo "\n".'<div class="artist_pn_links_div">';
    if ($shownplinks === true) {
        // Show the Previous/Next artist links
        echo '<div class="prev_artist_link_div">'.$prevartist.'</div><div class="next_artist_link_div">'.$nextartist.'</div>';
    }
    echo '</div>';
    
    // Return to artist list
    echo '<div class="return_to_artist_list_div">'.kgCreateLink(get_lang('returntoartistlist')).'</div>';

    // Website and Wikipedia links
    echo "\n".'<div class="web_wiki_links_div">';
    if ($artistdata['Website'] != '') {
        // Add link to website
        echo '<div><a href="'.$artistdata['Website'].'" target="_blank">'.get_lang('websitelink').'</a></div>';
    }
    if ($artistdata['Wikipedia'] != '') {
        // Add link to Wikipedia Article
        echo '<div><a href="'.$artistdata['Wikipedia'].'" target="_blank">'.get_lang('wikipediaarticle').'</a></div>';
    }
    echo '</div>';

    echo '<div class="bio_art_btn_wrapper_div">';

    // Artist Bio
    if ($artistdata['Bio'] != '') {
        $bio = str_replace("\n\n", '<br><br>', $artistdata['Bio']);
        $bio = str_replace("\n", ' ', $bio);
        echo "\n".'<div class="artist_bio_wrapper_div"><div class="fieldpair_div">';
        echo '<span class="fieldname_span">'.get_lang('artistbio').':</span>';
        echo '<div class="artist_bio_data_div">'.$bio.'</div>';
        echo '</div></div>';
    } else {
?>
<script type="text/javascript">
function resizeAlbumArtRowDiv() {

    var row_height = $('#album_art_row_div').height();
    var browser_height = $(window).height();
    var browser_width = $(window).width();

    // The values subtracted from browser_height should really come from
    // the stylesheet to make this more dynamic.
    if (browser_width <= 800) {
        var new_height = browser_height - 75;
    } else {
        var new_height = browser_height - 70;
    }

    // Minimum height
    if (new_height < 407) {
        new_height = 407;
    }
    $('#album_art_row_div').css('height', new_height);

}

$(document).ready(function(){resizeAlbumArtRowDiv();});
</script>
<?php
    }

    echo "\n".'<div class="view_artist_button_row_div">';

    // Add album button
    $KG_SECURITY->hideMsgs();
    if ($KG_SECURITY->hasPermission('add') === true) {
        // Has permission
        $form->add_hidden(array('ACTON' => 'addalbum','artistid' => $artistid));
        $form->buttononlyform(kgGetScriptName(),'post','frmaddalbum',get_lang('addalbum'));
    }

    // Add song button
    $KG_SECURITY->hideMsgs();
    if ($KG_SECURITY->hasPermission('add') === true) {
        // Has permission
        $form->add_hidden(array('ACTON' => 'addsong','addvia' => 'artistpage','artistid' => $artistid));
        $form->buttononlyform(kgGetScriptName(),'post','frmaddsong',get_lang('addsong'));
    }

    // Edit artist button
    $KG_SECURITY->hideMsgs();
    if ($KG_SECURITY->hasPermission('edit') === true) {
        // Has permission
        $form->add_hidden(array('ACTON' => 'editartist','artistid' => $artistid));
        $form->buttononlyform(kgGetScriptName(),'post','frmeditartist',get_lang('editartistinfo'));
    }

    echo "</div><!-- END view_artist_button_row_div -->\n";

    echo "\n".'<div class="album_art_row_div" id="album_art_row_div">';

    if ($albumcnt > 0) {

        // Query to get album info for artists
        $albumquery = $dbc->select('Albums', 'ArtistID = '.$artistid, '', array('ORDER BY' => 'ReleaseDate ASC'));

        while ($row = $dbc->fetchAssoc($albumquery)) {
            // Display album info

            // Get list of song names for current album
            $songquery = $dbc->select('Lyrics', 'AlbumID = '.$row['AlbumID'], 'Name');
            $songcnt = $dbc->numRows($songquery);

            // Set string used for image title/alt text
            if ($songcnt > 0) {

                // Show album title and the name of all of the songs found
                $title = $row['Name'].' - ';

                while ($data = $dbc->fetchAssoc($songquery)) {
                    $title .= $data['Name'].', ';
                }

                // Remove trailing comma and space
                $title = substr($title,0,strlen($title)-2);

            } else {
                // No songs found for this album
                $title = $row['Name'];
            }

            echo '<div class="coverart_div" onclick="window.location=\''.kgCreateLink('',array('ACTON' => 'viewalbum', 'albumid' => $row['AlbumID'], 'NO_TAG' => 'NO_TAG'),$title).'\'">';

            // Display cover art if available
            if ($row['CoverArtFront'] != '') {

                // Get a bunch of URL's and <img> links for the coverart
                $imginfo = kgGetImageLink($row['CoverArtFront'], array('EXTRATEXT' => $title));

                if (array_key_exists('ImgTag',$imginfo)) {
                    // Cover art exists
                    echo $imginfo['ImgTagThumb'];
                } else {
                    // Cover art not found
                    echo $title;
                }

                echo '<br>'.$row['Name'];
            } else {
                // No cover art
                echo $title;
            }

            echo "</div>\n";

        }
        // END while ($row = $dbc->fetchAssoc($albumdata))

    } else {

        echo get_lang('NoAlbumsFound');

    }
    // END if ($albumcnt > 0)

    echo "</div><!-- END album_art_row_div -->\n";

    echo "</div><!-- END bio_art_btn_wrapper_div -->\n";

    /////
    // Check for orphaned songs
    $KG_SECURITY->hideMsgs();
    if ($KG_SECURITY->hasPermission('edit') === true) {
        $orphanquery = $dbc->select('Lyrics', array('AlbumID' => 0, 'ArtistID' => $artistid), array('SongID', 'Name'));

        if ($dbc->numRows($orphanquery) > 0) {

            // Display list of songs that are not associated with any album

            echo "\n".'<div class="orphaned_songs_main_div">';
            echo '<div class="orphaned_songs_header_div">'.get_lang('OrphanedSongs').'</div>';

            echo '<div class="row_div">';
            echo '<div class="orphaned_songs_column_name_div">'.get_lang('SongName').'</div><div class="orphaned_songs_column_name_div">'.get_lang('AssignToAlbum').'</div>';
            echo '</div>';

            while ($row = $dbc->fetchAssoc($orphanquery)) {
                // List each song

                
                echo '<div class="fieldpair_div">';
                echo '<span class="fieldname_span">'.kgCreateLink($row['Name'],array('ACTON' => 'viewsong', 'songid' => $row['SongID'])).':</span>';
                echo '<span class="fieldvalue_span">';
                $form->add_hidden(array(
                    'ACTON' => 'assignalbum',
                    'songid' => $row['SongID'],
                    'artistid' => $artistid
                ));
                $form->start_form(kgGetScriptName(),'post','frmassignalbum');
                $form->add_select_db_autoecho('albumid','SELECT AlbumID,Name from Albums WHERE ArtistID = '.$artistid.' ORDER BY Name','AlbumID',0,'Name',false);
                $table->new_cell();
                $form->add_button_submit(get_lang('Assign'));
                $form->end_form();

                echo '</span></div>';

            }
            // END while ($row = $dbc->fetchAssoc($orphanquery))

            echo '</div>';
        }
        // END if ($dbc->numRows($orphanquery) > 0)
    }
    // if ($KG_SECURITY->hasPermission('edit') === true)

}
// END function ViewArtist()
/////

function ViewSong() {
    /**
     * Shows info about the selected song.
     *
     * Added: 2017-06-24
     * Modified: 2017-06-24
     *
     * @param None
     *
     * @return None
    **/

    global $dbc, $valid, $KG_SECURITY;

    $form = new HtmlForm();
    $table = new HtmlTable();

    // Display errors if any
    $valid->displayErrors();

    $songid = $valid->get_value_numeric('songid', 0);

    if ($songid < 1) {
        echo '<center><b>'.get_lang('invalidsongid').'</b></center>';
        return;
    }

    // Song data
    $songdata = $dbc->fetchAssoc($dbc->select('Lyrics', 'SongID = '.$songid));

    // Artist data
    $artistdata = $dbc->fetchAssoc($dbc->select('Artists', 'ArtistID = '.$songdata['ArtistID']));

    // Album data
    $albumdata = $dbc->fetchAssoc($dbc->select('Albums', 'AlbumID = '.$songdata['AlbumID']));

    // Count number of songs for current album
    if ($songdata['AlbumID'] > 0) {
        $songcnt = $dbc->numRows($dbc->select('Lyrics', 'AlbumID = '.$songdata['AlbumID'], 'SongID'));
    } else {
        // This song has not been assigned to an album
        $songcnt = 1;
    }

    // -No need to create previous/next links unless there are at least
    //  2 songs for this album/artist combo in the database
    if ($songcnt > 1) {
        // Creates a array of all songs (using SongID) in the current album
        $query = $dbc->select('Lyrics', array('ArtistID' => $songdata['ArtistID'], 'AlbumID = '.$songdata['AlbumID']), 'SongID', array('ORDER BY' => 'TrackNumber ASC'));

        $tracklisting = array();
        while ($row = $dbc->fetchAssoc($query)) {
            array_push($tracklisting,$row['SongID']);
        }

        // -Find postion (key) of current song ID
        // -Array is zero (0) based
        $curkey = array_search($songid,$tracklisting);

        // Get previous/next song ID numbers
        if ($curkey == 0) {
            // $curkey at beginning of array
            $previd = $tracklisting[$songcnt-1];
            $nextid = $tracklisting[1];
        } elseif ($curkey == $songcnt-1) {
            // $curkey at end of array
            $previd = $tracklisting[$songcnt-2];
            $nextid = $tracklisting[0];
        } else {
            // $curkey some other position in array
            $previd = $tracklisting[$curkey-1];
            $nextid = $tracklisting[$curkey+1];
        }
    }

    // Let the mysqlnd library decide which server to use
    $dbc->mysqlndSwitch();

    // Start the table
    $table->new_table('fullwidth');

    // Album art
    $table->set_colspan(2);
    $table->new_row();
    $table->new_cell();
    // Display cover art if available
    if ($albumdata['CoverArtFront'] != '') {
        $imginfo = kgGetImageLink($albumdata['CoverArtFront']);
        if (array_key_exists('ImgTag',$imginfo) === true) {
            // Cover art exists
            echo '<div class="coverart_viewsong_div">'.kgCreateLink($imginfo['ImgTagThumb'],array('ACTON' => 'viewalbum', 'albumid' => $albumdata['AlbumID'])).'</div>';
        } else {
            // Cover art not found
            echo kgCreateLink($albumdata['Name'],array('ACTON' => 'viewalbum', 'albumid' => $albumdata['AlbumID'])).'<br /><br />('.get_lang('NoCoverArt').')';
        }
    } else {
        // No cover art, show album name
        echo kgCreateLink($albumdata['Name'],array('ACTON' => 'viewalbum', 'albumid' => $albumdata['AlbumID'])).'<br /><br />('.get_lang('NoCoverArt').')';
    }

    // Song lyrics
    $table->set_rowspan(10);
    $table->new_cell('lyrics_cell');
    if ($songdata["Lyrics"] != '') {
        $lyrics = str_replace("\n", '<br>', $songdata["Lyrics"]);
        echo '<div class="lyrics_div">'.$lyrics.'</div>';
    } else {
        echo '&nbsp;';
    }

    // Song Name
    $table->new_row();
    $table->new_cell('viewsong_leftcolumn_cell');
    echo get_lang('songname');
    $table->new_cell('viewsong_rightcolumn_cell');
    echo $songdata['Name'];

    // Artist Name
    $table->new_row();
    $table->new_cell();
    echo get_lang('artistname');
    $table->new_cell();
    echo kgCreateLink($artistdata['Name'],array('ACTON' => 'viewartist', 'artistid' => $songdata['ArtistID']));

    // Album Name
    $table->new_row();
    $table->new_cell();
    echo get_lang('albumname');
    $table->new_cell();
    echo kgCreateLink($albumdata['Name'],array('ACTON' => 'viewalbum', 'albumid' => $albumdata['AlbumID']));

    // Track Number
    $table->new_row();
    $table->new_cell();
    echo get_lang('tracknumber');
    $table->new_cell();
    echo $songdata['TrackNumber'];

    // Track Time
    $table->new_row();
    $table->new_cell();
    echo get_lang('tracktime');
    $table->new_cell();
    if (($songdata['TrackMinutes'] < 0) && ($songdata['TrackSeconds'] < 0)) {
        echo get_lang('Unknown');
    } else {
        if ($songdata['TrackMinutes'] < 0) {
            $m = 0;
        } else {
            $m = $songdata['TrackMinutes'];
        }
        if ($songdata['TrackSeconds'] < 0) {
            $s = 0;
        } else {
            $s = $songdata['TrackSeconds'];
        }
        echo $m.':'.sprintf("%02d",$s);
    }

    // Genre
    $table->new_row();
    $table->new_cell();
    echo get_lang('genre');
    $table->new_cell();
    if ($songdata['GenreID'] > 0) {
        echo genreIdToName($songdata['GenreID']);
    } else {
        echo get_lang('notset');
    }

    // Volume
    $table->new_row();
    $table->new_cell();
    echo get_lang('volume');
    $table->new_cell();
    if ($songdata['volume'] > -1) {
        echo $songdata['volume'].'%';
    } else {
        echo get_lang('notset');
    }

    $table->blank_row(2);

    // Update/Edit button
    $KG_SECURITY->hideMsgs();
    if ($KG_SECURITY->hasPermission('edit') === true) {
        // Has permission
        $table->new_row();
        $table->new_cell('centertext');
        $form->add_hidden(array(
            'ACTON' => 'editsong',
            'songid' => $songid
        ));
        $form->buttononlyform(kgGetScriptName(),'post','frmedit',get_lang('UpdateEdit'));

        $table->blank_cell();

    } else {
        $table->blank_row(2);
    }

    $table->blank_row(3);

    $table->new_row();

    // Prev/Next Song links
    if ($songcnt > 1) {
        // Only display if there are at least 2 songs in the database for this album
        $table->set_colspan(2);
        $table->new_cell();
        echo kgCreateLink(get_lang('prevsong'), array('ACTON' => 'viewsong', 'songid' => $previd, 'USE_CSS' => 'href_to_button_small_one_row'), get_lang('prevsong'));
        echo kgCreateLink(get_lang('nextsong'), array('ACTON' => 'viewsong', 'songid' => $nextid, 'USE_CSS' => 'href_to_button_small_one_row'), get_lang('nextsong'));
    } else {
        $table->blank_cell(2);
    }

    // Return to artist list
    $table->new_cell('centertext');
    echo kgCreateLink(get_lang('returntoartistlist'), array('USE_CSS' => 'href_to_button_small_one_row'));

    $table->end_table();

}
// END function ViewSong()
/////

if ($dbc->isConnectedDB() === true) {

    $ACTON = $valid->get_value('ACTON');

    if ($ACTON == 'addartist') {
        // Add a new artist to the database

        AddArtist();

    } elseif ($ACTON == 'addalbum') {
        // Add a new album to the database

        AddAlbum();

    } elseif ($ACTON == 'addsong') {
        // Add a new song to the database

        //AddSong();
        AddEditSong(true);

    } elseif ($ACTON == 'assignalbum') {
        // Set album id for a song that didn't have one

        AssignAlbum();

        ViewArtist();

    } elseif ($ACTON == 'deletealbum') {
        // Delete a song from the database

        DeleteAlbum();

        ViewArtist();

    } elseif ($ACTON == 'deletealbumart') {
        // Removes info about the selected album art from
        // the database and any related files from the server

        DeleteAlbumArt();

        if ($valid->get_value('dowhat') == 'editalbum') {
            EditAlbum();
        } else {
            AddAlbum();
        }

    } elseif ($ACTON == 'deleteartist') {
        // Delete an artist AND all albums and songs from the database

        $rv = DeleteArtist();

        if ($rv) {
            ListArtists();
        }

    } elseif ($ACTON == 'deletegenre') {
        // Remove a genre from the database

        DeleteGenre();

        EditGenres();

    } elseif ($ACTON == 'deletesong') {
        // Delete a song from the database

        DeleteSong();

        ViewAlbum();

    } elseif ($ACTON == 'editalbum') {
        // Edit info about an existing album in the database

        EditAlbum();

    } elseif ($ACTON == 'editartist') {
        // Edit info about an existing artist in the database

        EditArtist();

    } elseif ($ACTON == 'editgenres') {
        // Edit list of genres

        EditGenres();

    } elseif ($ACTON == 'editsong') {
        // Edit an existing song

        //EditSong();
        AddEditSong(false);

    } elseif ($ACTON == 'orphancheckalbums') {
        // Check for orphaned albums and songs

        OrphanCheckAlbums();

    } elseif ($ACTON == 'renamegenre') {
        // Rename a genre

        RenameGenre();

    } elseif ($ACTON == 'runsearch') {
        // Run the search against the database

        RunSearch();

    } elseif ($ACTON == 'savechangesalbum') {
        // Save changes made to an album

        $rv = SaveChangesAlbum();

        if ($rv) {
            ViewAlbum();
        } else {
            EditAlbum();
        }

    } elseif ($ACTON == 'savechangesartist') {
        // Save changes made to an artist

        SaveChangesArtist();

        ViewArtist();

    } elseif ($ACTON == 'savechangesgenre') {

        SaveChangesGenre();

        EditGenres();

    } elseif ($ACTON == 'savechangessong') {
        // Save changes made to a song

        $rv = SaveChangesSong();

        if ($rv) {
            ViewSong();
        } else {
            AddEditSong(false);
        }

    } elseif ($ACTON == 'savegenre') {
        // Add a new genre to the database

        SaveGenre();

        EditGenres();

    } elseif ($ACTON == 'savenewalbum') {
        // Add a new album to the database

        SaveNewAlbum();

        ViewAlbum();

    } elseif ($ACTON == 'savenewartist') {
        // Add a new artist to the database

        SaveNewArtist();

        ViewArtist();

    } elseif ($ACTON == 'savenewsong') {
        // Add a song to the database

        $rv = SaveNewSong();

        if ($rv) {
            ViewSong();
        } else {
            AddEditSong(false);
        }

    } elseif ($ACTON == 'searchform') {
        // Display the search form

        SearchForm();

    } elseif ($ACTON == 'viewartist') {
        // Display artist info and albums

        ViewArtist();

    } elseif ($ACTON == 'viewalbum') {
        // View album info and song lists

        ViewAlbum();

    } elseif ($ACTON == 'viewsong') {
        // View song details

        ViewSong();

    } else {
        // List all of the artists in the database

        ListArtists();

    }
    // END if ($ACTON == 'addartist')
} // END if ($dbc->isConnectedDB() === true)
?>
