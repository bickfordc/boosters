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
        $middle = ""; 
        try {
        parseStudentName($student, $first, $middle, $last);
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
        } catch (Exception $ex) {
            $pageMsg = "Card assignment failed.<br>" . $ex->getMessage();
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
