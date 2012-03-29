<?php
/**
 * Description of BartRoute
 *
 * @author thejo
 */
class BartRoute {
    private $xml;
    /**
     * @var Agency
     */
    private $agency;

    function __construct(Agency $agency, SimpleXMLElement $xml) {
        $this->xml = $xml;
        $this->agency = $agency;
    }

    /**
     * For BART, the color value is the main identifier for a route
     */
    public function updateRoutes() {
        //Build an array of Route objects
        $routeArray = array();
        $position = 0;

        foreach ($this->xml->routes->route as $r) {
            $position++;
            $route = new Route();
            $routeColor = substr((string) $r->color, 1); // We don't want the #

            $routeTag = BartColorCodes::getBartColor($routeColor);

            $route->setAgency($this->agency);
            $route->setTag($routeTag);
            $route->setTitle($routeTag);
            $route->setShortTitle($routeTag);
            $route->setColor($routeColor);
            $route->setVehicleType("train");
            $route->setPosition($position);

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
}
