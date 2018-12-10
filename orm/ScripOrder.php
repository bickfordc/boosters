<?php

include 'orm/Student.php';
include 'RebatePercentages.php';

/**
 * Description of an order from ShopWithScrip.
 * An order includes a rebate, and a percentage of the rebate will go to 
 * the student associated with the ShopWithScrip family that placed the order. 
 *
 */
class ScripOrder {
    
    private $familyFirst;
    private $familyLast;
    private $orderId;
    private $orderCount;
    private $orderDate;
    private $orderAmount;
    private $rebate;
    private $student;
    
    /**
     * 
     * @param type $row A row from a ShopWithScrip Family Earnings report in .csv format
     */
    function __construct($row) {
        
        $this->familyFirst = $row[0];
        $this->familyLast = $row[1];
        $this->orderCount = $row[4];
        $this->orderId = $row[5];
        $this->orderDate = $this->parseDate($row[6]);
        $this->orderAmount = $row[7];
        $this->rebate = floatval($row[7]) - floatval($row[8]);
        
        $studentId = $this->getStudentId();
        if ($studentId){
            $this->student = new Student($studentId);
        }
    }
    
    public function getFamilyFirst() {
        return $this->familyFirst;
    }

    public function getFamilyLast() {
        return $this->familyLast;
    }

    public function getOrderId() {
        return $this->orderId;
    }

    public function getOrderCount() {
        return $this->orderCount;
    }

    public function getOrderDate() {
        return $this->orderDate;
    }

    public function getRebate() {
        return $this->rebate;
    }

    public function getStudent() {
        return $this->student;
    }
    
    public function setFamilyFirst($familyFirst) {
        $this->familyFirst = $familyFirst;
    }

    public function setFamilyLast($familyLast) {
        $this->familyLast = $familyLast;
    }

    public function setOrderId($orderId) {
        $this->orderId = $orderId;
    }

    public function setOrderCount($orderCount) {
        $this->orderCount = $orderCount;
    }

    public function setOrderDate($orderDate) {
        $this->orderDate = $orderDate;
    }

    public function setRebate($rebate) {
        $this->rebate = $rebate;
    }

    /**
     * A DB transaction that: 
     *  - inserts the Scrip order
     *  - If there is an associated active student, updates the student balance
     *
     * @param type boolean $transaction If true, performs the db updates as a transaction
     *        pass FALSE if you will be calling persist on  a number of orders and 
     *        will provide your own outer transaction
     * @throws Exception
     */
    public function persist($transaction = TRUE) {
        
        try {
            if ($transaction) {
                pg_query("BEGIN");
            }
            $this->insertOrderToDb();

            if ($this->student && $this->student->isActive())
            {
                $this->student->adjustBalance($this->rebate * RebatePercentages::$STUDENT_SHARE);
                $this->student->updateBalanceInDb();
            }
            if ($transaction) {
                pg_query("COMMIT");
            }
        }
        catch (Exception $ex) {
            if ($transaction) {
                pg_query("ROLLBACK");
            }
            throw $ex;
        }    
    }
    
    /**
     * Get the ID of the student that the Scrip Family is associated with.
     * @return int student ID, or NULL if card is not assigned
     * 
     */
    public function getStudentId() {
        $result = pg_query_params("SELECT student FROM scrip_families WHERE family_first=$1 AND family_last=$2", 
                array($this->familyFirst, $this->familyLast));
        if (($row = pg_fetch_array($result)) === false)
        {
            return NULL;
        }
        else
        {
            return $row["student"];
        }
    }
            
    public function insertOrderToDb() {
        
        if ($this->student && $this->student->isActive())
        {
            $result = pg_query_params("INSERT INTO scrip_orders VALUES ($1, $2, $3, $4, $5, $6, $7, $8)", 
            array(
                $this->orderId,
                $this->orderCount,
                $this->orderDate,
                $this->orderAmount,
                $this->rebate,
                $this->familyFirst,
                $this->familyLast,
                $this->student->getId()
                ));
            if (!$result) {
                throw new Exception(pg_last_error());
            }
        }
        else
        {
            $result = pg_query_params("INSERT INTO scrip_orders (order_id, order_count, "
                . "order_date, order_amount, rebate, scrip_first, scrip_last) VALUES ($1, $2, $3, $4, $5, $6, $7)", 
                array(
                $this->orderId,
                $this->orderCount,
                $this->orderDate,
                $this->orderAmount,
                $this->rebate,
                $this->familyFirst,
                $this->familyLast
                ));
            if (!$result) {
                throw new Exception(pg_last_error());
            }
        }
    }
    
    private function parseDate($dateStr) {
        $elements = explode(" ", $dateStr);
        return $elements[0];
    }
}
