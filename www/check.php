<?php
define("ROOT", "../");

require ROOT . 'www/common.php';

header("Content-Type: text/xml");

$dbObj = DBPool::getInstance();

$response = '<?xml version="1.0" encoding="UTF-8" ?>';

if(isset($_GET['v'])) {
    $version = $_GET['v'];
    if(! ctype_digit($version)) {
        exit;
    }

    $dbObj->bindParams( array( $version ) );
    $versionDetails = $dbObj->get_row("SELECT id, created_date FROM version WHERE id=?");
    
} else {
    $versionDetails = $dbObj->get_row("SELECT id, created_date FROM version WHERE active=true");
}

if(null == $versionDetails) {
    $response .= '<error>Check if the version number is valid or if there is a live version in the database</error>';
} else {
    $response = '<update version="'.$versionDetails->id.'" resource="http://'. 
                        $_SERVER["HTTP_HOST"] .'/check.php" updateTime="'. strtotime($versionDetails->created_date) .'">
                    <data resource="http://'. $_SERVER["HTTP_HOST"] .'/data/xml/'.$versionDetails->id.'/data.zip" />
                    <images resource="http://'. $_SERVER["HTTP_HOST"] .'/data/images/'.$versionDetails->id.'/images.zip" />
                 </update>';
}

print $response;
?>
