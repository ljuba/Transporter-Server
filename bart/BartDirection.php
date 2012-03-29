<?php
/**
 * Description of BartDirection
 *
 * @author thejo
 */
class BartDirection {
    private $xml;
    /**
     * @var Agency
     */
    private $agency;

    function __construct($agency, $xml) {
        $this->xml = $xml;
        $this->agency = $agency;
    }

    public function updateDirections() {

        //Fetch all the routes that are already in the database
        try {
            $routeArray = Route::getRoutes($this->agency);
        } catch (Exception $ex) {
            throw new Exception ("BART directions could not be updated");
        }

        foreach ($routeArray as $routeTag => $routeObj) {
            //Build an array of direction objects
            $directionInfo = array();

            //Add the directions for every route
            foreach ($this->xml->routes->route as $d) {
                $tempRouteTag = BartColorCodes::getBartColor(substr((string) $d->color, 1));

                if ($tempRouteTag == $routeTag) {
                    $dirObj = new Direction();

                    $dirTag = (string) $d->number;
                    $useForUiValue = true; //Always true for BART

                    $dirObj->setRoute($routeObj);
                    $dirObj->setTag($dirTag);
                    $dirObj->setTitle($this->formatTitle((string) $d->name));
                    $dirObj->setName((string) $d->abbr);
                    $dirObj->setUseForUi($useForUiValue);
                    $dirObj->setShow($useForUiValue);

                    $directionInfo[$dirTag] = $dirObj;
                }
            }

            //var_dump($directionInfo);exit;

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

    private function formatTitle($title) {
        //We want only the destination to appear in the name
        $parts = explode("-", $title);
        $title = isset($parts[1]) ? $parts[1] : $title;
        return trim($title);
    }
}
