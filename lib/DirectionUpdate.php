<?php
/**
 * Description of DirectionUpdate
 *
 * @author thejo
 */
class DirectionUpdate extends TableUpdate {

    /**
     * Has to be from the database, i.e, the id field should be populated
     * @var Route
     */
    private $route;

    const TABLE = "direction";

    /**
     * Constructor
     * 
     * @param Route $route
     * @param Array $directionInfo - Array of direction objects (key is the direction tag)
     */
    function __construct($route, $directionInfo) {
        parent::__construct(self::TABLE);
        
        $this->route = $route;
        $this->updateInfo = $directionInfo;
    }

    protected function getAllExisting() {

        try {
            $liveRouteArray = Route::getLiveRoutes($this->route->getAgency());

            if( array_key_exists($this->route->getTag(), $liveRouteArray) ) {
                $liveRoute = $liveRouteArray[$this->route->getTag()];

                return Direction::getDirections($liveRoute, TableUpdate::getLiveVersion());
            }
        } catch (Exception $ex) {
            return false;
        }

        return false;
    }

    protected function add(array $directions) {
        $insertCnt = 0;
        $version = TableUpdate::getVersion();

        foreach ($directions as $d) {
            $boundParams = array($this->route->getId(), $d->getTitle(), 
                $this->getPrettyTitle($d->getTitle()), $d->getName(),
                $this->getPrettyName($d->getName()), $d->getTag(),
                        ($d->getUseForUi() ? 1 : 0), ($d->getShow() ? 1 : 0), $version);
            //var_dump($boundParams);
            $this->dbObj->bindParams( $boundParams );
            $query = "INSERT INTO direction (route_id, title, pretty_title,
                name, pretty_name, tag, use_for_ui, show, version, created_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $this->dbObj->query($query);
            //$this->dbObj->debug();
            //print $query;

            if($this->dbObj->rows_affected != 1) {
                throw new DBException("Addition of direction failed [agency:".
                        $this->route->getAgency()->getId() ."] [route tag:".
                        $this->route->getTag() ."] [route title:". $this->route->getTitle() ."]");
            }

            $insertCnt++;
        }

        //TODO: Add a log for the total number of directions added

        //Write the changes to the change log
        $this->saveChangesToFile();
    }

    private function getPrettyTitle($title) {
        //TODO: the "to" is optional. Use regexes for better search and replace
        $search = array("outbound to", "inbound to", "counterclockwise to", "clockwise to");
        $replace = array("", "", "", "");

        $prettyTitle = str_ireplace($search, $replace, $title);
        $prettyTitle = trim($prettyTitle);

        //TODO: Log any changes

        return $prettyTitle;
    }

    private function getPrettyName($name) {
        $search = array("clockwis", "kounterc");
        $replace = array("Clockwise", "Counterclock");

        $prettyName = str_ireplace($search, $replace, $name);
        $prettyName = trim($prettyName);

        //TODO: Log any changes

        return $prettyName;
    }

    /**
     * Determine changes between two directions of the same route with the same tag
     *
     * @param Direction $o
     * @param Direction $n
     * @return Array - of changes
     */
    protected function dataUpdated(Direction $o, Direction $n) {
        $changes = array();

        if($o->getName() != $n->getName()) {
            $changes["name"] = $o->getName(). " | " .$n->getName();
        }
        if($o->getTitle() != $n->getTitle()) {
            $changes["title"] = $o->getTitle(). " | " .$n->getTitle();
        }
        if($o->getUseForUi() != $n->getUseForUi()) {
            $changes["useForUI"] = $o->getUseForUi(). " | " .$n->getUseForUi();
        }

        return $changes;
    }
}
