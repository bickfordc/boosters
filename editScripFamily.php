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

function addStudent() {
    $first = sanitizeString($_POST['family_first']);
    $last = sanitizeString($_POST['family_last']);
    $middle = sanitizeString($_POST['middle']);
    $active = sanitizeString($_POST['active']);
    
    $result = queryPostgres("INSERT INTO students (first, middle, last, active) VALUES ($1, $2, $3, $4)", 
            array($first, $middle, $last, $active)); 
}

function editFamily() {
    $familyFirst = sanitizeString($_POST['family_first']);
    $familyLast = sanitizeString($_POST['family_last']);
    $active = sanitizeString($_POST['family_active']);
    $notes = sanitizeString($_POST['family_notes']);
        
    $result = queryPostgres("UPDATE scrip_families SET family_active=$1, family_notes=$2 WHERE family_first=$3 AND family_last=$4", 
            array($active, $notes, $familyFirst, $familyLast)); 
}

function delFamily() {
    $id = sanitizeString($_POST['id']);
    
    $result = queryPostgres("DELETE FROM students WHERE id=$1", 
            array($id)); 
}