<?php
/**
 * Description of BartStop
 *
 * @author thejo
 */
class BartStop {
    private $xml;
    /**
     * @var Agency
     */
    private $agency;

    private $appConfig;

    const GROUP_ID = 0;

    function __construct($agency, $xml) {
        $this->xml = $xml;
        $this->agency = $agency;

        $this->appConfig = Configuration::getAppConfig();
    }

    public function updateStops() {
        $routeArray = Route::getRoutes($this->agency);
        $stopArray = array(); //Final list of Stop objects
        $bartApiKey = $this->appConfig['BART_API_KEY'];

        //Platform mapping
        $xmlStr = '<?xml version="1.0"?><agency title="BART" shortTitle="bart"></agency>';

        $platformXml = new SimpleXMLElement($xmlStr);

        foreach ($this->xml->stations->station as $s) {
            $stationTag = (string) $s->abbr;

            //Fetch the station details
            $apiURL = str_replace(BartApiEndPoints::ORIG, $stationTag,
                    BartApiEndPoints::STATION_INFO . $bartApiKey);
            $stationInfoXmlBuilder = new XmlObjBuilder($apiURL);
            $stationInfoXml = $stationInfoXmlBuilder->getXmlObj();

            $stopObj = new Stop();

            $stopObj->setAgency($this->agency);
            $stopObj->setTag($stationTag);
            $stopObj->setTitle( (string) $stationInfoXml->stations->station->name);
            $stopObj->setLatitude( (string) $stationInfoXml->stations->station->gtfs_latitude);
            $stopObj->setLongitude( (string) $stationInfoXml->stations->station->gtfs_longitude);

            $stopArray[$stationTag] = $stopObj;

            /************* Add to platform XML object ****************/

            //Add stop node
            $stopNode = $platformXml->addChild("stop");
            $stopNode->addAttribute("tag", $stationTag);

            //Add platform node as a child of the stop node (north)
            $platformNode = $stopNode->addChild("platform");
            $northPlatforms = array();
            foreach($stationInfoXml->stations->station->north_platforms as $p) {
                foreach($p as $pNum) {
                    $northPlatforms[] = trim((string) $pNum);
                }
            }
            $platformNode->addAttribute("number", implode(",", $northPlatforms));

            //Add directions as a children of the platform node
            foreach ($stationInfoXml->stations->station->north_routes as $r) {
                foreach ($r->route as $direction) {
                    $dirStr = trim((string) $direction);

                    $dirTagArray = explode(" ", $dirStr);
                    $dirTag = $dirTagArray[1];

                    $platformNode->addChild("direction", $dirTag);
                }
            }

            //Add platform node as a child of the stop node (south)
            $platformNode = $stopNode->addChild("platform");
            $southPlatforms = array();
            foreach($stationInfoXml->stations->station->south_platforms as $p) {
                foreach($p as $pNum) {
                    $southPlatforms[] = trim((string) $pNum);
                }
            }
            $platformNode->addAttribute("number", implode(",", $southPlatforms));

            //Add directions as a children of the platform node
            foreach ($stationInfoXml->stations->station->south_routes as $r) {
                foreach ($r as $direction) {
                    $dirStr = trim((string) $direction);

                    $dirTagArray = explode(" ", $dirStr);
                    $dirTag = $dirTagArray[1];

                    $platformNode->addChild("direction", $dirTag);
                }
            }
        }

        //Write platform mapping XML to file
        $fileName = Util::getBaseDirectoryPath(Util::XML_FILE) . "bart-platforms.xml";
        Util::prettyPrintXml($platformXml, $fileName);

        //Write the stops to the database
        try {
            //Pass it to the base RouteUpdate class
            $stopUpdate = new StopUpdate($this->agency, $stopArray);
            $stopUpdate->updateDB();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function appendStationDetails($stationTag, $stationInfoXmlObj) {

    }
}
?>
