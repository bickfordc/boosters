$(function () {
    $("#list").jqGrid({
        url: "getCardData.php",
        datatype: "xml",
        mtype: "GET",
        colNames: ["Card Number", "Assigned", "Card Holder", "Notes", "Active", "Student First", "Student Middle", "Student Last"],
        colModel: [
            { name: "id", width: 100, editable:true },
            { name: "sold", width: 50, editable: true, search: false, formatter: "checkbox",
                edittype: "checkbox", align: "center",
                editoptions: { value: "true:false"} 
            },
            { name: "card_holder", width: 60, editable: true },
            { name: "notes", width: 40, editable: true },
            { name: "active", width: 50, editable: true, search : false, formatter: "checkbox", 
              edittype: "checkbox", align: "center",
              editoptions: { value: "true:false"} 
            },
            { name: "first", width: 60 },
            { name: "middle", width: 60 },
            { name: "last", width: 60 }
        ],
        //editurl: "editCardData.php",
        pager: "#pager",
        rowNum: 15,
        // rowList: [10, 20, 30],
        sortname: "id",
        sortorder: "asc",
        viewrecords: true,
        gridview: true,
        autoencode: true,
        caption: "King Soopers Cards",
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
        {editCaption: "Edit card"},     // modal edit   window parameters
        {addCaption: "Add a card"},     // modal add    window parameters
        {caption: "Delete card",        // modal del    window parameters
         width:500, msg: "Delete selected card?"},  
        {width:600},                       // modal search window parameters
        {}                                 // modal view   window parameters
    );
    
    // add custom button to export the data to excel
    $("#list").jqGrid('navButtonAdd','#pager',{
       caption:"", title:"Export to csv format", 
       onClickButton : function () { 
           //jQuery("#list").excelExport();
           createCsvFromGrid("list", "cards");
       } 
    });
    //jQuery("#mysearch").jqGrid('filterGrid','#list',options);
}); 
