<?php

require_once 'header.php';

if (!$loggedin) 
{
  header("Location: login.php");
}

$error = "";
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first = trim(sanitizeString($_POST[ 'first' ]));
    $middle = trim(sanitizeString($_POST[ 'middle' ]));
    $last = trim(sanitizeString($_POST[ 'last' ]));
    
    if (!empty($first) && !empty($last)) {
        try {
            validateNames(array($first, $middle, $last));
 
            $studentId = getStudentIdByName($first, $middle, $last);
            if ($studentId == NULL) {
                $result = queryPostgres("INSERT INTO students (first, middle, last) VALUES ($1, $2, $3)", 
                    array($first, $middle, $last)); 

                $message = "Added student " . $first . " " . $middle . " " . $last;
            } else {
                $message = "<span class='error'>Student " . $first . " " . $middle . " " . $last . " already exists.</span>";
            }
        } catch (Exception $ex) {
            $msg = $ex->getMessage();
            $error = "<span class='error'>$msg</span>";
        }
    } else {
        $error = "<span class='error'>Provide at least a first and last name</span>";
    }
}
    
  echo <<<_END
    <div>
     <p class='pageMessage'>$message</p>
     <div class='form'>
      <form method='post' action='addStudent.php' autocomplete='off'>$error
       <input type='text' placeholder='first name' name='first'/>
       <div data-tip="Use a middle name or initial if necessary to distinguish among common names.">
         <input type='text' placeholder='middle name (optional)' name='middle'/>
       </div>
       <input type='text' placeholder='last name' name='last'/>
       <button>Add student</button>
      </form>
     </div>
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