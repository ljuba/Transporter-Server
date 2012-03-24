<?php
define("ROOT", "../");

require ROOT . 'www/common.php';

//Return the contents of a requested file
if(isset($_GET['filetype'])) {
    try {
        print htmlentities(OverrideFile::getContents($_GET['filetype']));
    } catch (Exception $ex) {
        print "-1";
    }
}

//Update file contents
if(isset($_POST['filetype'])) {
    try {
        OverrideFile::updateContents($_POST['filetype'],
                urldecode(trim($_POST['filecontents'])));
        print "1";
    } catch (Exception $ex) {
        print "-1";
        //print $ex->getMessage();
    }
}
