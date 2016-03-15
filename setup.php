<!DOCTYPE html>
<html>
  <head>
    <title>Setting up database</title>
  </head>
  <body>

    <h3>Setting up...</h3>

<?php // Example 26-3: setup.php
  require_once 'functions.php';

  $sql =<<<EOF
    CREATE TABLE IF NOT EXISTS members 
      (usr varchar(32),
       pass varchar(32));
EOF;
  
  postgres_query($sql);
  
  $sql =<<<EOF
    CREATE TABLE IF NOT EXISTS messages 
      (id SERIAL PRIMARY KEY,
       auth varchar(32),
       recip varchar(32),
       pm char(1),
       time timestamp,
       message varchar(4096));
EOF;
  
  postgres_query($sql);
  
  $sql =<<<EOF
    CREATE TABLE IF NOT EXISTS friends 
      (usr varchar(32),
       friend varchar(32));
EOF;

  postgres_query($sql);
  
  $sql =<<<EOF
    CREATE TABLE IF NOT EXISTS profiles
      (usr varchar(32),
       text varchar(4096));    
EOF;
  
  postgres_query($sql);
  
    $sql =<<<EOF
    CREATE TABLE IF NOT EXISTS reset_requests
      (code char(6) PRIMARY KEY,
       usr varchar(32),
       expiration timestamp);    
EOF;
  
    postgres_query($sql);
    
  function postgres_query($sql) {
    $ret = pg_query($sql);
    if(!$ret){
        echo pg_last_error($db);
    } else {
      echo "$sql<br>Success.<br>";
    }
  }
  
?>

    <br>...done.
  </body>
</html>
