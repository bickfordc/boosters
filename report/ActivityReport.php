<?php

require_once 'RebatePercentages.php';

/**
 * Description of ActivityReport
 *
 */
class ActivityReport {

    private $style;
    protected $table;
    private $tableForHtml = null;
    protected $startDate;
    protected $endDate;
    
    function __construct($startDate, $endDate) {
        $this->initStyle();
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    
    private function initStyle()
    {       
        $this->style =<<<EOF
        <style type="text/css">
            
    * {
        font-family: arial;
    }
    
    table { 
        border-collapse: collapse;
        border: none;
        margin: auto;
    }

    td {
        font-size: 10px;
        padding: 10px 5px;
/*        border-style: solid;
        border-width: 0px; */
        overflow: hidden;
    }

    th {
        font-size: 10px;
        font-weight: normal;
        padding: 10px 5px;
 /*       border-style: solid;
        border-width: 0px; */
        overflow: hidden;
    }

    .tg-title {   
        font-size: 18px;
        font-weight: bold;
        color: black;
        text-align: center;
    }

    .tg-sthd {
        font-weight: bold;
        background-color: #efefef;
    }

    .tg-undr {
        border-bottom: 1px solid black
    }

    .tg-b3sr {
        font-weight: bold; 
        /*border-top: 1px solid black;
        border-right: 1px solid black;
        border-bottom: 1px solid black;*/
    }

    .tg-r3sr {
        font-weight: bold;
        color: red;
        /*border-top: 1px solid red;
        border-right: 1px solid red;
        border-bottom: 1px solid red;*/
    }

    .tg-b3sl {
        text-align: right;
        /*border-top: 1px solid black;
        border-left: 1px solid black;
        border-bottom: 1px solid black;*/
    }

    .tg-r3sl {
        text-align: right;
        color: red;
        /*border-top: 1px solid red;
        border-left: 1px solid red;
        border-bottom: 1px solid red;*/
    }

    .tg-ra {
        text-align: right;
    }

    .tg-rab { 
        text-align: right;
        font-weight: bold;
    }

    .tg-lab {
        text-align: left;
        font-weight: bold;
    }

    .tg-plab {
        text-align: left;
        font-weight: bold;
        padding-left: 80px;
    }
    </style>              
EOF;
        
    }
    
    public function getStartDate() {
        return $this->startDate;
    }
    
    public function getEndDate() {
        return $this->endDate;
    }
    
    protected function startTable()
    {
        $style = "class='tg'";
        $this->table .= $this->style;        
        $this->table .= "<table $style>";
    }
    
    protected function endTable()
    {
        $this->table .= "</table>";
    }
     
    protected function writeTitle($title)
    {
        $style = "class='tg-title'";
        $this->table .= "<tr><th $style colspan='7'>$title</th></tr>";
    }
    
    protected function writeDate()
    {
        $dates = $this->getStartDate() . " through " . $this->endDate;
        $style = "class='tg-title'";
        $this->table .= "<tr><th $style colspan='7'>$dates</th></tr>";
    }
    
    protected function writeLine()
    {
        $this->table .= "<tr><td colspan='7'></td></tr>";
    }
    
    protected function writeCardHeaders()
    {
        $styleRab = "class='tg-rab'";
        $styleLab = "class='tg-lab'";
        
        $this->table .=         
        "<tr>" .
            "<td $styleLab>Date</td>" .
            "<td $styleLab>Card</td>" .
            "<td $styleRab>Amount</td>" .
            "<td $styleRab>Rebate</td>" .
            "<td $styleRab>Boosters Share</td>" .
            "<td $styleRab>Student Share</td>" .
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
            "<td $styleRab>Amount</td>" .
            "<td $styleRab>Rebate</td>" .
            "<td $styleRab>Boosters Share</td>" .
            "<td $styleRab>Student Share</td>" .
            "<td></td>" .
        "</tr>";
    }
    
    protected function writeWithdrawalHeaders()
    {
        $styleRab = "class='tg-rab'";
        $styleLab = "class='tg-lab'";
        
        $this->table .=         
        "<tr>" .
            "<td $styleLab>Date</td>" .
            "<td $styleLab>Purpose</td>" .
            "<td $styleLab colspan='3'>Notes</td>" .
            "<td $styleRab>Amount</td>" .
            "<td></td>" .
        "</tr>";
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
            "<td></td>" .
        "</tr>";
    }
    
    private function writeCardReload($date, $card, $amount)
    {
        $styleRa = "class='tg-ra'";
        
        $rebate = $amount * RebatePercentages::$KS_CARD_RELOAD * RebatePercentages::$STUDENT_SHARE;
        $amountStr = $this->format($amount);
        $rebateStr = $this->format($rebate);
        
        $this->table .=
        "<tr>" .
            "<td>$date</td>" .
            "<td>$card</td>" .
            "<td $styleRa>$amountStr</td>" .
            "<td $styleRa>$rebateStr</td>" .
            "<td></td>" .
            "<td></td>" .
            "<td></td>" .
        "</tr>";
    }
    
    private function writeWithdrawal($date, $purpose, $notes, $amount)
    {
        $styleRa = "class='tg-ra'";
        
        $amountStr = $this->format($amount);
        
        $this->table .=
        "<tr>" .
            "<td>$date</td>" .
            "<td>$purpose</td>" .
            "<td colSpan='3'>$notes</td>" .
            "<td $styleRa>$amountStr</td>" .
            "<td></td>" .
        "</tr>";
    }
    
    protected function writeCardsTotal($total)
    {
        $styleLab = "class='tg-lab'";
        $stylePlab = "class='tg-plab'";
        $styleRa  = "class='tg-ra'";
        $styleB3sl = "class='tg-b3sl'";
        $styleR3sl = "class='tg-r3sl'";
        $styleB3sr = "class='tg-b3sr'";
        $styleR3sr = "class='tg-r3sr'";
               
        $rebate = $total * RebatePercentages::$KS_CARD_RELOAD;
        $boostersShare = $rebate * RebatePercentages::$BOOSTERS_SHARE;
        $studentShare = $rebate * RebatePercentages::$STUDENT_SHARE;
        
        $totalAmt = $this->format($total);
        $rebateAmt = $this->format($rebate);
        $boostersShareAmt = $this->format($boostersShare);
        $studentShareAmt = $this->format($studentShare);
        
        $this->table .=
        "<tr>" .
            "<td $stylePlab colspan='2'>Total</td>" .
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
    
    protected function writeScripTotal($totalAmount, $totalRebate)
    {
        $styleLab = "class='tg-lab'";
        $stylePlab = "class='tg-plab'";
        $styleRa  = "class='tg-ra'";
        $styleB3sl = "class='tg-b3sl'";
        $styleR3sl = "class='tg-r3sl'";
        $styleB3sr = "class='tg-b3sr'";
        $styleR3sr = "class='tg-r3sr'";
               
        $boostersShare = $totalRebate * RebatePercentages::$BOOSTERS_SHARE;
        $studentShare = $totalRebate * RebatePercentages::$STUDENT_SHARE;
        
        $totalAmt = $this->format($totalAmount);
        $rebateAmt = $this->format($totalRebate);
        $boostersShareAmt = $this->format($boostersShare);
        $studentShareAmt = $this->format($studentShare);
        
        $this->table .=
        "<tr>" .
            "<td $stylePlab colspan='2'>Total</td>" .
            "<td $styleRa>$totalAmt</td>" .
            "<td $styleRa>$rebateAmt</td>" .
            "<td $styleRa>$boostersShareAmt</td>" .
            "<td $styleB3sl>$studentShareAmt</td>" .
        "</tr>";     
 
        $this->writeLine();
    }
    
    protected function writeStudentKsReloads($reloads) {
        $total = 0;
        foreach ($reloads as $reload) {
            $this->writeCardReload($reload['reload_date'],
                            $reload['card'],
                            $reload['reload_amount']);
            $total += $reload['reload_amount'];
        }
        $this->writeCardsTotal($total);
    }
         
    protected function writeScripOrders($orders) {
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
        $this->writeScripTotal($totalAmount, $totalRebate);
    }
    
    protected function writeWithdrawals($debits) {
        $total = 0;
        foreach ($debits as $debit) {
            $this->writeWithdrawal(
                            $debit['date'],
                            $debit['purpose'],
                            $debit['notes'],
                            $debit['amount']);
            $total += $debit['amount'];
        }
        $this->writeWithdrawalTotal($total);
    }
    
    protected function buildTable()
    {
        $this->table = "";
        $this->startTable();
        $this->writeTitle("Default Title");
        $this->endTable();
    }
    
    public function getTable()
    {
        $result = null;

        if ($this->tableForHtml == null) 
        {
            $this->buildTable();
            $this->tableForHtml = $this->table;
        }
        $result = $this->tableForHtml; 
        return $result;
    }
    
    private function format($number) {
        // format mumbers with a '0' for padding
        // minimum 1 charcter before the decimal point,
        // and always two characters after. And treat it 
        // as a floating point number (f)
        // So 0 becomes 0.00
        return sprintf("%01.2f", $number);
    }
    
    private function numberToMoney($number)
    {
        // Not using PHP's money_format since not available on Windows.
        $money = "";
        if ($number < 0)
        {
            $money = "-$" . sprintf("%01.2f", abs($number));
        }
        else
        {
            $money = "$" . sprintf("%01.2f", $number);
        }
        return $money;
    }
}
