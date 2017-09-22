

.receipe_link_div {
    background-color: <?php echo $skin_colors['href_button_background']; ?>;
    color: <?php echo $skin_colors['text_color_primary']; ?>;
}

.newGroupTable {
    border: 1px solid <?php echo $colors['main_table_border']; ?>;
    padding: 4px 4px 4px 4px;
}

.detailswrapper {
    /* Place holder */
}

.ingredientsdiv div + div, .infodiv div + div {
    margin-top: 10px;
}

.ingredientsdiv {
    width: 308px;
    float: left;
}

.grouppadding {
    margin-left: 25px;
}

.grouphighlight {
    border: 1px solid <?php echo $colors['main_table_border']; ?>;
    background-color: <?php echo $colors['cell_background']; ?>;
    padding: 8px;
    width: 270px;
}

.directionsdiv {
    border: 1px solid <?php echo $colors['main_table_border']; ?>;
    width: 800px;
    min-height: 200px;
    max-height: 300px;
    overflow-x:auto;
    white-space:wrap;
    float: left;
    padding: 4px;
}

.infodiv {
    width: 410px;
    float: right;
}

.notesdiv, .servingsdiv, .preptimediv, .categoriesdiv {
    border: 1px solid <?php echo $colors['main_table_border']; ?>;
    background-color: <?php echo $colors['cell_background']; ?>;
    padding: 4px;
}

.tiptablerows {
    border: 2px solid <?php echo $colors['main_table_border']; ?>;
}

.tipname {
    background-color: <?php echo $colors['cell_background']; ?>;
    text-align: center;
}

.tipcontents {
    padding: 6px;
    background-color: <?php echo $colors['cell_alternate']; ?>;
}
