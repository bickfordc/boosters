$(function () {
    $("#list").jqGrid({
        url: "getScripOrderData.php",
        datatype: "xml",
        mtype: "GET",
        colNames: ["Order Date", "Order ID", "Family First", "Family Last", "Rebate", "Student First", "Middle", "Student Last"],
        colModel: [
            { name: "order_date", width: 50, searchoptions:{sopt: ['eq','lt','le','gt','ge']} },
            { name: "order_id", width: 50 },
            { name: "scrip_first", width: 60 },
            { name: "scrip_last", width: 60 },
            { name: "rebate", width: 40,  align: "right", searchoptions:{sopt: ['eq','lt','le','gt','ge']}},
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
        caption: "ShopWithScrip Orders",
        height: "auto",
        autowidth: true,
        loadError: function(jqXHR, textStatus, errorThrown) {
               alert('HTTP status code: ' + jqXHR.status + 
              'textStatus: ' + textStatus +
              'errorThrown: ' + errorThrown);
        }
    }); 
    
    $("#list").jqGrid('navGrid', "#pager", 
    { edit: false, add: false, del: false, search: true, refresh: true });
    
    // add custom button to export the data to excel
    $("#list").jqGrid('navButtonAdd','#pager',{
       caption:"", title:"Export to csv format", 
       onClickButton : function () { 
           //jQuery("#list").excelExport();
           createCsvFromGrid("list", "scripOrders");
       } 
    });
    //jQuery("#mysearch").jqGrid('filterGrid','#list',options);
}); 
