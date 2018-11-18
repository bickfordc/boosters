<?php

    require_once 'header.php';
    
    $successMsg;
    $errorMsg;
    $formError;
    $numCardsAdded;
    
    if ($_FILES)  {    
        
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

            $file = fopen($name, "r");
            if ($file == NULL)
            {
                throw new Exception("Could not open file $name");
            }

            $cards = array();
            $invoice;
            $orderDate;
            $firstRow = true;
            while (!feof($file))
            {
                $row = fgetcsv($file, 300, ",");

                if ($firstRow) {
                    // should be the invoice number and order date
                    $invoice = $row[0];
                    $orderDate = $row[1];
                    $firstRow = false;
                    continue;
                }
                $cardNumber = $row[0];

                if ($cardNumber == NULL) {
                    continue;
                }

                // strip anything but digits
                $strippedCardNumber = preg_replace("/[^0-9]/", "", $cardNumber);
                if (strlen($strippedCardNumber) != 19) {
                    throw new Exception("Card number " . $cardNumber . " is invalid. Card numbers must have 19 digits.");
                    break;
                }

               if (calculateCardType($strippedCardNumber) == NULL) {
                    throw new Exception("Card number " . $cardNumber . " is invalid. King Soopers cards begin with 6006,"
                            . " and Safeway cards begin with 6039.");
                }

                $cards[] = $strippedCardNumber;
            }
            fclose($file);

            $numCardsAdded = commitCards($cards, $invoice, $orderDate);
            $successMsg = "Successfully addded " . $numCardsAdded . " cards.";            
        } 
        catch(Exception $e) {
            $errorMsg = $e->getMessage() . "<br>" . "No cards were added.";
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
          <img src='img/excel-example.png'>
        </div>
        <div style="float:left;width:467px;height:823px;margin:10px"> 
          <p>To add new cards in bulk, use Excel to prepare the data as shown 
             on the left. The first row must contain the Invoice # and Order Date.
             On the remaining rows, enter the card numbers with spaces as shown.
             Save the file as CSV (Comma delimited) (*.csv)<br><br>
             Or instead of Excel, you may also use any text editor and create
             input data like this;<br>
             <div class="textexample">
             KSI226797,11/14/2018<br>
             6006495903 337 679 821<br>
             6006495903 337 679 839<br> </div>
             Save the file with a .csv extension.
          </p>
        </div>
        <div style="float:left;width:467px;height:423px;margin:10px"> 
          <div class="form" >
            <p style="text-align:center">Select the .csv file</p>
            <p class='error'>$formError</p>
            <form method='post' action='newCards.php' enctype='multipart/form-data'>    
              <input type='file' name='filename' size='10'>    
              <button type='submit'>Upload</button>   
            </form>
          </div>
        </div>
    </div>
_END;
    
    function calculateCardType($cardNumber) {
        
        $cardType = NULL;
        
        if (substr($cardNumber, 0, 4) == "6006") {
                $cardType = "KS";  
        }
        elseif (substr($cardNumber, 0, 4) == "6039") {
                $cardType = "SW";  
        }
        
        return $cardType;
    }
    
    function commitCards($cards, $invoice, $orderDate) {
        
        $numCardsAdded = 0;
        $isSold = 'f';
        
        if (!pg_query("BEGIN")) {
            throw new Exception(pg_last_error());
        }
        
        foreach ($cards as $card) {

            $donorCode = calculateCardType($card);
            $formattedCardNumber = formatCardNumber($card, $donorCode);

            if (!pg_query_params("INSERT INTO cards (id, sold, donor_code, invoice_number, order_date) VALUES ($1, $2, $3, $4, $5)",
                    array($formattedCardNumber, $isSold, $donorCode, $invoice, $orderDate))) {
                
                $error = pg_last_error();
                pg_query("ROLLBACK");
                throw new Exception($error);
            }
            $numCardsAdded++;
        }
        if (!pg_query("COMMIT")) {
            throw new Exception(pg_last_error());
        }
        
        return $numCardsAdded;
    }
?>

    <br></div>
  </body>
</html>

