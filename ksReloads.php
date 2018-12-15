<?php

    require 'vendor/autoload.php';
    
    require_once 'header.php';
    require_once 'RebatePercentages.php';
    require_once 'orm/KsReload.php';
    require_once 'orm/Student.php';
     
    if (!$loggedin) 
    {
        header("Location: login.php");
    }

    // Handle Mac OS X line endings (LF) on uploaded .csv files
    ini_set("auto_detect_line_endings", true);
    
    //error_reporting(E_ALL);
    //error_reporting(E_ERROR);
    //ini_set('display_errors', 'On');
    //set_time_limit ( 30 );
    
    $fatalError = false;
    $reportComplete = false;
    $name;
    $pageMsg = "Select the Neighborhood Rewards Statement from King Soopers in .csv format";
    
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

            if (!validateKingSoopers($name))
            {
                throw new Exception("That does not appear to be a King Soopers statement.");
            }

            $file = fopen($name, "r");
            if ($file == NULL)
            {
                throw new Exception("Could not open file $name");
            }
            
            $transactions = array();
            $rowNumber = 0;
            while (!feof($file))
            {
                $row = fgetcsv($file, 300, ","); 
                if (($rowNumber += 1) < 3) {  // third row starts the data
                   continue;
                }
                
                if ($row[0] == NULL) {
                    continue;
                }
                
                $ksReload = new KsReload(
                        $row[6], // card
                        $row[0], // transaction date
                        $row[2], // invoice #
                        $row[3], // invoice date
                        $row[5]  // amount
                );
                
                $transactions[] = $ksReload;
            }
            // Iterate over the transactions and determine how student balances are impacted
            $affectedStudents = getAffectedStudents($transactions);

            // Make all the database updates
            try {
                pg_query("BEGIN");
                foreach ($transactions as $trans) {
                    $trans->insertToDb();
                }
                foreach ($affectedStudents as $student) {
                    $student->updateBalanceInDb();
                }
                pg_query("COMMIT");
                $successMsg = count($transactions) . " transactions imported.";
            }
            catch(Exception $e) {
                pg_query("ROLLBACK");
                throw $e;
            }
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
      <form method='post' action='ksReloads.php' enctype='multipart/form-data'>
        <input type='file' name='filename' size='10'>
        <button type='submit'>Import card reloads</button>   
      </form>
    </div>
_END;
        
    function validateKingSoopers($tmpName)
    {
        $isValid = false;
        
        if (($file = fopen($tmpName, "r")) !== false)
        {
            $row = fgetcsv($file, 300, ",");
            $row = fgetcsv($file, 300, ",");
            $row = fgetcsv($file, 300, ","); // Get 3rd line, it is start of real data
            $numFields = count($row);
            $cardNumber = $row[1];           // 2nd field is card number.
            $match = preg_match("/^[0-9]{2}-[0-9]{4}-[0-9]{4}-[0-9]$/", $cardNumber);
            if ($numFields >= 6 && $match == 1)
            {
                $isValid = true;
            }
            
            fclose($file);
        }
       
        return $isValid;
    }
        
    function getAffectedStudents($reloads) {
        $affectedStudents = array();
        foreach ($reloads as $reload) {
            if (($id = $reload->getStudentId()) != NULL) {
                if (Student::isStudentActive($id)) {
                    // The card is assigned to an active student. 
                    // Add the transaction amount to the students running total
                    $student = $affectedStudents[$id];
                    if ($student == NULL) {
                        $student = new Student($id);
                        $affectedStudents[$id] = $student;
                    }
                    $student->adjustBalance($reload->getAmount() 
                        * RebatePercentages::$KS_CARD_RELOAD 
                        * RebatePercentages::$STUDENT_SHARE);
                }
            }
        }
        return $affectedStudents;
    }
    
//    function processKingSoopers($tmpName)
//    {
//        $cardTotals = array();
//        if (($file = fopen($tmpName, "r")) !== false)
//        {
//            $line = 0;
//            while(($row = fgetcsv($file, 300, ",")) !== false)
//            {
//                if (++$line < 3)
//                {
//                    // 3rd line is start of real data
//                    continue;
//                }
//                $transactDate = $row[0];
//                $cardNumber = $row[1];
//                $cardNumber = modifyKingSoopersCardNumber($row[1]);
//                if ($cardNumber == "")
//                {
//                    // Ignore any line without a card number
//                    continue;
//                }
//                $amount = handleCurrency($row[5]);
//                $cardTotals[$cardNumber] += $amount;
//            }     
//        }
//        else
//        {
//            $pageMsg = "Could not open file $tmpName";
//        }
//        return $cardTotals;
//        
//    }
    

    
//    function getCardData($cardTotals, &$cardsNotFound, &$soldCardTotal, &$unsoldCardTotal)
//    {
//        $cards = array();
//        $cardData = array();
//        $notFoundCount = 0;
//        foreach ($cardTotals as $key => $val)
//        {
//            $result = queryPostgres("SELECT * FROM cards where id=$1", array($key));
//            if (pg_num_rows($result) == 0)
//            {
//                $cardsNotFound[$notFoundCount] = $key;
//                $notFoundCount++;
//                //return $cards;
//            }
//            if (pg_num_rows($result) > 1)
//            {
//                // this should never happen.
//                die("Card $key is not unique in database.");
//            }
//            else
//            {
//                $row = pg_fetch_array($result);
//                $cardData["sold"] = $row["sold"];
//                $cardData["cardHolder"] = $row["card_holder"];
//                $cardData["total"] = $val;
//                $cardData["cardNumber"] = $key;
//                if ($cardData["sold"] == "t")
//                {
//                    $cardData["studentId"] = getStudentIdByCard($key);
//                    $soldCardTotal += $val;
//                }
//                else
//                {
//                    $unsoldCardTotal += $val;
//                }
//                $cards[] = $cardData;
//            }
//        }
//        return $cards;  // includes both sold and unsold cards.
//    }
    
    // given cards, an array of cardData arrays
    //   cards[0] => 
    //      sold => t
    //      card_holder => Grace Bickford
    //      total => 100.00
    //      cardNumber => 01-2345-6789-0
    //      studentId => 1156
    // produce students, an array keyed by student 'last first' (for ksort)
    // of studentData arrays.
    //   students[Bickford Emma] =>
    //      ksCards => an array of cardData arrays
    //      ksCardsTotal => 150.00
    //      swCards => an array of cardData arrays
    //      swCardsTotal => 300.00
    //      first   => Emma
    //      last    => Bickford
    //      id      => 1156
//    function groupCardsByStudent($students, $cards, $cardType)
//    {
//        //$students = array();    
//        $cardKey = $cardType . "Cards";
//        
//        foreach($cards as $value)
//        {
//            if ($value["sold"] == "t")
//            {
//                // Get data from students table
//                $studentId = $value["studentId"];
//                $result = queryPostgres("SELECT * FROM students WHERE id=$1", array($studentId));
//                $row = pg_fetch_array($result);
//                $first = $row["first"];
//                $last = $row["last"];
//                $studentKey = $last . " " . $first;
//                
//                if (array_key_exists($studentKey, $students))
//                {
//                    $students[$studentKey][$cardKey][] = $value;
//                }
//                else
//                {
//                    $studCards = array($value);
//                    $studData = array($cardKey => $studCards, "first" => $first, "last" => $last, "id" => $studentId);
//                    $students[$studentKey] = $studData;
//                }                        
//            }
//        }
//        
//        // Calculate student total by card type
//        foreach($students as &$studData)
//        {
//            $sum = 0;
//            $cardData = $studData[$cardKey];
//            foreach($cardData as $card)
//            {
//                $sum += $card["total"];
//            }
//            
//            $studData[$cardKey . "Total"] = $sum;
//        }       
//        
//        return $students;
//    }
//    
//    function addScripFamiliesToStudents($students, $scripFamilies)
//    {
//        foreach($scripFamilies as $family)
//        {
//            if ($family->getStudentId() === NULL)
//            {
//                continue;
//            }
//                
//            $foundStudent = false;
//            foreach ($students as $student)
//            {
//                if ($student["id"] == $family->getStudentId())
//                {
//                    $last = $student["last"];
//                    $first = $student["first"];
//                    $key = $last . " " . $first;
//                    $students[$key]["scripFamilies"][] = $family;
////                    $students[$key]["scripTotalValue"] += $family->getTotalValue();
////                    $students[$key]["scripTotalRebate"] += $family->getTotalRebate();
//                    $foundStudent = true;
//                    break;
//                }
//            }
//            if (!$foundStudent)
//            {
//                // This is a student that is not already in student array because 
//                // there were no grocery card transactions
//                $first = $family->getStudentFirstName();
//                $last = $family->getStudentLastName();
//                $key = $last . " " . $first;
//                $students[$key]["scripFamilies"][] = $family;
//                $students[$key]["first"] = $first;
//                $students[$key]["last"] = $last;
////                $students[$key]["scripTotalValue"] += $family->getTotalValue();
////                $students[$key]["scripTotalRebate"] += $family->getTotalRebate();
//            }
//        }
//        return $students;
//    }
//    
//    function getStudentIdByCard($cardNumber)
//    {
//        $result = queryPostgres("SELECT * FROM student_cards WHERE card=$1", array($cardNumber));
//        if (($row = pg_fetch_array($result)) === false)
//        {
//            $pageMsg = "Card $cardNumber is marked as sold but is not associated with a student.";
//            $fatalError = true;
//        }
//        else
//        {
//            return $row["student"];
//        }
//    }
//    
//    // ($150.75) => -150.75
//    // $20       => 20
//    // $1,100.25 => 1100.25
//    //
//    function handleCurrency($moneyString)
//    {
//        $isNegative = false;
//        
//        if (substr($moneyString, 0, 1) == "(")
//        {
//            $isNegative = true;
//        }
//        
//        // strip off parens and $ from ends
//        $moneyString = trim($moneyString, "($)");
//        
//        // strip out commas
//        $amount = str_replace(",", "", $moneyString);
//        
//        if ($isNegative)
//        {
//            $amount *= -1.00;
//        }
//        
//        return $amount;
//    }
    

    
    /* 
     * King Soopers cards are 19 digits (really text) and are written on the card 
     * with spaces like so;  6006495903 177 095 385
     * For some reason however, in the monthly transaction report, they send us 
     * just the last 11 digits of the number, formatted differently with dashes
     * added, like this; 03-1770-9538-5
     * Presumably then, all KS cards must begin with a prefix of 60064959.
     * Add the prefix, remove the dashes, and add spacing to match how the number 
     * is presented on the card (which is also how we have it stored in the database)
     */
//    function modifyKingSoopersCardNumber($cardNumber) 
//    {
//        $modifiedCardNumber = $cardNumber;
//        
//        // strip everything but digits
//        $strippedCardNumber = preg_replace("/[^0-9]/", "", $cardNumber);
//        
//        // Add the prefix 
//        $strippedCardNumber = "60064959" . $strippedCardNumber;
//        
//        if (strlen($strippedCardNumber) == 19) 
//        {
//            $modifiedCardNumber = formatCardNumber($strippedCardNumber, "KS");
//        }
//        
//        return $modifiedCardNumber;
//    }
        

