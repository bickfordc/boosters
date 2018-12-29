<?php

//include the information needed for the connection to database server. 
// 
require_once 'functions.php';

// Which operation was requested; add, edit, or delete?
$op = $_POST['oper'];    
if ($op === "edit") {
    editFamily();
} elseif ($op === "del") {
    delFamily();
}

function editFamily() {
    $familyFirst = sanitizeString($_POST['family_first']);
    $familyLast = sanitizeString($_POST['family_last']);
    //$active = sanitizeString($_POST['family_active']);
    $notes = sanitizeString($_POST['family_notes']);
        
    $result = queryPostgres("UPDATE scrip_families SET family_notes=$1 WHERE family_first=$2 AND family_last=$3", 
            array($notes, $familyFirst, $familyLast)); 
}

function delFamily() {
    $familyFirst = sanitizeString($_POST['family_first']);
    $familyLast = sanitizeString($_POST['family_last']);
    $id = sanitizeString($_POST['id']);
    
    $result = queryPostgres("DELETE FROM scrip_families WHERE family_first=$1 AND family_last=$2", 
            array($familyFirst, $familyLast)); 
}