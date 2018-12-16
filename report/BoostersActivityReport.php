<?php

require_once 'report/ActivityReport.php';
require_once 'orm/Student.php';

class BoostersActivityReport extends ActivityReport {
    
    private $includeKsCards;
    private $includeScrip;
    private $includeWithdrawals;
    private $activeStudentCardReloads;
    private $studentIdMap = array();
    
    
    function __construct($startDate, $endDate, $includeKsCards, $includeScrip, $includeWithdrawals) {

        parent::__construct($startDate, $endDate);  

        $this->includeKsCards = $includeKsCards;
        $this->includeScrip = $includeScrip;
        $this->includeWithdrawals = $includeWithdrawals;
        
        if ($this->includeKsCards) {
            $this->activeStudentCardReloads = $this->getActiveStudentCardReloads();
        }
    }
                   
    private function getActiveStudentCardReloads() {
        // Get all card reloads in the date range
        $result = pg_query_params(
            "SELECT * from ks_card_reloads WHERE " . 
            "reload_date>=$1 AND reload_date<=$2 " .
            "AND student IS NOT NULL " .
            "ORDER BY student, reload_date ASC", 
            array($this->startDate, $this->endDate));
        
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
    
    protected function writeStudentKsReloads($reloads) {
        $total = 0;
        foreach ($reloads as $reload) {
            $this->writeCardReload($reload['reload_date'],
                            $reload['card'],
                            $this->getStudentName($reload['student']) ,   
                            $reload['reload_amount']);
            $total += $reload['reload_amount'];
        }
        $this->writeCardsTotal($total);
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
            $this->writeStudentCardHeaders();
            $this->writeStudentKsReloads($this->activeStudentCardReloads);
        }
    }
}
