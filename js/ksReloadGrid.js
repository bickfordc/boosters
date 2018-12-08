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
        colNames: ["Transaction date", "Card", "Original invoice", "Original invoice date", "Reload amount"],
        colModel: [
            { name: "reload_date", width: 55, editable: false,
//              formatter: 'date',
//                formatoptions: { 
//                    srcformat: 'ISO8601Long', 
//                    newformat: 'm/d/Y', 
//                    defaultValue:null 
//                }, 
//              edittype: 'text', 
//              editoptions: { 
//                size: 12, 
//                maxlengh: 12, 
//                dataInit: function (element) { 
//                  $(element).datepicker({ dateFormat: 'mm/dd/yy' })
//                }
//              }, 
//              editrules: { date: true } 
            },
            { name: "card", width: 100, editable: false },
            { name: "original_invoice_number", width: 75, editable: false },
            { name: "original_invoice_date", width: 75, editable: false },
            { name: "reload_amount", width: 75, editable: false, align: "right", searchoptions:{sopt: ['eq','lt','le','gt','ge']} }
            //{ name: "total", width: 80, align: "right" },
            //{ name: "note", width: 150, sortable: false }
        ],
        //editurl: "editStudentData.php",
        pager: "#pager",
        rowNum: 15,
        // rowList: [10, 20, 30],
        sortname: "reload_date",
        sortorder: "asc",
        viewrecords: true,
        gridview: true,
        autoencode: true,
        caption: "King Soopers card reloads",
        height: "auto",
        autowidth: true,
        //subGrid: true,
        //subGridUrl: "getStudentCards.php",
//        subGridModel: [
//            {
//                name: ["King Soopers Cards"],
//                width: [80],
//                align: ["left"]
//                //params: ["id"]
//            }
//        ],
        loadError: function(jqXHR, textStatus, errorThrown) {
               alert('HTTP status code: ' + jqXHR.status + '<br>' +
              'textStatus: ' + textStatus + '<br>' +
              'errorThrown: ' + errorThrown + '<br>' +
              'HTTP message body:<br><br>' + jqXHR.responseText);
        }
    }); 
    
    $("#list").jqGrid('navGrid', "#pager", 
        {alerttext: "No row is selected"}, // general navigator parameters
        //{editCaption: "Edit student"},     // modal edit   window parameters
        //{addCaption: "Add a student"},     // modal add    window parameters
        //{caption: "Delete student",        // modal del    window parameters
        // width:500, msg: "Delete selected student?"},  
        {width:600},                       // modal search window parameters
        {}                                 // modal view   window parameters
    );
    //jQuery("#mysearch").jqGrid('filterGrid','#list',options);
    
    // add custom button to export the data to excel
    $("#list").jqGrid('navButtonAdd','#pager',{
       caption:"", title:"Export to csv format", 
       onClickButton : function () { 
           createCsvFromGrid("list", "ksCardReloads");
       } 
    });
}); 

