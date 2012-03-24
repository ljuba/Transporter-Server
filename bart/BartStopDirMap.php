<?php
/**
 * Description of BartStopDirMap
 *
 * @author thejo
 */
class BartStopDirMap {
    /**
     * @var Agency
     */
    private $agency;

    private $appConfig;

    function __construct(Agency $agency) {
        $this->agency = $agency;

        $this->appConfig = Configuration::getAppConfig();
    }

    public function updateStopDirMap() {
        /**
         * @var DB
         */
        $dbObj = DBPool::getInstance();

        $routeArray = Route::getRoutes($this->agency);
        $stopArray = Stop::getStops($this->agency);
        $bartApiKey = $this->appConfig['BART_API_KEY'];

        foreach ($routeArray as $routeTag => $routeObj) {

            $pos = 0;
            $directionArray = Direction::getDirections($routeObj);

            foreach ($directionArray as $dirTag => $dirObj) {

                //We're only interested in the directions we're showing
                if (!$dirObj->getShow()) { continue; }

                //Fetch the direction details
                $apiURL = str_replace(BartApiEndPoints::DIRECTION, $dirObj->getTag(),
                        BartApiEndPoints::ROUTE_INFO . $bartApiKey);
                $dirInfoXmlBuilder = new XmlObjBuilder($apiURL);
                $dirInfoXml = $dirInfoXmlBuilder->getXmlObj();

                foreach ($dirInfoXml->routes->route->config as $c) {
                    foreach ($c as $station) {
                        $pos++;

                        $stopTag = (string) $station;

                        $tempStopObj = $stopArray[$stopTag];
                        $stopId = $tempStopObj->getId();

                        $tempDirObj = $directionArray[$dirTag];
                        $dirId = $tempDirObj->getId();

                        $dbObj->bindParams(array($stopId, $dirId, $pos, 
                            TableUpdate::getVersion()));
                        $dbObj->query("INSERT INTO stop_direction_map
                            (stop_id, direction_id, position, version, created_date)
                            VALUES (?, ?, ?, ?, NOW())");

                        if ($dbObj->rows_affected != 1) {
                            //TODO: Log it
                        }
                    } //Stations
                }
            } //Directions
        } //Routes
    }
}
