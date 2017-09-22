<?php
// Last Updated: 2016-01-02
$dbc = new genesis();
$db = $dbc->connectServer();
$dbc->connectDatabase('Cookbook', 0.23);

function AddCategory() {
    // Add a category to the database

    global $dbc, $valid;

    $name = $valid->get_value('newcategory');

    if ($name != '') {
        $addarray = array(
            'Name' => $name
        );
        if (!$dbc->insert('Categories', $addarray)) {
            // Unable to add the category
            $valid->add_warning(get_lang('UnableToAddCategory').$dbc->errorString());
        }
    }
}
// END function AddCategory()
/////

function CheckCategoryStatus($list) {
    /**
     * Checks if the category exists. Adds it if not found.
     *
     * Added: 2014-??-??
     * Updated: 2015-05-25
     *
     * @param Required string $list Contains the category names to check.
     *                              Names must be sperated by a comma (,)
     *
     * @return string The category ID numbers separated by commas or empty if no categories
    **/

    global $dbc, $valid;

    // Create an array of the category names
    $listarray = explode(',', $list);

    // Clean-up the name
    foreach ($listarray as $key => $name) {

        // Remove double quotes (")
        $name = str_replace('"', '', $name);

        // Removes leading and trailing spaces
        $name = trim($name);

        // Only allows a-z, A-Z, 0-9, space and the underscore character
        $name = preg_replace("/[^A-Za-z0-9_ ]/", '', $name);

        // Convert name to Title Case and update array
        $listarray[$key] = ucwords(strtolower($name));
    }

    $catidlist = '';

    foreach ($listarray as $key => $name) {

        // MySQL does not care about uppercase or lowercase when doing the search
        // so CatONE and cAToNe will return the same results.
        $query = $dbc->select('Categories','Name = "'.$name.'"', array('CategoryID'));
        $numrows = $dbc->numRows($query);

        // $numrows should equal 0 or 1, any other number means I have a problem in the database

        if ($numrows == 1) {
            // $name is already in use

            $id = $dbc->fieldValue($query);

        } elseif ($numrows > 1) {
            // $name is already in use

            // Get ID number for first result
            $id = $dbc->fieldValue($query);

            $sm = new SystemMessages();
            $sm->AddEntry(get_lang('MultipleCategoriesSameName').$name, __METHOD__, __FILE__);
        } else {
            // Category name was not found
            // Add new category

            $insertarray = array(
                'Name' => $name
            );
            if (!$dbc->insert('Categories', $insertarray)) {
                // Unable to add new category
                $valid->add_error(get_lang('UnableToAddCategory').$dbc->errorString().'<br />'.get_lang('Category').': '.$name);
                $id = '';
            } else {
                // Category added
                // Get ID number
                $id = $dbc->GetInsertID();
            }

        }
        // END if ($numrows >= 1)

        if (is_numeric($id) === true && $id > 0) {
            if (strlen($catidlist) > 0) {
                $catidlist .= ','.$id;
            } else {
                $catidlist = $id;
            }
        }
    }
    // END foreach ($listarray as $key => $name)

    return $catidlist;
}
// END function CheckCategoryStatus()
/////

function cooknbookids() {

    global $dbc, $valid;

    // Get ID numbers to see if we need to add a new cook/cookbook to database
    $cookid = $valid->get_value('cookid');
    $cookother = $valid->get_value('cookother');
    $cookbookid = $valid->get_value('cookbookid');
    $cookbookother = $valid->get_value('cookbookother');

    // Do we need to add a new cook?
    if ($cookid == 0 && $cookother != '') {

        // Make sure cook name isn't already in database
        $query = $dbc->select('Cooks', 'Name = "'.$cookother.'"');
        if ($dbc->numRows($query) > 0) {
            // Name already exists
            $cookid = $dbc->fieldValue($dbc->select('Cooks', 'Name = "'.$cookother.'"', 'CookID'));
        } else {
            // New cook
            $dbc->insert('Cooks', array('Name' => $cookother));
            $cookid = $dbc->GetInsertID();
        }

    } elseif ($cookid == 0 && $cookother == '') {
        // No name entered by user
        $valid->add_error('MissingCookName');
    }

    // Do we need to add a new cookbook?
    if ($cookbookid == 0 && $cookbookother <> '') {

        // Make sure cook book isn't already in the datavase
        $query = $dbc->select('Cookbooks', 'Name = "'.$cookbookother.'"');
        if ($dbc->numRows($query) > 0) {
            // Name already exists
            $cookbookid = $dbc->fieldValue($dbc->select('Cookbooks', 'Name = "'.$cookbookother.'"', 'CookbookID'));
        } else {
            // New cook book
            $dbc->insert('Cookbooks', array('Name' => $cookbookother));
            $cookbookid = $dbc->GetInsertID();
        }

    } elseif ($cookbookid == 0 && $cookbookother <> '') {
        // No name entered by user
        $valid->add_error('MissingCookbookName');
    }

    return array($cookid, $cookbookid);
}
// END function cooknbookids
/////

function DeleteCategory() {
    // Delete the category with the given ID number

    global $dbc, $valid;

    $categoryid = $valid->get_value_numeric('categoryid',0);

    if ($categoryid > 0) {
        // ID number appears valid so delete category

        if (!$dbc->delete('Categories', 'CategoryID = '.$categoryid)) {
            $valid->add_warning(get_lang('UnableToDeleteCategory').$dbc->errorString());
        } else {
            // Category deleted. Update recipes filed under this category.

            $query = $dbc->select('Recipes', 'CategoryID LIKE "%'.$categoryid.'%"', array('RecipeID', 'CategoryID'));
            while ($row = $dbc->fetchAssoc($query)) {
                $list[$row['RecipeID']] = $row['CategoryID'];
            }

            foreach ($list as $recipeid => $catids) {

                if ($catids == '' || is_null($catids)) {
                    // Reset category ID
                    $catids = "0";
                } else {
                    // Remove the category ID number
                    $catids = str_replace($categoryid, '', $catids);

                    // Remove extra commas
                    $catids = str_replace(',,', ',', $catids);
                    $catids = trim($catids,',');

                    if ($catids == '') {
                        // Recipe is no longer associated with any categories
                        $catids = "0";
                    }
                }

                // Update the database
                $updatearray = array(
                    'CategoryID' => $catids
                );
                $dbc->update('Recipes', 'RecipeID = '.$recipeid, $updatearray);
            }
        }
    } else {
        // Invalid category ID number
        $valid->add_error(get_lang('InvalidCategoryIDDuringDelete'));
    }
}
// END function DeleteCategory()
/////

function DeleteRecipe() {
    // Delete a recipe

    global $dbc, $valid;

    $recipeid = $valid->get_value_numeric('recipeid',0);

    if ($recipeid > 0) {
        // Delete recipe info from database

        if (!$dbc->delete('Recipes', 'RecipeID = '.$recipeid)) {
            $valid->add_error(get_lang('UnableToDeleteInstructions').$dbc->errorString());
        }

        if (!$dbc->delete('Ingredients', 'RecipeID = '.$recipeid)) {
            $valid->add_error(get_lang('UnableToDeleteIngredientsList').$dbc->errorString());
        }
    } else {
        $valid->add_error(get_lang('InvalidRecipeIDDuringDelete').$recipeid);
    }

}
//END function DeleteRecipe()

function DisplayForm($dowhat) {
    // Added: 201?-??-??
    // Updated: 2016-01-02

    global $dbc, $valid, $KG_SECURITY, $kgSiteSettings;

    $form = new HtmlForm();
    $table = new HtmlTable();
    $innertable = new HtmlTable();

    // Display errors if any
    $valid->displayErrors();

    if ($dowhat == 'viewrecipe' || $dowhat == 'editrecipe') {
        // Get recipe data from database

        // ID number of recipe
        $recipeid = $valid->get_value_numeric('recipeid',0);

        // Load recipe data
        $recipe = $dbc->fetchAssoc($dbc->select('Recipes', 'RecipeID = '.$recipeid));

        // Get ingredients list.
        // 'ORDER BY RowID' causes the ingredients to be displayed in the same order every time
        // the recipe is viewed.
        $ingredients = array();
        $ingredientsquery = $dbc->select('Ingredients', 'RecipeID = '.$recipeid, '', 'ORDER BY RowID');

        while ($row = $dbc->fetchAssoc($ingredientsquery)) {

            // Set group name
            $group = $dbc->fieldValue($dbc->select('Groups', 'GroupID = '.$row['GroupID'], 'Name'));
            if ($group == '') {
                $group = 'None';
            }

            // The ingredients are sorted by group here
            $ingredients[$group][] = $row;
        }

        // Count number of ingredients
        $numingredients = $dbc->numRows($ingredientsquery);

        // Count number of ingredient groups
        $numsections = count($ingredients);

    } else {
        // Adding a recipe

        // Create an empty array
        $fieldlist = $dbc->listFields('Recipes');
        foreach ($fieldlist as $name => $type) {
            $type = strtolower($type);

            // Set a default value depending on field type.
            // This makes it easier to do comparisons later on.
            if ($type == 'smallint' || $type == 'tinyint' || $type == 'mediumint' || $type == 'int' || $type == 'integer' || $type == 'bigint') {
                $recipe[$name] = 0;
            } else {
                $recipe[$name] = '';
            }
        }

        // No ingredients yet
        $numingredients = 0;

    }

    // The javascript functions that are used depend in the current action ($dowhat)
    if ($dowhat == 'viewrecipe') {
        $KG_SECURITY->hideMsgs();
        if ($KG_SECURITY->hasPermission('delete') === true) {
            echo '<script type="text/javascript">
                function confirmDelete() {
                    var answer = confirm("'.get_lang('ConfirmDeleteMsg').'");

                    if (answer) {
                        return true;
                    } else {
                        return false;
                    }
                }

                function disableServingsByTheDozen() {
                    var frm = document.frmsavechangesedit;

                    frm.dozencount.disabled = true;

                    frm.servingsmin.disabled = false;
                    frm.servingsmax.disabled = false;
                }

                function disableServingsNumberOfPeople() {
                    var frm = document.frmsavechangesedit;

                    frm.servingsmin.disabled = true;
                    frm.servingsmax.disabled = true;

                    frm.dozencount.disabled = false;
                }
            </script>
';
        }

    } else {
        // $dowhat == 'addrecipe' || $dowhat == 'editrecipe'

        echo '<script type="text/javascript">
            function validate() {
                // Some client side validation of the form data

                // Recipe name
                if (document.getElementById("name").value == \'\') {
                    document.getElementById("name").focus();
                    alert("'.get_lang('RecipeNameRequired').'");
                    return false;
                }

                // Recipe directions
                if (document.getElementById("directions").value == \'\') {
                    document.getElementById("directions").focus();
                    alert("'.get_lang('RecipeDirectionsRequired').'");
                    return false;
                }

                // Servings
                var smin = parseInt(document.getElementById("servingsmin").value, 10);
                var smax = parseInt(document.getElementById("servingsmax").value, 10);
                //
                // servingsmin cannot be greater than servingsmax but it can be equal to servingsmax
                if (smin > smax) {
                    document.getElementById("servingsmin").focus();
                    alert("'.get_lang('ServingsMinGreaterServingsMax').'");
                    return false;
                }
                //
                // servingsmax must be equal to or greater than servingsmin
                if (smax < smin) {
                    smax = smin;
                }

                // Cook name
                var cid = parseInt(document.getElementById("cookid").value, 10);
                if (cid == 0 && document.getElementById("cookother").value == \'\') {
                    document.getElementById("cookother").focus();
                    alert("'.get_lang('MissingCookName').'");
                    return false;
                }

                // Cookbook name
                cid = parseInt(document.getElementById("cookbookid").value, 10);
                if (cid == 0 && document.getElementById("cookbookother").value == \'\') {
                    document.getElementById("cookbookother").focus();
                    alert("'.get_lang('MissingCookbookName').'");
                    return false;
                }

                // Number of ingredients
                if (document.getElementsByName("measurementid[]").length < 1) {
                    alert("'.get_lang('RecipeNeedsIngredients').'");
                    return false;
                }

                // Check for missing amounts/quantities
                var measurements = document.getElementsByName("measurementid[]");
                var amounts = document.getElementsByName("amount[]");
                var ingredients = document.getElementsByName("ingredient[]");
                //
                for (var i = 0; i < measurements.length; i++) {

                    if (amounts[i].value != \'\') {
                        // Only [0-9]/. allowed
                        amounts[i].value = amounts[i].value.replace(/[^0-9\/\.]/gi, \'\');
                    }

                    if (measurements[i].value > 1) {
                        if (amounts[i].value == \'\') {
                            amounts[i].focus();
                            alert("'.get_lang('MissingAmountFor').'"+ingredients[i].value);
                            return false;
                        }
                    }
                }

                // Page number
                if (isNaN(document.getElementById("pagenum").value) === true) {
                    document.getElementById("pagenum").focus();
                    alert(document.getElementById("pagenum").value+"'.get_lang('NotAPageNumber').'");
                    return false;
                }

                var catstring = document.getElementById("categorylist").value;

                if (catstring.length < 1) { 
                    alert("'.get_lang('SelectACategory').'");
                    return false;
                }

                var catarray = catstring.split(",");
                var catarraycount = catarray.length;
                for (var i = 0; i <= catarraycount; i++) {
                    catarray[i] = catarray[i].replace(/"/gi, "");
                    catarray[i] = catarray[i].trim();
                    if (/^([A-Za-z0-9_ ]{1,})$/.test(catarray[i]) === false) {
                        alert("'.get_lang('InvalidCatNamePart1').'"+catarray[i]+"'.get_lang('InvalidCatNamePart2').'");
                        document.getElementById("categorylist").setfocus();
                        return false;
                    }
                }

                return true;
            }
            // END javascript function validate()

            function switchCategoryAction(type) {

                if (type == 1) {
                    // Select a category
                    document.getElementById("selectcatdiv").className = "";
                    document.getElementById("addcatdiv").className = "hidden";
                    document.getElementById("categoryid").focus();

                } else if (type == 2) {
                    // Add a new category
                    document.getElementById("selectcatdiv").className = "hidden";
                    document.getElementById("addcatdiv").className = "";
                    document.getElementById("newcategory").focus();

                }
            }

            function checkDup() {
                var desiredValue = document.getElementById("newcategory").value;

                if (desiredValue == "") {
                    return false;
                }

                var desiredValuelc = desiredValue.toLowerCase();

                var el = document.getElementById("categoryid");
                for(var i=0; i<el.options.length; i++) {
                    var eltextlc = el.options[i].text.toLowerCase();
                    if (eltextlc == desiredValuelc) {
                        el.selectedIndex = i;
                        return false;
                    }
                }

                // Adds item to bottom of list and selects it
                var option = document.createElement("option");
                option.text = desiredValue;
                option.value = el.options.length+1;
                el.add(option);
                el.selectedIndex = el.options.length-1;

                switchCategoryAction(1);
            }

            function addec() {
                var catlist = document.getElementById("categorylist").value;
                var selectobject=document.getElementById("existingcategories")
                var addme = selectobject.options[selectobject.selectedIndex].text;

                if (catlist.length > 0) {
                    var res = catlist.split(", ");

                    // Check for and remove blank values
                    for(var i=0; i<res.length; i++) {
                        if (res[i] == "") {
                            res.splice(i, 1);
                        }
                    }

                    // Note: browser support for indexOf is limited, it is not supported in IE7-8.
                    var index = res.indexOf(addme);

                    if (index == -1) {
                        catlist = res.join(", ")+", "+addme;
                    }
                } else {
                    catlist = addme;
                }

                catlist.replace(/, ,/gi, \', \');

                document.getElementById("categorylist").value = catlist;
            }

            $(function() {
                $(document).tooltip();
            });

            function disableServingsByTheDozen() {

                if(document.getElementById("frmsavechangesedit")){
                    var frm = document.frmsavechangesedit;
                } else {
                    var frm = document.frmsavenew;
                }

                frm.dozencount.disabled = true;

                frm.servingsmin.disabled = false;
                frm.servingsmax.disabled = false;
            }

            function disableServingsNumberOfPeople() {

                if(document.getElementById("frmsavechangesedit")){
                    var frm = document.frmsavechangesedit;
                } else {
                    var frm = document.frmsavenew;
                }

                frm.servingsmin.disabled = true;
                frm.servingsmax.disabled = true;

                frm.dozencount.disabled = false;
            }

            function disableServingsNumberAndDozen() {

                if(document.getElementById("frmsavechangesedit")){
                    var frm = document.frmsavechangesedit;
                } else {
                    var frm = document.frmsavenew;
                }

                frm.servingsmin.disabled = true;
                frm.servingsmax.disabled = true;

                frm.dozencount.disabled = true;
            }
                
        </script>
';
?>
<script type="text/javascript">
$(document).ready(function(){
    // Dynamically add ingredient rows to the ingredients list
    $(".addCF").click(function(){
        $.ajax({
            type:"POST",
            url:"usermodules/cookbook_ajax.php",
            data:{ajaxacton:"getgroups"},
            success:function(data){

                // Split the code for the row over multiple lines for easier reading/searching
                var pone = '<tr><td align="left"><input type="text" name="amount[]" id="amount" value="" size="3"></td>';
                var ptwo = '<td align="left"><?php echo $form->add_select_db("measurementid[]","SELECT MeasurementID, Name FROM Measurements ORDER BY Name ASC","MeasurementID",1,"Name",false); ?></td>';
                var pthree = '<td align="left"><input type="text" name="ingredient[]" id="ingredient" value="" size="25"></td><td>';
                var pfour = '</td><td><a href="javascript:void(0);" class="remCF"><?php echo get_lang("Remove"); ?></a></td></tr>';

                // Put it all together
                row = $(pone+ptwo+pthree+data+pfour);
                row.appendTo("#ingredientstable");
            },
            error:function (xhr, ajaxOptions, thrownError){
                //On error, we alert user
                alert(thrownError);
            }
        });
    });

    // Dynamically removes a ingredient row
    $("#ingredientstable").on('click','.remCF',function(){
        $(this).parent().parent().remove();
    });

    // Create a new group to group ingredients into
    $(".newGroup").click(function(){
        if( $('#newgroup').val().length === 0 ) {
            alert('Group name required');
        } else {
            $.ajax({
                type:"POST",
                url:"usermodules/cookbook_ajax.php",
                data:{ajaxacton:"addgroup",newgroup:$('#newgroup').val(),format:"clean"},
                success:function(data){

                    // Clear text box for the next group name
                    $('#newgroup').val('');

                    // Reduces the amount of typing needed
                    var group = document.getElementsByName("group[]");

                    for (var i = 0; i < group.length; i++) {

                        // Keep track of which item is selected
                        var si = group[i].selectedIndex;
                        if (si > -1) {
                            // User selected something
                            var sv = group[i].options[si].value;
                        } else {
                            // Nothing selected so select option with text 'None'
                            var sv = 1;
                        }

                        // Empty the list
                        for(var x = group[i].options.length; x >= 0; x--){
                            group[i].remove(x);
                        }

                        // Repopulate it
                        for (var x = 0; x < data.length; x++) {
                            var option = document.createElement("option");
                            option.value = data[x][0];
                            option.text = data[x][1];
                            group[i].add(option);
                        }

                        // Reselect the item
                        group[i].value = sv;
                    }
                },
                error:function (xhr, ajaxOptions, thrownError){
                    //On error, we alert user
                    alert(thrownError);
                }
            });
        }
    });

    // Checks if the recipe name is already in use
    $("#name").blur(function() {
        if($('#name').val().length > 0) {
            $.ajax({
                type:"POST",
                url:"usermodules/cookbook_ajax.php",
                data:{ajaxacton:"dupnamecheck",newname:$('#name').val()},
                success:function(data){
                    data = parseInt(data, 10);
                    if (data == 1) {
                        alertNameInUse();
                    }
                },
                error:function (xhr, ajaxOptions, thrownError){
                    //On error, we alert user
                    alert(thrownError);
                }
            });
        }
        // END if($('#name').val().length > 0)
    });
});
</script>
<?php
    echo '<script type="text/javascript">
        function alertNameInUse() {
            alert("'.get_lang('RecipeNameInUse').'");
        }
    </script>
';

    }

    // Start form if needed
    if ($dowhat == 'editrecipe') {

        // Start form
        $form->add_hidden(array(
            'recipeid' => $recipe['RecipeID'],
            'ACTON' => 'savechangesedit'
        ));
        $form->start_form(kgGetScriptName(),'post','frmsavechangesedit');

    } elseif ($dowhat == 'addrecipe') {

        // Start form
        $form->onsubmit('return validate();');
        $form->add_hidden(array('ACTON' => 'savenew'));
        $form->start_form(kgGetScriptName(),'post','frmsavenew');

    }

    // This table encompasses every thing on the page
    $table->new_table('centered', 'width="100%"');

    $table->new_row();
    $table->set_width(50,'%');
    $table->new_cell('left');

    // Recipe name
    if ($dowhat == 'viewrecipe') {

        echo '<b>'.$recipe['Name'].'</b>';

    } else {

        $innertable->set_width(100,'%');
        $innertable->new_table();

        $innertable->new_row();
        $innertable->set_width(25,'%');
        $innertable->new_cell();
        echo get_lang('RecipeName');
        $innertable->set_width(75,'%');
        $innertable->new_cell();
        $form->add_text('name', $recipe['Name'],40);

        $innertable->end_table();

    }

    $table->new_cell('right');

    /////
    // BEGIN Navigation buttons
    if ($dowhat == 'editrecipe') {

        $innertable->set_width(50,'%');
        $innertable->new_table();

        $innertable->new_row();
        // Save changes
        $innertable->new_cell('right');
        $form->add_button_submit(get_lang('SaveChanges'));
        $innertable->new_cell('right');
        // Cancel changes
        $form->add_button_generic('submit',get_lang('cancel_changes'),"location.href='".kgCreateLink('',array('NO_TAG' => 'NO_TAG', 'recipeid' => $recipe['RecipeID'], 'ACTON' => 'viewrecipe'))."'");

        $innertable->end_table();

    } elseif ($dowhat == 'addrecipe') {

        $innertable->set_width(50,'%');
        $innertable->new_table();

        $innertable->new_row();
        // Save changes
        $innertable->new_cell('right');
        $form->add_button_submit(get_lang('SaveInfo'));
        // Cancel changes
        $innertable->new_cell('right');
        $form->add_button_generic('submit',get_lang('Cancel'),'location.href=\''.kgCreateLink('',array('NO_TAG' => 'NO_TAG')).'\'');

        $innertable->end_table();

    } else {
        // $dowhat == 'viewrecipe'

        $innertable->set_width(70,'%');
        $innertable->new_table();
        $innertable->new_row();
        // Recipe index button
        $innertable->set_width(40,'%');
        $innertable->new_cell('right');
        $form->buttononlyform(kgGetScriptName(),'post','frmrecipeindex',get_lang('RecipeIndex'));

        // Edit recipe button
        $innertable->set_width(30,'%');
        $KG_SECURITY->hideMsgs();
        if ($KG_SECURITY->hasPermission('edit') === true) {
            $innertable->new_cell('right');
            $form->add_hidden(array('recipeid' => $recipe['RecipeID'],'ACTON' => 'editrecipe'));
            $form->buttononlyform(kgGetScriptName(),'post','frmeditrecipe',get_lang('EditRecipe'));
        } else {
            $innertable->blank_cell();
        }

        // Delete recipe button
        $innertable->set_width(30,'%');
        $KG_SECURITY->hideMsgs();
        if ($KG_SECURITY->hasPermission('delete') === true) {
            $innertable->new_cell('right');
            $form->onsubmit('return confirmDelete();');
            $form->add_hidden(array('recipeid' => $recipe['RecipeID'], 'ACTON' => 'deleterecipe'));
            $form->buttononlyform(kgGetScriptName(),'post','frmdeleterecipe',get_lang('Delete'));
        } else {
            $innertable->blank_cell();
        }

        $innertable->end_table();
    }
    // END Navigation buttons
    /////

    // Seperator row above ingredients, directions and notes
    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell();
    echo '<hr>';

    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell();

    if ($dowhat == 'viewrecipe') {

        if ($numingredients > 0) {

            $colcount = 0;

            echo "\n".'<div class="detailswrapper"><div class="ingredientsdiv">';

            // Display ingredients list
            foreach ($ingredients as $section => $value) {
                $colcount++;

                echo '<div class="grouphighlight">';
                if ($section != 'None') {
                    echo $section;
                } else {
                    echo get_lang('Miscellaneous');
                }

                // Shift the ingredients to the right a little bit to
                // make it easier to pickout the group name
                echo '<div class="grouppadding">';

                foreach ($value as $key => $list) {

                    if ($list['MeasurementID'] > 1) {
                        $measurement = $dbc->fieldValue($dbc->select('Measurements', 'MeasurementID = '.$list['MeasurementID'], 'Symbol'));
                        echo $list['Amount'].' '.$measurement.' '.$list['Name'].'<br />';
                    } else {
                        // Ingredients such as 'cinnamon to taste', 'choice of fruit', etc. that don't need a specific amount
                        echo ($list['Amount'] > 0 ? $list['Amount'].' ' : '' ).$list['Name'].'<br />';
                    }

                }

                echo '</div></div>';
            }
        } else {

            // Not sure how this happened but there appear to be no ingredients for this recipe
            echo get_lang('IngredientListNotFound');

        }
        // END if ($numingredients > 0)

        echo "</div>\n<!-- End ingredientsdiv -->\n";

        echo '<div class="directionsdiv">';
        echo nl2br($recipe['Directions'])."</div>\n";

        // This <div> encloses the notes, serving amount, categories and prep time
        echo '<div class="infodiv">'."\n";

        // Notes
        if ($recipe['Notes'] != '') {
            echo '<div class="notesdiv">';
            echo nl2br($recipe['Notes'])."</div>\n<!-- End notesdiv -->\n";
        }

        // Servings
        // Default text to display
        $text = get_lang('Unknown');
        echo '<div class="servingsdiv">'.get_lang('Servings').': ';
        if ($recipe['ServingsType'] == 1) {
            // Number of people
            if ($recipe['ServingsMax'] > $recipe['ServingsMin']) {
                $text = $recipe['ServingsMin'].' to '.$recipe['ServingsMax'];

            } elseif ($recipe['ServingsMin'] == 0 && $recipe['ServingsMax'] == 0) {
                // Number of servings not specified
            } elseif ($recipe['ServingsMin'] == $recipe['ServingsMax']) {
                $text = $recipe['ServingsMin'];
            }
        } elseif ($recipe['ServingsType'] == 2) {
            // By the dozen
            if ($recipe['ServingsMin'] > 0) {
                $text = $recipe['ServingsMin'].' '.get_lang('Dozen');
            }
        } elseif ($recipe['ServingsType'] == 3) {
            // Person adding/editing recipe didn't know which
            // servings type to select
        }

        echo "$text\n</div>\n";

        /////
        // Prep time
        // Get the hours and minutes
        if ($recipe['preptime'] != '00:00' && $recipe['preptime'] != '') {
            list($hrs, $mins) = explode(':', $recipe['preptime']);
            $hrs = intval($hrs, 10);
        } else {
            $hrs = 0;
            $mins = 0;
        }
        $text = '';
        if ($hrs > 0) {
            $text = $hrs.' '.get_lang('HoursShort');
        }
        if ($text != '') {
            $text .= ' ';
        }
        if ($mins > 0) {
            $text .= $mins.' '.get_lang('MinutesShort');
        }

        if ($text == '') {
            $text = get_lang('Unknown');
        }
        echo '<div class="preptimediv">'.get_lang('PrepTime').': '.$text;
        echo "</div>\n";

        /////
        // Categories
        // Get category ID numbers for this recipe
        $catids = $dbc->fieldValue($dbc->select('Recipes', 'RecipeID = '.$recipeid, 'CategoryID'));
        if ($catids != '') {
            $catids = explode(',', $catids);
            $catcount = count($catids);
            $temp = '';

            foreach ($catids as $key => $id) {
                // Get the category name using its ID number
                $name = $dbc->fieldValue($dbc->select('Categories', 'CategoryID = '.$id, 'Name'));
                if ($temp == '') {
                    $temp = $name;
                } else {
                    $temp .= '<br />'.$name;
                }
            }

            if ($catcount < 2) {
                // Recipe is only in 1 category or $catcount some how got assigned a negative value
                $label = get_lang('Category');
            } else {
                // Recipe is in 2 or more categories
                $label = get_lang('Categories');
            }
        }
        echo '<div class="categoriesdiv">'.$label.':<div class="grouppadding">'.$temp."</div></div>\n";

        echo "</div>\n<!-- End infodiv -->\n</div>\n<!-- End detailswrapper -->";
    } else {
        // $dowhat = 'addrecipe' || $dowhat == 'editrecipe'

        // The list of ingredient groups
        $query = $dbc->select('Groups','','',array('ORDER BY' => 'Name'));
        while ($row = $dbc->fetchAssoc($query)) {
            $groups[$row['GroupID']] = $row['Name'];
        }

        /////
        // This table contains the ingredient list
        $innertable->set_id('ingredientstable');
        $innertable->new_table();

        // The header row
        $innertable->new_row();
        $innertable->set_width(80,'px');
        $innertable->new_cell();
        echo get_lang('Amount');
        $innertable->set_width(195,'px');
        $innertable->new_cell();
        echo get_lang('Measurement');
        $innertable->set_width(230,'px');
        $innertable->new_cell();
        echo get_lang('Ingredient');
        $innertable->set_width(175,'px');
        $innertable->new_cell();
        echo get_lang('Group');
        $innertable->set_width(100,'px');
        $innertable->new_cell();
        echo '<a href="javascript:void(0);" class="addCF">'.get_lang('Add').'</a>';
        // Add new group text box
        $innertable->set_rowspan(3);
        $innertable->set_width(20,'px');
        $innertable->blank_cell();
        $innertable->set_rowspan(3);
        $innertable->new_cell();
        echo '<table class="newGroupTable"><tr><td>'.get_lang('CreateNewGroup').'</td></tr><tr><td>';
        $form->set_fieldtitle(get_lang('CreateNewGroupToolTip'));
        $form->add_text('newgroup');
        echo '<a href="javascript:void(0);" class="newGroup">'.get_lang('Add').'</a></td></tr></table>';

        if ($dowhat != 'addrecipe') {
            foreach ($ingredients as $section => $value) {
                foreach ($value as $key => $list) {

                    $innertable->new_row();
                    $innertable->new_cell();
                    $form->add_text('amount[]', $list['Amount'],3);
                    $innertable->new_cell();
                    $form->add_select_db_autoecho('measurementid[]','SELECT MeasurementID, Name FROM Measurements ORDER BY Name ASC','MeasurementID', $list['MeasurementID'],'Name',false);
                    $innertable->new_cell();
                    $form->add_text('ingredient[]', $list['Name'],25);
                    $innertable->new_cell();
                    $form->add_select_match_value('group[]', $groups, $section);
                    $innertable->new_cell();
                    echo '<a href="javascript:void(0);" class="remCF">'.get_lang('Remove').'</a>';
                }
            }
            // END foreach ($ingredients as $section => $value)
        }

        // Add 4 blank ingredient rows
        for ($i = 1; $i <= 4; $i++) {
            $innertable->new_row();
            $innertable->new_cell();
            $form->add_text('amount[]','',3);
            $innertable->new_cell();
            $form->add_select_db_autoecho('measurementid[]','SELECT MeasurementID, Name FROM Measurements ORDER BY Name ASC','MeasurementID',1,'Name',false);
            $innertable->new_cell();
            $form->add_text('ingredient[]','',25);
            $innertable->new_cell();
            $form->add_select_match_value('group[]', $groups,'None');
            $innertable->new_cell();
            echo '<a href="javascript:void(0);" class="remCF">'.get_lang('Remove').'</a>';
        }

        $innertable->end_table();
        // END Add ingredient table
        /////

        // Seperator row above directions and notes
        $table->new_row();
        $table->set_colspan(2);
        $table->new_cell();
        echo '<hr>';

        $table->new_row();
        $table->new_cell('left');
        echo get_lang('Directions');
        $table->new_cell();
        echo get_lang('Notes');
        $table->new_row();
        $table->new_cell('left','top');
        $form->add_textarea('directions', $recipe['Directions'],80,7);
        $table->new_cell('left','top');
        $form->add_textarea('notes', $recipe['Notes'],80,7);
    }
    // END if ($dowhat == 'viewrecipe')
    /////

    // Spacer row below directions and notes
    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell('left','top');
    echo '<hr>';

    if ($dowhat != 'viewrecipe') {

        $table->new_row();
        $table->new_cell();

        echo '<div style="margin-right: 10px;">';

        /////
        // BEGIN Servings min/max
        echo '<div style="float:left;margin-right: 20px;">';
        echo get_lang('Servings')."\n".'<div class="grouppadding">';

        // How many people will this recipe serve?
        $form->add_radio('ServingsType',1,get_lang('NumberOfPeople'),true,'disableServingsByTheDozen();');
        echo ' '.get_lang('ServingsMin').' ';
        $form->add_select_generic('servingsmin',range(1,100), $recipe['ServingsMin'],'v','','',false);
        echo '&nbsp;&nbsp;&nbsp;'.get_lang('ServingsMax').' ';
        $form->add_select_generic('servingsmax',range(1,100), $recipe['ServingsMax'],'v','','',false);

        echo '<br />';

        // How many dozen will this recipe make?
        $form->add_radio('ServingsType',2,get_lang('ByTheDozen'),false,'disableServingsNumberOfPeople();');
        echo ' ';
        $form->add_select_match_key('dozencount',range(0,25), $recipe['ServingsType']);
        echo ' '.get_lang('Dozen');

        echo '<br />';

        // Unknown servings amount/type
        $form->add_radio('ServingsType',3,get_lang('Unknown'),false,'disableServingsNumberAndDozen();');

        echo '</div></div>';
        // END Servings min/max
        /////

        /////
        // BEGIN Prep time

        // Get the hours and minutes
        if ($recipe['preptime'] != '00:00' && $recipe['preptime'] != '') {
            list($hrs, $mins) = explode(':', $recipe['preptime']);
            $hrs = intval($hrs, 10);
        } else {
            $hrs = 0;
            $mins = 0;
        }

        // 5 minute increments
        $minuterange = array(
            0 => 0,
            5 => 5,
            10 => 10,
            15 => 15,
            20 => 20,
            25 => 25,
            30 => 30,
            35 => 35,
            40 => 40,
            45 => 45,
            50 => 50,
            55 => 55
        );
    
        echo '<div style="float:left;">';
        echo get_lang('PrepTime').' ';
        $form->add_select_match_key('prephours',range(0,10), $hrs);
        echo get_lang('HoursShort').' ';
        $form->add_select_match_key('prepminutes', $minuterange, $mins);
        echo get_lang('MinutesShort');

        echo '</div>';
        // END Prep time
        /////

        $table->new_cell();

        /////
        // BEGIN Categories
        $innertable->new_table();
        $innertable->new_row();
        $innertable->set_width(100,'px');
        $innertable->new_cell();
        echo get_lang('Category');
        $innertable->new_cell();
        $namelist = '';
        if ($dowhat != 'addrecipe') {
            // Get category ID numbers for this recipe
            $catids = $dbc->fieldValue($dbc->select('Recipes', 'RecipeID = '.$recipeid, 'CategoryID'));
            if ($catids != '') {
                $catids = explode(',', $catids);

                foreach ($catids as $key => $id) {
                    // Get the category name using its ID number
                    $name = $dbc->fieldValue($dbc->select('Categories', 'CategoryID = '.$id, 'Name'));

                    // Add the name to the list
                    if ($namelist == '') {
                        $namelist = $name;
                    } else {
                        $namelist .= ', '.$name;
                    }
                }
            }
        }
        $form->set_fieldtitle(get_lang('CategoryToolTip'));
        $form->add_text('categorylist', $namelist,30);
        echo '&nbsp; &nbsp;';

        $eclist = array();
        $query = $dbc->select('Categories', '', array('Name', 'CategoryID'), array('ORDER BY' => 'Name ASC'));
        while($row = $dbc->fetchAssoc($query)) {
            $eclist[$row['CategoryID']] = $row['Name'];
        }
        
        $form->add_select_match_key('existingcategories', $eclist);
        echo '&nbsp; ';
        $form->add_button_generic('submit',get_lang('AddCategory'),'addec();');

        $innertable->end_table();
        // END Categories
        /////

        // Spacer row below servings min/max and categories
        $table->new_row();
        $table->set_colspan(2);
        $table->new_cell('left','top');
        echo '<hr>';
    }

    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell();

    /////
    // BEGIN Name of cook and cook book
    if ($dowhat == 'viewrecipe') {

        $cookbook = $dbc->fieldValue($dbc->select('Cookbooks', 'CookbookID = '.$recipe['CookbookID'], 'Name'));
        $cook = $dbc->fieldValue($dbc->select('Cooks', 'CookID = '.$recipe['CookID'], 'Name'));

        $innertable->set_width(100,'%');
        $innertable->new_table();

        $innertable->new_row();
        $innertable->set_width(600,'px');
        $innertable->new_cell('left');
        // Name of cookbook
        echo get_lang('CookbookName').' '.$cookbook.($recipe['Pagenum'] > 0 ? ' (pg '. $recipe['Pagenum'].')' : '');

        $innertable->set_width(150,'px');
        $innertable->blank_cell();

        // Name of cook
        $innertable->new_cell('right');
        echo get_lang('CookName').' '.$cook;

        $innertable->end_table();

    } else {
        // $dowhat == 'editrecipe' || $dowhat == 'addrecipe'
        $innertable->set_width(100,'%');
        $innertable->new_table();

        $innertable->new_row();
        $innertable->set_width(100,'px');
        $innertable->new_cell('right');
        echo get_lang('CookbookName');
        $innertable->set_width(400,'px');
        $innertable->new_cell('left');
        if ($dowhat == 'addrecipe') {
            $matchme = 1; // Unknown
        } else {
            $matchme = $recipe['CookbookID'];
        }
        $form->add_select_db_autoecho('cookbookid','SELECT CookbookID, Name FROM Cookbooks ORDER BY Name','CookbookID', $matchme,'Name',0);
        $innertable->set_width(250,'px');
        $innertable->new_cell('center');
        echo get_lang('PageNum');
        $form->add_text('pagenum', $recipe['Pagenum'],3);
        $innertable->set_width(250,'px');
        $innertable->new_cell('right');
        echo get_lang('CookName');
        $innertable->new_cell('left');
        if ($dowhat == 'addrecipe') {
            $matchme = 1; // Unknown
        } else {
            $matchme = $recipe['CookID'];
        }
        $form->add_select_db_autoecho('cookid','SELECT CookID, Name FROM Cooks ORDER BY Name','CookID', $matchme,'Name',0);

        $innertable->new_row();
        $innertable->new_cell('right');
        echo get_lang('Other');
        $innertable->new_cell('left');
        $form->add_text('cookbookother','',30);
        $innertable->blank_cell();
        $innertable->new_cell('right');
        echo get_lang('Other');
        $innertable->new_cell('left');
        echo $form->add_text('cookother','',30);

        $innertable->end_table();

?>
<!-- Adds the options to create new cook/cookbook to top of <select></select> lists -->
<script type="text/javascript">
    var option = document.createElement("option");
    option.value = 0;
    option.text = "<?php echo get_lang('NewCookBook'); ?>";
    cookbookid.add(option,0);

    var option = document.createElement("option");
    option.value = 0;
    option.text = "<?php echo get_lang('NewCook'); ?>";
    cookid.add(option,0);

    disableServingsByTheDozen();
</script>
<?php
        // END Name of cook and cook book
        /////

    }

    $table->end_table();

    if ($dowhat != 'viewrecipe') {
        // Only display if adding or editing a recipe
        $form->end_form();
    }
}
// END function DisplayForm()
//////////

function EditCategories() {

    global $dbc, $valid;

    $table = new HtmlTable();
    $form = new HtmlForm();

    if ($valid->is_error()) {
        $valid->errors_table();
    }

    if ($valid->is_warning()) {
        $valid->warnings_table();
    }

    // List all categories in the database
    $query = $dbc->select('Categories', '', '', array('ORDER BY' => 'Name ASC'));
    $numrows = $dbc->numRows($query);

    if ($numrows > 0) {
        echo '<script type="text/javascript">
            function confirmDelete(catname) {
                var answer = confirm("'.get_lang('ConfirmDeleteCategory').'"+catname);

                if (answer) {
                    return true;
                } else {
                    return false;
                }
            }

            function checkDupName() {
                var newname = document.getElementById("newcategory").value;
                var newnamelc = newname.toLowerCase();
                var existingcategories = document.getElementById("existingcategories").value;
                var existingcategorieslc = existingcategories.toLowerCase();

                // Note: browser support for indexOf is limited, it is not supported in IE7-8.
                var index = existingcategorieslc.indexOf(newnamelc);

                if (index > -1) {
                    alert("'.get_lang('CategoryAlreadyCreated').'");
                    return false;
                } else {
                    return true;
                }
            }
        </script>';
    }

    $table->new_table('centered');
    $table->new_row();
    $table->set_colspan(3);
    $table->new_cell('center');
    echo '<b>'.get_lang('EditCategories').'</b>';

    // List categories if any
    if ($numrows > 0) {

        $existingcategories = '';
        while ($row = $dbc->fetchAssoc($query)) {
            $table->new_row();
            $table->new_cell();
            echo $row['Name'];
            $table->new_cell();
            $form->onsubmit('return confirmDelete(\''.$row['Name'].'\');');
            $form->add_hidden(array('ACTON' => 'deletecategory', 'categoryid' => $row['CategoryID']));
            $form->buttononlyform(kgGetScriptName(),'post','frmdeletecategory',get_lang('Delete'));
            $table->new_cell();
            $form->add_hidden(array('ACTON' => 'renamecategory'));
            $form->buttononlyform(kgGetScriptName(),'post','frmaddrecipe',get_lang('Rename'));

            $existingcategories .= $row['Name'].', ';
        }

        echo "\n";
        $form->add_hidden(array('existingcategories' => $existingcategories));
        $form->show_hidden();
    }

    $table->new_row();
    $table->new_cell();
    $form->add_hidden(array('ACTON' => 'addcategory'));
    $form->onsubmit('return checkDupName();');
    $form->start_form();
    $form->add_text('newcategory');
    $table->new_cell();
    $form->add_button_submit(get_lang('AddCategory'));
    $form->end_form();

    $table->blank_row();

    $table->new_row();
    $table->set_colspan(3);
    $table->new_cell('center');
    $form->buttononlyform(kgGetScriptName(),'post','frmaddrecipe',get_lang('RecipeIndex'));

    $table->end_table();
}
// END function EditCategories()
/////

function ListRecipesAlpha() {
    /**
     * List all recipes in the database by first letter
     *
     * Added: 2017-??-??
     * Modified: 2017-09-03
     *
     * @param None
     *
     * @return Nothing
    **/

    global $dbc, $valid, $KG_SECURITY;

    // Display errors if any
    $valid->displayErrors();

    $form = new HtmlForm();
    $table = new HtmlTable();

    // -Selects all recipes in the database and sorts
    //  them in alphabetical order
    $query = $dbc->select('Recipes', '', array('RecipeID', 'Name'), array('ORDER BY' => 'Name'));
    $totalcount = $dbc->numRows($query);

    if ($totalcount > 0) {
        // -Sort recipe names into an array
        // -Group by first letter of name
        while ($row = $dbc->fetchAssoc($query)) {

            // Get the first letter of the artist name
            $fl = substr($row['Name'],0,1);
            $fl = strtoupper($fl);

            if (ctype_alpha($fl)) {
                // $fl matches [A-Za-z]
                $recipelist[$fl][$row['RecipeID']] = $row['Name'];
            } else {
                // $fl matches everything else
                $recipelist['Symbols'][$row['RecipeID']] = $row['Name'];
            }
        }

        // -Number of lines per column
        // -Includes blank lines
        $maxlines = 20;

        // To create the tabs, I'll need to run through the $recipelist array twice

        echo '<div class="tabslist_height_control_div"><div id="tabslist">';

        // -First run through the array
        // -Creates the list that becomes the tabs
        echo '<ul>'."\n";
        foreach ($recipelist as $key => $value) {
            // Include count of number of recipes starting with $key
            $count = count($value).' '.get_lang('Recipes');
            echo '<li>'.kgCreateLink($key,'#tabs-'.$key, $count).'</li>'."\n";
        }
        echo '</ul>';

        // -Second run through the array
        // -Creates the contents of the tabs
        foreach ($recipelist as $key => $value) {
            $linecnt = 0;
            $keycnt = count($value);
            $keynum = 0;

            echo '<div id="tabs-'.$key.'">';

            $table->new_table();
            $table->new_row();
            $table->new_cell('name_table_cell');

            foreach ($value as $id => $name) {
                echo '<div class="receipe_link_div" onclick="window.location=\''.kgCreateLink($data,array('ACTON' => 'viewrecipe','recipeid' => $id, 'NO_TAG' => 'NO_TAG')).'\'">'.$name.'</div>';
                $linecnt = $linecnt + 1;
                $keynum = $keynum + 1;

                // Start next column if needed
                if ($linecnt > $maxlines) {
                    $table->new_cell('name_table_cell');

                    $linecnt = 0;
                }
            }

            $table->end_table();
            echo "</div>\n";

        }
        // END foreach ($recipelist as $key => $value)

        echo "</div><!-- END tabslist -->\n</div><!-- END tabslist_height_control_div -->\n";

        echo '
<!-- jquery -->
<script>
    // This turns the divs and UL\'s into jQuery tabs
    $(function() {
        $( "#tabslist" ).tabs();
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

    } else {
        // No recipes in database
        echo get_lang('NoRecipesFound');
        $totalcount = 0;
    } // END if ($totalcount > 0)

    ListRecipeButtons($totalcount,'a');
}
// END function ListRecipesAlpha()
/////

function ListRecipesCat() {
    // List all recipes in database by category

    global $dbc, $KG_SECURITY, $valid;

    $table = new HtmlTable();
    $form = new HtmlForm();

    // Display errors if any
    $valid->displayErrors();

    // List all recipes in the database sorted alphabetically by name
    $query = $dbc->select('Recipes', '', array('RecipeID','Name','CategoryID'),array('ORDER BY' => 'Name'));

    if ($dbc->numRows($query) > 0) {
        // Display recipes

        // -Number of recipes to list per column
        // -Includes blank lines
        $maxlines = 20;

        $catnamesid = array(get_lang('Uncategorized') => 0);
        while ($row = $dbc->fetchAssoc($query)) {
            $temp = explode(',', $row['CategoryID']);
            foreach ($temp as $key => $catid) {
                if ($catid == '' || $catid == NULL) {
                    // Handle uncategorized recipes
                    $catid = 0;
                }

                $name = array_search($catid, $catnamesid);
                if ($name === false) {
                    $name = $dbc->fieldValue($dbc->select('Categories','CategoryID = '.$catid,'Name'));
                    $catnamesid[$name] = $catid;
                }

                /**
                 * We will loop through this array twice to get all of the info on screen
                **/
                $categories[$name][$row['Name']] = $row['RecipeID'];
            }
        }

        // Sort alphabetically by key/category name
        ksort($categories);

        // Total number of categories
        $totalcount = count($categories);

        // Start the tabs <div>
        echo '<div id="tabslist">';

        // Creates the list that jQuery turns into tabs
        echo '<ul>'."\n";
        foreach ($categories as $name => $value) {
            $id = $catnamesid[$name];
            echo '<li>'.kgCreateLink($name,'#tabs-'.$id).'</li>'."\n";
        }
        echo '</ul>';

        foreach ($categories as $catname => $data) {
            $linecnt = 0;

            echo '<div id="tabs-'.$catnamesid[$catname].'">';
            $table->new_table();
            $table->new_row();
            $table->new_cell();

            foreach ($data as $recname => $id) {
                echo kgCreateLink($recname,array('ACTON' => 'viewrecipe','recipeid' => $id)).'<br />';
                $linecnt = $linecnt + 1;

                // Start next column if needed
                if ($linecnt > $maxlines) {
                    $table->set_width(50,'px');
                    $table->blank_cell();
                    $table->new_cell('','top');

                    $linecnt = 0;
                }
            }

            $table->end_table();
            echo "</div>\n";
        } // END foreach ($categories as $name => $data)

        echo '</div>

<!-- jquery -->
<script>
    // This turns the divs and UL\'s into jQuery tabs
    $(function() {
        $("#tabslist").tabs();
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

    } else {
        // No recipes in database
        echo get_lang('NoCategoriesFound');
        $totalcount = 0;
    } // END if ($numrows > 0)

    ListRecipeButtons($totalcount,'c');
}
// END function ListRecipesCat()
/////

function ListRecipeButtons($count, $switch) {

    global $KG_SECURITY;

    $table = new HtmlTable();
    $form = new HtmlForm();

    $table->new_table('centered');

    $table->new_row();

    if ($switch === 'a') {
        // Recipe count
        $table->set_width(150,'px');
        $table->new_cell();
        echo $count.' '.get_lang('Recipes').' '.strtolower(get_lang('Total'));
    } elseif ($switch === 'c') {
        // Categories count
        $table->set_width(160,'px');
        $table->new_cell();
        echo $count.' '.get_lang('Categories').' '.strtolower(get_lang('Total'));
    }

    $table->set_width(30,'px');
    $table->blank_cell();

    $table->new_cell();
    if ($switch === 'a') {
        // View by category
        $form->add_hidden(array('ACTON' => 'browsecat'));
        $form->buttononlyform(kgGetScriptName(),'post','frmbrowsecat',get_lang('BrowseCat'));
    } elseif ($switch === 'c') {
        // View alphabetically
        $form->add_hidden(array('ACTON' => 'browsealpha'));
        $form->buttononlyform(kgGetScriptName(),'post','frmaddrecipe',get_lang('BrowseAlpha'));
    }

    $table->set_width(20,'px');
    $table->blank_cell();

    // Load search form
    $table->new_cell();
    $form->add_hidden(array('ACTON' => 'searchform','view' => 'alpha'));
    $form->buttononlyform(kgGetScriptName(),'post','frmsearchform',get_lang('search'));

    $table->set_width(20,'px');
    $table->blank_cell();

    // Add recipe button
    $KG_SECURITY->hideMsgs();
    if ($KG_SECURITY->hasPermission('add') === true) {
        $table->new_cell();
        $form->add_hidden(array('ACTON' => 'addrecipe'));
        $form->buttononlyform(kgGetScriptName(),'post','frmaddrecipe',get_lang('AddRecipe'));

        $table->set_width(20,'px');
        $table->blank_cell();
    }

    // Edit categories button
    $KG_SECURITY->hideMsgs();
    if ($KG_SECURITY->hasPermission('edit') === true) {
        $table->new_cell();
        $form->add_hidden(array('ACTON' => 'editcategories'));
        $form->buttononlyform(kgGetScriptName(),'post','frmeditcategories',get_lang('EditCategories'));

        $table->set_width(20,'px');
        $table->blank_cell();
    }

    // Tips
    $table->new_cell();
    if ($switch === 'a') {
        $view = 'browsealpha';
    } elseif ($switch === 'c') {
        $view = 'browsecat';
    }
    $form->add_hidden(array('ACTON' => 'tipsbrowse', 'view' => $view));
    $form->buttononlyform(kgGetScriptName(),'post','frmtipsbrowse',get_lang('CookingTips'));

    $table->end_table();
}
// END function ListRecipeButtons()
/////

function SaveChangesEdit() {

    global $dbc, $valid;

    $recipeid = $valid->get_value_numeric('recipeid',0);
    $servingstype = $valid->get_value_numeric('ServingsType',0);

    list($cookid, $cookbookid) = cooknbookids();

    // Build the query
    $hrs = $valid->get_value_numeric('prephours',0);
    if ($hrs < 10) {
        $hrs = '0'.$hrs;
    }
    $mins = $valid->get_value_numeric('prepminutes',0);
    if ($mins < 10) {
        $mins = '0'.$mins;
    }
    $recipedata = array(
        'Name' => ucwords(strtolower($valid->get_value('name'))),
        'Directions' => $valid->get_value('directions'),
        'CookID' => $cookid,
        'CookbookID' => $cookbookid,
        'Pagenum' => $valid->get_value_numeric('pagenum',0),
        'CategoryID' => CheckCategoryStatus($valid->get_value('categorylist')),
        'Notes' => $valid->get_value('notes'),
        'dateadded' => date('Y-m-d'),
        'preptime' => $hrs.':'.$mins,
        'ServingsType' => $servingstype
    );

    if ($servingstype == 1) {
        // Number of people
        $recipedata['ServingsMin'] = $valid->get_value_numeric('servingsmin',0);
        $recipedata['ServingsMax'] = $valid->get_value_numeric('servingsmax',0);
    } elseif ($servingstype == 2) {
        // By the dozen
        $recipedata['ServingsMin'] = $valid->get_value_numeric('dozencount',0);
        $recipedata['ServingsMax'] = 0;
    }

    // Update the recipe table
    if (!$dbc->update('Recipes', 'RecipeID = '.$recipeid, $recipedata)) {
        // Update failed
        $valid->add_error(get_lang('UnableToUpdateRecipe').$dbc->errorString());
    } else {

        // Delete existing ingredients for the recipe
        if (!$dbc->delete('Ingredients', 'RecipeID = '.$recipeid)) {
            // Deletion failed
            $valid->add_error(get_lang('UnableToDeleteIngredientsList').$dbc->errorString());
        } else {

            $amountsarray = $valid->get_value('amount');
            $measurementsarray = $valid->get_value('measurementid');
            $ingredientsarray = $valid->get_value('ingredient');
            $groupsarray = $valid->get_value('group');

            $count = count($measurementsarray);

            // Get data for ingredients table, build the queries and then update the ingredients table
            for ($i = 0; $i < $count; $i++) {

                // Only create SQL Insert statement if an ingredient name was given
                if ($ingredientsarray[$i] != '') {

                    $insertarray = array(
                        'Name' => ucwords(strtolower($ingredientsarray[$i])),
                        'Amount' => $amountsarray[$i],
                        'Measurementid' => $measurementsarray[$i],
                        'RecipeID' => $recipeid,
                        'GroupID' => $groupsarray[$i]
                    );

                    if (!$dbc->insert('Ingredients', $insertarray)) {
                        // Insert failed
                        $valid->add_error(get_lang('UnableToSaveIngredient').' "'.$valid->get_value('ingredient'.$i).'"<br />'.get_lang('Reason').' :'.$dbc->errorString());
                    }
                }
            } // END for ($i = 0; $i < $count; $i++)
        }
    } // END if (!$dbc->update('Recipes', 'RecipeID = '.$recipeid, $recipedata))

    // Set the recipe ID so the correct recipe is loaded
    $valid->setValue('recipeid', $recipeid);

    if ($valid->is_error()) {
        // Something didn't go correctly
        DisplayForm('editrecipe');
    } else {
        // Update complete
        DisplayForm('viewrecipe');
    }
}
// END function SaveChangesEdit()
/////

function SaveNew() {
    /**
     * Save a new recipe to the database
     *
     * Added: 201?-??-??
     * Updated: 2015-05-29
    **/

    global $dbc, $valid;

    list($cookid, $cookbookid) = cooknbookids();
    $servingstype = $valid->get_value_numeric('ServingsType',0);

    // Display any errors generated by function cooknbookids()
    if ($valid->is_error()) {

        DisplayForm('addrecipe');

        return;
    }

    // Build the query
    $hrs = $valid->get_value_numeric('prephours',0);
    if ($hrs < 10) {
        $hrs = '0'.$hrs;
    }
    $mins = $valid->get_value_numeric('prepminutes',0);
    if ($mins < 10) {
        $mins = '0'.$mins;
    }
    $recipedata = array(
        'Name' => ucwords(strtolower($valid->get_value('name'))),
        'Directions' => $valid->get_value('directions'),
        'CookID' => $cookid,
        'CookbookID' => $cookbookid,
        'Pagenum' => $valid->get_value_numeric('pagenum',0),
        'CategoryID' => CheckCategoryStatus($valid->get_value('categorylist')),
        'Notes' => $valid->get_value('notes'),
        'dateadded' => date('Y-m-d'),
        'preptime' => $hrs.':'.$mins,
        'ServingsType' => $servingstype
    );

    if ($servingstype == 1) {
        // Number of people
        $recipedata['ServingsMin'] = $valid->get_value_numeric('servingsmin',0);
        $recipedata['ServingsMax'] = $valid->get_value_numeric('servingsmax',0);
    } elseif ($servingstype == 2) {
        // By the dozen
        $recipedata['ServingsMin'] = $valid->get_value_numeric('dozencount',0);
        $recipedata['ServingsMax'] = 0;
    }

    if (!$dbc->insert('Recipes', $recipedata)) {
        // Unable to save recipe info to database
        $valid->add_error(get_lang('UnableToAddRecipe').$dbc->errorString());

    } else {
        // Recipe info saved

        $recipeid = $dbc->GetInsertID();

        $amountsarray = $valid->get_value('amount');
        $measurementsarray = $valid->get_value('measurementid');
        $ingredientsarray = $valid->get_value('ingredient');
        $groupsarray = $valid->get_value('group');

        $count = count($measurementsarray);

        // Get data for ingredients table, build the queries and then update the ingredients table
        for ($i = 0; $i < $count; $i++) {

            // Only create SQL Insert statement if an ingredient name was given
            if ($ingredientsarray[$i] != '') {

                $insertarray = array(
                    'Name' => ucwords(strtolower($ingredientsarray[$i])),
                    'Amount' => $amountsarray[$i],
                    'Measurementid' => $measurementsarray[$i],
                    'RecipeID' => $recipeid,
                    'GroupID' => $groupsarray[$i]
                );

                if (!$dbc->insert('Ingredients', $insertarray)) {
                    // Insert failed
                    $valid->add_error(get_lang('UnableToSaveIngredient').' "'.$valid->get_value('ingredient'.$i).'"<br />'.get_lang('Reason').' :'.$dbc->errorString());
                }
            }
        } // END for ($i = 0; $i < $count; $i++)
    } // END if (!$dbc->insert('Recipes', $recipedata))

    // Display status of recipe update
    if ($valid->is_error()) {
        // Something went wrong. Redisplay form.

        DisplayForm('addrecipe');
    } else {

        $valid->setValue('recipeid', $recipeid);
        DisplayForm('viewrecipe');
    }
}
// END function SaveNew()
/////

function searchform() {
    /**
     * Displays the search form
     *
     * Added: 2015-06-21
     * Updated: 2015-06-21
    **/

    global $valid;

    $table = new HtmlTable();
    $form = new HtmlForm();

    echo '<script type="text/javascript">
    function validate() {

        if (document.getElementById("recipename").value == \'\' && document.getElementById("ingredientname").value == \'\' && document.getElementById("servingsmin").value == 0 && document.getElementById("servingsmax").value == 0) {
            alert("'.get_lang('SearchTermMissing').'");
            return false;
        }

        // servingsmin cannot be greater than servingsmax but it can be equal to servingsmax
        var smin = parseInt(document.getElementById("servingsmin").value, 10);
        var smax = parseInt(document.getElementById("servingsmax").value, 10);
        if (smin > smax) {
            document.getElementById("servingsmin").focus();
            alert("'.get_lang('ServingsMinGreaterServingsMax').'");
            return false;
        }
    }
</script>
';

    $strmatchlist = array(
        'c' => get_lang('Contains'),
        'e' => get_lang('IsEqualTo'),
        'n' => get_lang('DoesNotContain')
    );

    $nummatchlist = array(
        'lt' => get_lang('LessThan'),
        'lte' => get_lang('LessThanEqualTo'),
        'e' => get_lang('IsEqualTo'),
        'gte' => get_lang('GreaterThanEqualTo'),
        'gt' => get_lang('GreaterThan'),
    );

    // Start the form
    $form->add_hidden(array('ACTON' => 'searchresults','view' => $valid->get_value('view')));
    $form->onsubmit('return validate();');
    $form->start_form(kgGetScriptName(),'post','frmsearch');

    // Start the table
    $table->new_table('centered');

    $table->new_row();
    $table->set_colspan(3);
    $table->new_cell();
    echo '<center><b>'.get_lang('SearchForm').'</b></center>';

    $table->blank_row(3);

    // Recipe name
    $table->new_row();
    $table->set_width(120,'px');
    $table->new_cell();
    echo get_lang('RecipeName');
    $table->new_cell();
    $form->add_select_match_key('rnmatch', $strmatchlist);
    $table->new_cell();
    $form->add_text('recipename');

    // Ingredient name
    $table->new_row();
    $table->new_cell();
    echo get_lang('Ingredient');
    $table->new_cell();
    $form->add_select_match_key('inmatch', $strmatchlist);
    $table->new_cell();
    $form->add_text('ingredientname');

    // Cook name
    $table->new_row();
    $table->new_cell();
    echo get_lang('CookName');
    $table->new_cell();
    $form->add_select_match_key('cookmatch', $strmatchlist);
    $table->new_cell();
    $form->add_text('cookname');

    // Cookbook name
    $table->new_row();
    $table->new_cell();
    echo get_lang('CookbookName');
    $table->new_cell();
    $form->add_select_match_key('cookbookmatch', $strmatchlist);
    $table->new_cell();
    $form->add_text('cookbookname');

    // Servings amount min
    $table->new_row();
    $table->new_cell();
    echo get_lang('Servings').' '.get_lang('Min');
    $table->new_cell();
    $form->add_select_match_key('sminmatch', $nummatchlist);
    $table->new_cell();
    $form->add_select_match_key('servingsmin',range(0,100),0);

    // Servings amount max
    $table->new_row();
    $table->new_cell();
    echo get_lang('Servings').' '.get_lang('Max');
    $table->new_cell();
    $form->add_select_match_key('smaxmatch', $nummatchlist);
    $table->new_cell();
    $form->add_select_match_key('servingsmax',range(0,100),0);

    $table->blank_row(3);

    // The search and cancel buttons
    $table->new_row();
    $table->set_colspan(3);
    $table->new_cell('center');
    $form->add_button_submit(get_lang('Search'));
    echo '&nbsp; &nbsp; ';
    $form->add_button_generic('submit',get_lang('Cancel'),"location.href='".kgCreateLink('',array('NO_TAG' => 'NO_TAG','view' => $valid->get_value('view')))."'");

    $table->end_table();

    $form->end_form();
}
// END function searchform()
/////

function searchresults() {
    /**
     * Displays the results of a search
     * Searches using all info entered
     *
     * Added: 2015-06-21
     * Updated: 2015-06-21
    **/

    global $dbc, $valid;

    $table = new HtmlTable();
    $form = new HtmlForm();

    // Get the search info from the search form
    $name = $valid->get_value('recipename');
    $ingredient = $valid->get_value('ingredientname');
    $rnmatch = $valid->get_value('rnmatch'); // Recipe name match
    $inmatch = $valid->get_value('inmatch'); // Ingredient name match
    $servingsmin = $valid->get_value_numeric('servingsmin',0);
    $servingsmax = $valid->get_value_numeric('servingsmax',0);
    $sminmatch = $valid->get_value('sminmatch');
    $smaxmatch = $valid->get_value('smaxmatch');

    // These are the fields to use when creating the results table
    $fields = array(
        'RecipeID',
        'Name'
    );

    // The final SQL query will be stored here
    $sqltext = '';

    /////
    // BEGIN query generation

    // Filter results using $name
    if ($name != '') {
        $ingredientquerytext = '';
        $servingsquerytext = '';
        if ($ingredient != '') {
            if ($inmatch == 'c') {
                // Ingredient name contains $name
                $ingredientquerytext = $dbc->selectSQLText('Ingredients','Name LIKE "%'.$ingredient.'%"', 'RecipeID');
            } elseif ($inmatch == 'e') {
                // Ingredient name is $name
                $ingredientquerytext = $dbc->selectSQLText('Ingredients','Name = "'.$ingredient.'"', 'RecipeID');
            }
            $ingredientquerytext = ' AND RecipeID IN ('.$ingredientquerytext.')';
        }
        // Servings min
        if ($servingsmin > 0) {
            switch ($sminmatch) {
                case 'lt':
                    $sep = '<';
                    break;
                case 'lte':
                    $sep = '<=';
                    break;
                case 'e':
                    $sep = '=';
                    break;
                case 'gte':
                    $sep = '>=';
                    break;
                case 'gt':
                    $sep = '>';
                    break;
                default:
                    $sep = '=';
                    break;
            }
            $servingsquerytext = ' AND ServingsMin '.$sep.' '.$servingsmin;
        }
        // Servings max
        if ($servingsmax > 0) {
            switch ($smaxmatch) {
                case 'lt':
                    $sep = '<';
                    break;
                case 'lte':
                    $sep = '<=';
                    break;
                case 'e':
                    $sep = '=';
                    break;
                case 'gte':
                    $sep = '>=';
                    break;
                case 'gt':
                    $sep = '>';
                    break;
                default:
                    $sep = '=';
                    break;
            }
            if ($servingsquerytext != '') {
                $servingsquerytext = ' AND ServingsMax '.$sep.' '.$servingsmax;
            } else {
                $servingsquerytext .= ' AND ServingsMax '.$sep.' '.$servingsmax;
            }
        }

        // Put the queries together
        if ($rnmatch == 'c') {
            // Recipe name contains $name
            $sep = 'LIKE';
        } elseif ($rnmatch == 'e') {
            // Recipe name is $name
            $sep = '=';
        } elseif ($rnmatch == 'n') {
            // Recipe name does not contain $name
            $sep = 'NOT LIKE';
        }
        $sqltext = $dbc->selectSQLText('Recipes','Name '.$sep.' "%'.$name.'%"'.$servingsquerytext.$ingredientquerytext, $fields);
    }
    // END if ($name != '')

    // Search without a recipe name
    if ($ingredient != '' && $sqltext == '') {
        $servingsquerytext = '';
        if ($servingsmin > 0) {
            switch ($sminmatch) {
                case 'lt':
                    $sep = '<';
                    break;
                case 'lte':
                    $sep = '<=';
                    break;
                case 'e':
                    $sep = '=';
                    break;
                case 'gte':
                    $sep = '>=';
                    break;
                case 'gt':
                    $sep = '>';
                    break;
                default:
                    $sep = '=';
                    break;
            }
            $servingsquerytext = ' AND ServingsMin '.$sep.' '.$servingsmin;
        }
        if ($servingsmax > 0) {
            switch ($smaxmatch) {
                case 'lt':
                    $sep = '<';
                    break;
                case 'lte':
                    $sep = '<=';
                    break;
                case 'e':
                    $sep = '=';
                    break;
                case 'gte':
                    $sep = '>=';
                    break;
                case 'gt':
                    $sep = '>';
                    break;
                default:
                    $sep = '=';
                    break;
            }
            if ($servingsquerytext != '') {
                $servingsquerytext = ' AND ServingsMax '.$sep.' '.$servingsmax;
            } else {
                $servingsquerytext .= ' AND ServingsMax '.$sep.' '.$servingsmax;
            }
        }
        if ($inmatch == 'c') {
            // Ingredient name contains $name
            $sqltext = $dbc->selectSQLText('Ingredients','Name LIKE "%'.$ingredient.'%"'.$servingsquerytext, 'RecipeID', array('ORDER BY' => 'Name ASC'));
        } elseif ($inmatch == 'e') {
            // Ingredient name is $name
            $sqltext = $dbc->selectSQLText('Ingredients','Name = "'.$ingredient.'"'.$servingsquerytext, 'RecipeID', array('ORDER BY' => 'Name ASC'));
        }
    }
    // END if ($ingredient != '' && $sqltext == '')

    // END query generation
    /////

    // Run the query
    $query = $dbc->query($sqltext);

    // Display results
    if ($dbc->numRows($query) > 0) {
        // At least one matching recipe found

        $table->new_table('centered');

        $table->new_row();
        $table->new_cell('center');
        echo '<b>'.get_lang('SearchResults').'</b>';

        $table->blank_row(1);

        while ($row = $dbc->fetchAssoc($query)) {
            $table->new_row();
            $table->new_cell();
            echo kgCreateLink($row['Name'],array('ACTON' => 'viewrecipe', 'recipeid' => $row['RecipeID']));
        }

        $table->end_table();
    } else {
        // Nothing found
        echo '<center><b>'.get_lang('NoResults').'</b></center>';
    }
}
// END function searchresults()
/////

function tipsaddeditform($dowhat, $view) {
    /**
     * Add a cooking tip
     *
     * Added: 2015-07-11
     * Modified: 2015-12-17
     *
     * @param Required string $dowhat The action to perform
     *              Must be 'insert' to add a new tip or
     *              'update' to edit an existing tip
     * @param Required string $view Value comes from $valid->get_value('view')
     *
     * @return Nothing
    **/

    global $dbc, $valid;

    $table = new HtmlTable();
    $form = new HtmlForm();

    if ($dowhat == 'insert') {
        $form->add_hidden(array('ACTON' => 'tipssavenew', 'view' => $view));
        $frmname = 'frmaddtip';
    } else {
        $id = $valid->get_value_numeric('tipid',0);
        if ($id > 0) {
            $data = $dbc->fetchAssoc($dbc->select('Tips','TipID = '.$id));
        }

        $form->add_hidden(array('ACTON' => 'tipssavechanges', 'tipid' => $id, 'view' => $view));
        $frmname = 'frmedittip';
    }        
    $form->start_form(kgGetScriptName(),'post', $frmname);

    $table->new_table('centered');

    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell('center');
    if ($dowhat == 'insert') {
        echo get_lang('AddTip');
    } else {
        echo get_lang('EditTip');
    }

    // Name
    $table->new_row();
    $table->set_width(60,'px');
    $table->new_cell();
    echo get_lang('Name');
    $table->new_cell();
    if ($dowhat == 'insert') {
        $text = '';
    } else {
        $text = $data['Name'];
    }
    $form->add_text('Name',$text,40);

    // Content/Tip
    $table->new_row();
    $table->new_cell('','top');
    echo get_lang('Tip');
    $table->new_cell();
    if ($dowhat == 'insert') {
        $text = '';
    } else {
        $text = $data['Name'];
    }
    $form->add_textarea('Content',$text,10,15);

    $table->blank_row();

    $table->new_row();
    $table->new_cell();
    $form->add_button_submit(get_lang('SaveTip'));
    $table->new_cell('right');
    $form->add_button_generic('submit',get_lang('cancel_changes'),"location.href='".kgCreateLink('',array('NO_TAG' => 'NO_TAG', 'ACTON' => 'tipsbrowse'))."'");

    $table->end_table();
    $form->end_form();
}
// END function tipsaddeditform()
/////

function tipsbrowse($view) {
    // Display the cooking tips

    global $dbc, $KG_SECURITY;

    $table = new HtmlTable();
    $form = new HtmlForm();

    $query = $dbc->select('Tips','','',array('ORDER BY' => 'Name ASC'));

    $table->new_table('centered bordercollapse');

    // Header row
    $table->new_row();
    $table->set_colspan(2);
    $table->new_cell();
    echo '<b><center>'.get_lang('CookingTips').'</center></b>';

    $table->blank_row();

    $table->new_row();
    $table->set_width(225, 'px');
    $table->new_cell();
    echo '<b>'.get_lang('Name').'</b>';
    $table->set_width(550, 'px');
    $table->new_cell();
    echo '<b>'.get_lang('Summary').'</b>';

    // Display each tip with a summary
    while ($row = $dbc->fetchAssoc($query)) {
        $table->new_row_css('tiptablerows');
        $table->new_cell_css('tipname');
        echo kgCreateLink($row['Name'],array('ACTON' => 'tipsedit', 'tipid' => $row['TipID'], 'view' => $view));
        $table->new_cell_css('tipcontents');
        echo nl2br($row['Content']);
    }

    $table->end_table();

    $table->new_table('centered');

    $table->blank_row();

    $table->new_row();
    // Add tip button
    $KG_SECURITY->hideMsgs();
    if ($KG_SECURITY->hasPermission('add') === true) {
        $table->new_cell();
        $form->add_hidden(array('ACTON' => 'tipsadd', 'view' => $view));
        $form->buttononlyform(kgGetScriptName(),'post','frmaddtip',get_lang('AddTip'));
    } else {
        $table->blank_cell();
    }

    $table->set_width(30,'px');
    $table->blank_cell();

    $table->new_cell();
    $form->add_hidden(array('ACTON' => $view)); 
    $form->buttononlyform(kgGetScriptName(),'post','frmrecipeindex',get_lang('RecipeIndex'));

    $table->end_table();
}
// END function tipsbrowse
/////

function tipssavenew() {
    // Save a new tip to the database

    global $dbc, $valid;

    $name = $valid->get_value('name');
    $dirtytip = $valid->get_value('tip');

    $text = trim($dirtytip);
    $text = str_replace('é','e', $text);
    $text = str_replace('‘','\'', $text);
    $text = str_replace('ﬂ','fl', $text);
    $text = str_replace('ﬁ','fi', $text);
    $text = str_replace('’','\'', $text);
    $text = str_replace('—','-', $text);

    $cleantip = $text;

    // Removes leading and trailing spaces
    $name = trim($name);

    // Only allows a-z, A-Z, 0-9, space and the underscore character
    $name = preg_replace("/[^A-Za-z0-9_ ]/", '', $name);

    $insertarray = array(
        'Name' => ucwords(strtolower($name)),
        'Content' => $cleantip
    );

    if (!$dbc->insert('Tips', $insertarray)) {
        $valid->add_error(get_lang('UnableToAddTip').$dbc->errorString());
    }
}
// END function tipssavenew()
/////

if ($dbc->isConnectedDB() === true) {

    $ACTON = $valid->get_value('ACTON');
    $recipeid = $valid->get_value('recipeid');

    if ($ACTON == 'addcategory') {
        // Add a new category

        AddCategory();

        EditCategories();

    } elseif (($ACTON == 'addrecipe') || ($ACTON == 'editrecipe') || ($ACTON == 'viewrecipe')) {
        // Add, edit, or view recipe details

        DisplayForm($ACTON);

    } elseif ($ACTON == 'deletecategory') {
        // Delete a category

        DeleteCategory();

        EditCategories();

    } elseif ($ACTON == "deleterecipe") {
        // Delete a recipe

        DeleteRecipe();

        ListRecipes();

    } elseif ($ACTON == 'editcategories') {
        // Edit recipe categories

        EditCategories();

    } elseif ($ACTON == 'renamecategory') {
        // Renames a category

        RenameCategory();

        EditCategories();

    } elseif ($ACTON == 'savechangesedit') {
        // Save changes to an existing recipe

        SaveChangesEdit();

    } elseif ($ACTON == 'savenew') {
        // Saves a new recipe to the database

        SaveNew();

    } elseif ($ACTON == 'browsealpha') {
        // Browse recipes by first letter sorted alphabetically

        ListRecipesAlpha();

    } elseif ($ACTON == 'browsecat') {
        // Browse recipes by category

        ListRecipesCat();

    } elseif ($ACTON == 'searchform') {
        // Load the search form

        searchform();

    } elseif ($ACTON == 'searchresults') {
        // Display search results

        searchresults();

    } elseif ($ACTON == 'tipsadd') {
        // Add a cooking tip

        tipsaddeditform('insert', $valid->get_value('view'));

    } elseif ($ACTON == 'tipsedit') {
        // Edit an existing cooking tip

        tipsaddeditform('update', $valid->get_value('view'));

    } elseif ($ACTON == 'tipsbrowse') {
        // Browse through the cooking tips

        tipsbrowse($valid->get_value('view'));

    } elseif ($ACTON == 'tipssavenew') {
        // Browse through the cooking tips

        tipssavenew();

        tipsbrowse($valid->get_value('view'));

    } else {
        // List all recipes in database

        ListRecipesAlpha();

    } // END if ($ACTON == 'viewrecipe')
} // END if ($dbc->isConnectedDB() === true)
?>
