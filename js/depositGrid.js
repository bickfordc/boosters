$(function () {
    $("#list").jqGrid({
        url: "getDepositData.php",
        datatype: "xml",
        mtype: "GET",
        colNames: ["Id", "Date", "Student first", "Student last", "Notes", "Amount"],
        colModel: [
            { name: "id", width: 20, editable: false },
            { name: "date", width: 50, editable: false },
            { name: "first", width: 80, editable: false },
            { name: "last", width: 80, editable: false },
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
        caption: "Student deposits",
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
    
    // add custom button to export the data to excel
    $("#list").jqGrid('navButtonAdd','#pager',{
       caption:"", title:"Export to csv format", 
       onClickButton : function () { 
           createCsvFromGrid("list", "withdrawals");
       } 
    });
}); 
