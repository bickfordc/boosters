/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(function () {
    $("#list").jqGrid({
        url: "getKsReloadData.php",
        datatype: "xml",
        mtype: "GET",
        colNames: ["Reload date", "Card", "Original invoice", "Original invoice date", "Reload amount", "Student first", "Middle", "Student last"],
        colModel: [
            { name: "reload_date", width: 55, searchoptions:{sopt: ['eq','lt','le','gt','ge']} },
            { name: "card", width: 100 },
            { name: "original_invoice_number", width: 75 },
            { name: "original_invoice_date", width: 85, searchoptions:{sopt: ['eq','lt','le','gt','ge']} },
            { name: "reload_amount", width: 68, align: "right", searchoptions:{sopt: ['eq','lt','le','gt','ge']} },
            { name: "first", width: 63 },
            { name: "middle", width: 40 },
            { name: "last", width: 63 }
        ],
        pager: "#pager",
        rowNum: 15,
        sortname: "reload_date",
        sortorder: "asc",
        viewrecords: true,
        gridview: true,
        autoencode: true,
        caption: "King Soopers card reloads",
        height: "auto",
        autowidth: true,
 
//        loadError: function(jqXHR, textStatus, errorThrown) {
//               alert('HTTP status code: ' + jqXHR.status + '<br>' +
//              'textStatus: ' + textStatus + '<br>' +
//              'errorThrown: ' + errorThrown + '<br>' +
//              'HTTP message body:<br><br>' + jqXHR.responseText);
//        }
        loadError: function(jqXHR, textStatus, errorThrown) {
               alert('HTTP status code: ' + jqXHR.status  +
              ' textStatus: ' + textStatus  +
              ' errorThrown: ' + errorThrown);
        }
    }); 
    
    $("#list").jqGrid('navGrid', "#pager", 
    { edit: false, add: false, del: false, search: true, refresh: true });

    //jQuery("#mysearch").jqGrid('filterGrid','#list',options);
    
    // add custom button to export the data to excel
    $("#list").jqGrid('navButtonAdd','#pager',{
       caption:"", title:"Export to csv format", 
       onClickButton : function () { 
           createCsvFromGrid("list", "ksCardReloads");
       } 
    });
}); 

