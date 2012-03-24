<?php
/**
 * Description of BartXmlObjBuilder
 *
 * @author thejo
 */
class BartXmlObjBuilder {
    private $appConfig;

    function __construct() {
        $this->appConfig = Configuration::getAppConfig();
    }

    public function getRoutes() {
        $apiURL = BartApiEndPoints::ROUTES . $this->getBartApiKey();
        $routeInfoXmlBuilder = new XmlObjBuilder($apiURL);
        return $routeInfoXmlBuilder->getXmlObj(true);
    }

    public function getDirections() {
        return $this->getRoutes();
    }

    public function getStops() {
        $apiURL = BartApiEndPoints::STATIONS . $this->getBartApiKey();
        $stationInfoXmlBuilder = new XmlObjBuilder($apiURL);
        return $stationInfoXmlBuilder->getXmlObj(true);
    }

    private function getBartApiKey() {
        return $this->appConfig['BART_API_KEY'];
    }

}
