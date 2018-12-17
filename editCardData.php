<?php

//include the information needed for the connection to database server. 
// 
require_once 'functions.php';

// Which operation was requested; add, edit, or delete?
$op = $_POST['oper'];    
if ($op === "edit") {
    editCard();
} elseif ($op === "add") {
    addCard();
}

function addCard() {
    $cardNumber = sanitizeString($_POST['id']);
    $sold = sanitizeString($_POST['sold']);
    $card_holder = sanitizeString($_POST['card_holder']);
    $notes = sanitizeString($_POST['notes']);
    
    $result = queryPostgres("INSERT INTO cards (id, sold, card_holder, notes) VALUES ($1, $2, $3, $4)", 
            array($cardNumber, $sold, $card_holder, $notes)); 
}

function editCard() {
    $cardNumber = sanitizeString($_POST['id']);
    $assigned = sanitizeString($_POST['sold']);
    $card_holder = sanitizeString($_POST['card_holder']);
    $notes = sanitizeString($_POST['notes']);
        
    $result = queryPostgres("UPDATE cards SET id=$1, sold=$2, card_holder=$3, notes=$4 WHERE id=$1", 
            array($cardNumber, $assigned, $card_holder, $notes)); 
}

function delCard() {
    $id = sanitizeString($_POST['id']);
    
    $result = queryPostgres("DELETE FROM cards WHERE id=$1", 
            array($id)); 
}