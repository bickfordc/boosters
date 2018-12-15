<?php

require_once 'header.php';
require_once 'report/BoostersActivityReport.php';

if (!$loggedin) 
{
  header("Location: login.php");
}

$gatheredRequirements = false;
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $startDate = trim(sanitizeString($_POST[ 'startDate' ]));
    $endDate = trim(sanitizeString($_POST[ 'endDate' ]));
    
    try {
        if (empty($startDate) || empty($endDate)) {
            $error = "Complete all required fields.";
            throw new Exception();
        }
                       
        $gatheredRequirements = true;
        
    } catch (Exception $ex) {
        $msg = $ex->getMessage();
        $message = "<span class='errorMessage'>$msg</span>";
    }
}

if ($gatheredRequirements == false) {
  echo <<<_END
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
    <script src="//code.jquery.com/jquery-1.12.4.js"></script>
    <script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script> 
    <script src="js/datePicker.js" type="text/javascript"></script>
    <p class='pageMessage'>$message</p>
    <div class='form'>
      <form method='post' action='boostersActivityReport.php' autocomplete='off'>$error
        <input type='text' placeholder='start date' name='startDate' id='startDate'/>
        <input type='text' placeholder='end date' name='endDate' id='endDate'/>
        <div>
        <label>
          <input type="checkbox" name="kscards" id="kscards" checked/>King Soopers cards</label>
        </div>
        <div>
        <label>
          <input type="checkbox" name="scrip" id="scrip" checked/>ShopWithScrip</label>
        </div>
        <div>
        <label>
          <input type="checkbox" name="withdrawals" id="withdrawals" checked/>Student withdrawals</label>
        </div>
        <button>Run activity report</button>
      </form>
    </div>
_END;
} 
else {
//  echo "<div class='tile_div'>" .
//    "<button class='styleButton' id='pdf'>Download as .PDF file</button>" .
//    "<button class='last styleButton' id='done'>Done</button>" .
//    "<div class='clear'></div></div>" .
//    "<script>" .
//      "$('#pdf').click(function(event){ " .
//        "$('body').css('cursor', 'progress');" .
//        "window.location.href = 'download.php';" .
//        "$('body').css('cursor', 'default');" .
//      "});" .
//      "$('#done').click(function(event){ " .
//        "window.location.href = 'index.php'" .
//      "});" .
//    "</script>";
    $report = new BoostersActivityReport($startDate, $endDate);
    echo $report->getTable();   
} 