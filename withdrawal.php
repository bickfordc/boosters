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
            makeWithdrawal($studentId, $amount, $remainingBalance); 
            $pageMsg = "Withdrew $amount from $student<br>Remaining balance: $remainingBalance";          
        } 
        catch (Exception $ex) {
            $pageMsg = "Withdrawal failed.<br>" . $ex->getMessage();
        }
    } else {
        $error = "Provide a student and an amount.";
    }
}

echo <<<_END
<script src="js/autocomplete.js"></script>

<p class='pageMessage'>$pageMsg</p>
        
<div class="form">
      <form method='post' action='withdrawal.php' autocomplete='off'>
        <div id='info' class='error'>$error</div>
        <input type='text' placeholder='student' id='student' name='student' autocomplete='off'>
        <div id="results" class='searchresults'></div>
        <input type='text' placeholder='withdrawal amount (e.g. 45.90)' id='amount' name='amount' autocomplete='off' pattern='[0-9]{1,6}.[0-9]{2}'>
        <button type='submit'>Make withdrawal</button>
        <input type='hidden' id='studentid' name='studentid' value=''>
      </form>
    </div>
</body>
</html>
_END;

function makeWithdrawal($studentId, $amount, &$remainingBalance) {
    
    $student = new Student($studentId);
    $balance = $student->getBalance();
    if ($amount > $student->getBalance()) {
        throw new Exception("Requested amount $amount is greater than the student balance of $balance");
    }
    $newBalance = $balance - $amount;
    
    $result = pg_query_params("UPDATE students SET balance=$1 WHERE id=$2", array($newBalance, $studentId));
  
    $remainingBalance = number_format($balance - $amount, 2); 
}

function assignCardToStudent($studentId, $cardNumber, $cardHolder, &$errorMsg) {
    
    $errorMsg = "";
    $sql;
    $args;
    
    if (!empty($cardHolder)) {
        $sql = "UPDATE cards SET sold='t', card_holder=$1 WHERE id=$2";
        $args = array($cardHolder, $cardNumber);
    } else {
        $sql = "UPDATE cards SET sold='t' WHERE id=$1";
        $args = array($cardNumber);
    }
    
    try {
        $result = pg_query_params("SELECT sold from cards WHERE id=$1", array($cardNumber));
        if (!result) {
            throw new Exception(pg_last_error());
        }
        else {
            if (pg_fetch_result($result, 0, "sold") == "t") {
                throw new Exception("Card $cardNumber is already assigned. Unassign the card first.");
            }
        }
        
        pg_query("BEGIN");

        $result = pg_query_params($sql, $args);
        if ($result) {
            $affectedRows = pg_affected_rows($result);
            if ($affectedRows == 0) {
                throw new Exception("Card " . $cardNumber . " was not found");
            }
        }
        if (!$result) {
           throw new Exception(pg_last_error());
        }

        $result = pg_query_params("INSERT INTO student_cards VALUES ($1, $2)", array($studentId, $cardNumber));
        if (!$result) {
            throw new Exception(pg_last_error());
        }

        $result = pg_query("COMMIT");
        if (!$result) {
            throw new Exception(pg_last_error());
        } 
    } catch (Exception $e)
    {
        $errorMsg = $e->getMessage();
        return FALSE;
    }
    return TRUE;
}

function parseStudentName($fullName, &$first, &$middle, &$last) {
    
    $first = "";
    $middle = "";
    $last = "";
        
    $parts = preg_split('/\s+/', $fullName);
    $len = count($parts);
    
    if ($len < 2) {
        throw new Exception("Must have at least a first and last name");
    } 
    elseif ($len == 2) {
        $first =$parts[0];
        $last = $parts[1];
    } 
    elseif ($len == 3) {
        $first =$parts[0];
        $middle = $parts[1];
        $last = $parts[2];    
    } 
    else {
        throw new Exception("Invalid name $fullName");
    }
}

