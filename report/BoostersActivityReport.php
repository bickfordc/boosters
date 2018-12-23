<?php

require_once 'report/ActivityReport.php';
require_once 'orm/Student.php';

class BoostersActivityReport extends ActivityReport {
    
    private $includeKsCards;
    private $includeScrip;
    private $includeWithdrawals;
    
    function __construct($startDate, $endDate, $includeKsCards, $includeScrip, $includeWithdrawals) {

        parent::__construct($startDate, $endDate);  

        $this->includeKsCards = $includeKsCards;
        $this->includeScrip = $includeScrip;
        $this->includeWithdrawals = $includeWithdrawals;
    }
                          
    private function getReloadsOfActiveStudents(&$count, &$sum) {
        
        $result = pg_query_params(
            "SELECT ks.reload_date, ks.card, st.first, st.middle, st.last, ks.reload_amount, st.id " .
            "FROM ks_card_reloads AS ks INNER JOIN students AS st " . 
            "ON ks.student = st.id " .
            "WHERE allocation = 'activeStudent' AND ks.reload_date>=$1 AND ks.reload_date<=$2" .
            "ORDER BY st.last, st.first, st.middle, ks.reload_date",
            array($this->startDate, $this->endDate));
             
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $count = pg_num_rows($result);
        
        $sumResult = pg_query_params(
            "SELECT SUM(ks.reload_amount) FROM ks_card_reloads AS ks " .
            "INNER JOIN students AS st " . 
            "ON ks.student = st.id " .
            "WHERE allocation = 'activeStudent' AND ks.reload_date>=$1 AND ks.reload_date<=$2",
            array($this->startDate, $this->endDate));
        
        if (!$sumResult) {
            throw new Exception(pg_last_error());
        }
        
        $sum = pg_fetch_result($sumResult, 0, 0);
        
        return pg_fetch_all($result);
    }
            
    private function getReloadsOfInactiveStudents(&$count, &$sum) {
        
        $result = pg_query_params(
            "SELECT ks.reload_date, ks.card, st.first, st.middle, st.last, ks.reload_amount, st.id " .
            "FROM ks_card_reloads AS ks INNER JOIN students AS st " . 
            "ON ks.student = st.id " .
            "WHERE ks.allocation = 'inactiveStudent' AND ks.reload_date>=$1 AND ks.reload_date<=$2" .
            "ORDER BY st.last, st.first, st.middle, ks.reload_date",
            array($this->startDate, $this->endDate));
             
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $count = pg_num_rows($result);
        
        $sumResult = pg_query_params(
            "SELECT SUM(reload_amount) FROM ks_card_reloads " . 
            "WHERE allocation = 'inactiveStudent' AND reload_date>=$1 AND reload_date<=$2",
            array($this->startDate, $this->endDate));
        
        if (!$sumResult) {
            throw new Exception(pg_last_error());
        }
        
        $sum = pg_fetch_result($sumResult, 0, 0);
        
        return pg_fetch_all($result);
    }
    
    private function getReloadsOfCardHolders(&$count, &$sum) {
        
        $result = pg_query_params(
            "SELECT reload_date, card, card_holder, reload_amount " .
            "FROM ks_card_reloads WHERE allocation = 'cardHolder' " .
            "AND reload_date>=$1 AND reload_date<=$2" .
            "ORDER BY card_holder, reload_date",
            array($this->startDate, $this->endDate));
             
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $count = pg_num_rows($result);
        
        $sumResult = pg_query_params(
            "SELECT SUM(reload_amount) FROM ks_card_reloads " . 
            "WHERE allocation = 'cardHolder' AND reload_date>=$1 AND reload_date<=$2",
            array($this->startDate, $this->endDate));
        
        if (!$sumResult) {
            throw new Exception(pg_last_error());
        }
        
        $sum = pg_fetch_result($sumResult, 0, 0);
        
        return pg_fetch_all($result);
    }
    
    private function getUnassignedCardKsReloads(&$count, &$sum) {
        $result = pg_query_params(
            "SELECT reload_date, card, reload_amount, " .
            "original_invoice_number, original_invoice_date " .
            "FROM ks_card_reloads WHERE allocation = 'unassigned' " .
            "AND reload_date >= $1 AND reload_date <= $2 " .
            "ORDER BY reload_date",
            array($this->startDate, $this->endDate));
        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $count = pg_num_rows($result);
        
        $sumResult = pg_query_params(
            "SELECT SUM(reload_amount) " .
            "FROM ks_card_reloads WHERE allocation = 'unassigned' " .
            "AND reload_date >= $1 AND reload_date <= $2",
            array($this->startDate, $this->endDate));    

        if (!$sumResult) {
            throw new Exception(pg_last_error());
        }
        
        $sum = pg_fetch_result($sumResult, 0, 0);
        
        return pg_fetch_all($result);
    }
    
    private function getUnknownCardKsReloads(&$count, &$sum) {
        $result = pg_query_params(
            "SELECT ks.reload_date, ks.card, ks.reload_amount, " .
            "ks.original_invoice_number, ks.original_invoice_date " .
            "FROM ks_card_reloads as ks LEFT JOIN cards as c " .
            "ON ks.card = c.id where c.id IS NULL " .
            "AND ks.reload_date >= $1 AND ks.reload_date <= $2 " .
            "ORDER BY ks.card, ks.reload_date",
            array($this->startDate, $this->endDate));
 
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $count = pg_num_rows($result);
        
        $sumResult = pg_query_params(
            "SELECT SUM(ks.reload_amount) FROM ks_card_reloads as ks " .
            "LEFT JOIN cards as c " .
            "ON ks.card = c.id where c.id IS NULL " .
            "AND ks.reload_date >= $1 AND ks.reload_date <= $2",
            array($this->startDate, $this->endDate));
 
        if (!$sumResult) {
            throw new Exception(pg_last_error());
        }
        
        $sum = pg_fetch_result($sumResult, 0, 0);
        
        return pg_fetch_all($result);
    }
         
    private function writeActiveStudentKsReloads() {    
        $count = 0;
        $sum = 0;
        $reloads = $this->getReloadsOfActiveStudents($count, $sum);
        if ($sum > 0) {
            $this->writeCategoryHeader("King Soopers reloads with active student", $count);
            $this->writeStudentCardHeaders();
            foreach ($reloads as $reload) {
                $this->writeCardReload(
                    $reload['reload_date'],
                    $reload['card'],
                    $reload['first'] . " " . $reload['middle'] . " " .  $reload['last'] ,   
                    $reload['reload_amount']);
            }
            $this->writeUnderline();
            $this->writeSubTotalHeaders();
            $this->writeCardsTotal("Total for active students", $sum, true);
        }
    }
       
    private function writeInactiveStudentKsReloads() {
        $count = 0;
        $sum = 0;
        $reloads = $this->getReloadsOfInactiveStudents($count, $sum);
        if ($sum > 0) {
            $this->writeCategoryHeader("King Soopers reloads with inactive student", $count); 
            $this->writeStudentCardHeaders();
            foreach ($reloads as $reload) {
                $this->writeCardReload(
                        $reload['reload_date'],
                        $reload['card'],
                        $reload['first'] . " " . $reload['middle'] . " " .  $reload['last'] ,   
                        $reload['reload_amount']);
            }
            $this->writeUnderline();
            $this->writeSubTotalHeaders();
            $this->writeCardsTotal("Total for inactive students", $sum, false);
        }
    }
    
    private function writeCardHolderKsReloads() {
        $count = 0;
        $sum = 0;
        $reloads = $this->getReloadsOfCardHolders($count, $sum);
        if ($sum > 0) {
            $this->writeCategoryHeader("King Soopers reloads with card holder", $count); 
            $this->writeStudentCardHeaders();
            foreach ($reloads as $reload) {
                $this->writeCardReload(
                        $reload['reload_date'],
                        $reload['card'],
                        $reload['card_holder'],   
                        $reload['reload_amount']);
            }
            $this->writeUnderline();
            $this->writeSubTotalHeaders();
            $this->writeCardsTotal("Total for card holders", $sum, false);
        }
    }

    private function writeUnassignedCardKsReloads() {
        $count = 0;
        $sum = 0;
        $unassignedCardReloads = $this->getUnassignedCardKsReloads($count, $sum);
        if ($sum > 0) {
            $this->writeCategoryHeader("King Soopers reloads on unassigned cards", $count);
            $this->writeUnknownCardHeaders();
            foreach ($unassignedCardReloads as $reload) {
                $this->writeUnknownCardReload(
                    $reload['reload_date'],
                    $reload['card'],
                    $reload['reload_amount'],
                    $reload['original_invoice_number'],
                    $reload['original_invoice_date']);
            }
            $this->writeUnderline();
            $this->writeSubTotalHeaders();
            $this->writeCardsTotal("Total for unassigned cards", $sum, false);
        }
    }
    
    private function writeUnknownCardKsReloads() {
        $count = 0;
        $sum = 0;
        $unknownCardReloads = $this->getUnknownCardKsReloads($count, $sum);
        if ($sum > 0) {
            $this->writeCategoryHeader("King Soopers reloads on unrecorded cards", $count);
            $this->writeUnknownCardHeaders();
            foreach ($unknownCardReloads as $reload) {
                $this->writeUnknownCardReload(
                    $reload['reload_date'],
                    $reload['card'],
                    $reload['reload_amount'],
                    $reload['original_invoice_number'],
                    $reload['original_invoice_date']);
            }
            $this->writeUnderline();
            $this->writeSubTotalHeaders();
            $this->writeCardsTotal("Total for unrecorded cards", $sum, false);
        }
    }

    private function writeStudentCardHeaders()
    {
        $styleRab = "class='tg-rab'";
        $styleLab = "class='tg-lab'";
        
        $this->table .=         
        "<tr>" .
            "<td $styleLab>Date</td>" .
            "<td $styleLab>Card</td>" .
            "<td $styleLab>Student</td>" .    
            "<td $styleRab>Amount</td>" .
            "<td></td>" .
            "<td></td>" .
            "<td></td>" .
        "</tr>";
    }
    
    private function writeUnknownCardHeaders()
    {
        $styleRab = "class='tg-rab'";
        $styleLab = "class='tg-lab'";
        
        $this->table .=         
        "<tr>" .
            "<td $styleLab>Date</td>" .
            "<td $styleLab>Card</td>" .
            "<td></td>" .
            "<td $styleRab>Amount</td>" .  
            "<td></td>" .
            "<td $styleRab>Original Invoice</td>" .
            "<td $styleRab>Original Invoice Date</td>" . 
        "</tr>";
    }
        
    private function writeCardReload($date, $card, $student, $amount)
    {
        $styleRa = "class='tg-ra'";
        $amountStr = $this->format($amount);
        
        $this->table .=
        "<tr>" .
            "<td>$date</td>" .
            "<td>$card</td>" .
            "<td>$student</td>" .
            "<td $styleRa>$amountStr</td>" .
            "<td></td>" .
            "<td></td>" .
            "<td></td>" .
        "</tr>";
    }
    
    private function writeUnknownCardReload($date, $card, $amount, $invoice, $invoiceDate)
    {
        $styleRa = "class='tg-ra'";
        $amountStr = $this->format($amount);
        
        $this->table .=
        "<tr>" .
            "<td>$date</td>" .
            "<td>$card</td>" .
            "<td></td>" .
            "<td $styleRa>$amountStr</td>" .
             "<td></td>" .
            "<td $styleRa>$invoice</td>" . 
            "<td $styleRa>$invoiceDate</td>" .
        "</tr>";
    }
    
    protected function writeCardsTotal($description, $total, $studentGetsShare)
    {
        $styleLab = "class='tg-lab'";
        $styleRab = "class='tg-rab'";
        $styleRa  = "class='tg-ra'";
        $styleB3sl = "class='tg-b3sl'";
        $styleR3sl = "class='tg-r3sl'";
               
        $rebate = $total * RebatePercentages::$KS_CARD_RELOAD;
        $rebate = round($rebate, 2);
        if ($studentGetsShare) {
            $studentShare = $rebate * RebatePercentages::$STUDENT_SHARE;
            $studentShare = round($studentShare, 2);
            $boostersShare = $rebate - $studentShare;
        }
        else {
            $boostersShare = $rebate;
            $studentShare = 0;
        }
        
        $totalAmt = $this->format($total);
        $rebateAmt = $this->format($rebate);
        $boostersShareAmt = $this->format($boostersShare);
        $studentShareAmt = $this->format($studentShare);
        
        $this->table .=
        "<tr>" .
            "<td $styleRab colspan='3'>$description</td>" .
            "<td $styleRa>$totalAmt</td>" .
            "<td $styleRa>$rebateAmt</td>" .
            "<td $styleRa>$boostersShareAmt</td>";
        
        if ($studentShare < 0) {
            $this->table .=               
            "<td $styleR3sl>$studentShareAmt</td>";
        }
        else {
            $this->table .= 
            "<td $styleB3sl>$studentShareAmt</td>";        
        }
        $this->table .= "</tr>";
        
        $this->writeLine();
    }
    
    protected function buildTable()
    {
        $this->table = "";
        $this->startTable();
        $this->writeNameDateTitle("Boosters Activity");
        if ($this->includeKsCards) {
            $this->writeActiveStudentKsReloads();
            $this->writeInactiveStudentKsReloads();
            $this->writeCardHolderKsReloads();
            $this->writeUnassignedCardKsReloads();
            $this->writeUnknownCardKsReloads();
        }
    }
}
