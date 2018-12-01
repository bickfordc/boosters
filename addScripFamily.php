<?php

require_once 'header.php';

if (!$loggedin) 
{
  header("Location: login.php");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentId = (int) sanitizeString($_POST[ 'studentid' ]);
    $student = sanitizeString($_POST[ 'student' ]);
    $familyFirst = sanitizeString($_POST[ 'first' ]);
    $familyLast = sanitizeString($_POST[ 'last' ]);
    $notes = sanitizeString($_POST[ 'notes' ]);
    
    try {
        if (empty($student) || empty($familyFirst) || empty($familyLast)) {
            $error = "Complete all required fields.";
            throw new Exception();
        }
        if (empty($studentId)) {
            $first = "";
            $last = "";
            $middle = ""; 
            parseStudentName($student, $first, $middle, $last);
            $studentId = getStudentIdByName($first, $middle, $last);
        }
        addFamily($studentId, $familyFirst, $familyLast);
        $msg = "Added Scrip Family $familyFirst $familyLast";
        echo "<p class='successMessage pageMessage'>$msg</p>";
        
    } catch (Exception $ex) {
        $err = $ex->getMessage();
        echo "<p class='errorMessage pageMessage'>$err</p>";
    }
} else {
    $pageMsg = "Notes:<br>"
         . "Before adding a Scrip family you must have <a href='addStudent.php'>added the student</a> it will be linked to.<br>"
         . "Get the first and last name of the family from the Family Accounts at <a href='https://shop.shopwithscrip.com'>ShopWithScrip.com</a>";
    echo "<p class='pageMessage'>$pageMsg</p>";
}

function addFamily($studentId, $familyFirst, $familyLast) {

    $result = pg_query_params(
        "INSERT INTO scrip_families (student, family_first, family_last) VALUES ($1, $2, $3)", 
        array($studentId, $familyFirst, $familyLast));
    if (!$result) {
        throw new Exception(pg_last_error());
    }
}

echo <<<_END
<script src="js/autocomplete.js"></script>
        
<div class="form">
      <form method='post' action='addScripFamily.php'>
        <div id='info' class='error'>$error</div>
        <input type='text' placeholder='student (start typing to search)' id='student' name='student' autocomplete='off'>
        <div id="results" class='searchresults'></div>
        <input type='text' placeholder='family first name' id='first' name='first'>
        <input type='text' placeholder='family last name' id='last' name='last'>
        <button type='submit'>Add Scrip family</button>
        <input type='hidden' id='studentid' name='studentid' value=''>
      </form>
    </div>
</body>
</html>
_END;

