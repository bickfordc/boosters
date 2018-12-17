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
            "WHERE ks.student = $1 AND ks.reload_date>=$2 AND ks.reload_date<=$3",
            array($studentId, $this->startDate, $this->endDate));
        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
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
    
//    private function getActiveStudentCardReloads() {
//        // Get all card reloads in the date range that had an associated active student
//        $result = pg_query_params(
//            "SELECT ks.reload_date, ks.card, st.first, st.middle, st.last, ks.reload_amount, st.id " .
//            "FROM ks_card_reloads AS ks INNER JOIN students AS st " . 
//            "ON ks.student = st.id " .
//            "WHERE ks.reload_date>=$1 AND ks.reload_date<=$2 " .
//            "ORDER BY st.last, st.first, st.middle, ks.reload_date", 
//            array($this->startDate, $this->endDate));
//        
//        if (!$result) {
//            throw new Exception(pg_last_error());
//        }
//        
//        return pg_fetch_all($result);
//    }
    
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
    
    protected function writeActiveStudentKsReloads() {
       
        $involvedActiveStudents = $this->getInvolvedActiveStudents();
        $grandTotal = 0;
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
        $this->writeUnderline();
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
    
    private function writeStudentCardsTotal($total, $studentGetsShare)
    {
        $styleLab = "class='tg-lab'";
        $styleRab = "class='tg-rab'";
        $styleRa  = "class='tg-ra'";
        $styleB3sl = "class='tg-b3sl'";
        $styleR3sl = "class='tg-r3sl'";
        $styleB3sr = "class='tg-b3sr'";
        $styleR3sr = "class='tg-r3sr'";
               
        $rebate = $total * RebatePercentages::$KS_CARD_RELOAD;
        if ($studentGetsShare) {
            $boostersShare = $rebate * RebatePercentages::$BOOSTERS_SHARE;
            $studentShare = $rebate * RebatePercentages::$STUDENT_SHARE;
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
        $styleB3sr = "class='tg-b3sr'";
        $styleR3sr = "class='tg-r3sr'";
               
        $rebate = $total * RebatePercentages::$KS_CARD_RELOAD;
        if ($studentGetsShare) {
            $boostersShare = $rebate * RebatePercentages::$BOOSTERS_SHARE;
            $studentShare = $rebate * RebatePercentages::$STUDENT_SHARE;
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
        
        $rebate = $amount * RebatePercentages::$KS_CARD_RELOAD * RebatePercentages::$STUDENT_SHARE;
        $amountStr = $this->format($amount);
        $rebateStr = $this->format($rebate);
        
        $this->table .=
        "<tr>" .
            "<td>$date</td>" .
            "<td>$card</td>" .
            "<td>$student</td>" .
            "<td $styleRa>$amountStr</td>" .
            "<td $styleRa>$rebateStr</td>" .
            "<td></td>" .
            "<td></td>" .
        "</tr>";
    }
    
    protected function buildTable()
    {
        $this->table = "";
        $this->startTable();
        $this->writeNameDateTitle("Boosters Activity");
        if ($this->includeKsCards) {
            $this->writeCategoryHeader("King Soopers reloads with active student");
            $this->writeActiveStudentKsReloads();
            $this->writeCategoryHeader("King Soopers reloads with inactive student");
            $this->writeInactiveStudentKsReloads();
            $this->writeCategoryHeader("King Soopers reloads with no student");
            $this->writeNoStudentKsReloads();
            
        }
    }
}
