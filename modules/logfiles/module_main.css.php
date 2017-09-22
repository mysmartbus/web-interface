
/***********/
/* AddEdit */

.note {
    color: <?php echo $skin_colors['note_text']; ?>;
}

.ae_form_status_red_div {
    background-color: #c50000; /* RGB: 197, 0, 0 */
}

.ae_form_status_green_div {
    background-color: #00C800; /* RGB: 0, 200, 0 */
}

.ae_location_div #locationname {
    border: 1px solid <?php echo $skin_colors['border_highlight']; ?>;
}

/* BEGIN Lean Modal */
#lean_overlay {
    position: fixed;
    z-index: 500;
    top: 0px;
    left: 0px;
    height: 100%;
    width: 100%;
    background: #000;
    display: none;
}

#setlocation {
    width: 404px;
    padding-bottom: 2px;
    display: none;
    background: #FFF;
    border-radius: 5px;
    -moz-border-radius: 5px;
    -webkit-border-radius: 5px;
    box-shadow: 0px 0px 4px rgba(0,0,0,0.7);
    -webkit-box-shadow: 0 0 4px rgba(0,0,0,0.7);
    -moz-box-shadow: 0 0px 4px rgba(0,0,0,0.7);
}

#setlocation-header {
    background: url(<?php global $kgSkin; echo $kgSkin.'/images/hd-bg.png'; ?>);
    padding: 18px 18px 14px 18px;
    border-bottom: 1px solid #CCC;
    border-top-left-radius: 5px;
    -moz-border-radius-topleft: 5px;
    -webkit-border-top-left-radius: 5px;
    border-top-right-radius: 5px;
    -moz-border-radius-topright: 5px;
    -webkit-border-top-right-radius: 5px;
}

#setlocation-header .headertext {
    color: #444;
    font-size: 2em;
    font-weight: 600;
    margin-bottom: 3px;
    text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);
}

#setlocation select {
    width: 244px;
    margin-left: auto;
    margin-right: auto;
    padding: 8px;
    border-radius: 4px;
    -moz-border-radius: 4px;
    -webkit-border-radius: 4px;
    font-size: 1.4em;
    color: #222;
    background: #F7F7F7;
    font-family: "Helvetica Neue";
    outline: none;
    border-top: 1px solid #CCC;
    border-left: 1px solid #CCC;
    border-right: 1px solid #E7E6E6;
    border-bottom: 1px solid #E7E6E6;
}
        
#setlocation .modal_close {
    position: absolute;
    top: 12px;
    right: 12px;
    display: block;
    width: 14px;
    height: 14px;
    background: url(<?php global $kgSkin; echo $kgSkin.'/images/modal_close.png'; ?>);
    z-index: 9000;
}
/* LEAN MODAL */
/* END */

/**************/
/* Month view */

.mv_grid, .mv_grid td {
    border: 1px solid <?php echo $skin_colors['border']; ?>;
}

.mv_mini, .mv_mini td {
    border: 1px solid <?php echo $skin_colors['border']; ?>;
}

.titlecell {
    border: 1px solid <?php echo $skin_colors['border']; ?>;
}

.tableborder {
    border: 1px solid <?php echo $skin_colors['border']; ?>;
}

.tabtable {
    border: 1px solid <?php echo $skin_colors['border']; ?>;
    border-collapse: collapse;
    width: 100%;
}

.tabselected {
    border-right: 0px !important;
    background-color: #3a3a3a;
    border: 1px solid <?php echo $skin_colors['border']; ?>;
}

.tabnotselected {
    border: 1px solid <?php echo $skin_colors['border']; ?>;
}

.tabsizer {
    width: 150px;
    height: 75px;
}
