<?php // Example 26-11: messages.php
  require_once 'header.php';

  if (!$loggedin) die();

  if (isset($_GET['view'])) $view = sanitizeString($_GET['view']);
  else                      $view = $user;

  if (isset($_POST['text']))
  {
    $text = sanitizeString($_POST['text']);

    if ($text != "")
    {
      $pm   = substr(sanitizeString($_POST['pm']),0,1);
      $time = date('Y-m-d H:i:s');
      queryPostgres("INSERT INTO messages VALUES(DEFAULT, $1,
        $2, $3, $4, $5)", array($user, $view, $pm, $time, $text));
    }
  }

  if ($view != "")
  {
    if ($view == $user) $name1 = $name2 = "Your";
    else
    {
      $name1 = "<a href='members.php?view=$view'>$view</a>'s";
      $name2 = "$view's";
    }

    echo "<div class='main'><h3>$name1 Messages</h3>";
    showProfile($view);
    
    echo <<<_END
      <form method='post' action='messages.php?view=$view'>
      Type here to leave a message:<br>
      <textarea name='text' cols='40' rows='3'></textarea><br>
      Public<input type='radio' name='pm' value='0' checked='checked'>
      Private<input type='radio' name='pm' value='1'>
      <input type='submit' value='Post Message'></form><br>
_END;

    if (isset($_GET['erase']))
    {
      $erase = sanitizeString($_GET['erase']);
      queryPostgres("DELETE FROM messages WHERE id=$1 AND recip=$2", array($erase, $user));
    }
    
    $query  = "SELECT * FROM messages WHERE recip=$1 ORDER BY time DESC";
    $result = queryPostgres($query, array($view));
    $num    = pg_num_rows($result);
    
    for ($j = 0 ; $j < $num ; ++$j)
    {
      if ($row['pm'] == 0 || $row['auth'] == $user || $row['recip'] == $user)
      {
        //echo date(DATE_RFC2822, $row['time']);
        echo $row['time'];
        echo " <a href='messages.php?view=" . $row['auth'] . "'>" . $row['auth']. "</a> ";

        if ($row['pm'] == 0)
          echo "wrote: &quot;" . $row['message'] . "&quot; ";
        else
          echo "whispered: <span class='whisper'>&quot;" .
            $row['message']. "&quot;</span> ";

        if ($row['recip'] == $user)
          echo "[<a href='messages.php?view=$view" .
               "&erase=" . $row['id'] . "'>erase</a>]";

        echo "<br>";
      }
    }
  }

  if (!$num) echo "<br><span class='info'>No messages yet</span><br><br>";

  echo "<br><a class='button' href='messages.php?view=$view'>Refresh messages</a>";
?>

    </div><br>
  </body>
</html>
