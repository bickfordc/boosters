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
            $successMsg = count($orders) . " transactions imported.";
        }
        catch(Exception $e) {
            $pageMessage = "";
            $errorMsg = $e->getMessage() . "<br>" . "No transactions were imported.";
        }
    }
        
    if (!empty($errorMsg)) {
        echo "<p class='errorMessage pageMessage'>$errorMsg</p>";
    }
    else if (!empty($successMsg)) {
        echo "<p class='successMessage pageMessage'>$successMsg</p>";
    }
        
    echo <<<_END
    <p class='pageMessage'>$pageMsg</p>
    <div class="form">
      <form method='post' action='importScripOrders.php' enctype='multipart/form-data'>
        <input type='file' name='filename' size='10'>
        <button type='submit'>Import Scrip data</button>   
      </form>
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
                    $student->adjustBalance($order->getRebate() * STUDENT_PERCENTAGE);
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
          

    
    