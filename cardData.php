<?php

require_once 'header.php';

if (!$loggedin) 
{
  header("Location: login.php");
}

echo <<<_END
<link rel="stylesheet" type="text/css" media="screen" href="css/ui-custom/jquery-ui.theme.css" />
<link rel="stylesheet" type="text/css" media="screen" href="css/ui.jqgrid.css" />

<script src="js/jquery-1.11.0.min.js" type="text/javascript"></script>
<script src="js/i18n/grid.locale-en.js" type="text/javascript"></script>
<script src="js/jquery.jqGrid.min.js" type="text/javascript"></script>

<script src="js/csv.js" type="text/javascript"></script>
<script src="js/cardGrid.js" type="text/javascript"></script>
    
<div class="topSpace">
    <table id="list"><tr><td></td></tr></table> 
    <div id="pager"></div> 
    <div id="mysearch"></div>
</div>
</body>
</html>
_END;


