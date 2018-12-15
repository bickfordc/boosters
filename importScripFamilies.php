<?php

    require 'vendor/autoload.php';
    
    require_once 'header.php';
     
    if (!$loggedin) 
    {
        header("Location: login.php");
    }

    // Handle Mac OS X line endings (LF) on uploaded .csv files
    ini_set("auto_detect_line_endings", true);
       
    $fatalError = false;
    $reportComplete = false;
    $name;
    $pageMsg = "Select a ShopWithScrip .csv file";
    
    if ($_FILES)  
    {   
        try {
            $name = $_FILES['filename']['tmp_name']; 
            if (empty($name)) {
                throw new Exception("Please select a file.");
            }

            $type = $_FILES['filename']['type'];
            if ($type != "text/csv" && $type != "application/vnd.ms-excel" && $type != "text/plain")
            {
                throw new Exception("That file type was $type, not text/csv.");
            }

            $file = fopen($name, "r");
            if ($file == NULL)
            {
                throw new Exception("Could not open file $name");
            }
            
            validateReport($file);
            
            $familiesImported = 0;
            while (!feof($file))
            {
                // validation took the header row, so this row is start of data
                $row = fgetcsv($file, 300, ","); 

                if ($row[0] == NULL) {
                    continue;
                }
                if (updateDatabase($row)) {
                    $familiesImported++;
                }
            }
            fclose($file);
            
            $successMsg = $familiesImported . " families imported.";
        }
        catch(Exception $e) {
            if ($file) {
                fclose($file);
            }
            $pageMessage = "";
            $errorMsg = $e->getMessage() . "<br>" . "No families were imported.";
        }
    }
        
    if (!empty($errorMsg)) {
        echo "<p class='errorMessage pageMessage'>$errorMsg</p>";
    }
    else if (!empty($successMsg)) {
        echo "<p class='successMessage pageMessage'>$successMsg</p>";
    }
        
    echo <<<_END
    <div class="container">
      <div style="float:left;margin:10px">
        <img src='img/familyMailMerge.png'>
      </div>
      <div style="float:left;width:467px;height:823px;margin:10px"> 
        <p>From the <a href='https://shop.shopwithscrip.com/Org/Manage/Report'>Reports</a>
           page at ShopWithScrip, click <b>Run Report</b> for <b>Family Mail Merge Summary</b>.
           <br><br>This will bring up a form as shown at left. 
           For the Output Format, choose Comma Delimited (*.csv).<br><br>
           Click Run Report to save the file to your computer, then select it with the form at right.
           <br><br>If after choosing the .csv file and clicking Import Scrip Families, you get an error 
           stating <em>The file does not appear to be a ShopWithScrip Family Mail Merge Summary</em> then
           try opening the file in Excel and then saving as CSV (Comma delimited) (*.csv)
        </p>
      </div>
      <div style="float:left;width:467px;height:423px;margin:10px"> 
        <div class="form">
          <p style="text-align:center">Select the .csv file</p>
          <p class='error'>$formError</p>
          <form method='post' action='importScripFamilies.php' enctype='multipart/form-data'>
            <input type='file' name='filename' size='10'>
            <button type='submit'>Import Scrip families</button>   
          </form>
        </div>
      </div>
    </div>
_END;
        
    function validateReport($file)
    {      
        $row = fgetcsv($file, 300, ",");
        if ($row[0] !== "OrgName" ||
            $row[1] !== "FamilyName")
        {
            throw new Exception("The file does not appear to be a ShopWithScrip 'Family Mail Merge Summary'");
        }
    }
            
    function updateDatabase($row) 
    {
        $firstName = trim($row[2]);
        $lastName = trim($row[1]);

        $result = pg_query_params("SELECT family_last FROM scrip_families WHERE family_first=$1 AND family_last=$2",
                array($firstName, $lastName));
        
        if (pg_num_rows($result) == 1) {
            // already have this scrip family, return false indicating it was not added.
            return FALSE;
        } 
        else {
            $result = pg_query_params("INSERT INTO scrip_families (family_first, family_last) VALUES($1, $2)",
                array($firstName, $lastName));
            
            if (!$result) {
                throw new Exception(pg_last_error());
            }
            return TRUE;
        }
    }