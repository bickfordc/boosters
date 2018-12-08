<?php

//include the information needed for the connection to database server. 
// 
require_once 'functions.php';

// Which operation was requested; add, edit, or delete?
$op = $_POST['oper'];    
if ($op === "add") {
    addStudent();
} elseif ($op === "edit") {
    editStudent();
} elseif ($op === "del") {
    delStudent();
}

function addStudent() {
    $first = sanitizeString($_POST['first']);
    $last = sanitizeString($_POST['last']);
    $middle = sanitizeString($_POST['middle']);
    $graduation = sanitizeString($_POST['graduation_year']);
    $active = sanitizeString($_POST['active']);
    
    $result = queryPostgres("INSERT INTO students (first, middle, last, graduation_year, active) VALUES ($1, $2, $3, $4, $5)", 
            array($first, $middle, $last, $graduation, $active)); 
}

function editStudent() {
    $first = sanitizeString($_POST['first']);
    $middle = sanitizeString($_POST['middle']);
    $last = sanitizeString($_POST['last']);
    $graduation = sanitizeString($_POST['graduation_year']);
    if (empty($graduation)) {
        $graduation = NULL;
    }
    $active = sanitizeString($_POST['active']);
    $id = sanitizeString($_POST['id']);
    
    $result = queryPostgres("UPDATE students SET first=$1, middle=$2, last=$3, graduation_year=$4, active=$5 WHERE id=$6", 
            array($first, $middle, $last, $graduation, $active, $id)); 
}

function delStudent() {
    $id = sanitizeString($_POST['id']);
    
    $result = queryPostgres("DELETE FROM students WHERE id=$1", 
            array($id)); 
}