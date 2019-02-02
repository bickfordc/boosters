<?php

    require_once 'header.php';

    if (!$loggedin) 
    {
        header("Location: login.php");
    }
    
    $error = "";    
    $resetCode = "";
    $user = "";
    $allegedPw = "";
    $resetAllowed = false;
    $message = "";
        
    if (isset($_GET['code']) && isset($_GET['user']))
    {
        // This is a forgotten password reset request. 
        // The user has followed the link sent to their email, 
        // but has not submitted a new password yet.
        
        $resetCode = sanitizeString($_GET['code']);
        $user = sanitizeString($_GET['user']);
        
        if (isValidResetRequest($resetCode, $user))
        {
            //Set flag so we embed hidden input values
            $resetAllowed = true;
        }
        else
        {
            exit();
        }
    }
    
    else if (isset($_POST['code']) && isset($_POST['user']))
    {
        // This is a forgotten password reset request. 
        // The user has submitted a new password. 
        
        $resetCode = sanitizeString($_POST['code']);
        $user = sanitizeString($_POST['user']);
        
        if (isValidResetRequest($resetCode, $user))
        {
            $newPw1 = sanitizeString($_POST['newPw1']);       
            $newPw2 = sanitizeString($_POST['newPw2']); 
            
            // delete the reset code so it cannot be used again
            queryPostgres(
              "DELETE FROM reset_requests WHERE code=$1 AND usr=$2",
               array($resetCode, $user));
            
            validateAndChangePassword($user, $newPw1, $newPw2, $error);    
        }
        else 
        {
            exit();
        }
    }
    
    else if (isset($_POST['currentPw']))
    {
        // This is a request from a logged in user to change password. 
        if (!$loggedin) die();
        
        $allegedPw = sanitizeString($_POST['currentPw']);
        $newPw1    = sanitizeString($_POST['newPw1']);       
        $newPw2    = sanitizeString($_POST['newPw2']); 
        
        $userToken = $_SESSION['userToken'];
        $allegedToken  = getToken($allegedPw);
        
        if ($allegedToken != $userToken)
        {
            $error = "Incorrect current password";
            $allegedPw = "";
        }
        else
        {
            $user = $_SESSION['user'];
            validateAndChangePassword($user, $newPw1, $newPw2, $error);
            $message = "Password changed.";
        }
    }    

    echo <<<_END
    <script src='passwordMatch.js'></script>
    <p class='pageMessage'>$message</p>
    <div class="reset-page">
      <div class="form">
       <form id='resetForm' class='reset-form' method='post' action='changePassword.php'>
        <a href='index.php' class='cancelX'>X</a>
_END;
    if ($resetAllowed)
    {
        echo "<input type='hidden' name='code' value='$resetCode'/>";
        echo "<input type='hidden' name='user' value='$user'/>";
    }
    if ($loggedin)
    {
        echo "<input id='current' type='password' placeholder='Current password' name='currentPw' value='$allegedPw'/>";
    }
    
    echo <<<_END
        <input id='pw1' type="password" placeholder="New password" name='newPw1'/>
        <input id='pw2' type="password" placeholder="Reenter new password" name='newPw2'/>
        <div id='info' class=error>$error</div>
        <button type='submit' id='send'>Change password</button>
       </form>
      </div>
    </div>
_END;

?>

    <br></div>
  </body>
</html>