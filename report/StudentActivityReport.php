<?php

require_once 'report/ActivityReport.php';
require_once 'orm/Student.php';

/**
 * Description of StudentActivityReport
 *
 */
class StudentActivityReport extends ActivityReport {
    
    private $student;
    private $cardReloads;
    
    function __construct($studentId, $startDate, $endDate) {
        
        parent::__construct($startDate, $endDate);   
        $this->student = new Student($studentId);
        
        // Get all card reloads in the date range that are assigned to the student
        $result = pg_query_params(
            "SELECT * from ks_card_reloads WHERE student=$1 " . 
            "AND reload_date>=$2 AND reload_date<=$3 " .
            "ORDER BY reload_date ASC", 
            array($studentId, $startDate, $endDate));
        
        if (!$result) {
            throw new Exception(pg_last_error());
        }
        
        $this->cardReloads = pg_fetch_all($result);
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
        
        $this->endTable();
    }
        
}
