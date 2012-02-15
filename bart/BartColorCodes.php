<?php
/**
 * Description of BartColorCodes
 *
 * @author thejo
 */
class BartColorCodes {
    static $colors = array ("ffff33" => "yellow",
                            "0099cc" => "blue",
                            "339933" => "green",
                            "ff9933" => "orange",
                            "ff0000" => "red");

    public static function getBartColor($routeColor) {
        $bartColors = BartColorCodes::$colors;
        if( isset($bartColors[$routeColor]) ) {
            return $bartColors[$routeColor];
        } else {
            return $routeColor;
        }
    }
}
?>
