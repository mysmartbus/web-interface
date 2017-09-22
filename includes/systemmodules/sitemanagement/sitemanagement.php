<?php
// Last Updated: 2017-01-20
// Uses servers main database
$db = $dbc_main->connectDatabase($dbc_main->getMainDatabase(), 0.66);

function DisplayForm() {

    global $dbc_main, $valid, $kgSiteSettings, $IP;

    $table = new HtmlTable();
    $form = new HtmlForm();

    // Check for warnings
    if ($valid->is_warning() === true) {
        $valid->warnings_table();
    }
    // Check for errors
    if ($valid->is_error() === true) {
        $valid->errors_table();
    }

    $moduledir = $IP.'/usermodules';

    // Create a list of all of the user modules
    $modulelist = kgFilterFileList($moduledir,'.php',true);

    foreach ($modulelist as $key => $value) {
        $module_name = str_replace('.php','',$value);
        if (is_readable($moduledir.'/info/'.$module_name.'.info.php')) {
            require $moduledir.'/info/'.$module_name.'.info.php';

            $list[$module_name] = $data['MenuName'];
        }
    }

    // Alphabetical sort by $data['ModuleName']
    asort($list);

    $matchme = $kgSiteSettings['DefaultModule'];

    echo '<center><b>'.get_lang('SitePreferences').'</b></center><br/>';

    // Start the form
    $form->add_hidden(array(
        'ACTON' => 'savesettings'
    ));
    $form->onsubmit('return validate();');
    $form->start_form(kgGetScriptName(),'post','frmsitesettings');

    $table->set_width(490,'px');
    $table->new_table_css('centered');

    $table->new_row();
    $table->set_width(150,'px');
    $table->new_cell();
    echo get_lang('DefaultModule');
    $table->new_cell();
    $form->add_select_match_key('defaultmodule',$list,$matchme);

    $table->new_row();
    $table->blank_cell();
    $table->new_cell();
    echo '<span class="settingnote">'.get_lang('DefaultModuleNote').'</span>';

    $table->blank_row();

    $table->new_row();
    $table->new_cell();
    echo get_lang('DefaultLanguage');
    $table->new_cell();
    $form->add_select_match_key('defaultlanguage',kgGetLangList());

    $table->new_row();
    $table->blank_cell();
    $table->new_cell();
    echo '<span class="settingnote">'.get_lang('DefaultLanguageNote').'</span>';

    $table->blank_row();

    $table->new_row();
    $table->new_cell();
    $form->add_button_submit(get_lang('Save'));
    $table->new_cell();
    $form->add_button_reset();

    $table->end_table();

    $form->end_form();

    echo '
<script type="text/javascript">
    var dm = document.getElementById("defaultmodule");

    // Adds the "None" option to the module list
    dm.options.add(new Option("'.get_lang('None').'", ""), dm.options[0]);

    // Figure out which option to select
    var selectme = 0;
    for(var i = 0;i < dm.length;i++){
        if(dm.options[i].value == "'.$matchme.'" ){
            selectme = i;
        }
    }
    //
    // Select that option
    dm.selectedIndex = selectme;
</script>
';

}
// END function DisplayForm()
/////

function SaveSettings() {

    global $dbc_main, $valid;

    $updatequery = array(
        'DefaultModule' => $valid->get_value('defaultmodule'),
        'Language' => $valid->get_value('defaultlanguage')
    );

    if (!$dbc_main->update('Settings', '', $updatequery)) {
        $valid->add_warning(get_lang('UnableToSaveChangesReason').$dbc_main->errorString());
    }
}
// END function SaveSettings()
/////

// Must be connected to the database, must be logged in and must be in the admins group
if ($dbc_main->isConnectedDB() === true && $KG_SECURITY->isLoggedIn() === true && $KG_SECURITY->isAdmin() === true) {

    $ACTON = $valid->get_value('ACTON');

    if ($ACTON == 'savesettings') {
        // Validate and save the settings

        SaveSettings();

        // Update the global settings array
        $kgSiteSettings = $dbc_main->fetchAssoc($dbc_main->select('Settings'));

    }

    DisplayForm();

}
?>
