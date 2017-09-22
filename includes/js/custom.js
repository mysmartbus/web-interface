function openwindow(db,listname,fid,settings) {
    alert('Needs to be converted to modal dialog');
/**
    settings = settings || "nope";
    var width = settings.width || 400;
    var height = settings.height || 150;
    var module = settings.module || "index";

    var url = "/includes/_listpopup.php?db="+db+"&listname="+listname+"&field="+fid+"&kg_module="+module;
    window.open(url,"_blank",'width=' + width + ',height=' + height + ',status=yes,toolbar=no,menubar=no,location=no');
**/
}

function jsOrdinal(i) {
    /**
     * Adds (st, nd, rd, th) to the end of a number
     *
     * Added: 2017-03-07
     * Modified: 2017-03-07
     *
     * @param Required int The number to work with
     *
     * @return string
    **/

    var j = i % 10;
    var k = i % 100;

    if (j == 1 && k != 11) {
        return i + "st";
    }

    if (j == 2 && k != 12) {
        return i + "nd";
    }

    if (j == 3 && k != 13) {
        return i + "rd";
    }

    return i + "th";
}
