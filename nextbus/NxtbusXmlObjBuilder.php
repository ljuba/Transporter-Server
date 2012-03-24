<?php
class NxtbusXmlObjBuilder {
    private $agency;

    /**
     * @var DB
     */
    protected $dbObj = null;

    public static $MAX_ROUTES = 100;

    function __construct(Agency $agency) {
        $this->agency = $agency;
        $this->dbObj = DBPool::getInstance();
    }

    public function getXmlObj($getNextBus) {
        $routeXmlObj = $this->getRoutes();
        
        if ( count($routeXmlObj->route) >= self::$MAX_ROUTES ) {
            //There are more than 100 routes, we need to fetch each one separately
            $xmlStr = '<?xml version="1.0" encoding="utf-8" ?><body></body>';
            $xmlObj = new SimpleXMLElement($xmlStr);

            $cnt = 0;
            foreach($routeXmlObj->route as $r) {
                $routeTag = (string) $r['tag'];

                $routeConfigUrl = $this->getRouteConfigApiUrl() .
                            $this->agency->getShortTitle() . "&r=" . $routeTag;

                $xmlObjBuilder = new XmlObjBuilder($routeConfigUrl);
                $routeConfigXmlObj = $xmlObjBuilder->getXmlObj(true);

                foreach($routeConfigXmlObj->route as $rc) {
                    Util::AddXMLElement($xmlObj, $rc);
                }
            }

            return $xmlObj;

        } else {
            $routeConfigUrl = $this->getRouteConfigApiUrl() . $this->agency->getShortTitle();
            $xmlObjBuilder = new XmlObjBuilder($routeConfigUrl);
            return $xmlObjBuilder->getXmlObj(true);
        }
    }

    private function getRoutes() {
        $apiURL = $this->getRouteListApiUrl() . $this->agency->getShortTitle();
        $routeInfoXmlBuilder = new XmlObjBuilder($apiURL);
        return $routeInfoXmlBuilder->getXmlObj(true);
    }

    private function getRouteListApiUrl() {
        if ($this->agency->getShortTitle() == "emery") {
            return NxtbusApiEndPoints::EMERY_ROUTE_LIST;
        } else {
            return NxtbusApiEndPoints::ROUTE_LIST;
        }
    }

    private function getRouteConfigApiUrl() {
        if ($this->agency->getShortTitle() == "emery") {
            return NxtbusApiEndPoints::EMERY_ROUTE_CONFIG;
        } else {
            return NxtbusApiEndPoints::ROUTE_CONFIG;
        }
    }

}
