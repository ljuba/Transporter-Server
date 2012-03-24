<?php
/**
 * Description of NxtbusStopDirMap
 *
 * @author thejo
 */
class NxtbusStopDirMap {
    private $xml;
    /**
     * @var Agency
     */
    private $agency;

    function __construct(Agency $agency, SimpleXMLElement $xml) {
        $this->xml = $xml;
        $this->agency = $agency;
    }

    //TODO: Bit of a hack job. Need to modularize.
    public function updateStopDirMap() {
        /**
         * @var DB
         */
        $dbObj = DBPool::getInstance();

        $routeArray = Route::getRoutes($this->agency);
        $stopArray = Stop::getStops($this->agency);

        foreach ($this->xml->route as $r) {
            $routeTag = (string) $r['tag'];
            $routeObj = $routeArray[$routeTag];

            foreach ($r->direction as $d) {

                $pos = 0;
                $dirTag = (string) $d['tag'];
                $directionArray = Direction::getDirections($routeObj);
                $dirObj = $directionArray[$dirTag];

                //We're only interested in the directions we're showing
                //if (!$dirObj->getShow()) { continue; }

                foreach ($d->stop as $s) {
                    $pos++;

                    $stopTag = (string) $s['tag'];

                    $tempStop = $stopArray[$stopTag];
                    if (empty($tempStop)) { var_dump("$routeTag $stopTag"); }
                    $stopId = $tempStop->getId();

                    $tempDir = $directionArray[$dirTag];
                    $dirId = $tempDir->getId();

                    $dbObj->bindParams(array($stopId, $dirId, $pos,
                        TableUpdate::getVersion()));
                    $dbObj->query("INSERT INTO stop_direction_map
                        (stop_id, direction_id, position, version, created_date)
                        VALUES (?, ?, ?, ?, NOW())");

                    if ($dbObj->rows_affected != 1) {
                        //TODO: Log it
                    }
                }
            }
        }
    }
}
