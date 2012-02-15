<?php
/**
 * Description of Agency
 *
 * @author thejo
 */
class Agency {
    private $id;
    private $title;
    private $shortTitle;
    private $defaultVehicleType;

    public static function getAgencies() {
        $dbObj = DBPool::getInstance();

        $agencies = $dbObj->get_results("SELECT * FROM agency");

        if($dbObj->num_rows > 0) {
            $agencyArray = array();

            foreach($agencies as $a) {
                $agencyObj = new Agency();

                $agencyObj->setId($a->id);
                $agencyObj->setTitle($a->title);
                $agencyObj->setShortTitle($a->short_title);
                $agencyObj->setDefaultVehicleType($a->default_vehicle);

                $agencyArray[$a->short_title] = $agencyObj;
            }

            return $agencyArray;
        } else {
            throw new Exception("There are no agencies in the database");
        }
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
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

    public function getDefaultVehicleType() {
        return $this->defaultVehicleType;
    }

    public function setDefaultVehicleType($defaultVehicleType) {
        $this->defaultVehicleType = $defaultVehicleType;
    }

}
?>
