<!doctype html>
<html>
<head>
    <title>Kravens Web Interface v1.0</title>

    <!-- Load the main CSS stylesheet -->
    <link rel="stylesheet" href="/skins/css/main.css" />

    <!-- Load the menu CSS stylesheet -->
    <link rel="stylesheet" href="/skins/css/menu.css" />

    <!-- jQuery javascript and CSS files -->
    <link rel="stylesheet" href="/includes/js/jquery/themes/<?php echo $kgJqueryTheme; ?>/jquery-ui.css">
    <script src="/includes/js/jquery/external/jquery/jquery.js"></script>
    <script src="/includes/js/jquery/jquery-ui.min.js"></script>

    <!-- Custom javascript -->
    <script src="/includes/js/custom.js"></script>

<?php
    // Module specific javascript/jquery code
    $incfile = $IP.'/'.$modpath.'/'.$KG_MODULE_NAME.'/module_javascript.inc';
    $link = $RP.$modpath.'/'.$KG_MODULE_NAME.'/module_javascript.inc';
    if (is_file($incfile)) {
        include $link;
    }

    echo "\n".'<style type="text/css" media="all">';

    // Load the main PHP enhanced CSS stylesheet
    include $RP.'skins/css/main.css.php';

    echo "\n";

    // Load the menu's PHP enhanced CSS stylesheet
    include $RP.'skins/css/menu.css.php';

    echo "</style>\n";

    // Load the modules CSS stylesheet
    // $modpath comes from index.php
    $cssfile = $IP.'/'.$modpath.'/'.$KG_MODULE_NAME.'/module_main.css';
    $link = $RP.$modpath.'/'.$KG_MODULE_NAME.'/module_main.css';
    if (is_file($cssfile)) {
        echo '<link rel="stylesheet" href="'.$link.'">'."\n";
    }

    // Load the modules PHP enhanced CSS stylesheet
    $cssfile = $IP.'/'.$modpath.'/'.$KG_MODULE_NAME.'/module_main.css.php';
    $link = $RP.$modpath.'/'.$KG_MODULE_NAME.'/module_main.css.php';
    if (is_file($cssfile)) {
        echo "\n".'<style type="text/css" media="all">';
        include $link;
        echo "</style>\n";
    }
?>
</head>
<body>
