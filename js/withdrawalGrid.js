$(function () {
    $("#list").jqGrid({
        url: "getWithdrawalData.php",
        datatype: "xml",
        mtype: "GET",
        colNames: ["Id", "Date", "Student first", "Student last", "Purpose", "Notes", "Amount"],
        colModel: [
            { name: "id", width: 20, editable: false },
            { name: "date", width: 50, editable: false },
            { name: "first", width: 80, editable: false },
            { name: "last", width: 80, editable: false },
            { name: "purpose", width: 50, editable: false },
            { name: "notes", width: 200, editable: false },
            { name: "amount", width: 40, editable: false, align: "right", searchoptions:{sopt: ['eq','lt','le','gt','ge']} },
        ],
        //editurl: "editCardData.php",
        pager: "#pager",
        rowNum: 15,
        // rowList: [10, 20, 30],
        sortname: "date",
        sortorder: "asc",
        viewrecords: true,
        gridview: true,
        autoencode: true,
        caption: "Student withdrawals",
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
    { edit: false, add: false, del: false, search: true, refresh: true });
//        {alerttext: "No row is selected"}, // general navigator parameters
//        {editCaption: "Edit card"},     // modal edit   window parameters
//        {addCaption: "Add a card"},     // modal add    window parameters
//        {caption: "Delete card",        // modal del    window parameters
//         width:500, msg: "Delete selected card?"},  
//        {width:600},                       // modal search window parameters
//        {}                                 // modal view   window parameters
//    );
    
    // add custom button to export the data to excel
    $("#list").jqGrid('navButtonAdd','#pager',{
       caption:"", title:"Export to csv format", 
       onClickButton : function () { 
           //jQuery("#list").excelExport();
           createCsvFromGrid("list", "withdrawals");
       } 
    });
    //jQuery("#mysearch").jqGrid('filterGrid','#list',options);
}); 
