<?php

require_once 'report/ActivityReport.php';
require_once 'orm/Student.php';

class BoostersActivityReport extends ActivityReport {
    
    private $includeKsCards;
    private $includeScrip;
    private $includeWithdrawals;
    //private $activeStudentCardReloads;
    //private $studentIdMap = array();
    
    
    function __construct($startDate, $endDate, $includeKsCards, $includeScrip, $includeWithdrawals) {

        parent::__construct($startDate, $endDate);  

        $this->includeKsCards = $includeKsCards;
        $this->includeScrip = $includeScrip;
        $this->includeWithdrawals = $includeWithdrawals;
        
//        if ($this->includeKsCards) {
//            $this->activeStudentCardReloads = $this->getActiveStudentCardReloads();
//        }
    }
                   
    private function getInvolvedActiveStudents() {
        // Get the Ids of active students that had a card reload, sorted by student name
        $result = pg_query_params(
            "SELECT DISTINCT ks.student, st.first, st.middle, st.last " . 
            "FROM ks_card_reloads AS ks INNER JOIN students AS st " .
            "ON ks.student = st.id WHERE ks.student IS NOT NULL " .
            "AND ks.reload_date >= $1 AND ks.reload_date <= $2 " .
            "ORDER BY st.last, st.first, st.middle",
            array($this->startDate, $this->endDate));
        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        return pg_fetch_all($result);        
    }
    
    private function getInvolvedInactiveStudents() {
        // Get the Ids of active students that had a card reload, sorted by student name
        $result = pg_query_params(
            "SELECT DISTINCT sc.student, st.last, st.first, st.middle " .
            "FROM ks_card_reloads AS ks " .
            "INNER JOIN student_cards AS sc ON ks.card = sc.card " .
            "INNER JOIN students AS st ON sc.student = st.id " .
            "WHERE st.active = 'f' AND ks.reload_date >= $1 AND ks.reload_date <= $2" .
            "ORDER BY st.last, st.first, st.middle",
            array($this->startDate, $this->endDate));
                        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        return pg_fetch_all($result);        
    }
    
    private function getReloadsOfActiveStudent($studentId) {
        // Get all card reloads for the student in the date range
        $result = pg_query_params(
            "SELECT ks.reload_date, ks.card, st.first, st.middle, st.last, ks.reload_amount, st.id " .
            "FROM ks_card_reloads AS ks INNER JOIN students AS st " . 
            "ON ks.student = st.id " .
            "WHERE ks.student = $1 AND ks.reload_date>=$2 AND ks.reload_date<=$3" .
            "ORDER BY ks.reload_date",
            array($studentId, $this->startDate, $this->endDate));
        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        return pg_fetch_all($result);
    }
       
    private function getReloadsOfActiveStudents(&$count, &$sum) {
        
        $result = pg_query_params(
            "SELECT ks.reload_date, ks.card, st.first, st.middle, st.last, ks.reload_amount, st.id " .
            "FROM ks_card_reloads AS ks INNER JOIN students AS st " . 
            "ON ks.student = st.id " .
            "WHERE ks.reload_date>=$1 AND ks.reload_date<=$2" .
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
            "WHERE ks.reload_date>=$1 AND ks.reload_date<=$2",
            array($this->startDate, $this->endDate));
        
        if (!$sumResult) {
            throw new Exception(pg_last_error());
        }
        
        $sum = pg_fetch_result($sumResult, 0, 0);
        
        return pg_fetch_all($result);
    }
    
    private function getReloadsOfInactiveStudent($studentId) {
        // Get all card reloads for the student in the date range
        $result = pg_query_params(
            "SELECT ks.reload_date, ks.card, st.first, st.middle, st.last, ks.reload_amount " . 
            "FROM ks_card_reloads AS ks INNER JOIN student_cards AS sc ON ks.card = sc.card " .
            "INNER JOIN students AS st ON sc.student = st.id  WHERE st.id = $1 " .
            "AND ks.reload_date >= $2 AND ks.reload_date <= $3" .
            "ORDER BY ks.reload_date",
            array($studentId, $this->startDate, $this->endDate));
                     
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        return pg_fetch_all($result);
    }
        
    private function getStudentName($studentId) {
        
        $studentName = $this->studentIdMap[$studentId];
        if ($studentName == NULL) {
            $studentName = Student::getStudentName($studentId);
            $this->studentIdMap[$studentId] = $studentName;
        }
        return $studentName;
    }
    
    protected function writeStudentCardHeaders()
    {
        $styleRab = "class='tg-rab'";
        $styleLab = "class='tg-lab'";
        
        $this->table .=         
        "<tr>" .
            "<td $styleLab>Date</td>" .
            "<td $styleLab>Card</td>" .
            "<td $styleLab>Student</td>" .    
            "<td $styleRab>Amount</td>" .
            "<td $styleRab>Rebate</td>" .
            "<td $styleRab>Boosters Share</td>" .
            "<td $styleRab>Student Share</td>" .
        "</tr>";
    }
    
    protected function writeUnknownCardHeaders()
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
    
    private function writeUnknownCardTotalHeaders()
    {
        $styleRab = "class='tg-rab'";
        $styleLab = "class='tg-lab'";
        
        $this->table .=         
        "<tr>" .
            "<td $styleLab>Date</td>" .
            "<td $styleLab>Card</td>" .
            "<td $styleRab>Amount</td>" .  
            "<td></td>" .
            "<td $styleLab>Original Invoice</td>" .
            "<td $styleLab>Original Invoice Date</td>" .
            "<td></td>" .
        "</tr>";
    }
    
    protected function writeActiveStudentKsReloads() {
       
        $includeStudentSubtotals = false;
        $grandTotal = 0;
        
        if (!$includeStudentSubtotals) {
            $count = 0;
            $sum = 0;
            $reloads = $this->getReloadsOfActiveStudents($count, $sum);
            $grandTotal = $sum;
            $this->writeCategoryHeader("King Soopers reloads with active student", $count);
            foreach ($reloads as $reload) {
                $this->writeCardReload(
                    $reload['reload_date'],
                    $reload['card'],
                    $reload['first'] . " " . $reload['middle'] . " " .  $reload['last'] ,   
                    $reload['reload_amount']);
            }
        }
        else {
            $involvedActiveStudents = $this->getInvolvedActiveStudents();
            foreach ($involvedActiveStudents as $student) {
                $this->writeStudentCardHeaders();
                $studentTotal = 0;
                $id = $student['student'];
                $reloads = $this->getReloadsOfActiveStudent($id);
                foreach ($reloads as $reload) {
                    $this->writeCardReload(
                            $reload['reload_date'],
                            $reload['card'],
                            $reload['first'] . " " . $reload['middle'] . " " .  $reload['last'] ,   
                            $reload['reload_amount']);
                    $studentTotal += $reload['reload_amount'];
                }
                $this->writeStudentCardsTotal($studentTotal, true);
                $grandTotal += $studentTotal;
            }
        }
        $this->writeUnderline();
        $this->writeSubTotalHeaders();
        $this->writeCardsTotal("Total for Active Students", $grandTotal, true);
    }
       
    protected function writeInactiveStudentKsReloads() {
       
        $involvedInactiveStudents = $this->getInvolvedInactiveStudents();
        $grandTotal = 0;
        foreach ($involvedInactiveStudents as $student) {
            $this->writeStudentCardHeaders();
            $studentTotal = 0;
            $id = $student['student'];
            $reloads = $this->getReloadsOfInactiveStudent($id);
            foreach ($reloads as $reload) {
                $this->writeCardReload(
                        $reload['reload_date'],
                        $reload['card'],
                        $reload['first'] . " " . $reload['middle'] . " " .  $reload['last'] ,   
                        $reload['reload_amount']);
                $studentTotal += $reload['reload_amount'];
            }
            $this->writeStudentCardsTotal($studentTotal, false);
            $grandTotal += $studentTotal;
        }
        $this->writeUnderline();
        $this->writeCardsTotal("Total for Inactive Students", $grandTotal, false);
    }
    
    protected function writeNoStudentKsReloads() {
        
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
    
    protected function writeUnknownCardKsReloads() {
        $count = 0;
        $sum = 0;
        $unknownCardReloads = $this->getUnknownCardKsReloads($count, $sum);
        $this->writeCategoryHeader("King Soopers reloads on unrecorded cards", $count);
        $this->writeUnknownCardHeaders();
        
        $total = 0;
        foreach ($unknownCardReloads as $reload) {
            $this->writeUnknownCardReload(
                $reload['reload_date'],
                $reload['card'],
                $reload['reload_amount'],
                $reload['original_invoice_number'],
                $reload['original_invoice_date']);
            $total += $reload['reload_amount'];
        }
        $this->writeUnderline();
        $this->writeSubTotalHeaders();
        $this->writeCardsTotal("Unrecorded cards total", $total, false);
    }
    
    private function writeStudentCardsTotal($total, $studentGetsShare)
    {
        $styleLab = "class='tg-lab'";
        $styleRab = "class='tg-rab'";
        $styleRa  = "class='tg-ra'";
        $styleB3sl = "class='tg-b3sl'";
        $styleR3sl = "class='tg-r3sl'";
               
        $rebate = $total * RebatePercentages::$KS_CARD_RELOAD;
        if ($studentGetsShare) {
            $studentShare = $rebate * RebatePercentages::$STUDENT_SHARE;
            $studentShareAmt = $this->format($studentShare);
            $boostersShare = $rebate - $studentShareAmt;
            
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
            "<td $styleRab colspan='3'>Student Total</td>" .
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
    
    private function writeCardReload($date, $card, $student, $amount)
    {
        $styleRa = "class='tg-ra'";
        
        //$rebate = $amount * RebatePercentages::$KS_CARD_RELOAD;
        $amountStr = $this->format($amount);
        //$rebateStr = $this->format($rebate);
        
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
        
        //$rebate = $amount * RebatePercentages::$KS_CARD_RELOAD;
        $amountStr = $this->format($amount);
        //$rebateStr = $this->format($rebate);
        
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
    
    protected function buildTable()
    {
        $this->table = "";
        $this->startTable();
        $this->writeNameDateTitle("Boosters Activity");
        if ($this->includeKsCards) {
            
            $this->writeActiveStudentKsReloads();
            //$this->writeCategoryHeader("King Soopers reloads with inactive student");
            //$this->writeInactiveStudentKsReloads();
            //$this->writeCategoryHeader("King Soopers reloads with no student");
            //$this->writeNoStudentKsReloads();
            
            $this->writeUnknownCardKsReloads();
            
            
        }
    }
}
