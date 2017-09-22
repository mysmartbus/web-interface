<?php
// PHP enhanced style sheet
?>

body {
    background-color: <?php echo $skin_colors['body_background']; ?>;
    color: <?php echo $skin_colors['text_color_primary']; ?>;
}

a:link {
    color: <?php echo $skin_colors['a_link']; ?>;
}

a:visited {
    color: <?php echo $skin_colors['a_link_visited']; ?>;
}

a:hover {
    color: <?php echo $skin_colors['a_link_hover']; ?>;
}

.href_to_button, .href_to_button_small, .href_to_button_one_row, .href_to_button_small_one_row {
    background-color: <?php echo $skin_colors['href_button_background']; ?>;
    color: <?php echo $skin_colors['text_color_primary']; ?> !important;
}
