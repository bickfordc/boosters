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
    
    function __construct($studentId, $startDate, $endDate) {
        
        parent::__construct($startDate, $endDate);  
        $this->studentId = $studentId;
        $this->student = new Student($studentId);
        
        $this->cardReloads = $this->getCardReloads();
        $this->cardReloadTotal = $this->getCardReloadTotal();
    }
    
    protected function writeCategoryHeader($name)
    {
        $style = "class='tg-sthd'";
        $this->table .= "<tr><td $style colspan='7'>$name</td></tr>";
    }
    
    protected function buildTable()
    {
        $this->table = "";
        $this->startTable();
        $this->writeTitle($this->student->getFullName());
        $this->writeDate();
        $this->writeCategoryHeader("King Soopers");
        $this->writeCardHeaders();
        $this->writeStudentKsReloads($this->cardReloads);
        $this->writeCategoryHeader("ShopWithScrip");
        
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
}
