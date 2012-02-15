<?php
/**
 * Description of RouteUpdate
 *
 * @author thejo
 */
class RouteUpdate extends TableUpdate {
    /**
     * @var Agency
     */
    private $agency;

    const TABLE = "route";

    /**
     * Constructor
     * 
     * @param string $agency - The route tag
     * @param array $routeInfo - An array of Route objects
     */
    public function  __construct(Agency $agency, array $routeInfo) {
        parent::__construct(self::TABLE);

        $this->agency = $agency;
        $this->updateInfo = $routeInfo;
    }

    protected function getAllExisting() {
        try {
            return Route::getRoutes($this->agency, TableUpdate::getLiveVersion());
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Add the routes to the database
     * @param array $routes - Array of Route objects
     */
    protected function add(array $routes) {
        $insertCnt = 0;
        $version = TableUpdate::getVersion();

        foreach ($routes as $r) {

            //Add the mandatory columns
            $columns = array('agency_id', 'tag', 'color', 'title', 'short_title', 
                'vehicle_type', 'version', 'position');
            $boundParams = array($this->agency->getId(), $r->getTag(), $r->getColor(),
                $r->getTitle(), $r->getShortTitle(), $r->getVehicleType(),
                $version, $r->getPosition());
            $columnCnt = count($columns);

            //Check and add the optional columns
            if($r->getLatMin()) {
                $columns[] = 'lat_min';
                $boundParams[] = $r->getLatMin();
                $columnCnt++;
            }
            if($r->getLatMax()) {
                $columns[] = 'lat_max';
                $boundParams[] = $r->getLatMax();
                $columnCnt++;
            }
            if($r->getLonMin()) {
                $columns[] = 'lon_min';
                $boundParams[] = $r->getLonMin();
                $columnCnt++;
            }
            if($r->getLonMax()) {
                $columns[] = 'lon_max';
                $boundParams[] = $r->getLonMax();
                $columnCnt++;
            }


            $this->dbObj->bindParams( $boundParams );
            //var_dump($boundParams);

            $query = "INSERT INTO route (". implode(",", $columns) . ", created_date)
                VALUES (". implode(",", array_fill(0, $columnCnt, "?")) .", NOW())";

            $this->dbObj->query($query);

            if($this->dbObj->rows_affected != 1) {
                //$this->dbObj->debug();exit;
                throw new DBException("Addition of route failed [agency:".
                        $this->agency->getId() ."] [route tag:".
                        $r->getTag() ."] [route title:". $r->getTitle() ."]");
            }

            $insertCnt++;
        }

        //TODO: Add a log for the total number of rows added

        //Write the changes to the change log
        $this->saveChangesToFile();
    }

    /**
     * Determine changes between two routes with the same tag
     * 
     * @param Route $o - Old value
     * @param Route $n - New value
     * @return Array - of changes
     */
    protected function dataUpdated(Route $o, Route $n) {
        $changes = array();

        if($o->getTitle() != $n->getTitle()) {
            $changes["title"] = $o->getTitle(). " | " .$n->getTitle();
        }
        if($o->getShortTitle() != $n->getShortTitle()) {
            $changes["short title"] = $o->getShortTitle(). " | " .$n->getShortTitle();
        }
        if($o->getColor() != $n->getColor()) {
            $changes["color"] = $o->getColor(). " | " .$n->getColor();
        }
        if($o->getLatMax() != $n->getLatMax()) {
            $changes["lat max"] = $o->getLatMax(). " | " .$n->getLatMax();
        }
        if($o->getLatMin() != $n->getLatMin()) {
            $changes["lat min"] = $o->getLatMin(). " | " .$n->getLatMin();
        }
        if($o->getLonMax() != $n->getLonMax()) {
            $changes["lon max"] = $o->getLonMax(). " | " .$n->getLonMax();
        }
        if($o->getLonMin() != $n->getLonMin()) {
            $changes["lon min"] = $o->getLonMin(). " | " .$n->getLonMin();
        }
        if($o->getVehicleType() != $n->getVehicleType()) {
            $changes["vehicle type"] = $o->getVehicleType(). " | " .$n->getVehicleType();
        }
        /*
        if($o->getPosition() != $n->getPosition()) {
            $changes["position"] = $o->getPosition(). " | " .$n->getPosition();
        }
        */
        
        return $changes;
    }
}
?>
