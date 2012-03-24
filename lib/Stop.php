<?php
/**
 * Description of Stop
 *
 * @author thejo
 */
class Stop {
    private $id;
    /**
     * @var Agency
     */
    private $agency;

    private $tag;
    private $latitude;
    private $longitude;
    private $title;
    private $prettyTitle;
    private $flipStopTag;

    //TODO: Add memoization
    public static function getStops(Agency $agency, $version = 0) {
        /**
         * @var DB
         */
        $dbObj = DBPool::getInstance();

        $version = ($version == 0) ? TableUpdate::getVersion() : $version;
        $dbObj->bindParams( array($agency->getId(), TableUpdate::getVersion()));
        $stops = $dbObj->get_results("SELECT * FROM stop WHERE agency_id=?
            AND version = ?");

        if($dbObj->num_rows > 0) {
            $stopArray = array();
            foreach($stops as $s) {
               $stopObj = new Stop();

               $stopObj->setId($s->id);
               $stopObj->setAgency($agency);
               $stopObj->setFlipStopTag($s->flip_stop_tag);
               $stopObj->setTag($s->tag);
               $stopObj->setTitle($s->title);
               $stopObj->setPrettyTitle($s->pretty_title);
               $stopObj->setLatitude($s->latitude);
               $stopObj->setLongitude($s->longitude);

               $stopArray[$s->tag] = $stopObj;
            }

            return $stopArray;

        } else {
            //TODO: Don't use a generic exception
            throw new Exception("No data available - Stop::getStops");
        }

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

    public function getLatitude() {
        return $this->latitude;
    }

    public function setLatitude($latitude) {
        $this->latitude = $latitude;
    }

    public function getLongitude() {
        return $this->longitude;
    }

    public function setLongitude($longitude) {
        $this->longitude = $longitude;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getPrettyTitle() {
        return $this->prettyTitle;
    }

    public function setPrettyTitle($prettyTitle) {
        $this->prettyTitle = $prettyTitle;
    }

    public function getFlipStopTag() {
        return $this->flipStopTag;
    }

    public function setFlipStopTag($flipStopTag) {
        $this->flipStopTag = $flipStopTag;
    }
    
}
