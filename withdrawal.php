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
    $purpose = sanitizeString($_POST[ 'purpose' ]);
    $notes = sanitizeString($_POST[ 'notes' ]);
    
    if (!empty($student) && !empty($amount) && !empty($purpose)) {
        $first = "";
        $last = "";
        $middle = ""; 
        try {
            parseStudentName($student, $first, $middle, $last);
            $studentId = getStudentIdByName($first, $middle, $last);
            if ($studentId == NULL) {
                throw new Exception("Student $first $middle $last not found.");
            } 
            makeWithdrawal($studentId, $amount, convertWithdrawalPurpose($purpose), $notes, $remainingBalance); 
            $pageMsg = "Withdrew $amount from $student<br>Remaining balance: $remainingBalance";          
        } 
        catch (Exception $ex) {
            $pageMsg = "Withdrawal failed.<br>" . $ex->getMessage();
        }
    } else {
        $error = "Provide student, amount, and purpose.";
    }
}

echo <<<_END
<script src="js/autocomplete.js"></script>

<p class='pageMessage'>$pageMsg</p>
        
<div class="form">
      <form method='post' action='withdrawal.php'>
        <div id='info' class='error'>$error</div>
        <input type='text' placeholder='student' id='student' name='student'>
        <div id="results" class='searchresults'></div>
        <input type='text' placeholder='withdrawal amount (e.g. 45.90)' id='amount' name='amount' pattern='[0-9]{1,6}.[0-9]{2}'>
        <input type='text' placeholder='notes (optional, 80 characters)' id='notes' name='notes'>
        <div class='custom-select'>
          <select id='purpose' name='purpose'>
            <option value='0'>withdrawal purpose:</option>
            <option value='1'>travel</option>
            <option value='2'>uniforms</option>
            <option value='3'>instruments</option>
            <option value='4'>lessons</option>
            <option value='5'>consumables</option>
            <option value='6'>other</option>
          </select>
        </div>
        <button type='submit'>Make withdrawal</button>
        <input type='hidden' id='studentid' name='studentid' value=''>
      </form>
    </div>
</body>
</html>
_END;

function makeWithdrawal($studentId, $amount, $purpose, $notes, &$remainingBalance) {
    
    $student = new Student($studentId);
    $balance = $student->getBalance();
    if ($amount > $student->getBalance()) {
        throw new Exception("Requested amount $amount is greater than the student balance of $balance");
    }
    $newBalance = $balance - $amount;
    try {
        pg_query("BEGIN");
        
        $result = pg_query_params("UPDATE students SET balance=$1 WHERE id=$2", array($newBalance, $studentId));
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $result = pg_query_params(
                "INSERT INTO student_withdrawals (student, amount, purpose, notes, date) VALUES ($1, $2, $3, $4, $5)", 
                array($studentId, $amount, $purpose, $notes, date("Y-m-d")));
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        pg_query("COMMIT");
        
        $remainingBalance = number_format($balance - $amount, 2);    
    } 
    catch (Exception $ex) {
        pg_query("ROLLBACK");
        throw $ex;
    }
}

function convertWithdrawalPurpose($purposeNum) {
    $purposeStr = "";
    switch ($purposeNum) {
        case 1:
            $purposeStr = "travel";
            break;
        case 2:
            $purposeStr = "uniforms";
            break;
        case 3:
            $purposeStr = "instruments";
            break;
        case 4:
            $purposeStr = "lessons";
            break;
        case 5:
            $purposeStr = "consumables";
            break;    
       case 6:
            $purposeStr = "other";
            break;    
    }
    return $purposeStr;
}

?>
<script>
/*look for any elements with the class "custom-select":*/
x = document.getElementsByClassName("custom-select");

for (i = 0; i < x.length; i++) {
  selElmnt = x[i].getElementsByTagName("select")[0];
  
  /*for each element, create a new DIV that will act as the selected item:*/
  a = document.createElement("DIV");
  a.setAttribute("class", "select-selected");
  a.innerHTML = selElmnt.options[selElmnt.selectedIndex].innerHTML;
  x[i].appendChild(a);
  
  /*for each element, create a new DIV that will contain the option list:*/
  b = document.createElement("DIV");
  b.setAttribute("class", "select-items select-hide");
  for (j = 1; j < selElmnt.length; j++) {
    /*for each option in the original select element,
    create a new DIV that will act as an option item:*/
    c = document.createElement("DIV");
    c.innerHTML = selElmnt.options[j].innerHTML;
    c.addEventListener("click", function(e) {
        /*when an item is clicked, update the original select box,
        and the selected item:*/
        var y, i, k, s, h;
        s = this.parentNode.parentNode.getElementsByTagName("select")[0];
        h = this.parentNode.previousSibling;
        for (i = 0; i < s.length; i++) {
          if (s.options[i].innerHTML == this.innerHTML) {
            s.selectedIndex = i;
            h.innerHTML = this.innerHTML;
            y = this.parentNode.getElementsByClassName("same-as-selected");
            for (k = 0; k < y.length; k++) {
              y[k].removeAttribute("class");
            }
            this.setAttribute("class", "same-as-selected");
            break;
          }
        }
        h.click();
    });
    b.appendChild(c);
  }
  x[i].appendChild(b);
  a.addEventListener("click", function(e) {
      /*when the select box is clicked, close any other select boxes,
      and open/close the current select box:*/
      e.stopPropagation();
      closeAllSelect(this);
      this.nextSibling.classList.toggle("select-hide");
      this.classList.toggle("select-arrow-active");
  });
}

function closeAllSelect(elmnt) {
  /*a function that will close all select boxes in the document,
  except the current select box:*/
  var x, y, i, arrNo = [];
  x = document.getElementsByClassName("select-items");
  y = document.getElementsByClassName("select-selected");
  for (i = 0; i < y.length; i++) {
    if (elmnt == y[i]) {
      arrNo.push(i)
    } else {
      y[i].classList.remove("select-arrow-active");
    }
  }
  for (i = 0; i < x.length; i++) {
    if (arrNo.indexOf(i)) {
      x[i].classList.add("select-hide");
    }
  }
}

/*if the user clicks anywhere outside the select box,
then close all select boxes:*/
document.addEventListener("click", closeAllSelect);

</script>