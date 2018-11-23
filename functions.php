<?php

  date_default_timezone_set("America/Denver");

  $dbUrl = getenv('DATABASE_URL');
  
  if ($dbUrl) {  
    $dbOpts = parse_url($dbUrl);
    $host        = "host=".$dbOpts['host']; 
    $port        = "port=".$dbOpts['port'];
    $dbname      = "dbname=".ltrim($dbOpts['path'], '/');        
    $credentials = "user=".$dbOpts['user']." password=".$dbOpts['pass'];    
  } else {
    die("Environment variable DATABASE_URL not found");
  }
  
  
  $appname = "boosters"; 

  $db = pg_connect("$host $port $dbname $credentials");
  
  if (!$db) {
      die("Unable to open database : " . pg_last_error());
  } 
    
  function queryPostgres($query, $params)
  {
      $result = pg_query_params($query, $params);
      if (!$result) 
      {
          $err = pg_last_error();
          die (pg_last_error());
      }
      return $result;
  }
  
  function destroySession()
  {
    $_SESSION=array();

    if (session_id() != "" || isset($_COOKIE[session_name()]))
      setcookie(session_name(), '', time()-2592000, '/');

    session_destroy();
  }

  function sanitizeString($var)
  {
    $var = strip_tags($var);
    $var = htmlentities($var);
    return stripslashes($var);
  }

  function getToken($pw) 
  {
      $salt1="35=(%)";
      $salt2="Git76";    
      return hash("ripemd128", "$salt1$pw$salt2");
  }
    
    function validateAndChangePassword($user, $newPw1, $newPw2, &$error)
    {
        $passwordChanged = false;
        
        if ($newPw1 != $newPw2)
        {
            $error = "Passwords do not match.";
        }
        else if (strLen($newPw1) < 8)
        {
            $error = "New password must be at least 8 characters.";
        }
        else
        {
            queryPostgres("UPDATE members SET pass=$1 WHERE usr=$2",
               array(getToken($newPw1), $user));

            $passwordChanged = true;
        }
        
        return $passwordChanged;
    }
    
    function formatCardNumber($cardNumber, $cardType) 
    {

        $formattedCardNumber = $cardNumber;

        // Format King Soopers cards as 10 digits, space, 3 digits, space, 3 digits, space, 3 digits
        // 6006495903 177 095 385
        if ($cardType == "KS") {
            $formattedCardNumber = substr($cardNumber, 0, 10) . " " .
                                   substr($cardNumber, 10, 3) . " " .
                                   substr($cardNumber, 13, 3) . " " .
                                   substr($cardNumber, 16, 3);
        }
        // Format Safeway cards as 12 digits, space, 7 digits
        // 603953500106 5471470
        elseif ($cardType == "SW") {
            $formattedCardNumber = substr($cardNumber, 0, 12) . " " .
                                   substr($cardNumber, 12, 7);
        }

        return $formattedCardNumber;  
    }
    
 function getStudentIdByName($first, $middle, $last) {
    
    if (empty($middle)) {
        $result = queryPostgres("SELECT id FROM students WHERE first=$1 AND last=$2", array($first, $last));
        
    } elseif (!empty($middle)) {
        $result = queryPostgres("SELECT id FROM students WHERE first=$1 AND middle=$2 AND last=$3", array($first, $middle, $last));

    } else {
        return NULL;
    }
    
    if (($row = pg_fetch_array($result)) === false) {
        return NULL;
    }
    else {
        return $row["id"];
    }
}
?>
