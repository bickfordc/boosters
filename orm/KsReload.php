<?php

/**
 * A King Soopers card reload transaction
 *
 */
class KsReload {
    
    private $card;
    private $transactionDate;
    private $originalInvoiceNumber;
    private $originalInvoiceDate;
    private $amount;
    private $student;
    private $allocation;
    private $cardHolder = null;
    
    function __construct($card, $transactionDate, $originalInvoiceNumber, $originalInvoiceDate, $amount)
    {
        $this->card = $this->formatCardNumber($card);
        $this->transactionDate = $transactionDate;
        $this->originalInvoiceNumber = $originalInvoiceNumber;
        $this->originalInvoiceDate = $originalInvoiceDate;
        $this->amount = $this->handleCurrency($amount);
        
        $studentId = $this->getStudentId();
        if ($studentId){
            $this->student = new Student($studentId);
        }
        $this->determineAllocation();
    }
    
    public function getCard() {
        return $this->card;
    }

    public function getTransactionDate() {
        return $this->transactionDate;
    }

    public function getOriginalInvoiceNumber() {
        return $this->originalInvoiceNumber;
    }

    public function getOriginalInvoiceDate() {
        return $this->originalInvoiceDate;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function getStudent() {
        return $this->student;
    }
    
    public function setCard($card) {
        $this->card = $card;
    }

    public function setTransactionDate($transactionDate) {
        $this->transactionDate = $transactionDate;
    }

    public function setOriginalInvoiceNumber($originalInvoiceNumber) {
        $this->originalInvoiceNumber = $originalInvoiceNumber;
    }

    public function setOriginalInvoiceDate($originalInvoiceDate) {
        $this->originalInvoiceDate = $originalInvoiceDate;
    }

    public function setAmount($Amount) {
        $this->Amount = $Amount;
    }
        
    public function insertToDb() {
        
        $studentId = null;
        if ($this->student) {
            $studentId = $this->student->getId();
        }
        $result = pg_query_params("INSERT INTO ks_card_reloads " .
            "(card, reload_date, reload_amount, original_invoice_number, " .
            "original_invoice_date, card_holder, student, allocation) " .
            "VALUES ($1, $2, $3, $4, $5, $6, $7, $8)", 
            array(
            $this->card,
            $this->transactionDate,
            $this->amount,
            $this->originalInvoiceNumber,
            $this->originalInvoiceDate,
            $this->cardHolder,
            $studentId,
            $this->allocation
            ));
        if (!$result) {
                throw new Exception(pg_last_error());
            }
        }
//        else {
//            $result = pg_query_params("INSERT INTO ks_card_reloads (card, reload_date, "
//                    . "reload_amount, original_invoice_number, original_invoice_date, allocation) VALUES ($1, $2, $3, $4, $5, $6)", 
//                    array(
//                    $this->card,
//                    $this->transactionDate,
//                    $this->amount,
//                    $this->originalInvoiceNumber,
//                    $this->originalInvoiceDate,
//                    $this->allocation
//                    ));
//            if (!$result) {
//                throw new Exception(pg_last_error());
//            }
//        }
//   }
    
    /**
     * Get the ID of the student that the card is assigned to.
     * @return int student ID, or NULL if card is not assigned
     * 
     */
    public function getStudentId() {
        $result = queryPostgres("SELECT * FROM student_cards WHERE card=$1", array($this->card));
        if (($row = pg_fetch_array($result)) === false)
        {
            return NULL;
        }
        else
        {
            return $row["student"];
        }
    }
    
    private function formatCardNumber($cardNumber) {
        
        // strip everything but digits
        $card = preg_replace("/[^0-9]/", "", $cardNumber);
        
        // Format King Soopers cards as 10 digits, space, 3 digits, space, 3 digits, space, 3 digits
        // 6006495903 177 095 385
        $formattedCardNumber = substr($card, 0, 10) . " " .
                               substr($card, 10, 3) . " " .
                               substr($card, 13, 3) . " " .
                               substr($card, 16, 3);
        
        return $formattedCardNumber;
    }
    
    // ($150.75) => -150.75
    // $20       => 20
    // $1,100.25 => 1100.25
    //
    private function handleCurrency($moneyString)
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
    
    // find the matching card in the cards table
    // if it exists, is it assigned?
    private function isCardAssigned($card) {
        $result = pg_query_params(
                "SELECT sold from cards WHERE id = $1",
                array($card));
        
        if (pg_num_rows($result) == 0) {
            throw new Exception("Card $card is not recorded");
        }
        
        $assigned = pg_fetch_result($result, 0, 0);
        if ($assigned == 't') {
            return true;
        } 
        else {
            return false;
        }
    }
    
    private function determineAllocation() {
        
        try {
            $assigned = $this->isCardAssigned($this->card);
            if (!$assigned) {
                $this->allocation = "unassigned";
            }
            elseif ($this->student) {
                if ($this->student->isActive()) {
                    $this->allocation = "activeStudent";
                }
                else {
                    $this->allocation = "inactiveStudent";
                }
            }
            else {
                $this->allocation = "donor";
                $this->cardHolder = $this->getCardHolder();
            }
        }
        catch (Exception $e) {
            $this->allocation = "unrecorded";
        } 
    }
    
    private function getCardHolder() {
        
        $result = pg_query_params(
            "SELECT card_holder from cards WHERE id = $1",
            array($this->card));

        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        if (pg_num_rows($result == 0)) {
            throw new Exception("Card $this->card is not recorded");
        }
        
        $cardHolder = pg_fetch_result($result, 0, 0);
        if (!$cardHolder) {
            $cardHolder = "";
        }
        
        return $cardHolder;
    }
    
}
