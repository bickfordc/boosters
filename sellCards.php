<?php

require_once 'header.php';

if (!$loggedin) 
{
  header("Location: login.php");
}

$error = "";
$pageMsg = "Begin typing a student name or card number to search. <em>TIP: Try typing " .
           "just the last 3 or 4 digits of a card.</em><br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentId = (int) sanitizeString($_POST[ 'studentid' ]);
    $student = sanitizeString($_POST[ 'student' ]);
    $cardNumber = sanitizeString($_POST[ 'card' ]);
    $cardHolder = sanitizeString($_POST[ 'cardholder' ]);
    
    if (!empty($student) && !empty($cardNumber)) {
        $first = "";
        $last = "";
        $middle = ""; //TODO account for middle name.
        parseStudentName($student, $first, $last);
        $studentId = getStudentIdByName($first, $middle, $last);
        if ($studentId == NULL) {
            $pageMsg = "Card assignment failed:<br>" .
                       "Student " . $first . " " . $last . " not found.";
        } else {
            
            if (assignCardToStudent($studentId, $cardNumber, $cardHolder, $errorMsg)) {
                $pageMsg = "Successfully assigned card " . $cardNumber . " to student " . $student;
                if (!empty($cardHolder)) {
                    $pageMsg .= "<br>Card holder = " . $cardHolder;
                }   
            } else {
                $pageMsg = "Card assignment failed.<br>" . $errorMsg;
            }
        }   
    } else {
        $error = "Provide a student and a card.";
    }
}

echo <<<_END
<script src="js/autocomplete.js"></script>

<p class='pageMessage'>$pageMsg</p>
        
<div class="form">
      <form method='post' action='sellCards.php' autocomplete='off'>
        <div id='info' class='error'>$error</div>
        <input type='text' placeholder='student' id='student' name='student' autocomplete='off'>
        <div id="results" class='searchresults'></div>
        <input type='text' placeholder='card number' id='card' name='card' autocomplete='off'>
        <div id="cardresults" class='searchresults'></div>
        <div data-tip="The optional card holder field is the person that will actually be using the card on behalf of the student.">
          <input type='text' placeholder='card holder (optional)' name='cardholder' autocomplete='off'> 
        </div>
        <button type='submit'>Assign card to student</button>
        <input type='hidden' id='studentid' name='studentid' value=''>
      </form>
    </div>
</body>
</html>
_END;

function assignCardToStudent($studentId, $cardNumber, $cardHolder, &$errorMsg) {
    
    $returnVal = true;
    $errorMsg = "";
    pg_query("BEGIN");
    
    $result = pg_query_params("UPDATE cards SET sold='t' WHERE id=$1", array($cardNumber)); 
    if (!$result) {
       $errorMsg = pg_last_error();
       $returnVal = false;
    }
    
    if (!empty($cardHolder)) {
        $result = pg_query_params("UPDATE cards SET card_holder=$1 WHERE id=$2", array($cardHolder, $cardNumber));
        if (!$result) {
            $errorMsg .= pg_last_error();
            $returnVal = false;
        }
    }
    
    $result = pg_query_params("INSERT INTO student_cards VALUES ($1, $2)", array($studentId, $cardNumber));
    if (!$result) {
        $errorMsg .= pg_last_error();
        $returnVal = false;
    }
    
    $result = pg_query("COMMIT");
    if (!$result) {
        $errorMsg .= pg_last_error(); 
        $returnVal = false;
    } 
    
    return $returnVal;
}

function parseStudentName($fullName, &$first, &$last) {
    
    $first = "";
    $last = "";
    
    $parts = preg_split('/\s+/', $fullName);
    $len = count($parts);
    if ($len >= 1) {
        // We consider the last element to be the last name
        $last = $parts[$len - 1];
        
        // And all preceding elements make up the first name
        for ($i = 0; $i < $len - 1; $i++) {
            $first .= $parts[$i] . " ";
        }
        $first = trim($first);
    }
}