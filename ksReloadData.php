<?php

require_once 'header.php';

if (!$loggedin) 
{
  header("Location: login.php");
}

$error = "";
$pageMsg = "Under construction";

echo <<<_END

<p class='pageMessage'>$pageMsg</p>
        
</body>
</html>
_END;
