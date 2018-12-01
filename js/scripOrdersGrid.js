$(function () {
    $("#list").jqGrid({
        url: "getScripOrderData.php",
        datatype: "xml",
        mtype: "GET",
        colNames: ["Order Date", "Order ID", "Family First", "Family Last", "Rebate Amount", "Student First", "Student Middle", "Student Last"],
        colModel: [
            { name: "order_date", width: 40, editable: false },
            { name: "order_id", width: 40, editable: false },
            { name: "scrip_first", width: 100, editable:false },
            { name: "scrip_last", width: 100, editable: false },
            { name: "rebate", width: 40, editable: false, align: "right"},
            { name: "first", width: 60 },
            { name: "middle", width: 60 },
            { name: "last", width: 60 }
        ],
        //editurl: "editCardData.php",
        pager: "#pager",
        rowNum: 15,
        // rowList: [10, 20, 30],
        sortname: "order_date",
        sortorder: "desc",
        viewrecords: true,
        gridview: true,
        autoencode: true,
        caption: "Shop with Scrip Orders",
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
//        {alerttext: "No row is selected"}, // general navigator parameters
//        {editCaption: "Edit card"},     // modal edit   window parameters
//        {addCaption: "Add a card"},     // modal add    window parameters
//        {caption: "Delete card",        // modal del    window parameters
//         width:500, msg: "Delete selected card?"},  
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
