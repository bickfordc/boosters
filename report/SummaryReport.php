<?php

require_once 'RebatePercentages.php';
require_once 'report/ActivityReport.php';

class SummaryReport extends ActivityReport{
    
    private $reloadCount;
    private $reloadSum;
    private $activeReloadSum;
    private $reloadRebate;
    private $orderCount;
    private $orderSum;
    private $orderRebate;
    private $activeOrderRebate;
    private $reloadStudentShare;
    private $orderStudentShare;
    
    function __construct($startDate, $endDate) {

        parent::__construct($startDate, $endDate);  
        
        $this->getReloads($this->reloadCount, $this->reloadSum);
        $this->activeReloadSum =  $this->getActiveReloadSum();
        
        $this->reloadRebate = $this->calcReloadRebate();
        
        $this->getOrders($this->orderCount, $this->orderSum, $this->orderRebate);
        $this->activeOrderRebate = $this->getActiveOrderRebate();
        
        $this->reloadStudentShare = $this->calcReloadStudentShare();
        $this->orderStudentShare = $this->calcOrderStudentShare();
    }
        
    private function getActiveReloadSum() {        
        $sumResult = pg_query_params(
            "SELECT SUM(reload_amount) FROM ks_card_reloads " . 
            "WHERE allocation = 'activeStudent' AND reload_date>=$1 AND reload_date<=$2",
            array($this->startDate, $this->endDate));
        
        if (!$sumResult) {
            throw new Exception(pg_last_error());
        }
        
        $sum = pg_fetch_result($sumResult, 0, 0);
        
        return $sum;
    }
    
    private function getReloads(&$count, &$sum) {
        
        $result = pg_query_params(
            "SELECT COUNT(reload_date), SUM(reload_amount) FROM ks_card_reloads " . 
            "WHERE reload_date>=$1 AND reload_date<=$2",
            array($this->startDate, $this->endDate));
             
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $count = 0;
        $sum = 0;
        $count = pg_fetch_result($result, 0, 0);
        if ($count > 0) {
            $sum = pg_fetch_result($result, 0, 1);
        }
    }
    
    private function getActiveOrderRebate() {
        
        $result = pg_query_params(
            "SELECT COUNT(rebate), SUM(rebate) FROM scrip_orders " . 
            "WHERE allocation = 'activeStudent' AND order_date>=$1 AND order_date<=$2",
            array($this->startDate, $this->endDate));
             
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $count = 0;
        $rebate = 0;
        $count = pg_fetch_result($result, 0, 0);
        if ($count > 0) {
            $rebate = pg_fetch_result($result, 0, 1);
        }
        
        return $rebate;
    }
    
    private function getOrders(&$count, &$orderSum, &$rebateSum) {
        
        $result = pg_query_params(
            "SELECT COUNT(order_id), SUM(order_amount), SUM(rebate) FROM scrip_orders " . 
            "WHERE order_date>=$1 AND order_date<=$2",
            array($this->startDate, $this->endDate));
             
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $count = 0;
        $orderSum = 0;
        $rebateSum = 0;
        $count = pg_fetch_result($result, 0, 0);
        if ($count > 0) {
            $orderSum = pg_fetch_result($result, 0, 1);
            $rebateSum = pg_fetch_result($result, 0, 2);
        }
    }
    
    private function getWithdrawals(&$count, &$sum) {
        $result = pg_query_params(
            "SELECT COUNT(amount), SUM(amount) FROM student_withdrawals " .
            "WHERE date>=$1 AND date<=$2",
            array($this->startDate, $this->endDate));
        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $count = 0;
        $sum = 0;
        $count = pg_fetch_result($result, 0, 0);
        if ($count > 0) {
            $sum = pg_fetch_result($result, 0, 1);
        }
    }
    
    private function calcReloadRebate() {
        $rebate = $this->reloadSum * RebatePercentages::$KS_CARD_RELOAD;
        return round($rebate, 2);
    }
    
    private function calcReloadStudentShare() {
        $studentShare = $this->activeReloadSum * RebatePercentages::$KS_CARD_RELOAD * RebatePercentages::$STUDENT_SHARE;
        return round($studentShare, 2);
    }
    
    private function calcOrderStudentShare() {
        $studentShare = $this->activeOrderRebate * RebatePercentages::$STUDENT_SHARE;
        return round($studentShare, 2);
    }
    
    private function writeReloads() {
        $this->writeCategoryHeader("King Soopers");
        $this->writeSummaryHeaders();
        $this->writeTotals();
        $this->writeLine();
    }
    
    private function writeOrders() {
        $this->writeCategoryHeader("ShopWithScrip");
        $this->writeSummaryHeaders();
        $this->writeOrderTotals();
        $this->writeLine();
    }
    
    private function writeDirectStudentDeposits() {
        $this->writeCategoryHeader("Direct Student Deposits");
        $this->writeSummaryHeaders();
        $this->writeOrderTotals();
        $this->writeLine();
    }

    private function writeRevenue() {
        $this->writeCategoryHeader("Total Deposits");
        $this->writeSummaryHeaders();
        $this->writeGrandTotal();
        $this->writeLine();
    }
    
    private function writeTotalWithdrawals() {
        $count = 0;
        $sum = 0;
        $this->getWithdrawals($count, $sum);
        $sumAmt = $this->format($sum);
        $this->writeCategoryHeader("Total Withdrawals");
        
        $styleRab = "class='tg-rab'";
        $styleRa = "class='tg-ra'";
        
        $this->table .=         
        "<tr>" .
            "<td $styleRab>Number of transactions</td>" .
            "<td $styleRab colSpan=6>Amount</td>" .
        "</tr>" .
        "<tr>" .
            "<td $styleRa>$count</td>" .
            "<td $styleRa colSpan=6>$sumAmt</td>" .
        "</tr>";
    }
    
    private function writeSummaryHeaders()
    {
        $styleRab = "class='tg-rab'";
        
        $this->table .=         
        "<tr>" .
            "<td $styleRab>Number of transactions</td>" .
            "<td $styleRab>Amount</td>" .
            "<td $styleRab>Rebate</td>" .
            "<td></td>" .
            "<td></td>" .
            "<td $styleRab>Boosters Share</td>" .
            "<td $styleRab>Student Share</td>" .
        "</tr>";
    }
    
    private function writeTotals() {
        
        $styleRa  = "class='tg-ra'";
                       
        $totalAmt = $this->format($this->reloadSum);
        $rebateAmt = $this->format($this->reloadRebate);
        $studentShareAmt = $this->format($this->reloadStudentShare);
        $boostersShareAmt = $this->format($this->reloadRebate - $this->reloadStudentShare);
        
        $this->table .=
        "<tr>" .
            "<td $styleRa>$this->reloadCount</td>" .
            "<td $styleRa>$totalAmt</td>" .
            "<td $styleRa>$rebateAmt</td>" .
            "<td></td>" .
            "<td></td>" .
            "<td $styleRa>$boostersShareAmt</td>" .
            "<td $styleRa>$studentShareAmt</td>" .
        "<tr>";       
    }
    
    private function writeOrderTotals() {
        
        $styleRa  = "class='tg-ra'";
                             
        $totalAmt = $this->format($this->orderSum);
        $rebateAmt = $this->format($this->orderRebate);
        $studentShareAmt = $this->format($this->orderStudentShare);
        $boostersShareAmt = $this->format($this->orderRebate - $this->orderStudentShare);
        
        $this->table .=
        "<tr>" .
            "<td $styleRa>$this->orderCount</td>" .
            "<td $styleRa>$totalAmt</td>" .
            "<td $styleRa>$rebateAmt</td>" .
            "<td></td>" .
            "<td></td>" .
            "<td $styleRa>$boostersShareAmt</td>" .
            "<td $styleRa>$studentShareAmt</td>" .
        "<tr>";       
    }
    
    private function writeGrandTotal() {
        
        $styleRa  = "class='tg-ra'";
        
        $count = $this->reloadCount + $this->orderCount;
        $amount = $this->reloadSum + $this->orderSum;
        $rebate = $this->reloadRebate + $this->orderRebate;
        $studentShare = $this->reloadStudentShare + $this->orderStudentShare;
        $boostersShare = $rebate - $studentShare;
        
        $totalAmt = $this->format($amount);
        $rebateAmt = $this->format($rebate);
        $boostersShareAmt = $this->format($boostersShare);
        $studentShareAmt = $this->format($studentShare);
        
        $this->table .=
        "<tr>" .
            "<td $styleRa>$count</td>" .
            "<td $styleRa>$totalAmt</td>" .
            "<td $styleRa>$rebateAmt</td>" .
            "<td></td>" .
            "<td></td>" .
            "<td $styleRa>$boostersShareAmt</td>" .
            "<td $styleRa>$studentShareAmt</td>" .
        "<tr>";       
    }
    
    protected function buildTable() {
        $this->table = "";
        $this->startTable();
        $this->writeNameDateTitle("Boosters Summary");
        $this->writeReloads();
        $this->writeOrders();
        //$this->writeDirectStudentDeposits();
        $this->writeRevenue();
        $this->writeTotalWithdrawals();
    }
}
