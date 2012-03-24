<?php
/**
 * Description of NxtbusDirection
 *
 * @author thejo
 */
class NxtbusDirection {
    private $xml;
    /**
     * @var Agency
     */
    private $agency;

    private $appConfig = array();

    function __construct($agency, $xml) {
        $this->xml = $xml;
        $this->agency = $agency;

        $this->appConfig = Configuration::getAppConfig();
    }

    public function updateDirections() {

        //Fetch all the routes that are already in the database
        try {
            $routeArray = Route::getRoutes($this->agency);
        } catch (Exception $ex) {
            throw new Exception ("Nextbus directions could not be updated. Error building route array.");
        }

        //Add the directions for every route
        foreach($this->xml->route as $r) {
            $routeTag = (string) $r['tag'];
            $routeObj = $routeArray[$routeTag];

            //Build an array of direction objects
            $directionInfo = array();

            foreach($r->direction as $d) {
                $dirObj = new Direction();
                $dirTag = (string) $d['tag'];
                $useForUiValue = (string) $d['useForUI'] == "false" ? false : true;

                $dirObj->setRoute($routeObj);
                $dirObj->setTag($dirTag);
                $dirObj->setTitle( (string) $d['title']);
                $dirObj->setName( (string)  $d['name']);
                $dirObj->setUseForUi( $useForUiValue );
                $dirObj->setShow( $this->getShowValue($routeTag, $dirTag,
                        $useForUiValue) );

                $directionInfo[$dirTag] = $dirObj;
            }

            //Update the database
            try {
                //Pass it to the base DirectionUpdate class
                $dirUpdate = new DirectionUpdate($routeObj, $directionInfo);
                $dirUpdate->updateDB();
            } catch (Exception $ex) {
                throw new Exception($ex->getMessage());
            }
        }
    }

    /**
     * Check what the value of the show column should be
     *
     * @param String $routeTag
     * @param String $dirTag
     * @param Boolean $userForUiValue
     * @return Boolean
     */
    private function getShowValue($routeTag, $dirTag, $userForUiValue) {
        $overrideArray = $this->getDirectionOverrides();
        if ( isset($overrideArray[$routeTag]) ) {
            $dirArray = $overrideArray[$routeTag];

            if( in_array($dirTag, $dirArray) ) {
                return true;
            } else {
                return false;
            }

        } else {
            return $userForUiValue;
        }
    }

    private function getDirectionOverrides() {
        $config = $this->appConfig;
        $filePath = $config['location_override'];

        //TODO: Check for exception
        $xmlObjBuilder = new XmlObjBuilder($filePath);
        $xml = $xmlObjBuilder->getXmlObj();

        $routeArray = array();

        foreach ($xml->agency as $a) {
            $shortTitle = $a['shortTitle'];

            //We only want the overrides for the current agency
            if($this->agency->getShortTitle() == $shortTitle) {
                foreach ($a->route as $r) {
                    $routeTag = (string) $r['tag'];

                    foreach ($r->direction as $d) {
                        $routeArray[$routeTag][] = (string) $d['tag'];
                    }
                }
            }
        }

        return $routeArray;
    }
}
?>
