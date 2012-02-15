<?php
require ROOT . 'www/common.php';
Util::authCheck();

$time = time();
print '<html>
        <head>
            <title>Transporter Server</title>
            <!-- Google-hosted jQuery -->
            <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>

            <script type="text/javascript" src="./static/js/script.js?v='.$time.'"></script>
        </head>
        <body>';
?>
