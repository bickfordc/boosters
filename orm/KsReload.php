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
    
    function __construct($card, $transactionDate, $originalInvoiceNumber, $originalInvoiceDate, $amount)
    {
        $this->card = $this->formatCardNumber($card);
        $this->transactionDate = $transactionDate;
        $this->originalInvoiceNumber = $originalInvoiceNumber;
        $this->originalInvoiceDate = $originalInvoiceDate;
        $this->amount = $this->handleCurrency($amount);
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
        $result = pg_query_params("INSERT INTO ks_card_reloads (card, reload_date, "
                . "reload_amount, original_invoice_number, original_invoice_date) VALUES ($1, $2, $3, $4, $5)", 
                array(
                $this->card,
                $this->transactionDate,
                $this->amount,
                $this->originalInvoiceNumber,
                $this->originalInvoiceDate
                ));
        if (!$result) {
            throw new Exception(pg_last_error());
        }
    }
    
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
}
