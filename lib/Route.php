<?php
/**
 * Description of Route
 *
 * @author thejo
 */
class Route {
    /**
     * @var Agency
     */
    private $agency;

    /**
     * May be null (when created from non database input)
     * @var integer
     */
    private $id;
    
    private $tag;
    private $color;
    private $title;
    private $shortTitle;

    private $latMin;
    private $latMax;
    private $lonMin;
    private $lonMax;

    private $vehicleType;
    private $position;

    private static $liveRoutes = array();

    //TODO: Add memoization
    public static function getRoutes(Agency $agency, $version = 0) {
        /**
         * @var DB
         */
        $dbObj = DBPool::getInstance();

        $version = ($version == 0) ? TableUpdate::getVersion() : $version;
        $dbObj->bindParams( array($agency->getId(), $version));
        $routes = $dbObj->get_results("SELECT * FROM route WHERE agency_id=?
            AND version = ? ORDER BY position");

        if($dbObj->num_rows > 0) {
            $routeArray = array();
            foreach($routes as $r) {
                $routeObj = new Route();

                $routeObj->setId($r->id);
                $routeObj->setAgency($agency);
                $routeObj->setTag($r->tag);
                $routeObj->setColor($r->color);
                $routeObj->setTitle($r->title);
                $routeObj->setShortTitle($r->short_title);

                $routeObj->setLatMin($r->lat_min);
                $routeObj->setLatMax($r->lat_max);
                $routeObj->setLonMin($r->lon_min);
                $routeObj->setLonMax($r->lon_max);
                $routeObj->setVehicleType($r->vehicle_type);
                $routeObj->setPosition($r->position);

                $routeArray[$r->tag] = $routeObj;

            }

            return $routeArray;
            
        } else {
            //TODO: Don't use a generic exception
            throw new Exception("No data available - Route::getRoutes");
        }
        
    }

    /**
     * Given an Agency object, return an array of Route objects for the live
     * version
     * @param Agency $agency
     * @return Array Key is route tag and value is Route object
     */
    public static function getLiveRoutes(Agency $agency) {
        //TODO: This gets called for every direction. Needs to be cached
        return self::getRoutes($agency, TableUpdate::getLiveVersion());
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getAgency() {
        return $this->agency;
    }

    public function setAgency(Agency $agency) {
        $this->agency = $agency;
    }

    public function getTag() {
        return $this->tag;
    }

    public function setTag($tag) {
        $this->tag = $tag;
    }

    public function getColor() {
        return $this->color;
    }

    public function setColor($color) {
        $this->color = $color;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getShortTitle() {
        return $this->shortTitle;
    }

    public function setShortTitle($shortTitle) {
        $this->shortTitle = $shortTitle;
    }

    public function getLatMin() {
        return $this->latMin;
    }

    public function setLatMin($latMin) {
        $this->latMin = $latMin;
    }

    public function getLatMax() {
        return $this->latMax;
    }

    public function setLatMax($latMax) {
        $this->latMax = $latMax;
    }

    public function getLonMin() {
        return $this->lonMin;
    }

    public function setLonMin($lonMin) {
        $this->lonMin = $lonMin;
    }

    public function getLonMax() {
        return $this->lonMax;
    }

    public function setLonMax($lonMax) {
        $this->lonMax = $lonMax;
    }

    public function getVehicleType() {
        return $this->vehicleType;
    }

    public function setVehicleType($vehicleType) {
        $this->vehicleType = $vehicleType;
    }

    public function getPosition() {
        return $this->position;
    }

    public function setPosition($position) {
        $this->position = $position;
    }
}
