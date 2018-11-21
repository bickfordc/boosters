<?php

require_once 'header.php';

if (!$loggedin) 
{
  header("Location: login.php");
}

$error = "";
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first = sanitizeString($_POST[ 'first' ]);
    $middle = sanitizeString($_POST[ 'middle' ]);
    $last = sanitizeString($_POST[ 'last' ]);
    
    if (!empty($first) && !empty($last)) {
    
        $studentId = getStudentIdByName($first, $middle, $last);
        if ($studentId == NULL) {
            $result = queryPostgres("INSERT INTO students (first, middle, last) VALUES ($1, $2, $3)", 
                array($first, $middle, $last)); 
        
            $message = "Added student " . $first . " " . $middle . " " . $last;
        } else {
            $message = "<span class='error'>Student " . $first . " " . $middle . " " . $last . " already exists.</span>";
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
       <input type='text' placeholder='first name' name='first' value='$first'/>
       <div data-tip="Use a middle name or initial if necessary to distinguish among common names.">
         <input type='text' placeholder='middle name (optional)' name='middle' value='$middle'/>
       </div>
       <input type='text' placeholder='last name' name='last' value='$last'/>
       <button>submit</button>
      </form>
     </div>
    </div>
_END;
  