<?php

//include the information needed for the connection to database server. 
// 
require_once 'functions.php';

$key=$_GET['key'];
$array = array();

$result = queryPostgres("SELECT family_first, family_last FROM scrip_families WHERE UPPER(family_first) LIKE UPPER('%{$key}%') "
. "OR UPPER(family_last) LIKE UPPER('%{$key}%')", array()); 

while($row = pg_fetch_array($result))
{
    $array[] = $row['family_first'] . " " .$row['family_last'];
}
echo json_encode($array);
