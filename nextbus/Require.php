<?php
if (!defined("KRONOS_NXTBUS")) { define("KRONOS_NXTBUS", dirname(__FILE__) . DIRECTORY_SEPARATOR); }

require KRONOS_NXTBUS.'NxtbusRoute.php';
require KRONOS_NXTBUS.'NxtbusDirection.php';
require KRONOS_NXTBUS.'NxtbusStop.php';
require KRONOS_NXTBUS.'NxtbusStopDirMap.php';

require KRONOS_NXTBUS.'NxtbusXmlObjBuilder.php';
require KRONOS_NXTBUS.'NxtbusApiEndPoints.php';
