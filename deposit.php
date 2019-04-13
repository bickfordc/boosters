<?php

require_once 'header.php';
require_once 'orm/Student.php';

if (!$loggedin) 
{
  header("Location: login.php");
}

$error = "";
$pageMsg = "Begin typing a student name to search.";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentId = (int) sanitizeString($_POST[ 'studentid' ]);
    $student = sanitizeString($_POST[ 'student' ]);
    $amount = sanitizeString($_POST[ 'amount' ]);
    $notes = sanitizeString($_POST[ 'notes' ]);
    
    if (!empty($student) && !empty($amount)) {
        $first = "";
        $last = "";
        $middle = ""; 
        try {
            parseStudentName($student, $first, $middle, $last);
            $studentId = getStudentIdByName($first, $middle, $last);
            if ($studentId == NULL) {
                throw new Exception("Student $first $middle $last not found.");
            } 
            makeDeposit($studentId, $amount, $notes, $newBalance); 
            $pageMsg = "Deposited $amount for $student<br>New balance: $newBalance";          
        } 
        catch (Exception $ex) {
            $pageMsg = "Deposit failed.<br>" . $ex->getMessage();
        }
    } else {
        $error = "Provide student, amount, and optional note.";
    }
}

echo <<<_END
<script src="js/autocomplete.js"></script>

<p class='pageMessage'>$pageMsg</p>
        
<div class="form">
      <form method='post' action='deposit.php' autocomplete="off">
        <div id='info' class='error'>$error</div>
        <input type='text' placeholder='student' id='student' name='student' autocomplete="off">
        <div id="results" class='searchresults'></div>
        <input type='text' placeholder='deposit amount (e.g. 45.90)' id='amount' name='amount' pattern='[0-9]{1,6}.[0-9]{2}'>
        <input type='text' placeholder='notes (optional, 80 characters)' id='notes' name='notes'>
        <button type='submit'>Make deposit</button>
        <input type='hidden' id='studentid' name='studentid' value=''>
      </form>
    </div>
</body>
</html>
_END;

function makeDeposit($studentId, $amount, $notes, &$newBalance) {
    
    $student = new Student($studentId);
    $balance = $student->getBalance();
    $newBalance = $balance + $amount;
    try {
        pg_query("BEGIN");
        
        $result = pg_query_params("UPDATE students SET balance=$1 WHERE id=$2", array($newBalance, $studentId));
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $result = pg_query_params(
                "INSERT INTO student_deposits (student, amount, notes, date) VALUES ($1, $2, $3, $4)", 
                array($studentId, $amount, $notes, date("Y-m-d")));
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        pg_query("COMMIT");
        
        $newBalance = number_format($newBalance, 2);    
    } 
    catch (Exception $ex) {
        pg_query("ROLLBACK");
        throw $ex;
    }
}

?>
