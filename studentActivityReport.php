<?php

require_once 'header.php';

if (!$loggedin) 
{
  header("Location: login.php");
}
// jQuery date picker and student name autocomplete
echo <<<_END
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
  <script src="//code.jquery.com/jquery-1.12.4.js"></script>
  <script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script> 
  <script src="js/datePicker.js" type="text/javascript"></script>
  <script src="js/autocomplete.js"></script>
_END;

$error = "";
//$message = "Add a student";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentName = trim(sanitizeString($_POST[ 'student' ]));
    $startDate = trim(sanitizeString($_POST[ 'startDate' ]));
    $endDate = trim(sanitizeString($_POST[ 'endDate' ]));
    $studentId = trim(sanitizeString($_POST[ 'studentid' ]));
    
    try {
        if (empty($studentName) || empty($startDate) || empty($endDate)) {
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
        
        // Get all card reloads in the date range that are assigned to the student
        $result = pg_query_params(
            "SELECT * from ks_card_reloads WHERE student=$1 AND reload_date>=$2 AND reload_date<=$3", 
            array($studentId, $startData, $endDate));
        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
    } catch (Exception $ex) {
        $msg = $ex->getMessage();
        $message = "<span class='errorMessage'>$msg</span>";
    }
}
    
  echo <<<_END
     <p class='pageMessage'>$message</p>
     <div class='form'>
      <form method='post' action='studentActivityReport.php' autocomplete='off'>$error
       <input type='text' placeholder='student name (start typing to search)' name='student' id='student'/>
       <div id="results" class='searchresults'></div>
       <input type='text' placeholder='start date' name='startDate' id='startDate'/>
       <input type='text' placeholder='end date' name='endDate' id='endDate'/>
       <button>Run student activity report</button>
       <input type='hidden' id='studentid' name='studentid' value=''>
      </form>
     </div>
_END;
  

