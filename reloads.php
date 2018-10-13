<?php

    require 'vendor/autoload.php';
    
    require_once 'header.php';
    require_once 'RebateReport.php';
    require_once 'RebatePercentages.php';
    require_once 'ScripFamily.php';
     
    if (!$loggedin) die();

    // Handle Mac OS X line endings (LF) on uploaded .csv files
    ini_set("auto_detect_line_endings", true);
    
    //error_reporting(E_ALL);
    //error_reporting(E_ERROR);
    //ini_set('display_errors', 'On');
    //set_time_limit ( 30 );
    
    $fatalError = false;
    $reportComplete = false;
    $names = array();
    $pageMsg = "";
    $skip = array();
    
    if ($_FILES)  
    {   
        //print_r($_FILES);
        $tmpNames = array();
        $types = array();
        $messages = array();
        $uploadError = false;
        
        for ($i = 0; $i <= 2; $i++) 
        {
            $messages[$i] = "OK";
            
            if ($_FILES['file']['error'][$i] === UPLOAD_ERR_OK)
            {
                $skip[$i] = false;
                $names[$i]    = $_FILES['file']['name'][$i];
                $tmpNames[$i] = $_FILES['file']['tmp_name'][$i];
                $types[$i]    = $_FILES['file']['type'][$i];

                if ($i == 0)
                {
                    if (!validateKingSoopers($tmpNames[$i]))
                    {
                        $messages[$i] = "This does not appear to be a King Soopers statement.";
                        $uploadError = true;
                    }
                }
                if ($i == 1)
                {
                    if (!validateSafeway($tmpNames[$i]))
                    {
                        $messages[$i] = "This does not appear to be a Safeway statement.";
                        $uploadError = true;
                    }
                }
                if ($i == 2)
                {
                    if (!validateScrip($tmpNames[$i]))
                    {
                        $messages[$i] = "This does not appear to be a ShopWithScrip statement.";
                        $uploadError = true;
                    }
                }
            }
            else if ($_FILES['file']['error'][$i] === UPLOAD_ERR_NO_FILE)
            {
                // It's ok to leave out file(s) and run the report with only one or two input files.
                $skip[$i] = true;
                $names[$i] = "Not reported";
            }
            else 
            {
                $messages[$i] = "File array error " . $_FILES['file']['error'][$i];
                $uploadError = true; 
            }
        }
        
        if ($uploadError)
        {
            $pageMsg = "There was a problem uploading files.<br>" .
                       "File 1 : $messages[0]<br>File 2 : $messages[1]<br>File 3 : $messages[2]<br>" .
                       "Please try again.";
        }
        else
        {
            if (!$skip[0])
            {
                $ksCardTotals = processKingSoopers($tmpNames[0]);
                $ksCardsNotFound = array();
                $ksSoldCardTotal = 0;
                $ksUnsoldCardTotal = 0;
                $ksCardData = getCardData($ksCardTotals, $ksCardsNotFound, $ksSoldCardTotal, $ksUnsoldCardTotal);
            }
            
            if (!$skip[1])
            {
                $swCardTotals = processSafeway($tmpNames[1]);
                $swCardsNotFound = array();
                $swSoldCardTotal = 0;
                $swUnsoldCardTotal = 0;
                $swCardData = getCardData($swCardTotals, $swCardsNotFound, $swSoldCardTotal, $swUnsoldCardTotal);
            }
            
            if (!$skip[2])
            {
                $scripFamilies = processScrip($tmpNames[2]);
            }
            
            $rebatePercentages = new RebatePercentages(
                    $ksSoldCardTotal + $ksUnsoldCardTotal,
                    $swSoldCardTotal + $swUnsoldCardTotal);
            
            $cardsNotFound = array_merge($ksCardsNotFound, $swCardsNotFound);
            if (count($cardsNotFound) > 0)
            {
                $pageMsg = "The following grocery cards were not found:<br>";
                foreach ($cardsNotFound as $val)
                {
                    $pageMsg .= $val . "<br>";
                }
                $fatalError = true;
            }
            
            //print_r($ksCardData);
            $sudents = array();
            $students = groupCardsByStudent($students, $ksCardData, "ks");
            $students = groupCardsByStudent($students, $swCardData, "sw");
            $students = addScripFamiliesToStudents($students, $scripFamilies);
            
            $report = new RebateReport($students, $rebatePercentages, $ksCardData, $swCardData, 
                    $names, $scripFamilies);
            $reportComplete = true;

            if ($reportComplete === true)
            {
                //file_put_contents("pdfsrc.html", $report->getTable(true));  // CEB isForPdf
                file_put_contents("pdfsrc.html", $report->getTable());
                
                echo "<div class='tile_div'>" .
                     "<button class='styleButton' id='pdf'>Download as .PDF file</button>" .
                     "<button class='last styleButton' id='done'>Done</button>" .
                     "<div class='clear'></div></div>" .
                     "<script>" .
                     "$('#pdf').click(function(event){ " .
                       "$('body').css('cursor', 'progress');" .
                       "window.location.href = 'download.php';" .
                       "$('body').css('cursor', 'default');" .
                     "});" .
                     "$('#done').click(function(event){ " .
                       "window.location.href = 'index.php'" .
                     "});" .
                     "</script>";
                echo $report->getTable();   
            }
        }
    }
    else
    {
        $pageMsg = "Select the .csv files received from King Soopers, Safeway and
        ShopWithScrip.<br>If the file is in another form such as .xls or .xlsx, open it 
        with Excel and save as \"CSV (Comma delimited) (*.csv)\"";
    }
    
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
    
    function validateSafeway($tmpName)
    {
        $isValid = false;
        
        if (($file = fopen($tmpName, "r")) !== false)
        {
            $row = fgetcsv($file, 300, ",");
            $row = fgetcsv($file, 300, ","); // Get 2nd line, it is start of real data
            $numFields = count($row);
            $cardNumber = $row[2];           // 3rd field is card number.
            $match = preg_match("/^[0-9]{19}$/", $cardNumber);
            if ($numFields == 8 && $match == 1)
            {
                $isValid = true;
            }
            
            fclose($file);
        }
       
        return $isValid;
    }
    
    function validateScrip($tmpName) 
    {
        $isValid = false;
        
        if (($file = fopen($tmpName, "r")) !== false)
        {
            $row = fgetcsv($file, 300, ",");
            $row = fgetcsv($file, 300, ","); // Get 2nd line, it is start of real data
            $numFields = count($row);
            $firstName = $row[0];
            $lastName = $row[1];
            $value = $row[7];
            $cost  = $row[8];
            $matchFirst = preg_match("/^[a-zA-Z ]*$/", $firstName);
            $matchLast  = preg_match("/^[a-zA-Z ]*$/", $lastName);
            $matchValue = preg_match("/^\d*[\.]?\d*$/", $value);
            $matchCost  = preg_match("/^\d*[\.]?\d*$/", $cost);
            if ($numFields == 13 && $matchFirst == 1 && $matchLast == 1 &&
                $matchValue == 1 && $matchCost == 1)
            {
                $isValid = true;
            }
            
            fclose($file);
        }
       
        return $isValid;        
    }
    
    function processKingSoopers($tmpName)
    {
        $cardTotals = array();
        if (($file = fopen($tmpName, "r")) !== false)
        {
            $line = 0;
            while(($row = fgetcsv($file, 300, ",")) !== false)
            {
                if (++$line < 3)
                {
                    // 3rd line is start of real data
                    continue;
                }
                $transactDate = $row[0];
                $cardNumber = $row[1];
                $cardNumber = modifyKingSoopersCardNumber($row[1]);
                if ($cardNumber == "")
                {
                    // Ignore any line without a card number
                    continue;
                }
                $amount = handleCurrency($row[5]);
                $cardTotals[$cardNumber] += $amount;
            }     
        }
        else
        {
            $pageMsg = "Could not open file $tmpName";
        }
        return $cardTotals;
        
    }
    
    function processSafeway($tmpName)
    {
        $cardTotals = array();
        if (($file = fopen($tmpName, "r")) !== false)
        {
            $line = 0;
            while(($row = fgetcsv($file, 300, ",")) !== false)
            {
                if (++$line < 2)
                {
                    // 2nd line is start of real data
                    continue;
                }
                $transactDate = $row[4];
                $cardNumber = modifySafewayCardNumber($row[2]);
                if ($cardNumber == "")
                {
                    // Ignore any line without a card number
                    continue;
                }
                $amount = handleCurrency($row[3]);
                $cardTotals[$cardNumber] += $amount;
            }     
        }
        else
        {
            $pageMsg = "Could not open file $tmpName";
        }
        return $cardTotals;
    }
    
    function processScrip($tmpName)
    {
        $scripFamilies = array();
        if (($file = fopen($tmpName, "r")) !== false)
        {
            $line = 0;
            while(($row = fgetcsv($file, 300, ",")) !== false)
            {
                if (++$line < 2)
                {
                    // 2nd line is start of real data
                    continue;
                }
                $firstName = $row[0];
                $lastName = $row[1];
                $fullName = $firstName . " " . $lastName;
                $value = handleCurrency($row[7]);
                $cost  = handleCurrency($row[8]);
                $scripFamily;
                if (array_key_exists($fullName, $scripFamilies))
                {
                    $scripFamily = $scripFamilies[$fullName];
                }
                else
                {
                    $scripFamily = new ScripFamily($firstName, $lastName);
                    $scripFamilies[$fullName] = $scripFamily;
                }
                $scripFamily->addOrder($value, $cost);
            }     
        }
        else
        {
            $pageMsg = "Could not open file $tmpName";
        }
        return $scripFamilies;
    }
    
    function getCardData($cardTotals, &$cardsNotFound, &$soldCardTotal, &$unsoldCardTotal)
    {
        $cards = array();
        $cardData = array();
        $notFoundCount = 0;
        foreach ($cardTotals as $key => $val)
        {
            $result = queryPostgres("SELECT * FROM cards where id=$1", array($key));
            if (pg_num_rows($result) == 0)
            {
                $cardsNotFound[$notFoundCount] = $key;
                $notFoundCount++;
                //return $cards;
            }
            if (pg_num_rows($result) > 1)
            {
                // this should never happen.
                die("Card $key is not unique in database.");
            }
            else
            {
                $row = pg_fetch_array($result);
                $cardData["sold"] = $row["sold"];
                $cardData["cardHolder"] = $row["card_holder"];
                $cardData["total"] = $val;
                $cardData["cardNumber"] = $key;
                if ($cardData["sold"] == "t")
                {
                    $cardData["studentId"] = getStudentIdByCard($key);
                    $soldCardTotal += $val;
                }
                else
                {
                    $unsoldCardTotal += $val;
                }
                $cards[] = $cardData;
            }
        }
        return $cards;  // includes both sold and unsold cards.
    }
    
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
    function groupCardsByStudent($students, $cards, $cardType)
    {
        //$students = array();    
        $cardKey = $cardType . "Cards";
        
        foreach($cards as $value)
        {
            if ($value["sold"] == "t")
            {
                // Get data from students table
                $studentId = $value["studentId"];
                $result = queryPostgres("SELECT * FROM students WHERE id=$1", array($studentId));
                $row = pg_fetch_array($result);
                $first = $row["first"];
                $last = $row["last"];
                $studentKey = $last . " " . $first;
                
                if (array_key_exists($studentKey, $students))
                {
                    $students[$studentKey][$cardKey][] = $value;
                }
                else
                {
                    $studCards = array($value);
                    $studData = array($cardKey => $studCards, "first" => $first, "last" => $last, "id" => $studentId);
                    $students[$studentKey] = $studData;
                }                        
            }
        }
        
        // Calculate student total by card type
        foreach($students as &$studData)
        {
            $sum = 0;
            $cardData = $studData[$cardKey];
            foreach($cardData as $card)
            {
                $sum += $card["total"];
            }
            
            $studData[$cardKey . "Total"] = $sum;
        }       
        
        return $students;
    }
    
    function addScripFamiliesToStudents($students, $scripFamilies)
    {
        foreach($scripFamilies as $family)
        {
            if ($family->getStudentId() === NULL)
            {
                continue;
            }
                
            $foundStudent = false;
            foreach ($students as $student)
            {
                if ($student["id"] == $family->getStudentId())
                {
                    $last = $student["last"];
                    $first = $student["first"];
                    $key = $last . " " . $first;
                    $students[$key]["scripFamilies"][] = $family;
//                    $students[$key]["scripTotalValue"] += $family->getTotalValue();
//                    $students[$key]["scripTotalRebate"] += $family->getTotalRebate();
                    $foundStudent = true;
                    break;
                }
            }
            if (!$foundStudent)
            {
                // This is a student that is not already in student array because 
                // there were no grocery card transactions
                $first = $family->getStudentFirstName();
                $last = $family->getStudentLastName();
                $key = $last . " " . $first;
                $students[$key]["scripFamilies"][] = $family;
                $students[$key]["first"] = $first;
                $students[$key]["last"] = $last;
//                $students[$key]["scripTotalValue"] += $family->getTotalValue();
//                $students[$key]["scripTotalRebate"] += $family->getTotalRebate();
            }
        }
        return $students;
    }
    
    function getStudentIdByCard($cardNumber)
    {
        $result = queryPostgres("SELECT * FROM student_cards WHERE card=$1", array($cardNumber));
        if (($row = pg_fetch_array($result)) === false)
        {
            $pageMsg = "Card $cardNumber is marked as sold but is not associated with a student.";
            $fatalError = true;
        }
        else
        {
            return $row["student"];
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
    
    /*
     * Safeway card numbers are 19 digits, but are really text, not numeric.
     * This causes a lot of grief in Excel. Excel will automatically use 
     * scientific notation to represent a number greater than 12 digits (if the 
     * cell is the default "General" format). Since these card numbers are often
     * used in Excel, we will avoid this problem by inserting a space after the 
     * 12th digit. Excel then treats the number as text.
     */
    function modifySafewayCardNumber($cardNumber)
    {
        $modifiedCardNumber = $cardNumber;
        
        // Only deal with 19 digit numbers. If input is not as expected,
        // we will simply return it unchanged.
        if (preg_match("/^[0-9]{19}$/", $cardNumber) == 1)
        {
            $prefix = substr($cardNumber, 0, 12);
            $suffix = substr($cardNumber, 12);
            $modifiedCardNumber = $prefix . " " . $suffix;
        }
        
        return $modifiedCardNumber;
    }
    
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
    function modifyKingSoopersCardNumber($cardNumber) 
    {
        $modifiedCardNumber = $cardNumber;
        
        // strip everything but digits
        $strippedCardNumber = preg_replace("/[^0-9]/", "", $cardNumber);
        
        // Add the prefix 
        $strippedCardNumber = "60064959" . $strippedCardNumber;
        
        if (strlen($strippedCardNumber) == 19) 
        {
            $modifiedCardNumber = formatCardNumber($strippedCardNumber, "KS");
        }
        
        return $modifiedCardNumber;
    }
    
    echo "<p class='pageMessage'>$pageMsg</p>";
    
    if ($reportComplete === false && $fatalError === false)
    {
    echo <<<_END
    <div class="form">
      <form method='post' action='reloads.php' enctype='multipart/form-data'>
        King Soopers
        <input type='file' name='file[]' size='10'>
        Safeway
        <input type='file' name='file[]' size='10'>    
        Scrip
        <input type='file' name='file[]' size='10'>    
        <button type='submit'>Upload</button>   
      </form>
    </div>
_END;
    }
    
?>
