$(function () {
    $("#list").jqGrid({
        url: "getScripFamilyData.php",
        datatype: "xml",
        mtype: "GET",
        colNames: ["Family First", "Family Last", "Notes", "Student First", "Middle", "Student Last"],
        colModel: [
            { name: "family_first", width: 60, editable: true, editoptions: {disabled: true} },
            { name: "family_last", width: 60, editable: true, editoptions: {disabled: true} },
            { name: "family_notes", width: 80, editable: true},
            { name: "first", width: 60 },
            { name: "middle", width: 30 },
            { name: "last", width: 60 }
        ],
        editurl: "editScripFamily.php",
        pager: "#pager",
        rowNum: 15,
        // rowList: [10, 20, 30],
        sortname: "family_last",
        sortorder: "asc",
        viewrecords: true,
        gridview: true,
        autoencode: true,
        caption: "ShopWithScrip Families",
        height: "auto",
        autowidth: true,
        loadError: function(jqXHR, textStatus, errorThrown) {
               alert('HTTP status code: ' + jqXHR.status + '<br>' +
              'textStatus: ' + textStatus + '<br>' +
              'errorThrown: ' + errorThrown + '<br>' +
              'HTTP message body:<br><br>' + jqXHR.responseText);
        }
    }); 
    
    $("#list").jqGrid('navGrid', "#pager", 
        {alerttext: "No row is selected"}, // general navigator parameters
        {editCaption: "Edit family"},     // modal edit   window parameters
        {addCaption: "Add a family"},     // modal add    window parameters
        {caption: "Delete a family",        // modal del    window parameters
         width:500, msg: "Delete selected family?"},  
        {width:600},                       // modal search window parameters
        {}                                 // modal view   window parameters
    );
    
    // add custom button to export the data to excel
    $("#list").jqGrid('navButtonAdd','#pager',{
       caption:"", title:"Export to csv format", 
       onClickButton : function () { 
           //jQuery("#list").excelExport();
           createCsvFromGrid("list", "scripFamilies");
       } 
    });
    //jQuery("#mysearch").jqGrid('filterGrid','#list',options);
}); 



