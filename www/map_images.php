<?php
define("ROOT", "../");
set_time_limit(0);

require ROOT . 'www/common.php';

function generateMapImages($version) {
    require ROOT . 'lib/GoogleMaps.php';

    $dbObj = DBPool::getInstance();
    $versionClause = " AND version=$version";
    $logger = Logger::getInstance();
    $zipFileContents = array();

    $basePathOrig = "./data/images/$version/orig_static_maps/";
    $basePathConverted = "./data/images/$version/";

    //If the images already exist, we're re-generating. Delete the existing images
    if ( file_exists ($basePathConverted) ) {
        Util::deleteAll($basePathConverted);
    }

    //Constants
    define("LEFT_EDGE", 28);
    define("RIGHT_EDGE", 28);
    define("TOP_EDGE", 130);
    define("BOTTOM_EDGE", 10);

    define("MAP_SIZE_X", 320);
    define("MAP_SIZE_Y", 600);
    define("FOOTER_HEIGHT", 30);
    define("FINAL_MAP_SIZE_Y", 367);

    //NOTE: The co-ordinate system starts at the top left corner for the purposes of
    //these calculations
    define("MARKER_MIN_X", LEFT_EDGE);
    define("MARKER_MAX_X", MAP_SIZE_X - RIGHT_EDGE);
    define("MARKER_MIN_Y", TOP_EDGE);
    define("MARKER_MAX_Y", FINAL_MAP_SIZE_Y - BOTTOM_EDGE);

    define("BASE_ZOOM", 13);
    define("PATH_LINE_WEIGHT", 2);

    $agencyArray = Agency::getAgencies();

    $xmlStr = '<?xml version="1.0" encoding="UTF-8" ?><body></body>';
    $xml = new SimpleXMLElement($xmlStr);

    foreach ($agencyArray as $agencyTitle => $agencyObj) {

        //if("bart" == $agencyTitle || "actransit" == $agencyTitle) { continue; }
        if("bart" == $agencyTitle) { continue; }
        //print $agencyTitle;

        $agencyNode = $xml->addChild("agency");
        $agencyNode->addAttribute("title", $agencyObj->getTitle());
        $agencyNode->addAttribute("shortTitle", $agencyObj->getShortTitle());

        //Get the routes
        $routeArray = Route::getRoutes($agencyObj);
        foreach($routeArray as $routeTag => $routeObj) {
            //if("1" != $routeObj->getTag()) { continue; }
            $zoom = BASE_ZOOM;

            $routeNode = $agencyNode->addChild("route");
            $routeNode->addAttribute("tag", $routeObj->getTag());

            //Calculate the co-ordinates for the center of the map
            $lat_avg = ($routeObj->getLatMin() + $routeObj->getLatMax()) / 2;
            $lon_avg = ($routeObj->getLonMin() + $routeObj->getLonMax()) / 2;

            //Calculate center as pixel coordinates in world map
            $center_y = Google_Maps::LatToY($lat_avg);
            $center_x = Google_Maps::LonToX($lon_avg);

            //Calculate center as pixel coordinates in image
            $center_offset_x = round(MAP_SIZE_X / 2);
            $center_offset_y = round(MAP_SIZE_Y / 2);

            $SW_target_y = Google_Maps::LatToY($routeObj->getLatMin());
            $SW_target_x = Google_Maps::LonToX($routeObj->getLonMin());

            $NE_target_y = Google_Maps::LatToY($routeObj->getLatMax());
            $NE_target_x = Google_Maps::LonToX($routeObj->getLonMax());

            //Fetch the last stop for all the directions in this route (with show=true)
            //NOTE: I know, very inefficient
            $dbObj->bindParams( array($routeObj->getId()));
            $dirs = $dbObj->get_results("SELECT id, tag FROM direction WHERE 
                    route_id=? AND show=true $versionClause");

            //if($dbObj->num_rows == 0) { print "<br />$agencyTitle - ". $routeObj->getTag() . "<br />"; }

            while(true) {
                $SW_delta_x  = ($SW_target_x - $center_x) >> (21 - $zoom);
                $SW_delta_y  = ($SW_target_y - $center_y) >> (21 - $zoom);
                $SW_marker_x = $center_offset_x + $SW_delta_x;
                $SW_marker_y = $center_offset_y + $SW_delta_y;

                $NE_delta_x  = ($NE_target_x - $center_x) >> (21 - $zoom);
                $NE_delta_y  = ($NE_target_y - $center_y) >> (21 - $zoom);
                $NE_marker_x = $center_offset_x + $NE_delta_x;
                $NE_marker_y = $center_offset_y + $NE_delta_y;

                $SW_X_LIMIT = LEFT_EDGE;
                $SW_Y_LIMIT = (MAP_SIZE_Y / 2) + round((FINAL_MAP_SIZE_Y - TOP_EDGE - BOTTOM_EDGE) / 2);
                $NE_X_LIMIT = MAP_SIZE_X - RIGHT_EDGE;
                $NE_Y_LIMIT = (MAP_SIZE_Y / 2) - round((FINAL_MAP_SIZE_Y - TOP_EDGE - BOTTOM_EDGE) / 2);

                if($SW_marker_x > $SW_X_LIMIT && $SW_marker_y < $SW_Y_LIMIT
                        && $NE_marker_x < $NE_X_LIMIT && $NE_marker_y > $NE_Y_LIMIT) {
                    break;
                } else {
                    $zoom--;
                }
            }

            //This iteration is to determine the x and y co-ordinates with the final
            //calculated zoom
            $dirIds = array();
            $dirXYDetails = array();
            foreach ($dirs as $d) {
                $dbObj->bindParams( array($d->id));
                $stopDetails = $dbObj->get_row("SELECT * FROM stop WHERE id =
                                                (SELECT stop_id FROM stop_direction_map
                                                    WHERE direction_id = ? $versionClause
                                                    ORDER BY position DESC LIMIT 1) $versionClause");

                $target_y = Google_Maps::LatToY($stopDetails->latitude);
                $target_x = Google_Maps::LonToX($stopDetails->longitude);


                $delta_x  = ($target_x - $center_x) >> (21 - $zoom);
                $delta_y  = ($target_y - $center_y) >> (21 - $zoom);
                $marker_x = $center_offset_x + $delta_x;
                $marker_y = $center_offset_y + $delta_y;

                //print "Route:".$routeObj->getTag()." | Direction: ".$d->tag." | zoom:".($zoom)." | x:$marker_x | y:$marker_y<br />";

                $dirXYDetails[$d->tag] = array('x' => $marker_x, 'y' => $marker_y);
                $dirIds[] = $d->id;
            }

            $routeNode->addAttribute("center", "$lat_avg,$lon_avg");
            $routeNode->addAttribute("zoom", $zoom);

            //Get the path co-ordinates
            //We fetch the direction with the largest number of stops and draw a
            //path for that
            $dirWithStops = $dbObj->get_results("SELECT direction_id, COUNT(*) as stops
                                                FROM stop_direction_map
                                                WHERE direction_id IN (". implode(",", $dirIds) .") $versionClause
                                                    GROUP BY direction_id
                                                    ORDER BY stops DESC");
            //$dbObj->debug();
            $dirStopsArray = array();
            foreach($dirWithStops as $dirStops) {
                $skipCondition = (($dirStops->stops % 2) == 0) ?
                        " AND mod(b.position, 2) = 0 " : " AND mod(b.position, 2) = 1 ";
                $stopsForPath = $dbObj->get_results("SELECT a.tag, b.position, a.latitude, a.longitude
                                                        FROM stop AS a, stop_direction_map AS b
                                                        WHERE a.id=b.stop_id AND a.id IN
                                                            (SELECT stop_id
                                                                FROM stop_direction_map
                                                                WHERE direction_id = ".$dirStops->direction_id." $versionClause)
                                                                AND b.direction_id = ".$dirStops->direction_id.
                                                                $skipCondition ." AND a.version=$version
                                                                ORDER BY b.position DESC");

                //$dbObj->debug();exit;
                $pathArray = array();
                foreach($stopsForPath as $sp) {
                    $pathArray[] = $sp->latitude.",".$sp->longitude;
                }

                if("actransit" == $agencyTitle) {
                    $pathColor = "0x008969";
                } elseif("sf-muni" == $agencyTitle) {
                    $pathColor = "0xC74F3A";
                }

                $getParamStr = "path=weight:".PATH_LINE_WEIGHT."|".getPathString($pathArray);

                $dirStopsArray[$dirStops->direction_id] = $getParamStr;
            }

            //Get the image from the Google Static Maps API
            $url = "http://maps.google.com/maps/api/staticmap?center=$lat_avg,$lon_avg&zoom=$zoom&size=".
                MAP_SIZE_X . "x" . MAP_SIZE_Y . "&maptype=roadmap&sensor=false&" . implode("&", $dirStopsArray);

            $pngImgFileName = $agencyTitle."_".$routeObj->getTag().".png";

            if(! file_exists($basePathOrig) ) {
                //print $basePathOrig;exit;
                Util::createDir($basePathOrig);
            }
            $filePath = $basePathOrig . $pngImgFileName;

            //print "$url<br />$fileName<br /><br />";

            if (!copy($url, $filePath)) {
                $logStr = "Failed to copy $fileName \n";
                $logger->log($logStr, Logger::WARN, "IMAGE" );
            }

            //Crop the image
            $intermediateImg_Y = (MAP_SIZE_Y / 2) + round((FINAL_MAP_SIZE_Y - TOP_EDGE - BOTTOM_EDGE) / 2) + BOTTOM_EDGE;
            $intermediateImg = imagecreatetruecolor(MAP_SIZE_X, $intermediateImg_Y);
            list($current_width, $current_height) = getimagesize($filePath);
            if(! imagecopy($intermediateImg, imagecreatefrompng($filePath),
                            0, 0, 0, 0, $current_width, $current_height) ) {
                $logStr = "Failed to crop and copy image [$filePath]";
                $logger->log($logStr, Logger::WARN, "IMAGE" );
            }


            $fileName = $agencyTitle."_".$routeObj->getTag().".jpg";
            $newFilePath = $basePathConverted . $fileName;

            $startFromTop_Y = $intermediateImg_Y - FINAL_MAP_SIZE_Y;
            $newImage = imagecreatetruecolor(MAP_SIZE_X, FINAL_MAP_SIZE_Y);
            if(! imagecopy($newImage, $intermediateImg,
                    0, 0, 0, $startFromTop_Y, MAP_SIZE_X, FINAL_MAP_SIZE_Y) ) {
                $logStr = "Failed to crop and copy image [$newFilePath]";
                $logger->log($logStr, Logger::WARN, "IMAGE" );
            } else {
                imagejpeg($newImage, $newFilePath);
            }

            imagedestroy($newImage);
            imagedestroy($intermediateImg);

            //Add file name to zip file contents array
            $zipFileContents[] = $newFilePath;

            foreach ($dirXYDetails as $dirTag => $xyDetails) {
                $dirFinalY = $xyDetails['y'] - $startFromTop_Y;
                $dirNode = $routeNode->addChild("direction");
                $dirNode->addAttribute("tag", $dirTag);
                $dirNode->addAttribute("x", $xyDetails['x']);
                $dirNode->addAttribute("y", $dirFinalY);
            }

            $routeNode->addAttribute("yCropPixels", $startFromTop_Y);

            sleep(1);

            //if($cnt++ == 1) { break;}

        }
    }

    $fileName = Util::getBaseDirectoryPath(Util::IMAGE_FILE) . "map_overlay_coordinates.xml";

    Util::prettyPrintXml($xml, $fileName);

    //Create the zip file
    $zipFileContents[] = $fileName;
    Util::createZip($zipFileContents, getImagesPath(), true);

}

function getImagesPath() {
    return Util::getBaseDirectoryPath(Util::IMAGE_FILE) . "images.zip";
}

function getPathString(array $pathArray) {

    $pathStr = implode("|", $pathArray);
    $stepCnt = 3; //We're retrieving alternate stop co-ordinates anyway.

    while(true) {

        if(strlen($pathStr) > 900) {
            $tmpArray = array();
            $tmpPathArray = array_reverse($pathArray); //We absolutely need the last stop
            foreach ($tmpPathArray as $key => $xy) {
                if($key < $stepCnt || ($key % $stepCnt == 0)) {
                    $tmpArray[] = $xy;
                }
            }

            $pathStr = implode("|", array_reverse($tmpArray));
            $stepCnt++;
            
        } else {
            break;
        }
    }

    //var_dump($stepCnt);

    return $pathStr;
}

$version = $_GET['version'];
TableUpdate::setVersion($version);

if(! ctype_digit($version)) {
    print '0';
    
} else {
    try {
        generateMapImages($version);

        $dbObj = DBPool::getInstance();
        $dbObj->bindParams( array($version) );
        $dbObj->query("UPDATE version SET images_generated=true WHERE id=?");
        
        print '1';
    } catch (Exception $ex) {
        //var_dump($ex->getTraceAsString());
        print '-2';
    }
}