<?php

require_once 'header.php';

if (!$loggedin) 
{
  header("Location: login.php");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentId = (int) sanitizeString($_POST[ 'studentid' ]);
    $student = sanitizeString($_POST[ 'student' ]);
    $scripFamily = sanitizeString($_POST[ 'scripFamily' ]);
    
    try {
        if (empty($student) || empty($scripFamily)) {
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
        
        $familyFirst = "";
        $familyLast = "";
        parseScripFamilyName($scripFamily, $familyFirst, $familyLast);
        
        assignFamily($studentId, $familyFirst, $familyLast);
        
        $msg = "Assigned Scrip family '$familyFirst $familyLast' to student $student";
        echo "<p class='successMessage pageMessage'>$msg</p>";
        
    } catch (Exception $ex) {
        $err = $ex->getMessage();
        echo "<p class='errorMessage pageMessage'>$err</p>";
    }
} else {
    $pageMsg = "Notes: Before assigning a Scrip family to a student you must have<br>" .
               "<a href='importScripFamilies.php'>imported the Scrip family</a> and <a href='addStudent.php'>added the student</a>";
      
    echo "<p class='pageMessage'>$pageMsg</p>";
}

function parseScripFamilyName($fullName, &$first, &$last) {
    
    //$fullName = trim($fullName);
    
    $parts = preg_split('/\s+/', $fullName);
    $len = count($parts);
    
    if ($len < 2) {
        throw new Exception("Invalid Scrip family name $fullName");
    } 
 
    $first =$parts[0];
    $last = $parts[1];
}

function assignFamily($studentId, $familyFirst, $familyLast) {

    // TODO make sure family is not already assigned to student.
    
    $result = pg_query_params(
        "UPDATE scrip_families SET student=$1 WHERE family_first=$2 AND family_last=$3", 
        array($studentId, $familyFirst, $familyLast));
    if ($result) {
        $affectedRows = pg_affected_rows($result);
        if ($affectedRows != 1) {
            throw new Exception("Update to scrip family failed.");
        }
    }
    if (!$result) {
        throw new Exception(pg_last_error());
    }
}

echo <<<_END
<script src="js/autocomplete.js"></script>
        
<div class="form">
      <form method='post' action='assignScripToStudent.php'>
        <div id='info' class='error'>$error</div>
        <input type='text' placeholder='Scrip family (start typing to search)' id='scripFamily' name='scripFamily' autocomplete='off'>
        <div id="scripFamilyResults" class='searchresults'></div>
        <input type='text' placeholder='student (start typing to search)' id='student' name='student' autocomplete='off'>
        <div id="results" class='searchresults'></div>
        <button type='submit'>Assign Scrip family to student</button>
        <input type='hidden' id='studentid' name='studentid' value=''>
      </form>
    </div>
</body>
</html>
_END;

