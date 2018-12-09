<?php

/**
 * 
 * 
 */
class Student {
    
    private $id;
    private $first;
    private $middle;
    private $last;
    private $email;
    private $balance;
    private $graduationYear;
    private $active;
    
    /**
     * Constructs a Student object from the database record
     * @param int $id
     */
    function __construct($id) {
        $result = pg_query_params("SELECT * FROM students WHERE id=$1", array($id));
        if (!$result) {
            throw new Exception("No student found with ID $id");
        }
        $dbObj = pg_fetch_object($result);
        if ($dbObj) {
            $this->id = $id;
            $this->first = $dbObj->first;
            $this->middle = $dbObj->middle;
            $this->last = $dbObj->last;
            $this->email = $dbObj->email;
            $this->balance = $dbObj->balance;
            $this->graduationYear = $dbObj->graduation_year;
            $this->active = $dbObj->active;
        }
        else 
        {
            throw new Exception("Error constucting student");
        }
    }
    
    public function getId() {
        return $this->id;
    }

    public function getFirst() {
        return $this->first;
    }

    public function getMiddle() {
        return $this->middle;
    }

    public function getLast() {
        return $this->last;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getBalance() {
        return $this->balance;
    }

    public function getGraduationYear() {
        return $this->graduationYear;
    }

    public function isActive() {
        if ($this->active == "t") {
            return TRUE;
        }
        else {
            return FALSE;;
        }
    }

    public function getFullName() {
        return "$this->first $this->middle $this->last";
    }
    
    public function setId($id) {
        $this->id = $id;
    }

    public function setFirst($first) {
        $this->first = $first;
    }

    public function setMiddle($middle) {
        $this->middle = $middle;
    }

    public function setLast($last) {
        $this->last = $last;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setBalance($balance) {
        $this->balance = $balance;
    }

    public function setGraduationYear($graduationYear) {
        $this->graduationYear = $graduationYear;
    }

    public function setActive($active) {
        $this->active = $active;
    }

    /**
     * Adjust the students balance. 
     * @param float $amount (may be negative)
     */
    public function adjustBalance($amount) {
        $this->balance += $amount;
    }
     
    /*
     * Update the students balance in the database
     */
    public function updateBalanceInDb() {
        $result = pg_query_params("UPDATE students SET balance=$1 WHERE id=$2", 
                array($this->balance, $this->id));
        if ($result) {
            $affectedRows = pg_affected_rows($result);
            if ($affectedRows != 1) {
                throw new Exception("Balance update error for student ID $this->id");
            }
        }
        if (!$result) {
           throw new Exception(pg_last_error());
        }
    }    
    
    public static function isStudentActive($studentId) {
        $result = pg_query_params("SELECT active FROM students WHERE id=$1", 
                array($studentId));
        
        if (!$result) {
           throw new Exception(pg_last_error());
        }
        
        if (pg_num_rows($result) == 0) {
            throw new Exception("No student found with id = $studentId");
        }
        
        $row = pg_fetch_row($result);
        if ($row[0] == "t") {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }
}
