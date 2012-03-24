<?php
require ROOT . 'config/config.php';
require ROOT . 'lib/Require.php';

date_default_timezone_set( $config['timezone'] );

//Configure DB access
$dbConfig = array (
'db_type' => DB::POSTGRESQL,
'db_name' => $config['db_name'],
'db_user' => $config['db_user'],
'db_password' => $config['db_pass'],
'db_host' => $config['db_host'],
);
DBPool::setInstance(new DB($dbConfig));

//Set the config
Configuration::setAppConfig($config);

//Configure the Logger
Logger::configure();
