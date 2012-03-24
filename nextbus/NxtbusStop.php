<?php
/**
 * Description of NxtbusStop
 *
 * @author thejo
 */
class NxtbusStop {
    private $xml;
    /**
     * @var Agency
     */
    private $agency;

    /**
     * @var Logger $logger
     */
    private $logger = null;

    private $appConfig;

    const PACKAGE = 'NXTBUS';

    function __construct($agency, $xml) {
        $this->xml = $xml;
        $this->agency = $agency;

        $this->logger = Logger::getInstance();
        $this->appConfig = Configuration::getAppConfig();
    }

    public function updateStops() {
        $routeArray = Route::getRoutes($this->agency);

        $flipStopMap = $this->getFlipStopOverrides();
        $stopTitleMap = array();
        $stopLatLonMap = array();
        $uniqueStopsAcrossRoutes = array();

        //Generate maps of stopTag => StopTitle and stopTag => [lat, lon] for
        //later look up
        foreach($this->xml->route as $r) {

            foreach($r->stop as $s) {
                $stopTag = (string) $s['tag'];
                $stopTitle = html_entity_decode( (string) $s['title'] );

                $lat = (string) $s['lat'];
                $lon = (string) $s['lon'];

                $stopTitleMap[$stopTag] = $stopTitle;
                $stopLatLonMap[$stopTag] = array("lat" => $lat, "lon" => $lon);

                $uniqueStopsAcrossRoutes[] = $stopTag;
            } //Stops
        } //Routes

        //We'll only consider the stops in the directions with show=true
        foreach($this->xml->route as $r) {
            $routeTag = (string) $r['tag'];
            //if($routeTag != "25") {continue;}
            $routeObj = $routeArray[$routeTag];
            $dirArray = Direction::getDirections($routeObj);
            $agencyName = $routeObj->getAgency()->getShortTitle();

            $trackDuplicatesInDir = array();

            /**
             * @var Array $routeDirDetails - Array representation of the
             * directions and stops in a route
             */
            $routeDirDetails = array();

            foreach($r->direction as $d) {
                $dirTag = (string) $d['tag'];
                $dirObj = $dirArray[$dirTag];
                $dirStops = array();

                //if( $dirObj->getShow() ) {
                    foreach ($d->stop as $s) {
                        $stopTag = (string) $s['tag'];
                        $stopTitle = $stopTitleMap[$stopTag];

                        $dirStops[$stopTag] = $stopTitle;
                    } //Stops

                    $routeDirDetails[$dirObj->getTag()] = $dirStops;
                //} //Check for show=true
            } //Directions

            //Now that we have the details of the directions for this group,
            //let's find the flip stops
            foreach ($routeDirDetails as $fDirTag => $fDirStops) {
                foreach ($routeDirDetails as $fDirTagDiffDir => $fDirStopsDiffDir) {
                    if($fDirTag == $fDirTagDiffDir) {
                        continue;
                    }

                    foreach ($fDirStops as $fStopTag => $fStopTitle) {
                        //Check if we have one or more matching flip stops in the
                        //direction we are checking against
                        $fFlipStopInOppDir = $this->getFlipStopsInOppDir(
                                $fStopTag, $fStopTitle, $fDirStopsDiffDir);

                        //If we don't have any flip stops continue to the next stop
                        if(count($fFlipStopInOppDir) == 0) {
                            continue;
                        }

                        if(count($fFlipStopInOppDir) > 1) {
                            //We have encountered more than one stop at the
                            //same intersection in a different direction
                            //TODO: This has to go in the e-mail report

                            //Generate the (stopTag, lat, lon) string
                            $fstopLatLonMap = array();
                            $fstopLatLonMap[] = "$fStopTag," . implode(",", $stopLatLonMap[$fStopTag]);
                            foreach ($fFlipStopInOppDir as $tempStopForOppDir) {
                                $fstopLatLonMap[] = "$tempStopForOppDir," . implode(",", $stopLatLonMap[$tempStopForOppDir]);
                            }

                            $logStr = "More than one stop in diff. direction [agency: ".
                                $agencyName . "] [route: ". $routeTag ."] [current stop|dir: $fStopTag|$fDirTag] [other stops|dir: " .
                                implode(",", $fFlipStopInOppDir) . "|$fDirTagDiffDir] [stop title: " .
                                    $fStopTitle . "] [" . implode("|", $fstopLatLonMap) . "]";
                            //$this->logger->log($logStr, Logger::WARN, NxtbusStop::PACKAGE);

                        } else if(! array_key_exists($fStopTag, $flipStopMap) ) {
                            $tempFlipStopTag = $fFlipStopInOppDir[0];
                            $flipStopMap[$fStopTag] = $tempFlipStopTag;
                            $flipStopMap[$tempFlipStopTag] = $fStopTag;
                        }
                    }
                } // Inner loop
            } //Find flip stops
        } //Routes

        //Check if any of the stops don't have a flip stop
        $uniqueStopsAcrossRoutes = array_unique($uniqueStopsAcrossRoutes);
        foreach ($uniqueStopsAcrossRoutes as $finalCheckStopTag) {
            if(! array_key_exists($finalCheckStopTag, $flipStopMap)) {
                $flipStopMap[$finalCheckStopTag] = "";
            }
        }

        //Create the Stop objects
        $stopArray = array();
        foreach($this->xml->route as $r) {

            foreach ($r->stop as $s) {
                $stopTag = (string) $s['tag'];
                $stopTitle = html_entity_decode($s['title']);

                if ( array_key_exists($stopTag, $flipStopMap) &&
                        ! array_key_exists($stopTag, $stopArray) ) {
                    $stopObj = new Stop();

                    $stopObj->setAgency($this->agency);
                    $stopObj->setTag($stopTag);
                    $stopObj->setTitle( (string) $stopTitle);
                    $stopObj->setFlipStopTag($flipStopMap[$stopTag]);
                    $stopObj->setLatitude( (string)  $s['lat']);
                    $stopObj->setLongitude( (string) $s['lon']);

                    $stopArray[$stopTag] = $stopObj;
                }
            } //Stops
        } //Routes

        try {
            //Pass it to the base RouteUpdate class
            $stopUpdate = new StopUpdate($this->agency, $stopArray);
            $stopUpdate->updateDB();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * Given a stopTag, it's stopTitle of direction-1 and an array of stops in
     * a different direction (say direction-2 - note that direction-1 and direction-2 are
     * in the same route), extract and return the stopTags in direction-2 that
     * have the same title as stopTitle. None of the stopTags that are returned
     * are the same as the stopTag of direction-1 that is passed in.
     *
     * @param String $stopTag
     * @param String $stopTitle
     * @param Array $stopsInOppDir
     * @return Array
     */
    private function getFlipStopsInOppDir($stopTag, $stopTitle, $stopsInOppDir) {
        $finalArray = array();
        //Get the list of stops in an opposite direction at the same intersection
        //as $stopTag/$stopTitle
        $stopsInOppDir = array_keys($stopsInOppDir, $stopTitle);

        if(count($stopsInOppDir) > 0) {
            //We have stops in the opp. direction.
            //Return only the ones with a different stop tag (with reference to $stopTag
            foreach ($stopsInOppDir as $oppStopTag) {
                if($stopTag != $oppStopTag) {
                    $finalArray[] = $oppStopTag;
                }
            }
        }

        return $finalArray;
    }



    /**
     * Read flip stop overrides from a file and return them as an array.
     * NOTE: This method returns the flip stops only for the agency being processed
     *
     * @return Array - stopTag => flipStopTag
     */
    private function getFlipStopOverrides() {
        $agencyShortTitle = $this->agency->getShortTitle();

        $config = $this->appConfig;
        $filePath = $config['flip_stop_override'];

        //TODO: Check for exception
        $xmlObjBuilder = new XmlObjBuilder($filePath);
        $xml = $xmlObjBuilder->getXmlObj();

        $flipStopMap = array();

        foreach($xml->agency as $tempAgency) {
            if((string) $tempAgency["shortTitle"] != $agencyShortTitle) { continue; }

            foreach ($tempAgency->stop as $tempStop) {
                $stopTag = (string) $tempStop["tag"];
                $flipStopTag = (string) $tempStop["reverseStopTag"];

                $flipStopMap[$stopTag] = $flipStopTag;

                if(! empty($flipStopTag) ) {
                    $flipStopMap[$flipStopTag] = $stopTag;
                }
            }
        }

        return $flipStopMap;
    }
}
