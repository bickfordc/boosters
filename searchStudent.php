<?php

//include the information needed for the connection to database server. 
// 
require_once 'functions.php';

$key=$_GET['key'];
$array = array();

$result = queryPostgres("SELECT * FROM students WHERE UPPER(first) LIKE UPPER('%{$key}%') "
. "OR UPPER(last) LIKE UPPER('%{$key}%')", array()); 

while($row = pg_fetch_array($result))
{
    $array[$row['id']] = $row['first'] . " " . $row['middle'] . " " .$row['last'];
}
echo json_encode($array);

?>
