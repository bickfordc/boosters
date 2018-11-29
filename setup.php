<!DOCTYPE html>
<html>
  <head>
    <title>Setting up database</title>
  </head>
  <body>

    <h3>Setting up...</h3>

<?php 
  require_once 'functions.php';

  $sql =<<<EOF
    CREATE TABLE IF NOT EXISTS members 
      (usr varchar(32),
       pass varchar(32));
EOF;
  
  postgres_query($sql);
  
  
    $sql =<<<EOF
    CREATE TABLE IF NOT EXISTS reset_requests
      (code char(8) PRIMARY KEY,
       usr varchar(32) NOT NULL,
       expiration timestamp NOT NULL DEFAULT NOW() + INTERVAL '20 minutes');    
EOF;
  
    postgres_query($sql);
    
    $sql =<<<EOF
    CREATE OR REPLACE FUNCTION delete_old_reset_requests() RETURNS trigger
      LANGUAGE plpgsql
      AS $$
      BEGIN
        DELETE FROM reset_requests WHERE expiration < NOW();
        RETURN NEW;
      END;
      $$;
EOF;
    
    postgres_query($sql);
    
    $sql =<<<EOF
    CREATE TRIGGER delete_old_reset_requests_trigger 
      AFTER INSERT ON reset_requests
      EXECUTE PROCEDURE delete_old_reset_requests();
EOF;
    
    postgres_query($sql);
    
    $sql =<<<EOF
    CREATE TABLE IF NOT EXISTS students
      (id SERIAL PRIMARY KEY,
       first varchar(32) NOT NULL,
       middle varchar(32),
       last varchar(32) NOT NULL,
       email varchar(80),
       balance numeric(10, 2) DEFAULT 0.00,
       graduation_year smallint,
       active boolean DEFAULT TRUE,
       notes varchar(80)
      );
EOF;
    
    postgres_query($sql);
    
    $sql =<<<EOF
    CREATE TABLE IF NOT EXISTS cards
      (id varchar(30) PRIMARY KEY,
       sold boolean DEFAULT FALSE,
       sell_date date,
       card_holder varchar(80),
       notes varchar(80),
       active boolean DEFAULT TRUE,
       donor_code char(2),
       invoice_number varchar(30),
       order_date date
      );       
EOF;
    
    postgres_query($sql);
            
    $sql =<<<EOF
    CREATE TABLE IF NOT EXISTS student_cards
      (student integer REFERENCES students (id),
       card varchar(30) REFERENCES cards (id),
       PRIMARY KEY (student, card)
      );           
EOF;
    
    postgres_query($sql);
   
$sql =<<<EOF
    CREATE TABLE IF NOT EXISTS scrip_families
      (first varchar(32),
       last varchar(32),
       active_family boolean DEFAULT TRUE,
       notes varchar(80),
       student integer REFERENCES students (id),
       PRIMARY KEY (first, last)
      );           
EOF;
    
    postgres_query($sql);
        
    $sql =<<<EOF
    CREATE TABLE IF NOT EXISTS scrip_orders
    (order_id varchar(10) PRIMARY KEY,
     order_count smallint,
     order_date date,
     rebate numeric(10,2),
     scrip_first varchar(32),
     scrip_last varchar(32),
     FOREIGN KEY (scrip_first, scrip_last) REFERENCES scrip_families(first, last)
    );
EOF;
    
    postgres_query($sql);
    
    $sql =<<<EOF
    CREATE TABLE IF NOT EXISTS ks_card_reloads
      (card varchar(30),
       reload_date date,
       reload_amount numeric(10, 2),
       original_invoice_number varchar(15),
       original_invoice_date date,
       PRIMARY KEY (card, reload_date, reload_amount)
      );      
EOF;
    
    postgres_query($sql);
           
    $sql =<<<EOF
            
    DO $$
    BEGIN
        IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'withdrawal_purpose') THEN
            CREATE TYPE withdrawal_purpose AS ENUM
            (
                'travel', 'uniforms', 'instruments', 'lessons', 'consumables', 'other'
            );
        END IF;
    END$$;
            
    CREATE TABLE IF NOT EXISTS student_withdrawals
      (id SERIAL PRIMARY KEY,
       student integer REFERENCES students (id),
       amount numeric (10,2),
       purpose withdrawal_purpose,
       notes varchar(80),
       date date
      );
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
