<?php
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