<?php
/**
 * Retreives the localized language text
 *
 * Added: 2013-??-??
 * Modified: 2015-012-13
**/

function get_lang($desc) {
    // Search the $lang array for matching key and return value

    global $lang, $KG_MODULE_NAME;

    // First search the module specific language array
    if (!empty($lang[$KG_MODULE_NAME][$desc])) {
        return $lang[$KG_MODULE_NAME][$desc];
    }

    // If not found check the global language array
    if (!empty($lang["global"][$desc])) {
        return $lang["global"][$desc];
    }

    // No match found
    return '';
}
?>
