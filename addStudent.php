<?php

require_once 'header.php';

if (!$loggedin) 
{
  header("Location: login.php");
}

$error = "";
$pageMsg = "Under construction";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first = sanitizeString($_POST[ 'first' ]);
    $middle = sanitizeString($_POST[ 'middle' ]);
    $last = sanitizeString($_POST[ 'last' ]);
    
    $studentId = getStudentIdByName($first, $middle, $last);
    if ($studentId == NULL) {
        $result = queryPostgres("INSERT INTO students (first, middle, last) VALUES ($1, $2, $3)", 
            array($first, $middle, $last)); 
        
        $pageMsg = "Added student " . $first . " " . $middle . " " . $last;
    } else {
        $pageMsg = "<span class='error'>Student " . $first . " " . $middle . " " . $last . " already exists.</span>";
    }
}
    
    
  echo <<<_END
    <p class='pageMessage'>$pageMsg</p>      
    <div>
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
  