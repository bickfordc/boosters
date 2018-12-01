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
                throw new Exception("That does not appear to be a ShopWithScrip report.");
            }

            $file = fopen($name, "r");
            if ($file == NULL)
            {
                throw new Exception("Could not open file $name");
            }
            
            $orders = array();
            while (!feof($file))
            {
                $row = fgetcsv($file, 300, ","); 
                
                if ($row[0] == NULL) {
                    continue;
                }
                
                $scripOrder = new ScripOrder(
                        $row[0], // family first name
                        $row[1], // family last name
                        $row[4], // order count
                        $row[5], // order id
                        $row[6], // order date
                        $row[7], // value          TODO test number with thousands separator
                        $row[8]  // cost
                );
                
                $orders[] = $scripOrder;
            }
            // Iterate over the orders and determine how student balances are impacted
            $affectedStudents = array();
            foreach ($orders as $order) {
                
                if (($id = $order->getStudentId()) != NULL) {
                    // The card is assigned to a student. 
                    // Add a percentage of the rebate amount to the students running total
                    $student = $affectedStudents[$id];
                    if ($student == NULL) {
                        $student = new Student($id);
                        $affectedStudents[$id] = $student;
                    }
                    $student->adjustBalance($order->getRebate() * STUDENT_PERCENTAGE);
                }
            }
            
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
            $first_name = $row[0];
            $last_name = $row[1];
            $student_name = $row[2];
            $order_id = $row[5];
            
            if ($numFields >= 6 && 
                $first_name == "first_name" &&
                $last_name == "last_name" &&
                $student_name == "student_name" &&
                $order_id == "order_id" )
            {
                $isValid = true;
            }
            
            fclose($file);
        }
       
        //return $isValid;
        return false;  // TODO 
    }
            
    function updateDatabase($orders, $affectedStudents) 
    {
        try {
            pg_query("BEGIN");
            foreach ($orders as $order) {
                $order->insertToDb();
            }
            foreach ($affectedStudents as $student) {
                $student->updateBalanceInDb();
            }
            pg_query("COMMIT");    
        }
        catch(Exception $e) {
            pg_query("ROLLBACK");
            throw $e;
        }
    }
       
    // ($150.75) => -150.75
    // $20       => 20
    // $1,100.25 => 1100.25
    //
    function handleCurrency($moneyString)
    {
        $isNegative = false;
        
        if (substr($moneyString, 0, 1) == "(")
        {
            $isNegative = true;
        }
        
        // strip off parens and $ from ends
        $moneyString = trim($moneyString, "($)");
        
        // strip out commas
        $amount = str_replace(",", "", $moneyString);
        
        if ($isNegative)
        {
            $amount *= -1.00;
        }
        
        return $amount;
    }
    

    
    