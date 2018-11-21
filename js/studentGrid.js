
$(function () {
    $("#list").jqGrid({
        url: "getStudentData.php",
        datatype: "xml",
        mtype: "GET",
        colNames: ["Id", "First name", "Middle name", "Last name", "Balance", "Active"],
        colModel: [
            { name: "id", width: 55 },
            { name: "first", width: 200, editable: true },
            { name: "middle", width: 100, editable: true },
            { name: "last", width: 200, editable: true },
            { name: "balance", width: 75, editable: false, align: "right" },
            { name: "active", width: 50, editable: true, align: "right", 
              formatter: "checkbox", edittype: "checkbox",
              editoptions: { value: "true:false"} 
            }
            //{ name: "total", width: 80, align: "right" },
            //{ name: "note", width: 150, sortable: false }
        ],
        editurl: "editStudentData.php",
        pager: "#pager",
        rowNum: 15,
        // rowList: [10, 20, 30],
        sortname: "last",
        sortorder: "asc",
        viewrecords: true,
        gridview: true,
        autoencode: true,
        caption: "Students",
        height: "auto",
        autowidth: true,
        subGrid: true,
        subGridUrl: "getStudentCards.php",
        subGridModel: [
            {
                name: ["King Soopers Cards"],
                width: [80],
                align: ["left"]
                //params: ["id"]
            }
        ],
        loadError: function(jqXHR, textStatus, errorThrown) {
               alert('HTTP status code: ' + jqXHR.status + '<br>' +
              'textStatus: ' + textStatus + '<br>' +
              'errorThrown: ' + errorThrown + '<br>' +
              'HTTP message body:<br><br>' + jqXHR.responseText);
        }
    }); 
    
    $("#list").jqGrid('navGrid', "#pager", 
        {alerttext: "No row is selected"}, // general navigator parameters
        {editCaption: "Edit student"},     // modal edit   window parameters
        {addCaption: "Add a student"},     // modal add    window parameters
        {caption: "Delete student",        // modal del    window parameters
         width:500, msg: "Delete selected student?"},  
        {width:600},                       // modal search window parameters
        {}                                 // modal view   window parameters
    );
    //jQuery("#mysearch").jqGrid('filterGrid','#list',options);
    
    // add custom button to export the data to excel
    $("#list").jqGrid('navButtonAdd','#pager',{
       caption:"", title:"Export to csv format", 
       onClickButton : function () { 
           createCsvFromGrid("list", "students");
       } 
    });
}); 

