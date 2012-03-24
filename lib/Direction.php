<?php
/**
 * Description of Direction
 *
 * @author thejo
 */
class Direction {
    private $id;
    private $route;
    private $title;
    private $prettyTitle;
    private $name;
    private $prettyName;
    private $tag;
    private $useForUi = false;
    private $show = false;

    //TODO: Add memoization
    public static function getDirections(Route $route, $version = 0) {
        /**
         * @var DB
         */
        $dbObj = DBPool::getInstance();

        $version = ($version == 0) ? TableUpdate::getVersion() : $version;
        $dbObj->bindParams(array($route->getId(), $version));
        $directions = $dbObj->get_results("SELECT * FROM direction WHERE route_id=?
            AND version = ?");

        if ($dbObj->num_rows > 0) {
            $directionArray = array();
            foreach ($directions as $d) {
                $dirObj = new Direction();

                $dirObj->setId($d->id);
                $dirObj->setName($d->name);
                $dirObj->setPrettyName($d->pretty_name);
                $dirObj->setRoute($route);
                $dirObj->setTag($d->tag);
                $dirObj->setTitle($d->title);
                $dirObj->setPrettyTitle($d->pretty_title);
                $dirObj->setUseForUi($d->use_for_ui);
                $dirObj->setShow($d->show);

                $directionArray[$d->tag] = $dirObj;

            }

            return $directionArray;

        } else {
            //TODO: Don't use a generic exception
            throw new Exception("No data available - Direction::getDirections");
        }

    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getRoute() {
        return $this->route;
    }

    public function setRoute(Route $route) {
        $this->route = $route;
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

    public function getName() {
        return $this->name;
    }

    public function getPrettyName() {
        return $this->prettyName;
    }

    public function setPrettyName($prettyName) {
        $this->prettyName = $prettyName;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getTag() {
        return $this->tag;
    }

    public function setTag($tag) {
        $this->tag = $tag;
    }

    public function getUseForUi() {
        return $this->useForUi;
    }

    public function setUseForUi($useForUi) {
        $this->useForUi = $useForUi;
    }

    public function getShow() {
        return $this->show;
    }

    public function setShow($show) {
        $this->show = $show;
    }


}
