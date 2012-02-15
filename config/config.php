<?php
/**
 * Application configuration
 */
static $config = array(
//Database connection settings
'db_name' => 'kronos',
'db_user' => 'postgres',
'db_pass' => 'postgres',
'db_host' => '127.0.0.1',

'user' => 'username',
'password' => 'password',

//Deployment environment
//Allowed values - dev, prod
'environment' => "dev",

'timezone' => 'America/New_York',

//Max. number of versions to keep in the database at any given time
'max version count' => 15,
//Min. number of versions to keep in the database at any given time
'min version count' => 10,

//Notification e-mails (comma separated)
'email ids' => "email@address.com",

//Application base path
'base_path' => '/var/www/kronos/',

//Location of direction over-rides
'location_override' => '/var/www/kronos/files/directions.xml',
//Location of flip stop over-rides
'flip_stop_override' => '/var/www/kronos/files/reverseStops.xml',
//Location of SF Muni vehicle type over-rides
'sfmuni_vehicle_override' => '/var/www/kronos/files/sf-muni-vehicles.xml',

//Location of cache files. Trailing slash is required
'cache_file_dir' => '/var/www/kronos/cache/',

//Log file
'log_file' => '/var/www/kronos/logs/kronos.log',

//public bart API key
'BART_API_KEY' => 'MW9S-E7SL-26DU-VV8V'

//BART and Nextbus API end points
//Refer to ROOT/bart/BartApiEndPoints.php and ROOT/nextbus/NxtbusApiEndPoints.php
);

?>
