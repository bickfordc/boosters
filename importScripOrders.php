<?php

    require 'vendor/autoload.php';
    
    require_once 'header.php';
    require_once 'orm/ScripOrder.php';
    require_once 'orm/Student.php';
     
    if (!$loggedin) die();

    // Handle Mac OS X line endings (LF) on uploaded .csv files
    ini_set("auto_detect_line_endings", true);
    
    //error_reporting(E_ALL);
    //error_reporting(E_ERROR);
    //ini_set('display_errors', 'On');
    //set_time_limit ( 30 );
    
    $fatalError = false;
    $reportComplete = false;
    $name;
    $pageMsg = "Select a ShopWithScrip .csv file";
    
    if ($_FILES)  
    {   
        try {
            $name = $_FILES['filename']['tmp_name']; 
            if (empty($name)) {
                throw new Exception("Please select a file.");
            }

            $type = $_FILES['filename']['type'];
            if ($type != "text/csv" && $type != "application/vnd.ms-excel" && $type != "text/plain")
            {
                throw new Exception("That file type was $type, not text/csv.");
            }

            if (!validateReport($name))
            {
                throw new Exception("That does not appear to be a ShopWithScrip Family Earnings Summary report.");
            }

            $file = fopen($name, "r");
            if ($file == NULL)
            {
                throw new Exception("Could not open file $name");
            }
            
            $orders = array();
            $rowNumber = 0;
            while (!feof($file))
            {
                $row = fgetcsv($file, 300, ","); 
                if (($rowNumber += 1) < 2) {  // second row starts the data
                   continue;
                }
                if ($row[0] == NULL) {
                    continue;
                }
                $orders[] = new ScripOrder($row);
            }
            fclose($file);
            $affectedStudents = getAffectedStudents($orders);
            updateDatabase($orders, $affectedStudents);
            $successMsg = count($orders) . " orders imported.";
        }
        catch(Exception $e) {
            $pageMessage = "";
            $errorMsg = $e->getMessage() . "<br>" . "No orders were imported.";
        }
    }
        
    if (!empty($errorMsg)) {
        echo "<p class='errorMessage pageMessage'>$errorMsg</p>";
    }
    else if (!empty($successMsg)) {
        echo "<p class='successMessage pageMessage'>$successMsg</p>";
    }
        
    echo <<<_END
    <div class="container">
      <div style="float:left;margin:10px">
        <img src='img/scripFamilyEarnings.png'>
      </div>
      <div style="float:left;width:467px;height:823px;margin:10px"> 
        <p>From the <a href='https://shop.shopwithscrip.com/Org/Manage/Report'>Reports</a>
           page at ShopWithScrip, click <b>Run Report</b> for <b>Rebate Summary by Family</b>.
           <br><br>This will bring up a form as shown at left. For Report Begin and End Date it is
           recommended that you choose the first and last day of the past month. For the 
           Output Format, choose Comma Delimited (*.csv).<br><br>
           Click Run Report to save the file to your computer, then select it with the form at right.
           <br><br>If after choosing the .csv file and clicking Import Scrip Orders, you get an error 
           stating <em>That does not appear to be a ShopWithScrip Family Earnings Summary report</em> then
           try opening the file in Excel and then saving as CSV (Comma delimited) (*.csv)
        </p>
      </div>
      <div style="float:left;width:467px;height:423px;margin:10px"> 
        <div class="form">
          <p style="text-align:center">Select the .csv file</p>
          <p class='error'>$formError</p>
          <form method='post' action='importScripOrders.php' enctype='multipart/form-data'>
            <input type='file' name='filename' size='10'>
            <button type='submit'>Import Scrip orders</button>   
          </form>
        </div>
      </div>
    </div>
_END;
        
    function validateReport($tmpName)
    {
        $isValid = false;
        
        if (($file = fopen($tmpName, "r")) !== false)
        {
            $row = fgetcsv($file, 300, ",");
            $numFields = count($row);
            $first_name = trim($row[0]);
            $last_name = trim($row[1]);
            $student_name = trim($row[2]);
            $order_id = trim($row[5]);
            
            if (($numFields == 13) &&
                ($first_name == "first_name") &&
                ($last_name == "last_name") &&
                ($student_name == "student_name") &&
                ($order_id == "order_id"))
            {
                $isValid = true;
            }           
            fclose($file);
        }
        return $isValid;
    }
            
    function getAffectedStudents($orders) {
        $affectedStudents = array();
        foreach ($orders as $order) {
            if (($id = $order->getStudentId()) != NULL) {
                if (Student::isStudentActive($id)) {
                    // The scrip family is assigned to an active student. 
                    // Add the rebate amount to the students running total
                    $student = $affectedStudents[$id];
                    if ($student == NULL) {
                        $student = new Student($id);
                        $affectedStudents[$id] = $student;
                    }
                    $student->adjustBalance($order->getRebate() * RebatePercentages::$STUDENT_SHARE);
                }
            }
        }
        return $affectedStudents;
    }
    
    function updateDatabase($orders, $students) 
    {
        try {
            pg_query("BEGIN");
            
            foreach ($orders as $order) {
                $order->insertOrderToDb();
            }
            
            foreach ($students as $student) {
                $student->updateBalanceInDb();
            }
            
            pg_query("COMMIT");    
        }
        catch(Exception $e) {
            pg_query("ROLLBACK");
            throw $e;
        }
    }
          

    
    