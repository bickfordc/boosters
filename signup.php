<?php // Example 26-5: signup.php
  require_once 'header.php';

  if (!$loggedin) 
  {
      header("Location: login.php");
  }
  
  echo <<<_END
  <script>
    function userExists(user)
    {
      if (user.value == '')
      {
        O('info').innerHTML = ''
        return
      }

      params  = "user=" + user.value
      request = new ajaxRequest()
      request.open("POST", "userExists.php", true)
      request.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
      request.setRequestHeader("Content-length", params.length)
      request.setRequestHeader("Connection", "close")

      request.onreadystatechange = function()
      {
        if (this.readyState == 4)
          if (this.status == 200)
            if (this.responseText == "true") {
              O('info').innerHTML = "<span class='taken'>&nbsp;&#x2718; This username is taken</span>"
            } else {
              O('info').innerHTML = "<span class='available'>&nbsp;&#x2714; This username is available</span>"
            }
      }
      request.send(params)
    }

    function ajaxRequest()
    {
      try { var request = new XMLHttpRequest() }
      catch(e1) {
        try { request = new ActiveXObject("Msxml2.XMLHTTP") }
        catch(e2) {
          try { request = new ActiveXObject("Microsoft.XMLHTTP") }
          catch(e3) {
            request = false
      } } }
      return request
    }
  </script>
  <div class='main'><h3>Please enter your details to sign up</h3>
_END;

  $error = $user = $pass = "";
  if (isset($_SESSION['user'])) destroySession();

  if (isset($_POST['user']))
  {
    $user = sanitizeString($_POST['user']);
    $pass = sanitizeString($_POST['pass']);

    if ($user == "" || $pass == "")
      $error = "Not all fields were entered<br><br>";
    else
    {
      $result = queryPostgres("SELECT * FROM members WHERE usr=$1", array($user));

      if (pg_num_rows($result))
        $error = "That username already exists<br><br>";
      else
      {
        $token = getToken($pass);
        queryPostgres("INSERT INTO members VALUES($1, $2)", array($user, $token));
        die("<h4>Account created</h4>Please Log in.<br><br>");
      }
    }
  }

  echo <<<_END
    <form method='post' action='signup.php'>$error
    <span class='fieldname'>Username</span>
    <input type='text' maxlength='32' name='user' value='$user'
      onBlur='userExists(this)'><span id='info'></span><br>
    <span class='fieldname'>Password</span>
    <input type='text' maxlength='32' name='pass'
      value='$pass'><br>
_END;
?>

    <span class='fieldname'>&nbsp;</span>
    <input type='submit' value='Sign up'>
    </form></div><br>
  </body>
</html>
