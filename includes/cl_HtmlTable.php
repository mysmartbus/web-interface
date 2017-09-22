<?php
/**
 * HTML table formatting stuff
 *
 * Added: 2017-06-10
 * Modified: 2017-06-10
**/

class HtmlTable {

    // These variables will have default values assigned by $this->reset_table();
    private $_cellopen;
    private $_celltitledata;
    private $_colspan;
    private $_height;
    private $_hpp;
    private $_newid;
    private $_onclickdata;
    private $_onmouseoverdata;
    private $_rowopen;
    private $_rowspan;
    private $_width;
    private $_wpp;

    function __construct() {
        // Set default values
        $this->reset_table();
    }

    function blank_cell($num=1, $css='', $extras='') {
        /**
         * Add a blank cell to the table
         *
         * Added: 20??-??-??
         * Modified: 2017-03-25
         *
         * @param Optional integer $num Number of blank cells to add
         * @param Optional string $css The CSS class to apply to all of the cells
         * @param Optional string $extras Use this to override the CSS from $css
         *
         * @return Nothing
        **/
        $this->end_cell();

        if (!is_numeric($num)) {
            $num = 1;
        }

        for($i=1;$i<=$num;$i++) {
            $this->new_cell($css,$extras);
            echo '&nbsp;';
        }
    }

    function blank_row($blankcells=2,$css='') {
        // Added 2013-05-27
        $this->new_row();
        $this->blank_cell($blankcells,$css);
        $this->end_row();
    }

    function end_cell() {
        if ($this->_cellopen) {
            echo "</td>\n";
            $this->_cellopen = false;
        }
    }

    function end_row() {
        if ($this->_cellopen) {
            $this->end_cell();
        }
        echo "</tr>\n";
        // Set to 0 so our 4x4 table doesn't have 16 columns
        $this->_rowopen = false;
    }

    function end_table() {
        // Close any open table cells
        if ($this->_cellopen) {
            $this->end_cell();
        }
        // Close any open table rows
        if ($this->_rowopen) {
            $this->end_row();
        }
        ?></table><?php
        echo "\n";

        $this->reset_table();
    }

    public function new_cell($css = '', $extras='') {
        /**
         * A CSS formated table cell
         *
         * You can still use colspan and rowspan but their values
         * will need to be set using $this->set_colspan(), $this->set_rowspan()
         * or the $extras argument
         *
         * @param Optional string $css Name of the CSS class(es) to use
         * @param Optional string $extras Can be used to provide extra formatting
         *                               styles or to override parts of $css
         *
         * @return Nothing
        **/

        $opts = '';

        if ($css == '') {
            $cssfirst = true;
        } else {
            $cssfirst = false;
        }

        // Close the previous cell if needed
        if ($this->_cellopen) {
            $this->end_cell();
        }

        // Check if column span was set via $this->set_colspan()
        if ($this->_colspan > 1) {
            $opts .= ' colspan="'.$this->_colspan.'"';

            $this->_colspan = 0;
        }

        // Check if row span was set via $this->set_rowspan()
        if ($this->_rowspan > 1) {
            $opts .= ' rowspan="'.$this->_rowspan.'"';

            $this->_rowspan = 0;
        }

        // Check if width was set via $this->set_width()
        if ($this->_width > 0) {
            $opts .= ' width="'.$this->_width.$this->_wpp.'"';

            $this->_width = 0;
            $this->_wpp = '';
        }

        // Check if height was set via $this->set_height()
        if ($this->_height > 0) {
            $opts .= ' height="'.$this->_height.$this->_hpp.'"';

            $this->_height = 0;
            $this->_hpp = '';
        }

        if ($this->_celltitledata != '') {
            $opts .= ' title="'.$this->_celltitledata.'"';

            $this->_celltitledata = '';
        }

        if ($this->_newid != '') {
            $opts .= ' id="'.$this->_newid.'"';

            $this->_newid = '';
        }

        if ($this->_onclickdata != '') {
            $opts .= ' onclick="'.$this->_onclickdata.'"';

            $this->_onclickdata = '';
        }

        if ($this->_onmouseoverdata != '') {
            $opts .= ' onmouseover="'.$this->_onmouseoverdata.'"';

            $this->_onmouseoverdata = '';
        }

        if ($css != '') {
            $opts = ' class="'.$css.'"'.$opts;
        }

        if ($extras != '') {
            // Allows inline styles to override formatting in the CSS stylesheet
            $opts .= ' '.$extras;
        }

        echo '<td'.$opts.'>';
        $this->_cellopen = true;
    }

    public function new_row($css = '', $extras='') {
        /**
         * A CSS formated table row
         *
         * @param Optional string $css Name of the CSS class to use
         * @param Optional string $extras Can be used to provide extra formatting
         *                               styles or to override parts of $css
         *
         * @return
        */

        if ($this->_cellopen) {
            $this->end_cell();
        }

        if ($this->_rowopen) {
            $this->end_row();
        }

        if ($this->_newid != '') {
            $extras .= ' id="'.$this->_newid.'"';

            $this->_newid = '';
        }

        echo '<tr'.($css != '' ? ' class="'.$css.'"' : '').($extras != '' ? ' '.$extras : '').'>';
        $this->_rowopen = true;
    }

    public function new_table($css = '', $extras='') {
        /**
         * CSS formatted table
         *
         * Added: 2017-05-13
         * Modified: 2017-05-13
         *
         * @param Optional string $css Name of the CSS class to use
         * @param Optional string $extras Can be used to provide extra formatting
         *                               styles or to override parts of $css
         *
         * @return A completed opening <table> tag
        */

        $opts = '';

        if ($css != '') {
            $css = ' class="'.$css.'"';
        }

        if ($this->_newid != '') {
            $opts = ' id="'.$this->_newid.'"';
            $this->_newid = '';
        }

        // Set table width
        if ($this->_width > 0) {
            $opts .= ' width="'.$this->_width.$this->_wpp.'"';

            $this->_width = 0;
            $this->_wpp = '';
        }

        if ($extras != '') {
            $opts .= ' '.$extras;
        }

        echo "\n".'<table'.$css.$opts.'>';
    }

    public function set_celltitle($data='') {
        /**
         * Data to display when mouse cursor is over a table cell
         *
         * Added: 201?-??-?
         * Modified: 2017-05-13
         *
         * @param Optional string $data The text to put in the title="" attribute
         *                              If blank (''), will clear anything set previously
        **/
        if ($data != '') {
            $this->_celltitledata = htmlentities($data);
        } else {
            $this->_celltitledata = '';
        }
    }

    public function set_colspan($colspan=0) {
        /**
         * Number of columns for the cell to span
         *
         * Added: 201?-??-?
         * Modified: 2017-05-21
         *
         * @param Required integer $colspan Column count
        **/
        if (is_numeric($colspan) && $colspan > 1) {
            $this->_colspan = $colspan;
        }
    }

    public function set_id($id) {
        /**
         * Normally, the HTML tag id="" attribute is set to the field name.
         * Use this function to override that value.
         *
         * Removes everything except a-z, A-Z and 0-9
         *    Example in: This i_s a t0est[]
         *    Example out: Thisisat0est
         * 
         * Added: 201?-??-?
         * Update: 2017-03-08
         *
         * @param Required string $id The string to be formatted for use in the id=""
         *                            attribute of a form field
         *
         * @return string $id
        **/
        $this->_newid = preg_replace("/[^a-zA-Z0-9]/", '', $id);
    }

    public function set_height($height, $hpp) {
        /**
         * Height of the table or cell in pixels or as a percentage
         * 
         * Added: 201?-??-??
         * Update: 2017-05-13
         *
         * @param Required integer $height The height of the item
         * @param Required string $height Must be either 'px' or '%'
         *
         * @return Nothing
        **/
        if (is_numeric($height) && $height > 0) {
            $hpp = strtolower($hpp);
            if ($hpp == 'px' || $hpp == '%') {
                $this->_height = abs($height);
                $this->_hpp = $hpp;
            }
        }
    }

    public function set_onclick($data='') {
        /**
         * Specify javascript to run when a table cell is clicked on
         *
         * Added: 201?-??-??
         * Updated: 2017-03-05
         *
         * @param Required string $data The javascript code or function to run
         *
         * @return Nothing
        **/

        if ($data != '') {
            $this->_onclickdata = $data;
        }
    }

    public function set_onmouseover($data='') {
        /**
         * Specify javascript to run when the mouse cursor hovers over something
         *
         * Added: 201?-??-??
         * Updated: 2017-03-08
         *
         * @param Required string $data The javascript code or function to run
         *
         * @return Nothing
        **/

        if ($data != '') {
            $this->_onmouseoverdata = $data;
        }
    }

    function set_rowspan($rowspan=0) {
        /**
         * Number of rows for the cell to span
         *
         * Added: 201?-??-??
         * Updated: 2017-05-13
         *
         * @param Required integer $rowspan Row count
         *
         * @return Nothing
        **/

        if (is_numeric($rowspan) && $rowspan > 1) {
            $this->_rowspan = $rowspan;
        }
    }

    public function set_width($width,$wpp) {
        /**
         * Width of the table or cell in pixels or as a percentage
         * 
         * Added: 201?-??-?
         * Update: 2017-05-13
         *
         * @param Required integer $width The width of the item
         * @param Required string $wpp Must be either 'px' or '%'
         *
         * @return Nothing
        **/
        if (is_numeric($width) && ($width > 0)) {
            $wpp = strtolower($wpp);
            if (($wpp == 'px') || ($wpp == '%')) {
                $this->_width = $width;
                $this->_wpp = $wpp;
            }
        }
    }

    function reset_table() {
        // Set default values
        $this->_cellopen = false;
        $this->_celltitledata = '';
        $this->_colspan = 0;
        $this->_height = 0;
        $this->_hpp = '';
        $this->_newid = '';
        $this->_onclickdata = '';
        $this->_onmouseoverdata = '';
        $this->_rowopen = false;
        $this->_rowspan = 0;
        $this->_width = 0;
        $this->_wpp = '';
    }
}
?>
