<?php

require_once 'report/ActivityReport.php';

class BoostersActivityReport extends ActivityReport {
    
    function __construct($startDate, $endDate) {

        parent::__construct($startDate, $endDate);  

//        $this->cardReloads = $this->getCardReloads();
//        $this->cardReloadTotal = $this->getCardReloadTotal();
//
//        $this->scripOrders = $this->getScripOrders();
//        $this->scripRebateTotal = $this->getScripRebateTotal();
//
//        $this->withdrawals = $this->getWithdrawals();
//        $this->withdrawalTotal = $this->getWithdrawalTotal();
    }
    
}
