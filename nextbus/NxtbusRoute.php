<?php
/**
 * Description of RouteBuild
 *
 * @author thejo
 */
class NxtbusRoute {

    private $xml;
    /**
     * @var Agency
     */
    private $agency;

    private $appConfig;

    function __construct(Agency $agency, SimpleXMLElement $xml) {
        $this->xml = $xml;
        $this->agency = $agency;

        $this->appConfig = Configuration::getAppConfig();
    }

    public function updateRoutes() {
        //Build an array of Route objects
        $routeArray = array();
        $position = 0;
        $vehicleTypeOverrides = $this->getVehicleTypeOverride();

        foreach($this->xml->route as $r) {
            $route = new Route();
            $routeTag = (string) $r['tag'];
            $vehicleType = isset ($vehicleTypeOverrides[$routeTag]) ?
                $vehicleTypeOverrides[$routeTag] : "bus";
            $position++;

            $route->setAgency($this->agency);
            $route->setTag($routeTag);
            $route->setTitle((string)$r['title']);
            $route->setShortTitle((string)$r['shortTitle']);
            $route->setPosition($position);
            $route->setVehicleType($vehicleType);
            $route->setColor((string)$r['color']);
            $route->setLatMin( isset($r['latMin']) ? (string)$r['latMin'] : "" );
            $route->setLatMax( isset($r['latMax']) ? (string)$r['latMax'] : "" );
            $route->setLonMin( isset($r['lonMin']) ? (string)$r['lonMin'] : "" );
            $route->setLonMax( isset($r['lonMax']) ? (string)$r['lonMax'] : "" );

            $routeArray[$routeTag] = $route;
        }

        try {
            //Pass it to the base RouteUpdate class
            $routeUpdate = new RouteUpdate($this->agency, $routeArray);
            $routeUpdate->updateDB();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function getVehicleTypeOverride() {
        $agencyShortTitle = $this->agency->getShortTitle();

        $config = $this->appConfig;
        $filePath = $config['sfmuni_vehicle_override'];

        //TODO: Check for exception
        $xmlObjBuilder = new XmlObjBuilder($filePath);
        $xml = $xmlObjBuilder->getXmlObj();

        $vehicleOverrides = array();

        //We only support SF-Muni for now
        if($agencyShortTitle == "sf-muni") {

            foreach ($xml->route as $r) {
                $routeTag = (string) $r['tag'];
                $vehicleType = (string) $r['vehicle'];

                $vehicleOverrides[$routeTag] = $vehicleType;
            }
        }

        return $vehicleOverrides;
    }
}
