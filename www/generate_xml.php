<?php

function generateXMLFile(Agency $agencyObj) {
    $dbObj = DBPool::getInstance();
    
    $routeArray = Route::getRoutes($agencyObj);
    $stopArray = Stop::getStops($agencyObj);

    $xmlStr = '<?xml version="1.0" encoding="UTF-8" ?><agency title="'.
            $agencyObj->getTitle().'" shortTitle="'.
            $agencyObj->getShortTitle().'"></agency>';

    $xml = new SimpleXMLElement($xmlStr);

    foreach ($stopArray as $s) {
        $stop = $xml->addChild("stop");
        $stop->addAttribute("tag", $s->getTag());
        $stop->addAttribute("title", $s->getPrettyTitle());
        $stop->addAttribute("lat", $s->getLatitude());
        $stop->addAttribute("lon", $s->getLongitude());
        $stop->addAttribute("oppositeStopTag", $s->getFlipStopTag());
    }

    foreach ($routeArray as $r) {
        $route = $xml->addChild("route");
        $route->addAttribute("tag", $r->getTag());
        $route->addAttribute("title", $r->getTitle());
        $route->addAttribute("shortTitle", $r->getShortTitle());
        $route->addAttribute("color", $r->getColor());
        $route->addAttribute("latMin", $r->getLatMin());
        $route->addAttribute("latMax", $r->getLatMax());
        $route->addAttribute("lonMin", $r->getLonMin());
        $route->addAttribute("lonMax", $r->getLonMax());
        $route->addAttribute("vehicle", $r->getVehicleType());
        $route->addAttribute("sortOrder", $r->getPosition());

        $directionsArray = Direction::getDirections($r);

        foreach ($directionsArray as $d) {
            $dir = $route->addChild("direction");
            $dir->addAttribute("tag", $d->getTag());
            $dir->addAttribute("title", $d->getPrettyTitle());
            $dir->addAttribute("name", $d->getPrettyName());
            $dir->addAttribute("show", $d->getShow() ? "true" : "false");
            $dir->addAttribute("useForUI", $d->getUseForUi() ? "true" : "false");

            $dbObj->bindParams(array( $d->getId() ));
            $stopDirMap = $dbObj->get_results("SELECT a.tag, b.position
                                                FROM stop as a, stop_direction_map as b
                                                WHERE a.id=b.stop_id AND b.direction_id=?
                                                ORDER BY b.position");
            if($dbObj->num_rows > 0) {
                foreach ($stopDirMap as $m) {
                    $map = $dir->addChild("stop");
                    $map->addAttribute("tag", $m->tag);
                }
            }
        }
    }

    $fileName = Util::getBaseDirectoryPath(Util::XML_FILE) . $agencyObj->getShortTitle().".xml";

    Util::prettyPrintXml($xml, $fileName);
}
