<?php
// Character encoding to use
header("Content-Type: text/html; charset=UTF-8");

require 'includes/_StartHere.php';

$KG_MODULE_NAME = $valid->get_value('KG_MODULE_NAME', 'menu');

$systemmodules = kgGetDirlist($IP.'/includes/systemmodules/');

// Set path to modules folder
if (in_array($KG_MODULE_NAME, $systemmodules)) {
    $modpath = 'includes/systemmodules';
} else {
    $modpath = 'modules';
}

require 'includes/_PageTop.php';

if ($KG_MODULE_NAME != '') {

    $module_path = $modpath.'/'.$KG_MODULE_NAME.'/'.$KG_MODULE_NAME.'.php';
    $module_info_path = $modpath.'/'.$KG_MODULE_NAME.'/'.$KG_MODULE_NAME.'.info.php';

    // Load the .info.php file for the module if it exists
    if (is_file($IP.'/'.$module_info_path)) {
        require $RP.$module_info_path;

        // Load a language array if possible.
        // Module will not display correctly if no language array is found.
        if (array_key_exists($user_language, $moduledata['Lang']) && is_array($moduledata['Lang'][$user_language])) {
            // Load the language array for the users chosen language
            $lang[$KG_MODULE_NAME] = array();
            $lang[$KG_MODULE_NAME] = array_merge($lang[$KG_MODULE_NAME], $moduledata['Lang'][$user_language]);
        } elseif (array_key_exists($kgDefaultLanguage, $moduledata['Lang']) && is_array($moduledata['Lang'][$kgDefaultLanguage])) {
            // Users chosen language not available so load the language specified in DefaultSettings.php
            $lang[$KG_MODULE_NAME] = array();
            $lang[$KG_MODULE_NAME] = array_merge($lang[$KG_MODULE_NAME], $moduledata['Lang'][$kgDefaultLanguage]);
        } else {
            // No language pack available
            $valid->addError(get_lang('nolanguagearraysfound'));
        }
    }

    echo '<div class="page_div">'."\n";

    if ($KG_MODULE_NAME != 'menu' && $KG_MODULE_NAME != 'login' && $KG_MODULE_NAME != 'logout') {
        // Add the menu icon to the upper left corner of each page
        require $RP.'includes/systemmodules/menu/menu.php';
    }

    echo "\n".'<div class="page_content">';
    if (is_file($IP.'/'.$module_path)) {
        require $module_path;
    } else {
        echo '<center><b>'.get_lang('modulenotfound').'</b></center>';
    }
    echo "\n</div><!-- END page_content -->";

    echo "\n</div><!-- END page_div -->";
} else {
    echo 'No module specified,';
}

require 'includes/_PageBottom.php';

?>
