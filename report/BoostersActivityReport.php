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
            "FROM ks_card_reloads WHERE allocation = 'donor' " .
            "AND reload_date>=$1 AND reload_date<=$2" .
            "ORDER BY card_holder, reload_date",
            array($this->startDate, $this->endDate));
             
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $count = pg_num_rows($result);
        
        $sumResult = pg_query_params(
            "SELECT SUM(reload_amount) FROM ks_card_reloads " . 
            "WHERE allocation = 'donor' AND reload_date>=$1 AND reload_date<=$2",
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
         
    private function getScripOrders($allocation, &$count, &$orderSum, &$rebateSum) {
        $result = pg_query_params(
            "SELECT so.order_date, so.scrip_first, so.scrip_last, " .
            "st.first, st.middle, st.last, so.order_amount, so.rebate " .
            "FROM scrip_orders AS so INNER JOIN students AS st " .
            "ON so.student = st.id WHERE so.allocation = $1 " .
            "AND so.order_date >= $2 AND so.order_date <= $3 " .
            "ORDER BY st.last, st.first, st.middle, so.order_date",
            array($allocation, $this->startDate, $this->endDate));
 
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $count = pg_num_rows($result);
        
        $orderSumResult = pg_query_params(
            "SELECT SUM(so.order_amount) " .
            "FROM scrip_orders AS so INNER JOIN students AS st " .
            "ON so.student = st.id WHERE so.allocation = $1 " .
            "AND so.order_date >= $2 AND so.order_date <= $3 ",
            array($allocation, $this->startDate, $this->endDate));
 
        if (!$orderSumResult) {
            throw new Exception(pg_last_error());
        }
        
        $orderSum = pg_fetch_result($orderSumResult, 0, 0);
        
        $rebateSumResult = pg_query_params(
            "SELECT SUM(so.rebate) " .
            "FROM scrip_orders AS so INNER JOIN students AS st " .
            "ON so.student = st.id WHERE so.allocation = $1 " .
            "AND so.order_date >= $2 AND so.order_date <= $3 ",
            array($allocation, $this->startDate, $this->endDate));
 
        if (!$rebateSumResult) {
            throw new Exception(pg_last_error());
        }
        
        $rebateSum = pg_fetch_result($rebateSumResult, 0, 0);
        
        return pg_fetch_all($result);
    }
    
    private function getUnassignedScripOrders($allocation, &$count, &$orderSum, &$rebateSum) {
        $result = pg_query_params(
            "SELECT order_date, scrip_first, scrip_last, order_amount, rebate " .
            "FROM scrip_orders WHERE allocation = $1 " .
            "AND order_date >= $2 AND order_date <= $3 " .
            "ORDER BY scrip_last, scrip_first, order_date",
            array($allocation, $this->startDate, $this->endDate));
 
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $count = pg_num_rows($result);
        
        $orderSumResult = pg_query_params(
            "SELECT SUM(order_amount) " .
            "FROM scrip_orders WHERE allocation = $1 " .
            "AND order_date >= $2 AND order_date <= $3 ",
            array($allocation, $this->startDate, $this->endDate));
 
        if (!$orderSumResult) {
            throw new Exception(pg_last_error());
        }
        
        $orderSum = pg_fetch_result($orderSumResult, 0, 0);
        
        $rebateSumResult = pg_query_params(
           "SELECT SUM(rebate) " .
            "FROM scrip_orders WHERE allocation = $1 " .
            "AND order_date >= $2 AND order_date <= $3 ",
            array($allocation, $this->startDate, $this->endDate));
 
        if (!$rebateSumResult) {
            throw new Exception(pg_last_error());
        }
        
        $rebateSum = pg_fetch_result($rebateSumResult, 0, 0);
        
        return pg_fetch_all($result);
    }
    
    private function getStudentWithdrawals(&$count, &$sum) {
        $result = pg_query_params(
            "SELECT w.date, st.first, st.middle, st.last, w.purpose, w.notes, w.amount " .
            "FROM student_withdrawals AS w INNER JOIN students AS st " .
            "ON w.student = st.id " .
            "WHERE date>=$1 AND date<=$2 " .
            "ORDER BY st.last, st.first, st.middle, w.date",
            array($this->startDate, $this->endDate));
             
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $count = pg_num_rows($result);
        
        $sumResult = pg_query_params(
            "SELECT SUM(amount) FROM student_withdrawals " .
            "WHERE date>=$1 AND date<=$2",
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
            $this->writeCardsTotal(3, "Total for active students", $sum, true);
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
            $this->writeCardsTotal(3, "Total for inactive students", $sum, false);
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
            $this->writeCardsTotal(3, "Total for card holders", $sum, false);
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
            $this->writeCardsTotal(3, "Total for unassigned cards", $sum, false);
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
            $this->writeCardsTotal(3, "Total for unrecorded cards", $sum, false);
        }
    }

    private function allocation2description($allocation, &$studentGetsShare) {
        
        $studentGetsShare = false;
        $description = "";
        switch ($allocation) {
            case "activeStudent":
                $description = "active student";
                $studentGetsShare = true;
                break;
            case "inactiveStudent":
                $description = "inactive student";
                break;
            case "unassigned":
                $description = "no assigned student";
                break;
            case "unrecorded":
                $description = "no recorded family";
                break;
        }
        return $description;
    }
    
    protected function writeScripOrders($allocation) {
        $studentGetsShare = false;
        $desc = $this->allocation2description($allocation, $studentGetsShare);
        $count = 0;
        $orderSum = 0;
        $rebateSum = 0;
        $orders = $this->getScripOrders($allocation, $count, $orderSum, $rebateSum);
        if ($orderSum > 0) {
            $this->writeCategoryHeader("Scrip orders with $desc", $count);
            $this->writeScripHeaders();
            foreach ($orders as $order) {
                $this->writeScripOrder(
                    $order['order_date'],
                    $order['scrip_first'] . " " . $order['scrip_last'],
                    $order['first'] . " " . $order['middle'] . " " . $order['last'],
                    $order['order_amount'],
                    $order['rebate']);
            }
            $this->writeUnderline();
            $this->writeSubTotalHeaders();
            $this->writeScripOrdersTotal("Total for scrip orders with $desc", $orderSum, $rebateSum, $studentGetsShare);
        }
    }
    
    protected function writeUnassignedScripOrders($allocation) {
        $studentGetsShare = false;
        $desc = $this->allocation2description($allocation, $studentGetsShare);
        $count = 0;
        $orderSum = 0;
        $rebateSum = 0;
        $orders = $this->getUnassignedScripOrders($allocation, $count, $orderSum, $rebateSum);
        if ($orderSum > 0) {
            $this->writeCategoryHeader("Scrip orders with $desc", $count);
            $this->writeScripHeaders();
            foreach ($orders as $order) {
                $this->writeScripOrder(
                    $order['order_date'],
                    $order['scrip_first'] . " " . $order['scrip_last'],
                    "",
                    $order['order_amount'],
                    $order['rebate']);
            }
            $this->writeUnderline();
            $this->writeSubTotalHeaders();
            $this->writeScripOrdersTotal("Total for scrip orders with $desc", $orderSum, $rebateSum, $studentGetsShare);
        }
    }
    
    private function writeStudentWithdrawals() {
        $count = 0;
        $sum = 0;
        $withdrawals = $this->getStudentWithdrawals($count, $sum);
        if ($sum > 0) {
            $this->writeCategoryHeader("Student withdrawals", $count);
            $this->writeWithdrawalHeaders();
            foreach ($withdrawals as $wd) {
                $this->writeWithdrawal(
                    $wd['date'],
                    $wd['first'] . " " . $wd['middle'] . " " . $wd['last'],
                    $wd['purpose'],
                    $wd['notes'],
                    $wd['amount']);
            }
            $this->writeUnderline();
            $this->writeWithdrawalsTotal("Total student withdrawals", $sum);
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
    
    protected function writeScripHeaders()
    {
        $styleRab = "class='tg-rab'";
        $styleLab = "class='tg-lab'";
        
        $this->table .=         
        "<tr>" .
            "<td $styleLab>Date</td>" .
            "<td $styleLab>Family</td>" .
            "<td $styleLab>Student</td>" .    
            "<td $styleRab>Amount</td>" .
            "<td $styleRab>Rebate</td>" .
        "</tr>";
    }
    
    protected function writeWithdrawalHeaders()
    {
        $styleRab = "class='tg-rab'";
        $styleLab = "class='tg-lab'";
        
        $this->table .=         
        "<tr>" .
            "<td $styleLab>Date</td>" .
            "<td $styleLab>Student</td>" .
            "<td $styleLab>Purpose</td>" .
            "<td $styleLab>Notes</td>" .
            "<td></td>" .
            "<td></td>" .
            "<td $styleRab>Amount</td>" .
        "</tr>";
    }
    
    private function writeScripOrder($date, $family, $student, $amount, $rebate) {
        
        $styleRa = "class='tg-ra'";
        
        $this->table .=
        "<tr>" .
            "<td>$date</td>" .
            "<td>$family</td>" .
            "<td>$student</td>" .
            "<td $styleRa>$amount</td>" .
            "<td $styleRa>$rebate</td>" .
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
    
    private function writeWithdrawal($date, $student, $purpose, $notes, $amount)
    {
        $styleRa = "class='tg-ra'";
        
        $amountStr = $this->format($amount);
        
        $this->table .=
        "<tr>" .
            "<td>$date</td>" .
            "<td>$student</td>" .
            "<td>$purpose</td>" .
            "<td>$notes</td>" .
            "<td></td>" .
            "<td></td>" .
            "<td $styleRa>$amountStr</td>" .
        "</tr>";
    }
    

    
    protected function writeScripOrdersTotal($description, $orderSum, $rebateSum, $studentGetsShare)
    {
        $styleLab = "class='tg-lab'";
        $styleRab = "class='tg-rab'";
        $styleRa  = "class='tg-ra'";
        $styleB3sl = "class='tg-b3sl'";
        $styleR3sl = "class='tg-r3sl'";
               
        if ($studentGetsShare) {
            $studentShare = $rebateSum * RebatePercentages::$STUDENT_SHARE;
            $studentShare = round($studentShare, 2);
            $boostersShare = $rebateSum - $studentShare;
        }
        else {
            $boostersShare = $rebateSum;
            $studentShare = 0;
        }
        
        $orderAmt = $this->format($orderSum);
        $rebateAmt = $this->format($rebateSum);
        $boostersShareAmt = $this->format($boostersShare);
        $studentShareAmt = $this->format($studentShare);
        
        $this->table .=
        "<tr>" .
            "<td $styleRab colspan='3'>$description</td>" .
            "<td $styleRa>$orderAmt</td>" .
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
    
    protected function writeWithdrawalsTotal($description, $total)
    {
        $styleRab = "class='tg-rab'";
        $styleRa  = "class='tg-ra'";

        $totalAmt = $this->format($total);
        
        $this->table .=
        "<tr>" .
            "<td $styleRab colspan='6'>$description</td>" .
            "<td $styleRa>$totalAmt</td>" .
        "</tr>";
        
        //$this->writeLine();
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
        if ($this->includeScrip) {
            $this->writeScripOrders("activeStudent");
            $this->writeScripOrders("inactiveStudent");
            $this->writeUnassignedScripOrders("unassigned");
        }
        if ($this->includeWithdrawals) {
            $this->writeStudentWithdrawals();
        }
    }
}
