<?php
/**
 * Description of BartApiEndPoints
 *
 * @author thejo
 */
class BartApiEndPoints {
    const ROUTES = 'http://api.bart.gov/api/route.aspx?cmd=routes&key=';
    const STATIONS = 'http://api.bart.gov/api/stn.aspx?cmd=stns&key=';

    const STATION_INFO = 'http://api.bart.gov/api/stn.aspx?cmd=stninfo&orig=%ORIG%&key=';
    const ORIG = '%ORIG%';

    const ROUTE_INFO = 'http://api.bart.gov/api/route.aspx?cmd=routeinfo&route=%DIRECTION%&key=';
    const DIRECTION = '%DIRECTION%';
}
