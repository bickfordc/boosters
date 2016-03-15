<?php

    require_once 'header.php';

    echo <<<_END
    <div class="reset-page">
      <div class="form">
       <form id='resetForm' class='reset-form' method='post' action='resetPassword.php'>
        <a href='index.php' class='cancelX'>X</a>
        <div id='info'>$error</div>
        <input id='current' type="password" placeholder="Current password" name='password1' value='$current'/>
        <input id='pw1' type="password" placeholder="New password" name='password1' value='$password1'/>
        <input id='pw2' type="password" placeholder="Reenter new password" name='password2' value='$password2'/>
        <button type='submit' id='send'>Change password</button>
       </form>
      </div>
    </div>
_END;

?>

    <br></div>
  </body>
</html>