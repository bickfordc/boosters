<?php

    require_once 'header.php';
  
    if (isset($_GET['email']))
    {
        $email = sanitizeString($_GET['email']);
        echo "<div class='pageMessage'>Please check your email at $email " .
             "for a reset code and enter it below.</div>";
    }
    
    $error = "";
   
    if (isset($_POST['code']) && isset($_POST['pw1']) && isset($_POST['pw2']))
    {
        // If this is a valid reset request, we'll have a session variable that
        // holds the corresponding email address.
        if (isset($_SESSION["userRequestingReset"]))
        {
            $email = $_SESSION["userRequestingReset"];
            $resetCode = sanitizeString($_POST['code']);
            
            if (isValidResetRequest($resetCode, $email))
            {
                $pw1 = sanitizeString($_POST['pw1']);
                $pw2 = sanitizeString($_POST['pw2']);
                
                if (validateAndChangePassword($email, $pw1, $pw2, $error))
                {
                    // delete the reset code so it cannot be used again
                    queryPostgres(
                        "DELETE FROM reset_requests WHERE code=$1 AND usr=$2",
                        array($resetCode, $email));
                
                    destroySession();
                    header("Location: login.php?msg=Password change was successful. " .
                           "Please login with your new password.");
                }
            }
            else
            {
                $error = "Invalid reset code";
            }
        }
        else
        {
            $error = "Invalid reset code";
        }
        
        if (isNumAttemptsExceeded())
        {
          echo "<div class=pageMessage>Invalid request. " .
               "Click <a href='forgotPassword.php'>here</a> to reset your password.</div>";
          exit();
        }
        
    } 
    
    function isNumAttemptsExceeded()
    {
        $exceeded = false;
        
        if (!isset($_SESSION['numAttempts']))
        {
            $_SESSION['numAttempts'] = 1;
        }
        else 
        {
            $_SESSION['numAttempts'] += 1;
            if ($_SESSION['numAttempts'] > 5)
            {
                $exceeded = true;
                unset($_SESSION['numAttempts']);
            }
        }
        
        return $exceeded;
    }
    
    function isValidResetRequest($resetCode, $user)
    {
        $isValidRequest = false;
        
        $result = queryPostgres(
            "SELECT code, expiration, usr FROM reset_requests WHERE code=$1 AND usr=$2",
            array($resetCode, $user));
        
        if (pg_num_rows($result) > 0) 
        {
            $row = pg_fetch_array($result);
            
            $requestExpiration = strtotime($row['expiration']);            
            $now = time();  
            
            if ($now < $requestExpiration)
            {
                $isValidRequest = true;
            }
        }
               
        return $isValidRequest;
    }
        
    echo <<<_END
    <script src='passwordMatch.js'></script>
    <div class="reset-page">
      <div class="form">
       <form id='resetForm' class='login-form' method='post' action='resetPassword.php?'>      
        <input type="text" placeholder="Reset code" name='code' value='$resetCode'/>
        <input id='pw1' type="password" placeholder="New password" name='pw1' value='$pw1'/>
        <input id='pw2' type="password" placeholder="Reenter new password" name='pw2' value='$pw2'/>
        <div id='info' class='error'>$error</div>
        <button type='submit' id='send'>Reset password</button>
       </form>
      </div>
    </div>
_END

?>

    <br></div>
  </body>
</html>
