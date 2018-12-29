<?php

require_once 'report/ActivityReport.php';
require_once 'orm/Student.php';

/**
 * Description of StudentActivityReport
 *
 */
class StudentActivityReport extends ActivityReport {
    
    private $studentId;
    private $student;
    private $cardReloads;
    private $cardReloadTotal;
    private $scripOrders;
    private $scripRebateTotal;
    private $withdrawals;
    private $withdrawalTotal;
    
    function __construct($studentId, $startDate, $endDate) {
        
        parent::__construct($startDate, $endDate);  
        $this->studentId = $studentId;
        $this->student = new Student($studentId);
        
        $this->cardReloads = $this->getCardReloads();
        $this->cardReloadTotal = $this->getCardReloadTotal();
        
        $this->scripOrders = $this->getScripOrders();
        $this->scripRebateTotal = $this->getScripRebateTotal();
        
        $this->withdrawals = $this->getWithdrawals();
        $this->withdrawalTotal = $this->getWithdrawalTotal();
    }
    
 
    
    protected function buildTable()
    {
        $this->table = "";
        $this->startTable();
        $this->writeNameDateTitle($this->student->getFullName());
        //$this->writeDate();
        
        $this->writeCategoryHeader("King Soopers");
        $this->writeCardHeaders();
        $this->writeStudentKsReloads($this->cardReloads);
       
        $this->writeCategoryHeader("ShopWithScrip");
        $this->writeScripHeaders();
        $this->writeScripOrders($this->scripOrders);
        
        $this->writeCategoryHeader("Withdrawals");
        $this->writeWithdrawalHeaders();
        $this->writeWithdrawals($this->withdrawals);
        
        $this->endTable();
    }
       
    private function getCardReloads() {
        // Get all card reloads in the date range that are assigned to the student
        $result = pg_query_params(
            "SELECT * from ks_card_reloads WHERE student=$1 " . 
            "AND reload_date>=$2 AND reload_date<=$3 " .
            "ORDER BY reload_date ASC", 
            array($this->studentId, $this->startDate, $this->endDate));
        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        return pg_fetch_all($result);
    }
    
    private function getCardReloadTotal() {
        // sum the reload_amount column
        $result = pg_query_params(
            "SELECT SUM(reload_amount) FROM ks_card_reloads WHERE student=$1 " .
            "AND reload_date>=$2 AND reload_date<=$3",
            array($this->studentId, $this->startDate, $this->endDate));
        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $row = pg_fetch_row($result);
        if ($row[0] == NULL) {
            return 0;
        }
        else {
            return row[0];
        } 
    }
    
    private function getScripOrders() {
        // Get all scrip orders in the date range that are assigned to the student
        $result = pg_query_params(
            "SELECT * from scrip_orders WHERE student=$1 " . 
            "AND order_date>=$2 AND order_date<=$3 " .
            "ORDER BY order_date ASC", 
            array($this->studentId, $this->startDate, $this->endDate));
        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        return pg_fetch_all($result);
    }
    
    private function getScripRebateTotal() {
        // sum the rebate column
        $result = pg_query_params(
            "SELECT SUM(rebate) FROM scrip_orders WHERE student=$1 " .
            "AND order_date>=$2 AND order_date<=$3",
            array($this->studentId, $this->startDate, $this->endDate));
        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $row = pg_fetch_row($result);
        if ($row[0] == NULL) {
            return 0;
        }
        else {
            return row[0];
        } 
    }
    
    private function getWithdrawals() {
        // Get all withdrawals for this student that are in the date range
        $result = pg_query_params(
            "SELECT * from student_withdrawals WHERE student=$1 " . 
            "AND date>=$2 AND date<=$3 " .
            "ORDER BY date ASC", 
            array($this->studentId, $this->startDate, $this->endDate));
        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        return pg_fetch_all($result);
    }
    
        private function getWithdrawalTotal() {
        // sum the rebate column
        $result = pg_query_params(
            "SELECT SUM(amount) FROM student_withdrawals WHERE student=$1 " .
            "AND date>=$2 AND date<=$3",
            array($this->studentId, $this->startDate, $this->endDate));
        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $row = pg_fetch_row($result);
        if ($row[0] == NULL) {
            return 0;
        }
        else {
            return row[0];
        } 
    }
        
    private function writeCardReload($date, $card, $amount)
    {
        $styleRa = "class='tg-ra'";
        $amountStr = $this->format($amount);
      
        $this->table .=
        "<tr>" .
            "<td>$date</td>" .
            "<td>$card</td>" .
            "<td $styleRa>$amountStr</td>" .
            "<td></td>" .
            "<td></td>" .
            "<td></td>" .
        "</tr>";
    }
    
    private function writeStudentKsReloads($reloads) {
        $total = 0;
        foreach ($reloads as $reload) {
            $this->writeCardReload($reload['reload_date'],
                            $reload['card'],
                            $reload['reload_amount']);
            $total += $reload['reload_amount'];
        }
        $this->writeSubTotalHeaders();
        $this->writeCardsTotal(2, "Total", $total, $this->student->isActive());
    }
    
    private function writeScripOrders($orders) {
        $totalAmount = 0;
        $totalRebate = 0;
        foreach ($orders as $order) {
            $this->writeScripOrder(
                            $order['order_date'],
                            $order['scrip_first'] . " " . $order['scrip_last'],
                            $order['order_amount'],
                            $order['rebate']);
            $totalAmount += $order['order_amount'];
            $totalRebate += $order['rebate'];
        }
        $this->writeSubTotalHeaders();
        $this->writeScripTotal($totalAmount, $totalRebate, $this->student->isActive());
    }
    
    protected function writeScripTotal($totalAmount, $totalRebate, $studentGetsShare)
    {
        $styleLab = "class='tg-lab'";
        $stylePlab = "class='tg-plab'";
        $styleRa  = "class='tg-ra'";
        $styleRab = "class='tg-rab'";
        $styleB3sl = "class='tg-b3sl'";
        $styleR3sl = "class='tg-r3sl'";
        $styleB3sr = "class='tg-b3sr'";
        $styleR3sr = "class='tg-r3sr'";
               
        if ($studentGetsShare) {
            $studentShare = $totalRebate * RebatePercentages::$STUDENT_SHARE;
            $boostersShare = $totalRebate - $studentShare;
        }
        else {
           $studentShare = 0; 
           $boostersShare = $totalRebate;
        }
        
        $totalAmt = $this->format($totalAmount);
        $rebateAmt = $this->format($totalRebate);
        $boostersShareAmt = $this->format($boostersShare);
        $studentShareAmt = $this->format($studentShare);
        
        $this->table .=
        "<tr>" .
            "<td $styleRab colspan='2'>Total</td>" .
            "<td $styleRa>$totalAmt</td>" .
            "<td $styleRa>$rebateAmt</td>" .
            "<td $styleRa>$boostersShareAmt</td>" .
            "<td $styleB3sl>$studentShareAmt</td>" .
        "</tr>";     
 
        $this->writeLine();
    }
    
    private function writeScripOrder($date, $family, $amount, $rebate) {
        
        $styleRa = "class='tg-ra'";
        
        $this->table .=
        "<tr>" .
            "<td>$date</td>" .
            "<td>$family</td>" .
            "<td $styleRa>$amount</td>" .
            "<td $styleRa>$rebate</td>" .
            "<td></td>" .
            "<td></td>" .
        "</tr>";
    }
    
    protected function writeSubTotalHeaders()
    {
        $styleRab = "class='tg-rab'";
        $styleLab = "class='tg-lab'";
        
        $this->table .=         
        "<tr>" .
            "<td></td>" .
            "<td></td>" .
            "<td $styleRab>Amount</td>" .
            "<td $styleRab>Rebate</td>" .
            "<td $styleRab>Boosters Share</td>" .
            "<td $styleRab>Student Share</td>" .
        "</tr>";
    }
}
