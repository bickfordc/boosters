<?php

//include the information needed for the connection to database server. 
// 
require_once 'functions.php';
 
// Four parameters are added to the url as described in colModel.
// Get these parameters to construct the needed query.
// Since we specify in the options of the grid that we will use a GET method 
// we should use the appropriate command to obtain the parameters. 
// In our case this is $_GET. If we specify that we want to use post 
// we should use $_POST. Maybe the better way is to use $_REQUEST, which
// contain both the GET and POST variables. For more information refer to php documentation.

// Get the requested page. By default grid sets this to 1. 
$page = $_GET['page'];
 
// get how many rows we want to have in the grid - rowNum parameter in the grid 
$limit = $_GET['rows']; 

// get index row - i.e. user click to sort. At first this will be sortname parameter 
// after that the index from colModel 
$sidx = $_GET['sidx']; 

// sorting order - At first this will be sortorder parameter
$sord = $_GET['sord'];
 
// if we didn't get a column index to sort on,
// sort on last name;
if(!$sidx) $sidx = "last"; 

$s = "";
$error = "";
if (!validate($page, $limit, $sidx, $sord, $error))
{
    $s = $error;
}
else
{
    // calculate the starting position of the rows 
    $start = $limit*$page - $limit;

    // if start position is negative, set it to 0 
    if($start <0) $start = 0; 

    $where = getWhereClause();
    
    $sql = "SELECT id, first, middle, last, balance, active FROM students $where ORDER BY $sidx $sord OFFSET $start LIMIT $limit";
    $result = queryPostgres($sql, array());
    
    $countResult = queryPostgres("SELECT id, first, middle, last, balance, active FROM students $where", array());
    $count = pg_num_rows($countResult);
    
    if( $count > 0 && $limit > 0) { 
        $total_pages = ceil($count/$limit); 
    } else { 
        $total_pages = 0; 
    } 
    
    // if the requested page is greater than the total, 
    // set the requested page to total pages 
    if ($page > $total_pages) $page=$total_pages;

    // Set the appropriate header information. 
    header("Content-type: text/xml;charset=utf-8");
    
    $s =  "<?xml version='1.0' encoding='utf-8'?>";
    $s .= "<rows>";
    $s .= "<page>".$page."</page>";
    $s .= "<total>".$total_pages."</total>";
    $s .= "<records>".$count."</records>";

    // be sure to put text data in CDATA
    while($row = pg_fetch_array($result)) {
        $s .= "<row id='". $row['id']."'>";            
        $s .= "<cell>". $row['id']."</cell>";
        $s .= "<cell><![CDATA[". $row['first']."]]></cell>";
        $s .= "<cell><![CDATA[". $row['middle']."]]></cell>";
        $s .= "<cell><![CDATA[". $row['last']."]]></cell>";
        $s .= "<cell>". $row['balance']."</cell>";
        $active = $row['active'] == 't' ? "true" : "false";
        $s .= "<cell>".$active."</cell>";
        $s .= "</row>";
    }
    $s .= "</rows>"; 
}
 
echo $s;

function validate($page, $limit, $sidx, $sord, &$error) {
    
    $isValid = false;
    
    if (!preg_match("/^\d+$/", $page)) {
        $error .= "Page value '$page' is not numeric.\n";
    }
    if (!preg_match("/^\d+$/", $limit)) {
        $error .= "Limit value '$limit' is not numeric.\n";
    }
    if (!preg_match("/^\w+$/", $sidx)) {
        $error .= "Invalid sort index: '$sidx'\n";
    }
    if (!preg_match("/^(asc|desc)$/i", $sord)) {
        $error .= "Invalid sort order '$sord' Must be asc or desc\n";
    }
    
    if ($error == "") {
        $isValid = true;
    } else {
        $isValid = false;
        http_response_code(400);
    }
    
    return $isValid;
    
}

function getWhereClause() {
    
    $where = "";
    if ($_GET['_search'] === "true") {
        $searchCol = $_GET['searchField'];
        $searchQuery = $_GET['searchString'];
        $searchOp = $_GET['searchOper'];
        $where = "WHERE ";
        
        switch ($searchOp) {
            
            case "eq":
                $v = pg_escape_literal($searchQuery);
                $where .= $searchCol . "=" . $v;
                break;
            
            case "ne":
                $v = pg_escape_literal($searchQuery);
                $where .= $searchCol . "!=" . $v;
                break;   
            
            case "lt":
                $v = pg_escape_literal($searchQuery);
                $where .= $searchCol . "<" . $v;
                break;  
            
            case "le":
                $v = pg_escape_literal($searchQuery);
                $where .= $searchCol . "<=" . $v;
                break;
            
            case "gt":
                $v = pg_escape_literal($searchQuery);
                $where .= $searchCol . ">" . $v;
                break;
            
            case "ge":
                $v = pg_escape_literal($searchQuery);
                $where .= $searchCol . ">=" . $v;
                break;
            
            case "bw":
                $v = pg_escape_literal($searchQuery . "%");
                $where .= $searchCol . " LIKE " . $v;
                break;
            
            case "bn":
                $v = pg_escape_literal($searchQuery . "%");
                $where .= $searchCol . " NOT LIKE " . $v;
                break;
            
            case "in":
                $pieces = explode(", ", $searchQuery);
                $v = "(";
                $pref = "";
                foreach ($pieces as $segment) {
                    $v .= $pref . pg_escape_literal($segment);
                    $pref = ", ";
                }
                $v .= ")";
                $where .= $searchCol . " IN " . $v;
                break;
            
            case "ni":
                $pieces = explode(", ", $searchQuery);
                $v = "(";
                $pref = "";
                foreach ($pieces as $segment) {
                    $v .= $pref . pg_escape_literal($segment);
                    $pref = ", ";
                }
                $v .= ")";
                $where .= $searchCol . " NOT IN " . $v;
                break;
                
            case "ew":
                $v = pg_escape_literal("%" . $searchQuery);
                $where .= $searchCol . " LIKE " . $v;
                break;
            
            case "en":
                $v = pg_escape_literal("%" . $searchQuery);
                $where .= $searchCol . " NOT LIKE " . $v;
                break;
            
            case "cn":
                $v = pg_escape_literal("%" . $searchQuery . "%");
                $where .= $searchCol . " LIKE " . $v;
                break;
            
            case "nc":
                $v = pg_escape_literal("%" . $searchQuery . "%");
                $where .= $searchCol . " NOT LIKE " . $v;
                break;
            
            case "nu":
                $where .= $searchCol . " IS NULL";
                break;
            
            case "nn":
                $where .= $searchCol . " IS NOT NULL";
                break;
        }
    }
    return $where;
}

?>