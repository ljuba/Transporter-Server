<?php
/**
 * Description of StopUpdate
 *
 * @author thejo
 */
class StopUpdate extends TableUpdate {
    /**
     * @var Agency
     */
    private $agency;

    const TABLE = "stop";

    /**
     * Constructor
     *
     * @param string $agency - An Agency object
     * @param array $stopInfo - An array of Stop objects
     */
    public function  __construct(Agency $agency, array $stopInfo) {
        parent::__construct(self::TABLE);

        $this->agency = $agency;
        $this->updateInfo = $stopInfo;
    }

    protected function getAllExisting() {
        try {
            return Stop::getStops($this->agency, TableUpdate::getLiveVersion());
        } catch (Exception $ex) {
            return false;
        }
    }

    protected function add(array $stops) {
        //var_dump($stops);exit;
        $insertCnt = 0;
        $version = TableUpdate::getVersion();

        foreach ($stops as $s) {
            $boundParams = array($this->agency->getId(), $s->getTag(),
                $s->getLatitude(), $s->getLongitude(), $s->getTitle(),
                $this->getPrettyTitle($s->getTitle()), $s->getFlipStopTag(), $version);
            $this->dbObj->bindParams( $boundParams );

            $query = "INSERT INTO stop (agency_id, tag, latitude, longitude, 
                title, pretty_title, flip_stop_tag, version, created_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $this->dbObj->query($query);

            if($this->dbObj->rows_affected != 1) {
                //$this->dbObj->debug();exit;
                throw new DBException("Addition of stop failed [agency:".
                        $this->agency->getId() ."] [stop tag:". $s->getTag(). "]");
            }

            $insertCnt++;
        }

        //TODO: Add a log for the total number of stops added

        //Write the changes to the change log
        $this->saveChangesToFile();
        
    }

    private function getPrettyTitle($title) {
        $search = array(" av. " , " ave. " , " ave " , " avenue ",
                        " rd. " , " road ",
                        " st. " , " street ",
                        " dr. " , " drive ",
                        " martin luther king jr wy ", " martin luther king jr. way " , " mlk jr way ",
                        " wy " , " wy. ",
                        " blvd. ",
                        " inbound ", " outbound ", " arr ", " ob ",
                        " bart station ");

        $replace = array(" Av ", " Av ", " Av ", " Av ",
                         " Rd ", " Rd ",
                         " St ", " St ",
                         " Dr ", " Dr ",
                         " MLK ", " MLK ", " MLK ",
                         " Way ", " Way ",
                         " Blvd ",
                         " ", " ", " ", " ",
                         " BART ");

        //TODO: Adding a space at the start and end is an ugly hack! Use regexes..
        $title = " ".$title." ";

        $prettyTitle = str_ireplace($search, $replace, $title);
        $prettyTitle = trim($prettyTitle);

        //TODO: Log changes

        return $prettyTitle;
    }

    protected function dataUpdated(Stop $o, Stop $n) {
        $changes = array();

        if($o->getFlipStopTag() != $n->getFlipStopTag()) {
            $changes["flip stop tag"] = $o->getFlipStopTag(). " | " .$n->getFlipStopTag();
        }
        if($o->getTitle() != $n->getTitle()) {
            $changes["title"] = $o->getTitle(). " | " .$n->getTitle();
        }
        if($o->getLatitude() != $n->getLatitude()) {
            $changes["latitude"] = $o->getLatitude(). " | " .$n->getLatitude();
        }
        if($o->getLongitude() != $n->getLongitude()) {
            $changes["longitude"] = $o->getLongitude(). " | " .$n->getLongitude();
        }

        return $changes;
    }

}
