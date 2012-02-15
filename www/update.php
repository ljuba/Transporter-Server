<?php
define("ROOT", "../");
set_time_limit(0);

require ROOT . 'www/common.php';
require ROOT . 'nextbus/Require.php';
require ROOT . 'bart/Require.php';

require ROOT . 'www/generate_xml.php';

$mailMessage = "";
$zipFileContents = array();
$logger = Logger::getInstance();
$agencyArray = Agency::getAgencies();

try {

    foreach ($agencyArray as $agencyTitle => $agencyObj) {
        //print "<br /><b>$agencyTitle</b><br />";
        file_put_contents(Util::getChangeLogFile(), "\n".strtoupper($agencyTitle)."\n\n",
                    FILE_APPEND);

        if("actransit" == $agencyTitle || "sf-muni" == $agencyTitle ||
                    "emery" == $agencyTitle) {
            /*
            if("sf-muni" == $agencyTitle) {
                $agencyObj->setDataUrl("./files/sf-muni.xml");
            } else if("actransit" == $agencyTitle) {
                $agencyObj->setDataUrl("./files/actransit.xml");
            }
            */
            
            //Get the XML object
            $xmlObjBuilder = new NxtbusXmlObjBuilder($agencyObj);
            $xmlObj = $xmlObjBuilder->getXmlObj(true);

            //Update the routes
            $nxtbusRoute = new NxtbusRoute($agencyObj, $xmlObj);
            $nxtbusRoute->updateRoutes();
            //print "Routes updated | ";

            //Update the directions
            $nxtbusDirection = new NxtbusDirection($agencyObj, $xmlObj);
            $nxtbusDirection->updateDirections();
            //print "Directions updated | ";

            //Update the stops
            $nxtbusStop = new NxtbusStop($agencyObj, $xmlObj);
            $nxtbusStop->updateStops();
            //print "Stops updated | ";

            //Update the stop-direction map
            $nxtbusStopDirMap = new NxtbusStopDirMap($agencyObj, $xmlObj);
            $nxtbusStopDirMap->updateStopDirMap();
            //print "Stop-Dir map updated";


        } else if("bart" == $agencyTitle) {

            $bartXmlObjBuilder = new BartXmlObjBuilder();

            //Update the routes
            $bartRoute = new BartRoute($agencyObj, $bartXmlObjBuilder->getRoutes());
            $bartRoute->updateRoutes();
            //print "Routes updated | ";

            //Update the directions
            $bartDirection = new BartDirection($agencyObj, $bartXmlObjBuilder->getDirections());
            $bartDirection->updateDirections();
            //print "Directions updated | ";

            //Update the stops
            $bartStop = new BartStop($agencyObj, $bartXmlObjBuilder->getStops());
            $bartStop->updateStops();
            //print "Stops updated | ";

            //Update the stop-direction map
            $bartStopDirMap = new BartStopDirMap($agencyObj);
            $bartStopDirMap->updateStopDirMap();
            //print "Stop-Dir map updated";
        }

        /******************** GENERATE THE XML FILE ******************/
        generateXMLFile($agencyObj);

        //Add filename to zip file contents array
        $zipFileContents[] = Util::getBaseDirectoryPath(Util::XML_FILE) .
            $agencyObj->getShortTitle().".xml";
    }

    //Create the zip file
    $zipFileContents[] = Util::getBaseDirectoryPath(Util::XML_FILE) . "bart-platforms.xml";
    $destination = Util::getBaseDirectoryPath(Util::XML_FILE) . "data.zip";
    Util::createZip($zipFileContents, $destination, true);

    //Delete old versions
    Version::deleteOldVersions();

    $mailMessage = "Version: " . TableUpdate::getVersion();
    $mailMessage .= "\n\nThe job was executed successfully without any errors.";
    if(Version::changesPresentInCurrentVersion()) {
        $mailMessage .= "\nChanges were detected when compared with the live version.
            Please login to the admin console for the changelog.";
    } else {
        $mailMessage .= "\nNo changes were detected when compared with the live version.";
    }

    print "1";
} catch (Exception $ex) {
    $logger->log($ex->getTraceAsString(), Logger::WARN, "UPDATE");
    $mailMessage = "Version: " . TableUpdate::getVersion();
    $mailMessage = "\n\nThe job could not be executed successfully. Please check the log for errors.";

    print "-1";
}

$subject = "Kronos Server update for " . date("Y-m-d");
Util::mail($config['email ids'], $subject, $mailMessage);
?>
