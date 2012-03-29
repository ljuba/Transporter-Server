<?php
class OverrideFile {
    const DIRECTIONS = "directions";
    const REVERSE_STOPS = "reverseStops";
    const SF_MUNI_VEHICLES = "sf-muni-vehicles";

    public static function getContents($fileType) {
        $filePath = Configuration::getBasePath() . "files/" . $fileType .".xml";

        if (!file_exists($filePath)) {
            throw new Exception ("File does not exist");
        }

        return file_get_contents($filePath);
    }

    public static function updateContents($fileType, $xmlStr) {
        try {
            $xml = new SimpleXMLElement($xmlStr);
        } catch (Exception $ex) {
            throw new Exception ("Invalid XML!");
        }
        
        $filePath = Configuration::getBasePath() . "files/" . $fileType .".xml";

        return file_put_contents($filePath, $xmlStr);
    }
}
