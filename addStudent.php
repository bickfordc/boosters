<?php

require_once 'header.php';

if (!$loggedin) 
{
  header("Location: login.php");
}

$error = "";
$message = "Add a student";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first = trim(sanitizeString($_POST[ 'first' ]));
    $middle = trim(sanitizeString($_POST[ 'middle' ]));
    $last = trim(sanitizeString($_POST[ 'last' ]));
    $graduation = trim(sanitizeString($_POST[ 'graduation' ]));
    
    if (!empty($first) && !empty($last) && !empty($graduation)) {
        try {
            validateNames(array($first, $middle, $last));
 
            $studentId = getStudentIdByName($first, $middle, $last);
            if ($studentId == NULL) {
                $result = pg_query_params("INSERT INTO students (first, middle, last, graduation_year) VALUES ($1, $2, $3, $4)", 
                    array($first, $middle, $last, $graduation)); 

                if (!result) {
                    throw new Exception(pg_last_error());
                }
                
                $message = "<span class='successMessage'>Added student $first $middle $last</span>";
                //echo "<p class='errorMessage pageMessage'>$message</p>";
            } else {
                $message = "<span class='errorMessage'>$first $middle $last already exists</spN>";
                //echo "<p class='errorMessage pageMessage'>$message</p>";
            }
        } catch (Exception $ex) {
            $msg = $ex->getMessage();
            $message = "<span class='errorMessage'>$msg</span>";
        }
    } else {
        $error = "<span class='error'>Provide first and last name and graduation year</span>";
    }
}
    
  echo <<<_END
     <p class='pageMessage'>$message</p>
     <div class='form'>
      <form method='post' action='addStudent.php' autocomplete='off'>$error
       <input type='text' placeholder='first name' name='first'/>
       <div data-tip="Use a middle name or initial if necessary to distinguish among common names.">
         <input type='text' placeholder='middle name (optional)' name='middle'/>
       </div>
       <input type='text' placeholder='last name' name='last'/>
       <input type='text' placeholder='year of graduation' name='graduation' pattern='20[0-9]{2}'/>
       <button>Add student</button>
      </form>
     </div>
_END;
  
function validateNames($names) {
    
    foreach($names as $name) {
        $name = trim($name);
        if ( preg_match('/\s/', $name) ) {
            $proposedName = preg_replace('/\s/', '-', $name);
            throw new Exception("Names may not contain spaces. Consider using $proposedName");
        }
    }
}