<?php
/**
 * Creates the websites main navigation menu
 *
 * Added: 2017-06-04
 * Modified: 2017-07-10
**/

function createMenuLink($moduledata) {
    /**
     * Creates the URLs used by the menu
     *
     * @param Required array $moduledata This info comes from the modules .info.php file
     *
     * @return String
    **/

    global $RP;

    if (array_key_exists('modulename', $moduledata)) {
        $module = str_replace(' ', '', strtolower($moduledata['modulename']));

        $link = '<a href="'.$RP.'index.php?KG_MODULE_NAME='.$module.'" class="href_to_button">'.$moduledata['modulename'].'</a>';

        return $link;
    }
}
// END function createMenuLink()
/////

if ($KG_MODULE_NAME == 'menu') {
    // Show the full menu

    // Get list of modules
    $menuitems = kgFilterFileList($IP.'/modules/', '.info.php', 1);

    // Sort array in alphabetical order
    sort($menuitems);

    if (count($menuitems) > 0) {

        // Generate the module links
        foreach ($menuitems as $key => $item) {

            // This is where $moduledata comes from
            require $RP.'modules/'.explode('.', $item)[0].'/'.$item;

            // Only create a link to the module if it sets the $moduledata variable
            if (isset($moduledata)) {
                echo createMenuLink($moduledata);

                // This prevents a module from being listed multiple times
                // if the module(s) after it do not properly set $moduledata.
                unset($moduledata);
            }
        }
    } else {
        echo '<center><b>'.get_lang('nomodulesfound').'</b></center>';
    }

    echo "\n".'<div class="special_button_div">';

    // Display the login/logout button
    if ($KG_SECURITY->isLoggedIn()) {
        echo '<a href="'.$RP.'index.php?KG_MODULE_NAME=logout" class="href_to_button">'.get_lang('logout').'</a>';
    } else {
        echo '<a href="'.$RP.'index.php?KG_MODULE_NAME=login" class="href_to_button">'.get_lang('login').'</a>';
    }

    if ($KG_SECURITY->isLoggedIn()) {

        // Display link to users control panel
        require $RP.'includes/systemmodules/usercontrolpanel/usercontrolpanel.info.php';
        echo '<a href="'.$RP.'index.php?KG_MODULE_NAME=usercontrolpanel" class="href_to_button">'.$moduledata['modulename'].'</a>';

        if ($KG_SECURITY->isAdmin()) {
            // Link to user management
            require $RP.'includes/systemmodules/usermanagement/usermanagement.info.php';
            echo '<a href="'.$RP.'index.php?KG_MODULE_NAME=usermanagement" class="href_to_button">'.$moduledata['modulename'].'</a>';
        }
    }

    echo '</div><!-- END special_button_div -->';

} else {
    // Menu icons only
    // Some other module has been loaded

    echo '<div class="icon_only_div">';

    // Show the main menu icon
    echo '<a href="'.$RP.'index.php?module=menu" title="Return to main menu"><div class="menu_white_bar"></div><div class="menu_clear_bar"></div><div class="menu_white_bar"></div><div class="menu_clear_bar"></div><div class="menu_white_bar"></div></a>';

    if ($KG_SECURITY->isLoggedIn()) {
        // Link to users control panel
        echo '<div class="users_control_panel_icon_div"><a href="'.$RP.'index.php?KG_MODULE_NAME=usercontrolpanel"><img class="users_control_panel_icon_img"></a></div>';

        if ($KG_SECURITY->isAdmin()) {
            // Link to administrators control panel
            echo '<div class="admin_control_panel_icon_div"><a href="'.$RP.'index.php?KG_MODULE_NAME=admincontrolpanel"><img class="admin_control_panel_icon_img"></a></div>';
        }
    }

    echo "\n</div><!-- END icon_only_div -->";
}
?>
