<?php
//This script is not used anywhere in the server code base
//It was created to show Ljuba the logic to be implemented in the client

//Source: http://svn.appelsiini.net/svn/javascript/trunk/google_maps_nojs/Google/Maps.php
class Google_Maps {

    static function LonToX($lon) {
        $offset = 268435456;
        $radius = $offset / pi();
        return round($offset + $radius * $lon * pi() / 180);
    }

    static function LatToY($lat) {
        $offset = 268435456;
        $radius = $offset / pi();
        return round($offset - $radius * log((1 + sin($lat * pi() / 180)) / (1 - sin($lat * pi() / 180))) / 2);
    }

    static function XToLon($x) {
        $offset = 268435456;
        $radius = $offset / pi();
        return ((round($x) - $offset) / $radius) * 180/ pi();
    }

    static function YToLat($y) {
        $offset = 268435456;
        $radius = $offset / pi();
        return (pi() / 2 - 2 * atan(exp((round($y) - $offset) / $radius))) * 180 / pi();
    }

    static function adjustLonByPixels($lon, $delta, $zoom) {
        return Google_Maps::XToLon(Google_Maps::LonToX($lon) + ($delta << (21 - $zoom)));
    }

    static function adjustLatByPixels($lat, $delta, $zoom) {
        return Google_Maps::YToLat(Google_Maps::LatToY($lat) + ($delta << (21 - $zoom)));
    }

}

/*
DETERMINING THE X,Y CO-ORDINATES FOR A GIVEN LAT/LONG PAIR ON THE FINAL RESIZED IMAGE
EXAMPLE FOR AC-TRANSIT DB
*/
//Zoom value used for a route is available in the XML file
$zoom = 10;

//Get the co-ordinates for the center of the region we are interested in
//Available in the XML file for each route
$lat_avg = 37.49217995;
$lon_avg = -122.09221;

//Calculate center as pixel coordinates in world map
$center_y = Google_Maps::LatToY($lat_avg);
$center_x = Google_Maps::LonToX($lon_avg);

//Calculate center as pixel coordinates in image
//X=320 and Y=600 is the image size we get from Google
$center_offset_x = round(320 / 2);
$center_offset_y = round(600 / 2);

//$userLocationLatitude and $userLocationLongitide come from the GPS
$target_y = Google_Maps::LatToY($userLocationLatitude);
$target_x = Google_Maps::LonToX($userLocationLongitide);

$delta_x  = ($target_x - $center_x) >> (21 - $zoom);
$delta_y  = ($target_y - $center_y) >> (21 - $zoom);
$marker_x = $center_offset_x + $delta_x;
$marker_y = $center_offset_y + $delta_y;

//Since we have performed all the calculations wrt to the original 320x600 image,
//we need to account for the part that was cropped off at the top
$marker_y = $marker_y - $yCropPixels; //$yCropPixels is available in the XML file per route

//Final x,y co-ordinates where you can show the user's current location is ($marker_x, $marker_y)